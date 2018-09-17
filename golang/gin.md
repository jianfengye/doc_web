# Gin框架解析

Gin框架是golang的一个常用的web框架，最近一个项目中需要使用到它，所以对这个框架进行了学习。gin包非常短小精悍，不过主要包含的路由，中间件，日志都有了。我们可以追着代码思考下，这个框架是如何一步一步过来的。

# 从http包说起

基本上现在的golang的web库都是从http上搭建起来，golang的http包的核心如下：

```
func ListenAndServe(addr string, handler Handler) error {
	server := &Server{Addr: addr, Handler: handler}
	return server.ListenAndServe()
}
```

这里的Handler是一个接口

```
type Handler interface {
	ServeHTTP(ResponseWriter, *Request)
}
```

所以，这里就是我们的入口，这里我们需要有一个类来实现这个接口：Engine。

```
type Engine struct {

}

func (engine *Engine) ServeHTTP(w http.ResponseWriter, req *http.Request) {
	...
}
```

这里ServeHTTP的方法传递的两个参数，一个是Request，一个是ResponseWriter，Engine中的ServeHTTP的方法就是要对这两个对象进行读取或者写入操作。而且这两个对象往往是需要同时存在的，为了避免很多函数都需要写这两个参数，我们不如封装一个结构来把这两个对象放在里面：Context

```
type Context struct {
  writermem responseWriter
	Request   *http.Request
	Writer    ResponseWriter
  ...
}


type responseWriter struct {
	http.ResponseWriter
	size   int
	status int
}

```

这里有几个需要讨论的点：
## Writer是否可以直接使用http包的ResponseWriter接口

```
type ResponseWriter interface {

	Header() Header

	Write([]byte) (int, error)

	WriteHeader(statusCode int)
}
```
但是考虑到我们web框架的最重要的就是输出数据给客户端，这里的输出逻辑我们极有可能需要自己封装一些框架自带的方法。所以我们不妨自定义一个结构responseWriter，来实现基本的http.ResponseWriter。并且实现一些具体的其他方法。这些具体的其他方法都有哪些呢？我们使用gin包自带的ResponseWriter接口来说明。

```
type ResponseWriter interface {
	responseWriterBase

	Pusher() http.Pusher
}

type responseWriterBase interface {
	http.ResponseWriter
	http.Hijacker
	http.Flusher
	http.CloseNotifier

	Status() int

	Size() int

	WriteString(string) (int, error)

	Written() bool

	WriteHeaderNow()
}
```

## 为什么Context有writermem和Writer两个实现了http.Response对象的结构？

首先我们自带的ResponseWriter必须实现比http.ResponseWriter更强大的接口功能，这个是毋庸置疑的。所以，我们不妨考虑下这里如果不是两个writermem和Writer两个的话，只有一个存在是否可能？

如果只有Writer接口存在，这个一定不可能，这个Writer实现的是我们gin自定义的接口，外部serveHTTP传递的是实现了http.ResponseWriter的类，并不能保证实现了gin自带的ResponseWriter。

如果只有writermen结构存在，这个是可能的。外部传递的http.ResponseWriter就被藏在了这个对象里面。但是这样就丢失了接口的灵活性。本质还是对外暴露的是接口还是结构的逻辑，设想一下如果使用这个框架的用户要自己实现一个ResponseWriter，就需要继承这个结构，而不是继承接口。而具体的调用的方法就变成了被继承结构的方法了。例子如下：

```
package main

func main() {
	customResp := new(customResponseWriter)

	c := new(context)
	c.Writermem = customResp.responseWriter
	c.Writermem.call()
}

type context struct {
	Writermem responseWriter
}

type customResponseWriter struct {
	responseWriter
}

func (r *customResponseWriter)call() {

}

type responseWriter struct{}

func (r *responseWriter)call() {

}

```
所以这里的Context结构，对外暴露的是接口ResponseWriter，内部的responseWriter结构实现了ResponseWriter接口。在reset()的时候进行拷贝是合理的。

