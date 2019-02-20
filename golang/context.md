# golang中的Context的使用场景

context在Go1.7之后就进入标准库中了。它主要的用处如果用一句话来说，是在于控制goroutine的生命周期。当一个计算任务被goroutine承接了之后，由于某种原因（超时，或者强制退出）我们希望中止这个goroutine的计算任务，那么就用得到这个Context了。

关于Context的四种结构，CancelContext,TimeoutContext,DeadLineContext,ValueContext的使用在这一篇[快速掌握 Golang context 包](https://deepzz.com/post/golang-context-package-notes.html)已经说的很明白了。

本文主要来盘一盘golang中context的一些使用场景：

# 场景一：RPC调用

![avatar](https://blog.lab99.org/pics/golang/context/rpc-fails-3.svg)

在主goroutine上有4个RPC，RPC2/3/4是并行请求的，我们这里希望在RPC2请求失败之后，直接返回错误，并且让RPC3/4停止继续计算。这个时候，就使用的到Context。

这个的具体实现如下面的代码。

```
package main

import (
	"context"
	"sync"
	"github.com/pkg/errors"
)

func Rpc(ctx context.Context, url string) error {
	result := make(chan int)
	err := make(chan error)

	go func() {
		// 进行RPC调用，并且返回是否成功，成功通过result传递成功信息，错误通过error传递错误信息
		isSuccess := true
		if isSuccess {
			result <- 1
		} else {
			err <- errors.New("some error happen")
		}
	}()

	select {
		case <- ctx.Done():
			// 其他RPC调用调用失败
			return ctx.Err()
		case e := <- err:
			// 本RPC调用失败，返回错误信息
			return e
		case <- result:
			// 本RPC调用成功，不返回错误信息
			return nil
	}
}


func main() {
	ctx, cancel := context.WithCancel(context.Background())

	// RPC1调用
	err := Rpc(ctx, "http://rpc_1_url")
	if err != nil {
		return
	}

	wg := sync.WaitGroup{}

	// RPC2调用
	wg.Add(1)
	go func(){
		defer wg.Done()
		err := Rpc(ctx, "http://rpc_2_url")
		if err != nil {
			cancel()
		}
	}()

	// RPC3调用
	wg.Add(1)
	go func(){
		defer wg.Done()
		err := Rpc(ctx, "http://rpc_3_url")
		if err != nil {
			cancel()
		}
	}()

	// RPC4调用
	wg.Add(1)
	go func(){
		defer wg.Done()
		err := Rpc(ctx, "http://rpc_4_url")
		if err != nil {
			cancel()
		}
	}()

	wg.Wait()
}
```
当然我这里使用了waitGroup来保证main函数在所有RPC调用完成之后才退出。

在Rpc函数中，第一个参数是一个CancelContext, 这个Context形象的说，就是一个传话筒，在创建CancelContext的时候，返回了一个听声器（ctx）和话筒（cancel函数）。所有的goroutine都拿着这个听声器（ctx），当主goroutine想要告诉所有goroutine要结束的时候，通过cancel函数把结束的信息告诉给所有的goroutine。当然所有的goroutine都需要内置处理这个听声器结束信号的逻辑（ctx->Done()）。我们可以看Rpc函数内部，通过一个select来判断ctx的done和当前的rpc调用哪个先结束。

这个waitGroup和其中一个RPC调用就通知所有RPC的逻辑，其实有一个包已经帮我们做好了。[errorGroup](https://godoc.org/golang.org/x/sync/errgroup)。具体这个errorGroup包的使用可以看这个包的test例子。

有人可能会担心我们这里的cancel()会被多次调用，context包的cancel调用是幂等的。可以放心多次调用。

我们这里不妨品一下，这里的Rpc函数，实际上我们的这个例子里面是一个“阻塞式”的请求，这个请求如果是使用http.Get或者http.Post来实现，实际上Rpc函数的Goroutine结束了，内部的那个实际的http.Get却没有结束。所以，需要理解下，这里的函数最好是“非阻塞”的，比如是http.Do，然后可以通过某种方式进行中断。比如像这篇文章[Cancel http.Request using Context](https://medium.com/@ferencfbin/golang-cancel-http-request-using-context-1f45aeba6464)中的这个例子：
```
func httpRequest(
  ctx context.Context,
  client *http.Client,
  req *http.Request,
  respChan chan []byte,
  errChan chan error
) {
  req = req.WithContext(ctx)
  tr := &http.Transport{}
  client.Transport = tr
  go func() {
    resp, err := client.Do(req)
    if err != nil {
      errChan <- err
    }
    if resp != nil {
      defer resp.Body.Close()
      respData, err := ioutil.ReadAll(resp.Body)
      if err != nil {
        errChan <- err
      }
      respChan <- respData
    } else {
      errChan <- errors.New("HTTP request failed")
    }
  }()
  for {
    select {
    case <-ctx.Done():
      tr.CancelRequest(req)
      errChan <- errors.New("HTTP request cancelled")
      return
    case <-errChan:
      tr.CancelRequest(req)
      return
    }
  }
}
```
它使用了http.Client.Do，然后接收到ctx.Done的时候，通过调用transport.CancelRequest来进行结束。
我们还可以参考[net/dail/DialContext](https://tip.golang.org/src/net/dial.go?s=11446:11534#L351)
换而言之，如果你希望你实现的包是“可中止/可控制”的，那么你在你包实现的函数里面，最好是能接收一个Context函数，并且处理了Context.Done。

# 场景二：PipeLine

pipeline模式就是流水线模型，流水线上的几个工人，有n个产品，一个一个产品进行组装。其实pipeline模型的实现和Context并无关系，没有context我们也能用chan实现pipeline模型。但是对于整条流水线的控制，则是需要使用上Context的。这篇文章[Pipeline Patterns in Go](https://medium.com/statuscode/pipeline-patterns-in-go-a37bb3a7e61d)的[例子](https://gist.github.com/claudiofahey/3afcf4f4fb3d8d3b35cadb100d4fb9b7)是非常好的说明。这里就大致对这个代码进行下说明。

runSimplePipeline的流水线工人有三个，lineListSource负责将参数一个个分割进行传输，lineParser负责将字符串处理成int64,sink根据具体的值判断这个数据是否可用。他们所有的返回值基本上都有两个chan，一个用于传递数据，一个用于传递错误。（<-chan string, <-chan error）输入基本上也都有两个值，一个是Context，用于传声控制的，一个是(in <-chan)输入产品的。

我们可以看到，这三个工人的具体函数里面，都使用switch处理了case <-ctx.Done()。这个就是生产线上的命令控制。

```
func lineParser(ctx context.Context, base int, in <-chan string) (
	<-chan int64, <-chan error, error) {
	...
	go func() {
		defer close(out)
		defer close(errc)

		for line := range in {

			n, err := strconv.ParseInt(line, base, 64)
			if err != nil {
				errc <- err
				return
			}

			select {
			case out <- n:
			case <-ctx.Done():
				return
			}
		}
	}()
	return out, errc, nil
}
```
# 场景三：超时请求

我们发送RPC请求的时候，往往希望对这个请求进行一个超时的限制。当一个RPC请求超过10s的请求，自动断开。当然我们使用CancelContext，也能实现这个功能（开启一个新的goroutine，这个goroutine拿着cancel函数，当时间到了，就调用cancel函数）。

鉴于这个需求是非常常见的，context包也实现了这个需求：timerCtx。具体实例化的方法是 WithDeadline 和 WithTimeout。

具体的timerCtx里面的逻辑也就是通过time.AfterFunc来调用ctx.cancel的。

官方的例子：
```
package main

import (
    "context"
    "fmt"
    "time"
)

func main() {
    ctx, cancel := context.WithTimeout(context.Background(), 50*time.Millisecond)
    defer cancel()

    select {
    case <-time.After(1 * time.Second):
        fmt.Println("overslept")
    case <-ctx.Done():
        fmt.Println(ctx.Err()) // prints "context deadline exceeded"
    }
}
```

在http的客户端里面加上timeout也是一个常见的办法
```
uri := "https://httpbin.org/delay/3"
req, err := http.NewRequest("GET", uri, nil)
if err != nil {
	log.Fatalf("http.NewRequest() failed with '%s'\n", err)
}

ctx, _ := context.WithTimeout(context.Background(), time.Millisecond*100)
req = req.WithContext(ctx)

resp, err := http.DefaultClient.Do(req)
if err != nil {
	log.Fatalf("http.DefaultClient.Do() failed with:\n'%s'\n", err)
}
defer resp.Body.Close()
```

在http服务端设置一个timeout如何做呢？
```
package main

import (
	"net/http"
	"time"
)

func test(w http.ResponseWriter, r *http.Request) {
	time.Sleep(20 * time.Second)
	w.Write([]byte("test"))
}


func main() {
	http.HandleFunc("/", test)
	timeoutHandler := http.TimeoutHandler(http.DefaultServeMux, 5 * time.Second, "timeout")
	http.ListenAndServe(":8080", timeoutHandler)
}
```

我们看看TimeoutHandler的内部，本质上也是通过context.WithTimeout来做处理。

```
func (h *timeoutHandler) ServeHTTP(w ResponseWriter, r *Request) {
  ...
		ctx, cancelCtx = context.WithTimeout(r.Context(), h.dt)
		defer cancelCtx()
	...
	go func() {
    ...
		h.handler.ServeHTTP(tw, r)
	}()
	select {
    ...
	case <-ctx.Done():
		...
	}
}
```

# 场景四：HTTP服务器的request互相传递数据

context还提供了valueCtx的数据结构。

这个valueCtx最经常使用的场景就是在一个http服务器中，在request中传递一个特定值，比如有一个中间件，做cookie验证，然后把验证后的用户名存放在request中。

我们可以看到，官方的request里面是包含了Context的，并且提供了WithContext的方法进行context的替换。

```
package main

import (
	"net/http"
	"context"
)

type FooKey string

var UserName = FooKey("user-name")
var UserId = FooKey("user-id")

func foo(next http.HandlerFunc) http.HandlerFunc {
	return func(w http.ResponseWriter, r *http.Request) {
		ctx := context.WithValue(r.Context(), UserId, "1")
		ctx2 := context.WithValue(ctx, UserName, "yejianfeng")
		next(w, r.WithContext(ctx2))
	}
}

func GetUserName(context context.Context) string {
	if ret, ok := context.Value(UserName).(string); ok {
		return ret
	}
	return ""
}

func GetUserId(context context.Context) string {
	if ret, ok := context.Value(UserId).(string); ok {
		return ret
	}
	return ""
}

func test(w http.ResponseWriter, r *http.Request) {
	w.Write([]byte("welcome: "))
	w.Write([]byte(GetUserId(r.Context())))
	w.Write([]byte(" "))
	w.Write([]byte(GetUserName(r.Context())))
}

func main() {
	http.Handle("/", foo(test))
	http.ListenAndServe(":8080", nil)
}
```

在使用ValueCtx的时候需要注意一点，这里的key不应该设置成为普通的String或者Int类型，为了防止不同的中间件对这个key的覆盖。最好的情况是每个中间件使用一个自定义的key类型，比如这里的FooKey，而且获取Value的逻辑尽量也抽取出来作为一个函数，放在这个middleware的同包中。这样，就会有效避免不同包设置相同的key的冲突问题了。

# 参考
[快速掌握 Golang context 包](https://deepzz.com/post/golang-context-package-notes.html)
[视频笔记：如何正确使用 Context - Jack Lindamood](https://blog.lab99.org/post/golang-2017-10-27-video-how-to-correctly-use-package-context.html)
[Go Concurrency Patterns: Context](https://blog.golang.org/context)
[Cancel http.Request using Context](https://medium.com/@ferencfbin/golang-cancel-http-request-using-context-1f45aeba6464)
[Pipeline Patterns in Go](https://medium.com/statuscode/pipeline-patterns-in-go-a37bb3a7e61d)
