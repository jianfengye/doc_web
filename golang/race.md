# golang中的race检测(未发布)

由于golang中的go是非常方便的，加上函数又非常容易隐藏go。
所以很多时候，当我们写出一个程序的时候，我们并不知道这个程序在并发情况下会不会出现什么问题。

所以在本质上说，goroutine的使用增加了函数的危险系数[论go语言中goroutine的使用](http://www.cnblogs.com/yjf512/archive/2012/06/30/2571247.html)。比如一个全局变量，如果没有加上锁，我们写一个比较庞大的项目下来，就根本不知道这个变量是不是会引起多个goroutine竞争。

官网的文章[Introducing the Go Race Detector](http://blog.golang.org/race-detector)给出的例子就说明了这点：

```golang
package main

import(
    "time"
    "fmt"
    "math/rand"
)

func main() {
    start := time.Now()
    var t *time.Timer
    t = time.AfterFunc(randomDuration(), func() {
        fmt.Println(time.Now().Sub(start))
        t.Reset(randomDuration())
    })
    time.Sleep(5 * time.Second)
}

func randomDuration() time.Duration {
    return time.Duration(rand.Int63n(1e9))
}
```

这个例子看起来没任何问题，但是实际上，time.AfterFunc是会另外启动一个goroutine来进行计时和执行func()。
由于func中有对t(Timer)进行操作(t.Reset)，而主goroutine也有对t进行操作(t=time.After)。
这个时候，其实有可能会造成两个goroutine对同一个变量进行竞争的情况。

这个例子可能有点复杂，我们简化一下，使用一个更为简单的例子：

```golang
package main

import(
    "time"
    "fmt"
)

func main() {
    a := 1
    go func(){
        a = 2
    }()
    a = 3
    fmt.Println("a is ", a)

    time.Sleep(2 * time.Second)
}

```
在上面的例子中，看代码，我们其实看的出来，这里的go func触发的goroutine会修改a。
主goroutine 也会对a进行修改。但是我们如果只go run运行，我们可能往往不会发现什么太大的问题。

```golang
runtime  go run race1.go
a is  3
```

可喜的是，golang在1.1之后引入了竞争检测的概念。我们可以使用go run -race  或者 go build -race 来进行竞争检测。
golang语言内部大概的实现就是同时开启多个goroutine执行同一个命令，并且纪录每个变量的状态。

如果用race来检测上面的程序，我们就会看到输出：

```golang
runtime  go run -race race1.go
a is  3
==================
WARNING: DATA RACE
Write by goroutine 5:
  main.func·001()
      /Users/yejianfeng/Documents/workspace/go/src/runtime/race1.go:11 +0x3a

Previous write by main goroutine:
  main.main()
      /Users/yejianfeng/Documents/workspace/go/src/runtime/race1.go:13 +0xe7

Goroutine 5 (running) created at:
  main.main()
      /Users/yejianfeng/Documents/workspace/go/src/runtime/race1.go:12 +0xd7
==================
Found 1 data race(s)
exit status 66
```

这个命令输出了Warning，告诉我们，goroutine5运行到第11行和main goroutine运行到13行的时候触发竞争了。
而且goroutine5是在第12行的时候产生的。

这样我们根据分析这个提示就可以看到这个程序在哪个地方写的有问题了。

当然这个参数会引发CPU和内存的使用增加，所以基本是在测试环境使用，不是在正式环境开启。