```
func (c *Context) reset() {
	c.Writer = &c.writermem
	c.Params = c.Params[0:0]
	c.handlers = nil
	c.index = -1
	c.Keys = nil
	c.Errors = c.Errors[0:0]
	c.Accepted = nil
}
```

content就是某个请求的上下文结构，这个结构当然是可以不断new的，但是new这个对象的代价可以使用一个对象池进行服用，节省对象频繁创建和销毁的开销。golang中的sync.Pool就是用于这个用途的。需要注意的是，这里的对象池并不是所谓的固定对象池，而是临时对象池，里面的对象个数不能指定，对象存储时间也不能指定，只是增加了对象复用的概率而已。

```
type Engine struct {
  ...
	pool             sync.Pool
  ...
}
```

这个Context是gin中最重要的数据结构之一了，它既然已经包了request了，那么从请求中获取参数的各个接口它必然也需要包了。
```

func (c *Context) Param(key string) string
...

func (c *Context) Query(key string) string

func (c *Context) DefaultQuery(key, defaultValue string) string

...

func (c *Context) PostFormArray(key string) []string
```

# 路由

从http请求进来的逻辑理清楚了，下面就进入到了路由部分，路由其实还是分为两个部分，一个是路由设置部分，一个是路由匹配部分。

路由其实并不仅仅是url，还包括HTTP的请求方法，而实现一个REST风格的http请求，需要支持REST支持的方法，比如GET，PUT，POST，DELETE，OPTION等。

路由一定是有很多个路由路径，可以使用数组存储，但更巧妙的是，使用Redix树结构进行存储。这样寻找的方法更为高效。

首先我们会在Engine这个结构中增加树结构,并且提供增加路由的功能

```
type Engine struct {
  ...
	pool             sync.Pool
	trees            methodTrees
}

type methodTrees []methodTree

type methodTree struct {
	method string
	root   *node
}

type node struct {
	path      string
	indices   string
	children  []*node
	handlers  HandlersChain
	priority  uint32
	nType     nodeType
	maxParams uint8
	wildChild bool
}

func (engine *Engine) addRoute(method, path string, handlers HandlersChain) {
	assert1(path[0] == '/', "path must begin with '/'")
	assert1(method != "", "HTTP method can not be empty")
	assert1(len(handlers) > 0, "there must be at least one handler")

	debugPrintRoute(method, path, handlers)
	root := engine.trees.get(method)
	if root == nil {
		root = new(node)
		engine.trees = append(engine.trees, methodTree{method: method, root: root})
	}
	root.addRoute(path, handlers)
}
```

其中我们可以看到engine.trees实际上是有多个树组成，这里的每个树都是根据HTTP method进行区分的。每增加一个路由，就往engine中对应的method的树中增加一个path和handler的关系。

