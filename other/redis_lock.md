# 解锁redis锁的正确姿势

redis是php的好朋友，在php写业务过程中，有时候会使用到锁的概念，同时只能有一个人可以操作某个行为。这个时候我们就要用到锁。锁的方式有好几种，php不能在内存中用锁，不能使用zookeeper加锁，使用数据库做锁又消耗比较大，这个时候我们一般会选用redis做锁机制。

## setnx

锁在redis中最简单的数据结构就是string。最早的时候，上锁的操作一般使用setnx，这个命令是当:lock不存在的时候set一个val，或许你还会记得使用expire来增加锁的过期，解锁操作就是使用del命令，伪代码如下：

```
if (Redis::setnx("my:lock", 1)) {
    Redis::expire("my:lock", 10);
    // ... do something

    Redis::del("my:lock")
}
```

这里其实是有问题的，问题就在于setnx和expire中间如果遇到crash等行为，可能这个lock就不会被释放了。于是进一步的优化方案可能是在lock中存储timestamp。判断timestamp的长短。

## set

现在官方建议直接使用[set](https://redis.io/commands/set)来实现锁。我们可以使用set命令来替代setnx，就是下面这个样子

```
if (Redis::set("my:lock", 1, "nx", "ex", 10)) {
    ... do something

    Redis::del("my:lock")
}
```

上面的代码把my:lock设置为1，当且仅当这个lock不存在的时候，设置完成之后设置过期时间为10。

获取锁的机制是对了，但是删除锁的机制直接使用del是不对的。因为有可能导致误删别人的锁的情况。

比如，这个锁我上了10s，但是我处理的时间比10s更长，到了10s，这个锁自动过期了，被别人取走了，并且对它重新上锁了。那么这个时候，我再调用Redis::del就是删除别人建立的锁了。

官方对解锁的命令也有建议，建议使用lua脚本，先进行get，再进行del

程序变成：

```

$token = rand(1, 100000);

function lock() {
    return Redis::set("my:lock", $token, "nx", "ex", 10);
}

function unlock() {
    $script = `
if redis.call("get",KEYS[1]) == ARGV[1]
then
    return redis.call("del",KEYS[1])
else
    return 0
end    
    `
    return Redis::eval($script, "my:lock", $token)
}

if (lock()) {
    // do something

    unlock();
}
```

这里的token是一个随机数，当lock的时候，往redis的my:lock中存的是这个token，unlock的时候，先get一下lock中的token，如果和我要删除的token是一致的，说明这个锁是之前我set的，否则的话，说明这个锁已经过期，是别人set的，我就不应该对它进行任何操作。

所以：不要再使用setnx，直接使用set进行锁实现。
