# php多进程处理

往往我们会碰到一个情况，需要写一个脚本，这个脚本要处理的数据量极大，单进程处理脚本非常慢，那么这个时候就会想到使用多进程或者多线程的方式了。

我习惯使用多进程的方式，php中使用多进程的时候需要使用pcntl，pcntl的使用可以看这个[PHP的pcntl多进程](http://www.cnblogs.com/yjf512/p/3217615.html)

但是这里有一个问题，一个主进程把任务分成n个部分，然后把任务分配给多个子进程，但是任务可能是有返回值的，所有的子进程处理完返回值以后需要把返回值返回给主进程。

这个就涉及到了进程间通信了。进程间通信可以使用的方法当然很多了，比如用redis，用数据库，用文件等。

php中最简单的要算shmop相关函数了。

* shmop_open
* shmop_read
* shmop_write
* shmop_size
* shmop_delete

那怎么让一个类很容易有多进程处理的能力呢？可以使用php的trait，创建一个PcntlTrait，所有需要有多进程处理功能的类就use 这个trait就行。

PcntlTrait代码如下：

    <?php namespace App\Console\Commands;

    trait PcntlTrait
    {
        private $workers = 1;

        public function worker($count)
        {
            $this->workers = $count;
        }

        public function pcntl_call($all, \Closure $callback)
        {
            $perNum = ceil(count($all) / $this->workers);

            $pids = [];
            for($i = 0; $i < $this->workers; $i++){
                $pids[$i] = pcntl_fork();
                switch ($pids[$i]) {
                    case -1:
                        echo "fork error : {$i} \r\n";
                        exit;
                    case 0:
                        $data = [];
                        try {
                            $data = $callback(array_slice($all, $i * $perNum, $perNum));
                        } catch(\Exception $e) {
                            echo ($e->getMessage());
                        }

                        $shm_key = ftok(__FILE__, 't') . getmypid();
                        $data = json_encode($data);
                        $shm_id = shmop_open($shm_key, "c", 0777, strlen($data) + 10);
                        shmop_write($shm_id, $data, 0);
                        shmop_close($shm_id);
                        exit;
                    default:
                        break;
                }
            }

            // only master process will go into here
            $ret = [];
            foreach ($pids as $i => $pid) {
                if($pid) {
                    pcntl_waitpid($pid, $status);

                    $shm_key = ftok(__FILE__, 't') . $pid;
                    $shm_id = shmop_open($shm_key, "w", 0, 0);

                    $data = trim(shmop_read($shm_id, 0, shmop_size($shm_id)));
                    $data = json_decode($data, true);
                    $ret = array_merge($ret, $data);
                    @shmop_close($shm_id);
                    @shmop_delete($shm_id);
                }
            }

            return $ret;
        }
    }

它有两个参数，第一个参数为传入数组，第二个参数为数组处理函数。

它的具体使用通过下面这个测试用例可以看出：

    <?php

    use App\Console\Commands\PcntlTrait;

    class PcntlImp
    {
            use PcntlTrait;
    }

    class TestPcntlTrait extends \TestCase
    {
        public function setup()
        {
            $this->createApplication();
        }

        public function testPcntlCall()
        {
            $arr = [1,2,3,4,5,6,7,8,9,10];

            $imp = new \PcntlImp();
            $imp->worker(2);

            $data = $imp->pcntl_call($arr, function($info){
                if (empty($info)){
                    return [];
                }

                $ret = [];
                foreach ($info as $item) {
                    $ret[] = $item * $item;
                }
                return $ret;
            });

            $this->assertEquals(10, count($data));
            $this->assertEquals(25, $data[4]);
        }
    }