这个树是一个Redix树，父节点存储子节点的公共部分，子节点存在各自的特有路径。
如图：
![](http://tuchuang.funaio.cn/18-9-17/94879855.jpg)

那么具体往这个trees中增加路由怎么增加呢？

这里选择使用一个结构RouterGroup

```
type RouterGroup struct {
	Handlers HandlersChain
	basePath string
	engine   *Engine
	root     bool
}

type HandlerFunc func(*Context)
type HandlersChain []HandlerFunc

func (group *RouterGroup) handle(httpMethod, relativePath string, handlers HandlersChain) IRoutes {
	absolutePath := group.calculateAbsolutePath(relativePath)
	handlers = group.combineHandlers(handlers)
	group.engine.addRoute(httpMethod, absolutePath, handlers)
	return group.returnObj()
}

func (group *RouterGroup) POST(relativePath string, handlers ...HandlerFunc) IRoutes {
	return group.handle("POST", relativePath, handlers)
}

// GET is a shortcut for router.Handle("GET", path, handle).
func (group *RouterGroup) GET(relativePath string, handlers ...HandlerFunc) IRoutes {
	return group.handle("GET", relativePath, handlers)
}

// DELETE is a shortcut for router.Handle("DELETE", path, handle).
func (group *RouterGroup) DELETE(relativePath string, handlers ...HandlerFunc) IRoutes {
	return group.handle("DELETE", relativePath, handlers)
}

// PATCH is a shortcut for router.Handle("PATCH", path, handle).
func (group *RouterGroup) PATCH(relativePath string, handlers ...HandlerFunc) IRoutes {
	return group.handle("PATCH", relativePath, handlers)
}

// PUT is a shortcut for router.Handle("PUT", path, handle).
func (group *RouterGroup) PUT(relativePath string, handlers ...HandlerFunc) IRoutes {
	return group.handle("PUT", relativePath, handlers)
}

// OPTIONS is a shortcut for router.Handle("OPTIONS", path, handle).
func (group *RouterGroup) OPTIONS(relativePath string, handlers ...HandlerFunc) IRoutes {
	return group.handle("OPTIONS", relativePath, handlers)
}

// HEAD is a shortcut for router.Handle("HEAD", path, handle).
func (group *RouterGroup) HEAD(relativePath string, handlers ...HandlerFunc) IRoutes {
	return group.handle("HEAD", relativePath, handlers)
}

// Any registers a route that matches all the HTTP methods.
// GET, POST, PUT, PATCH, HEAD, OPTIONS, DELETE, CONNECT, TRACE.
func (group *RouterGroup) Any(relativePath string, handlers ...HandlerFunc) IRoutes {
	group.handle("GET", relativePath, handlers)
	group.handle("POST", relativePath, handlers)
	group.handle("PUT", relativePath, handlers)
	group.handle("PATCH", relativePath, handlers)
	group.handle("HEAD", relativePath, handlers)
	group.handle("OPTIONS", relativePath, handlers)
	group.handle("DELETE", relativePath, handlers)
	group.handle("CONNECT", relativePath, handlers)
	group.handle("TRACE", relativePath, handlers)
	return group.returnObj()
}
```

那么Engine就继承RouterGroup:
```
type Engine struct {
  RouterGroup
  ...
	pool             sync.Pool
	trees            methodTrees
}
```

看到这里就有一点REST的味道了吧。

有人会问，为什么不把这些方法的具体实现放在Engine中呢？这里我考虑到是由于“路由”和“引擎”毕竟是两个逻辑，使用继承的方式有利于代码逻辑分离。并且gin还定义了接口IRoutes来表示RouterGroup实现的方法。

```
type IRoutes interface {
	Use(...HandlerFunc) IRoutes

	Handle(string, string, ...HandlerFunc) IRoutes
	Any(string, ...HandlerFunc) IRoutes
	GET(string, ...HandlerFunc) IRoutes
	POST(string, ...HandlerFunc) IRoutes
	DELETE(string, ...HandlerFunc) IRoutes
	PATCH(string, ...HandlerFunc) IRoutes
	PUT(string, ...HandlerFunc) IRoutes
	OPTIONS(string, ...HandlerFunc) IRoutes
	HEAD(string, ...HandlerFunc) IRoutes

	StaticFile(string, string) IRoutes
	Static(string, string) IRoutes
	StaticFS(string, http.FileSystem) IRoutes
}
```

将RouterGroup和Engine区分开，还有一个好处。我们有时候需要将一批路由加个统一前缀，这里需要用到方法：

使用例子如下：
```
v1 := router.Group("/v1")

v1.GET("/login", func(c *gin.Context) {
  c.String(http.StatusOK, "v1 login")
})

```

这里再看一下RouterGroup的Group函数。
```
func (group *RouterGroup) Group(relativePath string, handlers ...HandlerFunc) *RouterGroup {
	return &RouterGroup{
		Handlers: group.combineHandlers(handlers),
		basePath: group.calculateAbsolutePath(relativePath),
		engine:   group.engine,
	}
}
```
它把RouterGroup暴露出来，而不是把Engine暴露出来，这样整个逻辑就很清晰，我可以对这个RouterGroup进行各种自定义方法。在最后调用v1.GET的时候再将带有绝对路径的handler挂在engine上的tree上。

在请求进来的时候，路由匹配，在engine的handleHTTPRequest
```
func (engine *Engine) handleHTTPRequest(c *Context) {
	httpMethod := c.Request.Method
	path := c.Request.URL.Path
	unescape := false
	if engine.UseRawPath && len(c.Request.URL.RawPath) > 0 {
		path = c.Request.URL.RawPath
		unescape = engine.UnescapePathValues
	}

	// Find root of the tree for the given HTTP method
	t := engine.trees
	for i, tl := 0, len(t); i < tl; i++ {
		if t[i].method != httpMethod {
			continue
		}
		root := t[i].root
		// Find route in tree
		handlers, params, tsr := root.getValue(path, c.Params, unescape)
		if handlers != nil {
			c.handlers = handlers
			c.Params = params
			c.Next()
			c.writermem.WriteHeaderNow()
			return
		}
		if httpMethod != "CONNECT" && path != "/" {
			if tsr && engine.RedirectTrailingSlash {
				redirectTrailingSlash(c)
				return
			}
			if engine.RedirectFixedPath && redirectFixedPath(c, root, engine.RedirectFixedPath) {
				return
			}
		}
		break
	}
  ...
}
```
去Engine中的tree中调用getValue获取出对应的handlers进行处理。

# 中间件

下面就要聊到路由对应的handlers是什么了？这里我们看到tree中路由对应的是HandlersChain，实际就是[]HandlerFunc，所以一个路由，实际上会对应多个handlers。

首先我们已经把request和responseWriter封装在context里面了，多个handler只要处理好这个context就可以了，所以是可以一个路由拥有多个handler的。

其次这里的handler是怎么来的呢？

每个路由的handler有几个来源，第一个来源是在engine.GET的时候调用增加的。第二个来源是RouterGroup.GET的时候增加的，其实这两种方式都是调用
```
func (group *RouterGroup) GET(relativePath string, handlers ...HandlerFunc) IRoutes {
	return group.handle("GET", relativePath, handlers)
}

func (group *RouterGroup) handle(httpMethod, relativePath string, handlers HandlersChain) IRoutes {
	absolutePath := group.calculateAbsolutePath(relativePath)
	handlers = group.combineHandlers(handlers)
	group.engine.addRoute(httpMethod, absolutePath, handlers)
	return group.returnObj()
}

func (group *RouterGroup) combineHandlers(handlers HandlersChain) HandlersChain {
	finalSize := len(group.Handlers) + len(handlers)
	if finalSize >= int(abortIndex) {
		panic("too many handlers")
	}
	mergedHandlers := make(HandlersChain, finalSize)
	copy(mergedHandlers, group.Handlers)
	copy(mergedHandlers[len(group.Handlers):], handlers)
	return mergedHandlers
}
```

从两个copy的顺序可以看出，group的handler高于自定义的handler。这里自定义的handler可以是多个，比如:
```
router.GET("/before", MiddleWare(), func(c *gin.Context) {
  request := c.MustGet("request").(string)
  c.JSON(http.StatusOK, gin.H{
    "middile_request": request,
  })
})

func MiddleWare() gin.HandlerFunc {
	return func(c *gin.Context) {
		fmt.Println("before middleware")
		c.Set("request", "clinet_request")
		c.Next()
		fmt.Println("before middleware")
	}
}
```

这里的/before实际上是带了两个handler。

第三种方法是使用Use增加中间件的方式：
```
router.Use(MiddleWare())
```
这里的会把这个中间件（实际上也是一个handler）存放到routerRroup上。所以中间件是属于groupHandlers的。

在请求进来的时候是如何调用的呢？

答案还是在handleHTTPRequest中
```
func (engine *Engine) handleHTTPRequest(c *Context) {
	...
		handlers, params, tsr := root.getValue(path, c.Params, unescape)
		if handlers != nil {
			c.handlers = handlers
			c.Params = params
			c.Next()
			c.writermem.WriteHeaderNow()
			return
		}
	..
}

func (c *Context) Next() {
	c.index++
	for s := int8(len(c.handlers)); c.index < s; c.index++ {
		c.handlers[c.index](c)
	}
}
```

每个请求进来，匹配好路由之后，会获取这个路由最终combine的handlers，把它放在全局的context中，然后通过调用context.Next()来进行递归调用这个handlers。
