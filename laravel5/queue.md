# laravel的消息队列剖析

这篇来自于看到朋友转的58沈剑的一篇文章：[1分钟实现“延迟消息”功能](http://mp.weixin.qq.com/s?__biz=MjM5ODYxMDA5OQ==&mid=2651959961&idx=1&sn=afec02c8dc6db9445ce40821b5336736&chksm=bd2d07458a5a8e5314560620c240b1c4cf3bbf801fc0ab524bd5e8aa8b8ef036cf755d7eb0f6&mpshare=1&scene=1&srcid=0316rh7QmkSKJH06XFENtsgw#rd)

在实际工作中也不止遇见过一次这个问题，我在想着以前是怎么处理的呢？我记得当初在上家公司的时候直接使用的是laravel的queue来实现的。当然，这里说的laravel的queue实际上也是基于redis的队列实现的。正好今天遇上这个问题，追下底层机制。

使用如下：http://d.laravel-china.org/docs/5.3/queues

```
// 创建10分钟后执行的任务

$job = (new ProcessPodcast($pocast))
            ->delay(Carbon::now()->addMinutes(10));

dispatch($job);
```

```
//启动队列命令
php artisan queue:work
```

# 分发部分

首先看dispatch这边做的事情：

dispatch函数首先就是调用
```
return app(Dispatcher::class)->dispatch($job);
// Illuminate\Contracts\Bus\Dispatcher
```
首先需要理解这里的Dispatcher::class 实际注入的是哪个类。

看到vendor/laravel/framework/src/Illuminate/Bus/BusServiceProvider.php:26，有
```
public function register()
{
    $this->app->singleton('Illuminate\Bus\Dispatcher', function ($app) {
        return new Dispatcher($app, function ($connection = null) use ($app) {
            return $app['Illuminate\Contracts\Queue\Factory']->connection($connection);
        });
    });

    $this->app->alias(
        'Illuminate\Bus\Dispatcher', 'Illuminate\Contracts\Bus\Dispatcher'
    );

    $this->app->alias(
        'Illuminate\Bus\Dispatcher', 'Illuminate\Contracts\Bus\QueueingDispatcher'
    );
}
```

所以最后是实例化了Illuminate\Bus\Dispatcher

看看它的dispatch函数做了啥？
```
public function dispatch($command)
{
    if ($this->queueResolver && $this->commandShouldBeQueued($command)) {
        return $this->dispatchToQueue($command);
    } else {
        return $this->dispatchNow($command);
    }
}
```

假设我们的dispatch是基于队列的（ShouldQueue）。那么就是走dispatchToQueue，最终，走的是pushCommandToQueue

```
protected function pushCommandToQueue($queue, $command)
{
    ...

    if (isset($command->delay)) {
        return $queue->later($command->delay, $command);
    }
    ...
}
```

这里的queue就是队列的范畴了，假设我们用的队列是redis。（队列的解析器就是singleton的时候传入的Cluster）。最终这里落入的是vendor/laravel/framework/src/Illuminate/Queue/RedisQueue.php:111的
```
public function later($delay, $job, $data = '', $queue = null)
{
    $payload = $this->createPayload($job, $data);

    $this->getConnection()->zadd(
        $this->getQueue($queue).':delayed', $this->getTime() + $this->getSeconds($delay), $payload
    );

    return Arr::get(json_decode($payload, true), 'id');
}
```
这下就看清楚了：

laravel的延迟队列，使用的是zadd命令，往{$queue}:delayed，中插入一条job信息，它的score是执行时间。

（得到这条结论还真tmd是不容易）

# 队列监听部分

队列监听命令来自于： php artisan queue:work

命令行的入口就不追踪了，直接到vendor/laravel/framework/src/Illuminate/Queue/Console/WorkCommand.php:29 类

```
protected function runWorker($connection, $queue)
{
    $this->worker->setCache($this->laravel['cache']->driver());

    return $this->worker->{$this->option('once') ? 'runNextJob' : 'daemon'}(
        $connection, $queue, $this->gatherWorkerOptions()
    );
}
```

这里的daemon和runNextJob是只跑一次还是持续跑的意思，我们当然假定是以daemon的形式在跑。

这里的worker是vendor/laravel/framework/src/Illuminate/Queue/Worker.php:78

```
public function daemon($connectionName, $queue, WorkerOptions $options)
{
    $lastRestart = $this->getTimestampOfLastQueueRestart();

    while (true) {
        $this->registerTimeoutHandler($options);

        if ($this->daemonShouldRun($options)) {
            $this->runNextJob($connectionName, $queue, $options);
        } else {
            $this->sleep($options->sleep);
        }

        if ($this->memoryExceeded($options->memory) ||
            $this->queueShouldRestart($lastRestart)) {
            $this->stop();
        }
    }
}
```
这里的代码就值得我们自己写deamon的时候来参考了，它考虑了timeout，考虑了memory的情况。

而runNextJob的命令实际上就很清晰了

```
public function runNextJob($connectionName, $queue, WorkerOptions $options)
{
    ...
        $job = $this->getNextJob(
            $this->manager->connection($connectionName), $queue
        );

        ...
            return $this->process(
                $connectionName, $job, $options
            );
    ...
}
```

这里的Manager对应的是QueueManager, 这个类内部会创建一个connector（vendor/laravel/framework/src/Illuminate/Queue/Connectors/RedisConnector.php:30）

```
public function connect(array $config)
{
    return new RedisQueue(
        $this->redis, $config['queue'],
        Arr::get($config, 'connection', $this->connection),
        Arr::get($config, 'retry_after', 60)
    );
}
```

看到这里就明白了，最后还是掉落到RedisQueue中。 很好，和我们前面的任务分发终于对上了，圈子差不多画完了，我们可以看到曙光了。

追到RedisQueue里面，看它的pop行为。

```
public function pop($queue = null)
{
    $original = $queue ?: $this->default;

    $queue = $this->getQueue($queue);

    $this->migrateExpiredJobs($queue.':delayed', $queue);

    if (! is_null($this->expire)) {
        $this->migrateExpiredJobs($queue.':reserved', $queue);
    }

    list($job, $reserved) = $this->getConnection()->eval(
        LuaScripts::pop(), 2, $queue, $queue.':reserved', $this->getTime() + $this->expire
    );

    if ($reserved) {
        return new RedisJob($this->container, $this, $job, $reserved, $original);
    }
}
```

这段就是精华了。它做了什么事情呢？

先看migrateExpiredJobs:

```
public function migrateExpiredJobs($from, $to)
{
    $this->getConnection()->eval(
        LuaScripts::migrateExpiredJobs(), 2, $from, $to, $this->getTime()
    );
}
```

这里的eval就是对应redis的eval操作，https://redis.io/commands/eval，2是说明后面有两个key，最后一个getTime()获取的是arg。
下面就看lua脚本了。

```
public static function migrateExpiredJobs()
{
    return <<<'LUA'
local val = redis.call('zrangebyscore', KEYS[1], '-inf', ARGV[1])
if(next(val) ~= nil) then
redis.call('zremrangebyrank', KEYS[1], 0, #val - 1)
for i = 1, #val, 100 do
    redis.call('rpush', KEYS[2], unpack(val, i, math.min(i+99, #val)))
end
end
return true
LUA;
}
```

结合起来看就是：

* 使用zrangebyscore 和zremrangebyrank 从{queue}:delayed 队列中，从-inf到now的任务拿出来。
* 用rpush的方式存入到默认queue中（后续就是放入到{queue}:reserved ）

这个zrangebyscore就是判断延迟任务是否应该执行的操作了。

然后就进行的是

```
list($job, $reserved) = $this->getConnection()->eval(
    LuaScripts::pop(), 2, $queue, $queue.':reserved', $this->getTime() + $this->expire
);
```

这里的LuaScripts::pop()如下：

```
public static function pop()
{
    return <<<'LUA'
local job = redis.call('lpop', KEYS[1])
local reserved = false
if(job ~= false) then
reserved = cjson.decode(job)
reserved['attempts'] = reserved['attempts'] + 1
reserved = cjson.encode(reserved)
redis.call('zadd', KEYS[2], ARGV[1], reserved)
end
return {job, reserved}
LUA;
}
```

做了下面操作：

* 把默认队列中的任务lpop出来
* 将他的attempts次数+1
* zadd 存入{queue}:reserved 队列，score为now+60(默认的过期时间)

最后，我就返回这个job，这里结束了getNextJob的过程

process过程就是调用了一下：vendor/laravel/framework/src/Illuminate/Queue/Worker.php:187

```
public function process($connectionName, $job, WorkerOptions $options)
{
    try {
        $this->raiseBeforeJobEvent($connectionName, $job);

        $this->markJobAsFailedIfAlreadyExceedsMaxAttempts(
            $connectionName, $job, (int) $options->maxTries
        );

        // Here we will fire off the job and let it process. We will catch any exceptions so
        // they can be reported to the developers logs, etc. Once the job is finished the
        // proper events will be fired to let any listeners know this job has finished.
        $job->fire();

        $this->raiseAfterJobEvent($connectionName, $job);
    } catch (Exception $e) {
        $this->handleJobException($connectionName, $job, $options, $e);
    } catch (Throwable $e) {
        $this->handleJobException(
            $connectionName, $job, $options, new FatalThrowableError($e)
        );
    }
}
```

```

$this->events->fire(new Events\JobProcessing(
    $connectionName, $job
));
```

这里的raiseBeforeJobEvent和raiseAfterJobEvent又是使用event和listener的形式来做处理的。这里的$this->events是vendor/laravel/framework/src/Illuminate/Events/Dispatcher.php:197

这里就是触发了一个Events\JobProcessing事件，我们现在要找到对应的lister：

答案是在QueueManager中定义的

```
/**
 * Register an event listener for the before job event.
 *
 * @param  mixed  $callback
 * @return void
 */
public function before($callback)
{
    $this->app['events']->listen(Events\JobProcessing::class, $callback);
}

/**
 * Register an event listener for the after job event.
 *
 * @param  mixed  $callback
 * @return void
 */
public function after($callback)
{
    $this->app['events']->listen(Events\JobProcessed::class, $callback);
}

```

换句话说，我们希望监听一个job开始和结束的时候，我们可以使用QueueManager的before，after来监听。比如发个邮件，唱唱小曲啥的。

那么这里我们，从{queue}:reserved中获取了job之后（这里的job是RedisJob），我们是什么时候触发的delete呢？是在
```
$job->fire();
```

这个fire是RedisJob(vendor/laravel/framework/src/Illuminate/Queue/Jobs/RedisJob.php)但继承来自vendor/laravel/framework/src/Illuminate/Queue/Jobs/Job.php:72， 经过调用CallQueuedHandler，最终会落到
vendor/laravel/framework/src/Illuminate/Queue/RedisQueue.php:154
```
public function deleteReserved($queue, $job)
{
    $this->getConnection()->zrem($this->getQueue($queue).':reserved', $job);
}
```
这里就是将job从{queue}:reserved 队列中删除。

至此，整个队列及延迟机制就处理完了。

# 实际

我们实际监听一下redis就可以验证结果：

```
// 使用dispatch
1489802272.491060 [0 127.0.0.1:63798] "SELECT" "0"
1489802272.491513 [0 127.0.0.1:63798] "ZADD" "queues:default:delayed" "1489802332" "{\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"data\":{\"commandName\":\"App\\\\Jobs\\\\DelayTestJob\",\"command\":\"O:21:\\\"App\\\\Jobs\\\\DelayTestJob\\\":4:{s:6:\\\"\\u0000*\\u0000job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";O:13:\\\"Carbon\\\\Carbon\\\":3:{s:4:\\\"date\\\";s:26:\\\"2017-03-18 01:58:52.000000\\\";s:13:\\\"timezone_type\\\";i:3;s:8:\\\"timezone\\\";s:3:\\\"UTC\\\";}}\"},\"id\":\"q7ss6fRgCbMNHhCv6gOXX0Or7B43blU9\",\"attempts\":1}"


// 1分钟后
1489802333.957500 [0 127.0.0.1:63792] "EVAL" "local val = redis.call('zrangebyscore', KEYS[1], '-inf', ARGV[1])\nif(next(val) ~= nil) then\n    redis.call('zremrangebyrank', KEYS[1], 0, #val - 1)\n    for i = 1, #val, 100 do\n        redis.call('rpush', KEYS[2], unpack(val, i, math.min(i+99, #val)))\n    end\nend\nreturn true" "2" "queues:default:delayed" "queues:default" "1489802333"
1489802333.957563 [0 lua] "zrangebyscore" "queues:default:delayed" "-inf" "1489802333"
1489802333.957586 [0 lua] "zremrangebyrank" "queues:default:delayed" "0" "0"
1489802333.958628 [0 lua] "rpush" "queues:default" "{\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\",\"data\":{\"commandName\":\"App\\\\Jobs\\\\DelayTestJob\",\"command\":\"O:21:\\\"App\\\\Jobs\\\\DelayTestJob\\\":4:{s:6:\\\"\\u0000*\\u0000job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";O:13:\\\"Carbon\\\\Carbon\\\":3:{s:4:\\\"date\\\";s:26:\\\"2017-03-18 01:58:52.000000\\\";s:13:\\\"timezone_type\\\";i:3;s:8:\\\"timezone\\\";s:3:\\\"UTC\\\";}}\"},\"id\":\"q7ss6fRgCbMNHhCv6gOXX0Or7B43blU9\",\"attempts\":1}"
1489802333.959572 [0 127.0.0.1:63792] "EVAL" "local val = redis.call('zrangebyscore', KEYS[1], '-inf', ARGV[1])\nif(next(val) ~= nil) then\n    redis.call('zremrangebyrank', KEYS[1], 0, #val - 1)\n    for i = 1, #val, 100 do\n        redis.call('rpush', KEYS[2], unpack(val, i, math.min(i+99, #val)))\n    end\nend\nreturn true" "2" "queues:default:reserved" "queues:default" "1489802333"
1489802333.959672 [0 lua] "zrangebyscore" "queues:default:reserved" "-inf" "1489802333"
1489802333.959866 [0 127.0.0.1:63792] "EVAL" "local job = redis.call('lpop', KEYS[1])\nlocal reserved = false\nif(job ~= false) then\n    reserved = cjson.decode(job)\n    reserved['attempts'] = reserved['attempts'] + 1\n    reserved = cjson.encode(reserved)\n    redis.call('zadd', KEYS[2], ARGV[1], reserved)\nend\nreturn {job, reserved}" "2" "queues:default" "queues:default:reserved" "1489802343"
1489802333.959938 [0 lua] "lpop" "queues:default"
1489802333.959965 [0 lua] "zadd" "queues:default:reserved" "1489802343" "{\"id\":\"q7ss6fRgCbMNHhCv6gOXX0Or7B43blU9\",\"attempts\":2,\"data\":{\"command\":\"O:21:\\\"App\\\\Jobs\\\\DelayTestJob\\\":4:{s:6:\\\"\\u0000*\\u0000job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";O:13:\\\"Carbon\\\\Carbon\\\":3:{s:4:\\\"date\\\";s:26:\\\"2017-03-18 01:58:52.000000\\\";s:13:\\\"timezone_type\\\";i:3;s:8:\\\"timezone\\\";s:3:\\\"UTC\\\";}}\",\"commandName\":\"App\\\\Jobs\\\\DelayTestJob\"},\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\"}"
1489802333.963223 [0 127.0.0.1:63792] "ZREM" "queues:default:reserved" "{\"id\":\"q7ss6fRgCbMNHhCv6gOXX0Or7B43blU9\",\"attempts\":2,\"data\":{\"command\":\"O:21:\\\"App\\\\Jobs\\\\DelayTestJob\\\":4:{s:6:\\\"\\u0000*\\u0000job\\\";N;s:10:\\\"connection\\\";N;s:5:\\\"queue\\\";N;s:5:\\\"delay\\\";O:13:\\\"Carbon\\\\Carbon\\\":3:{s:4:\\\"date\\\";s:26:\\\"2017-03-18 01:58:52.000000\\\";s:13:\\\"timezone_type\\\";i:3;s:8:\\\"timezone\\\";s:3:\\\"UTC\\\";}}\",\"commandName\":\"App\\\\Jobs\\\\DelayTestJob\"},\"job\":\"Illuminate\\\\Queue\\\\CallQueuedHandler@call\"}"

```

精简下路径就是：

```
// 第一步：先往delayed队列中插入job
1489802272.491513 [0 127.0.0.1:63798] "ZADD" "queues:default:delayed" "1489802332" {job}

// 第二步，将delayed队列中到期的job取出，并且rpush进default队列
1489802333.957563 [0 lua] "zrangebyscore" "queues:default:delayed" "-inf" "1489802333"
1489802333.957586 [0 lua] "zremrangebyrank" "queues:default:delayed" "0" "0"
1489802333.958628 [0 lua] "rpush" "queues:default" {job}

// 第三步，从default队列中lpop出job
1489802333.959938 [0 lua] "lpop" "queues:default"

// 第四步，zadd到default:reserved
1489802333.959965 [0 lua] "zadd" "queues:default:reserved" "1489802343" {job}

// 第五步，程序处理这个job

// 第六步，讲job从default:reserved中删除
1489802333.963223 [0 127.0.0.1:63792] "ZREM" "queues:default:reserved" {job}

```

符合预期。

# 总结

laravel这边的延迟队列使用了三个队列。

* queue:default:delayed // 存储延迟任务
* queue:default // 存储“生”任务，就是未处理任务
* queue:default:reserved // 存储待处理任务

任务在三个队列中进行轮转，最后一定进入到queue:default:reserved，并且成功后把任务从这个队列中删除。

其间还使用了lua脚本，所以至少laravel5.3（本文的laravel环境）在无lua脚本支持的redis版本是跑不了的。

它用三个队列把所有的步骤给原子了，所以并没有使用multi等操作。也是防止了锁的使用把。每一步操作失败了，都会有后续的步骤继续帮忙完成，记录等行为的。
