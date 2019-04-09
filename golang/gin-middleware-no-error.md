# 记录最近遇到的几个问题

# Gin 中间件没有使用next会是什么反应？

周末老王提了一个问题，如果Gin中间件里面如果我忘记写context.Next了会有什么结果呢？

我第一个反应是直接不会执行后面的handler了呗。我印象中gin的middleware也是个handler，然后维护一个handler链条，使用next进行调用传递。

事实证明我错了，如果某个middleware里面忘记写c.Next()， 那么它还是会进行后续调用的。只是不会再回到这个middleware中了。

这块代码又加深了一些理解：

```
func (c *Context) Next() {
	c.index++
	for s := int8(len(c.handlers)); c.index < s; c.index++ {
		c.handlers[c.index](c)
	}
}
```

每个请求进来的时候，都已经创建了c.handlers数组，当第一个Next函数启动的时候，会进入到这里的for循环，在这个循环中，默认就是会调用所有的handler的。所以这里就回答了之前的问题，如果没有写next的话，就顺势进入到下一个排序的handler。

如果调用了Next的话呢，实际上就不会调度for循环里面的c.index++了，就进入了第一行的c.index++，并且调用下一个handler，由下一个handler里面的next进入第一行的c.index++。

这个设计确实有点反直觉。

但是看了这个帖子 https://github.com/gin-gonic/gin/issues/287 也就说了，c.Next其实不是必要的，它的必要性就是为了能执行Next函数后面的代码而已。

# 如何给Gorm 每个sql请求日志增加一个上下文的traceId

我的需求是一个请求用一个traceId进行串下来，不管是sql日志，还是请求日志。

这个想了老久了，最后结论：做不到

gorm是启动的时候就创建连接，然后每个请求进来的时候，去连接池获取连接，进行请求。它的logger接口里面没有带上context，导致上下文丢失。

关键的代码在jinzhu/gorm/logger.go
```
type LogWriter interface {
	Println(v ...interface{})
}
// Logger default logger
type Logger struct {
	LogWriter
}

// Print format & print log
func (logger Logger) Print(values ...interface{}) {
	logger.Println(LogFormatter(values...)...)
}
```

这里的LogWriter并没有使用上上下文。这个可能也只是由于gorm创建的时候还没有到go1.7。貌似又很多人也发现这个问题，希望gorm加上context，https://github.com/jinzhu/gorm/issues/1231 但是至少现在还未加上去。

后来脑洞了一下，其实还有可能有一种做法，https://github.com/huandu/go-tls 像这种把context存储在goroutine作用域存储里面，然后创建一个自定义的Logger，在Print的时候，去这个goroutine作用域存储里面获取context。

但是这个创建goroutine作用域存储本身就是golang官网不提倡的。于是便作罢。。。

当然还有另外一种办法，就是你自己在每次sql请求之后自己使用logger记录一下sql请求和结果。不过过于丑陋了。
