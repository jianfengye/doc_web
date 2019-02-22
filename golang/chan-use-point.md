# 使用chan的时候选择对象还是指针

今天在写代码的时候遇到一个问题，在创建一个通道的时候，不确定创建的通道是使用chan A还是chan \*A。

思考了一下，觉得这个应该和函数一样是一个值传递还是参数传递的问题。然后写了个play验证了一下。
```
package main


import (
	"fmt"
	"time"
)


type B struct {
       Value int
}

type A struct {
	Bv B
}


func main() {
  ch := make(chan *A)

	b := B{1}
	a := A{Bv:b}

	go func(ch chan *A){
	   for {
		select {
		case a := <-ch:
			a.Bv.Value = 2
		}
	    }
	}(ch)

	ch <- &a
	time.Sleep(2 * time.Second)
	fmt.Println(a)
}

```

这里a.Bv.Value的值改了。 但是如果我这里的ch是make(chan A)的话，则打印出来的值为1了。

事实证明确实是这样。再去源码里面看看。

chan的结构是在`src/runtime/chan.go` 的hchan。我们就看chan.go里面的recv方法
```
func chanrecv(c *hchan, ep unsafe.Pointer, block bool) (selected, received bool) {
```
这个函数就是<-ch 的时候调用的。这里的c代表的就是我们使用的这个chan, ep代表的是ch传输出来的数据存储的位置。

它在从channel中获取数据的时候调用的是
```
recv(c, sg, ep, func() { unlock(&c.lock) }, 3)
```
看到这个函数里面，就可以看到使用的是typedmemmove 这个函数，这个函数就是c中的memmove。

将原先的数据，直接拷贝到目标内存中。所以这里说明channel是进行值拷贝的。

# 总结

基本上，chan的使用，如果是结构体的话，建议能使用指针就使用指针。

# 备注

本文golang代码基于go1.11.4
