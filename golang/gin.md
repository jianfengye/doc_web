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

每个请求进来，匹配好路由之后，会获取这个路由最终combine的handlers，把它放在全局的context中，然后通过调用context.Next()来进行递归调用这个handlers。当然在中间件里面需要记得调用context.Next() 把控制权还给Context。

# 静态文件

golang的http包中对静态文件的读取是有封装的：
```
func ServeFile(w ResponseWriter, r *Request, name string)
```

routerGroup也是有把这个封装成为方法的
```
func (group *RouterGroup) Static(relativePath, root string) IRoutes {
	return group.StaticFS(relativePath, Dir(root, false))
}

func (group *RouterGroup) StaticFS(relativePath string, fs http.FileSystem) IRoutes {
  ...
	handler := group.createStaticHandler(relativePath, fs)
  ...
}

func (group *RouterGroup) createStaticHandler(relativePath string, fs http.FileSystem) HandlerFunc {
	...
		fileServer.ServeHTTP(c.Writer, c.Request)
	...
}

```

所以调用应该像这样：
```
router.Static("/assets", "./assets")
router.StaticFS("/more_static", http.Dir("my_file_system"))
router.StaticFile("/favicon.ico", "./resources/favicon.ico")
```

其中的StaticFS的第二个参数可以是实现了http.FileSystem的任何结构。

# 绑定

参数一个一个获取是很麻烦的，我们一般还会把参数赋值到某个struct中，这个时候解析参数，赋值的过程很繁琐。我们是不是提供一个自动绑定的方法来操作呢？

```
package main

import (
	"log"
	"time"

	"github.com/gin-gonic/gin"
)

type Person struct {
	Name     string    `form:"name"`
	Address  string    `form:"address"`
	Birthday time.Time `form:"birthday" time_format:"2006-01-02" time_utc:"1"`
}

func main() {
	route := gin.Default()
	route.GET("/testing", startPage)
	route.Run(":8085")
}

func startPage(c *gin.Context) {
	var person Person
	// If `GET`, only `Form` binding engine (`query`) used.
	// If `POST`, first checks the `content-type` for `JSON` or `XML`, then uses `Form` (`form-data`).
	// See more at https://github.com/gin-gonic/gin/blob/master/binding/binding.go#L48
	if c.ShouldBind(&person) == nil {
		log.Println(person.Name)
		log.Println(person.Address)
		log.Println(person.Birthday)
	}

	c.String(200, "Success")
}
```

```
$ curl -X GET "localhost:8085/testing?name=appleboy&address=xyz&birthday=1992-03-15"
```

这个是不是很方便？它是怎么实现的呢？

首先参数解析是和http请求的content-type头有关，当content-type头为application/json的时候，我们会在body中传递json，并且应该解析请求body中的json，而content-type头为application/xml的时候，我们会解析body中的xml。

我们之前说了，这些解析的行为应该都是Context包了的。所以这些方法都定义在Context中
```
func (c *Context) ShouldBind(obj interface{}) error {
	b := binding.Default(c.Request.Method, c.ContentType())
	return c.ShouldBindWith(obj, b)
}

// ShouldBindJSON is a shortcut for c.ShouldBindWith(obj, binding.JSON).
func (c *Context) ShouldBindJSON(obj interface{}) error {
	return c.ShouldBindWith(obj, binding.JSON)
}

// ShouldBindXML is a shortcut for c.ShouldBindWith(obj, binding.XML).
func (c *Context) ShouldBindXML(obj interface{}) error {
	return c.ShouldBindWith(obj, binding.XML)
}

// ShouldBindQuery is a shortcut for c.ShouldBindWith(obj, binding.Query).
func (c *Context) ShouldBindQuery(obj interface{}) error {
	return c.ShouldBindWith(obj, binding.Query)
}

// ShouldBindWith binds the passed struct pointer using the specified binding engine.
// See the binding package.
func (c *Context) ShouldBindWith(obj interface{}, b binding.Binding) error {
	return b.Bind(c.Request, obj)
}
```

这里binding这块应该怎么设计呢？其实知道了具体的解析方式，就知道如何绑定，比如知道了这个是json解析，我就可以很方便将参数直接json.Decode，如果知道这个是query解析，我可以直接从URL.Query中获取请求串，如果知道这个是表单form，我就可以直接request.ParseForm来解析。

所以，这个还是一个接口，多个结构实现的设计。

定义一个接口：
```
type Binding interface {
	Name() string
	Bind(*http.Request, interface{}) error
}
```

定一个多个结构：
```
type formBinding struct{}

func (formBinding) Bind(req *http.Request, obj interface{}) error {
	if err := req.ParseForm(); err != nil {
		return err
	}
	req.ParseMultipartForm(defaultMemory)
	if err := mapForm(obj, req.Form); err != nil {
		return err
	}
	return validate(obj)
}

type jsonBinding struct{}

func (jsonBinding) Bind(req *http.Request, obj interface{}) error {
	return decodeJSON(req.Body, obj)
}
var (
	JSON          = jsonBinding{}
	XML           = xmlBinding{}
	Form          = formBinding{}
	Query         = queryBinding{}
	FormPost      = formPostBinding{}
	FormMultipart = formMultipartBinding{}
	ProtoBuf      = protobufBinding{}
	MsgPack       = msgpackBinding{}
)
...
```
在使用绑定解析的时候，我们可以使用ShouldBindWith来指定我们要使用的是哪些解析方式。

# 参数验证

我们希望在绑定参数的时候，也能给我做一下验证，有点像laravel里面的Validater一样，我在绑定的对象设置一下这个字段是否可以为空，是否必须是int等。官网的例子：
```
package main

import (
	"net/http"
	"reflect"
	"time"

	"github.com/gin-gonic/gin"
	"github.com/gin-gonic/gin/binding"
	"gopkg.in/go-playground/validator.v8"
)

// Booking contains binded and validated data.
type Booking struct {
	CheckIn  time.Time `form:"check_in" binding:"required,bookabledate" time_format:"2006-01-02"`
	CheckOut time.Time `form:"check_out" binding:"required,gtfield=CheckIn" time_format:"2006-01-02"`
}

func bookableDate(
	v *validator.Validate, topStruct reflect.Value, currentStructOrField reflect.Value,
	field reflect.Value, fieldType reflect.Type, fieldKind reflect.Kind, param string,
) bool {
	if date, ok := field.Interface().(time.Time); ok {
		today := time.Now()
		if today.Year() > date.Year() || today.YearDay() > date.YearDay() {
			return false
		}
	}
	return true
}

func main() {
	route := gin.Default()

	if v, ok := binding.Validator.Engine().(*validator.Validate); ok {
		v.RegisterValidation("bookabledate", bookableDate)
	}

	route.GET("/bookable", getBookable)
	route.Run(":8085")
}

func getBookable(c *gin.Context) {
	var b Booking
	if err := c.ShouldBindWith(&b, binding.Query); err == nil {
		c.JSON(http.StatusOK, gin.H{"message": "Booking dates are valid!"})
	} else {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
	}
}
```

这种需要怎么做呢？

首先当然在上面说的Bind的函数里面需要加上验证的逻辑，比如像jsonBinding:
```
func decodeJSON(r io.Reader, obj interface{}) error {
	decoder := json.NewDecoder(r)
	if EnableDecoderUseNumber {
		decoder.UseNumber()
	}
	if err := decoder.Decode(obj); err != nil {
		return err
	}
	return validate(obj)
}

```

这里的validate:

```
func validate(obj interface{}) error {
	if Validator == nil {
		return nil
	}
	return Validator.ValidateStruct(obj)
}

var Validator StructValidator = &defaultValidator{}

```

调用了一个全局的defaultValidator:

```
type defaultValidator struct {
	once     sync.Once
	validate *validator.Validate
}
```
这里的defaultValidator的ValidateStruct()最终调用的就是validator.v8包的Stuct方法
```
func (v *defaultValidator) ValidateStruct(obj interface{}) error {
...
		if err := v.validate.Struct(obj); err != nil {
			return err
		}
...
}
```

同样的，gin为了不让Validator绑死在validator.v8上，这个default的Validator不是写死是validator.v8的结构，而是自己定义了一个接口：
```
type StructValidator interface {

	ValidateStruct(interface{}) error

	Engine() interface{}
}
```
如果你想用其他的validator，或者自定义一个validator，那么只要实现了这个接口，就可以把它赋值到Validator就可以了。

这种用接口隔离第三方库的方式确实很巧妙。

# Logger中间件

既然有中间件机制，我们可以定义几个默认的中间件，日志Logger()是一个必要的中间件。

这个Logger中间件的作用是记录下每个请求的请求地址，请求时长等：

```
[GIN] 2018/09/18 - 11:37:32 | 200 |     413.536µs |             ::1 | GET      /index
```

具体实现追下去看就明白了，请求前设置开始时间，请求后设置结束时间，然后打印信息。

# Recovery中间件

Recovery也是一个必要的中间件，试想一下，如果某个业务逻辑出现panic请求，难道整个http server就挂了？这是不允许的。所以这个Recovery做的事情是捕获请求中的panic信息，吧信息打印到日志中。

```
func RecoveryWithWriter(out io.Writer) HandlerFunc {
	var logger *log.Logger
	if out != nil {
		logger = log.New(out, "\n\n\x1b[31m", log.LstdFlags)
	}
	return func(c *Context) {
		defer func() {
			if err := recover(); err != nil {
				if logger != nil {
					stack := stack(3)
					httprequest, _ := httputil.DumpRequest(c.Request, false)
					logger.Printf("[Recovery] %s panic recovered:\n%s\n%s\n%s%s", timeFormat(time.Now()), string(httprequest), err, stack, reset)
				}
				c.AbortWithStatus(http.StatusInternalServerError)
			}
		}()
		c.Next()
	}
}
```

logger和Recovery这两个中间件在生成默认的Engine的时候已经加上了。
```
func Default() *Engine {
	debugPrintWARNINGDefault()
	engine := New()
	engine.Use(Logger(), Recovery())
	return engine
}
```

# 总结

gin是个很精致的框架，它的路由，参数绑定，中间件等逻辑使用非常方便，扩展性也是设计的非常好，没有多余的耦合。

# 附录

带个我从各个地方搜索出来的demo例子

```
package main

import (
	"github.com/gin-gonic/gin"
	"net/http"
	"log"
	"fmt"
	"time"
	"gopkg.in/go-playground/validator.v8"
	"reflect"
	"github.com/gin-gonic/gin/binding"
)

func main() {
	router := gin.Default()

	router.Use()
	router.GET("/", func(c *gin.Context) {
		c.String(http.StatusOK, "It works")
	})

	router.POST("/form_post", func(c *gin.Context) {
		message := c.PostForm("message")
		nick := c.DefaultPostForm("nick", "anonymous")

		c.JSON(200, gin.H{
			"status":  "posted",
			"message": message,
			"nick":    nick,
		})
	})

	router.POST("/upload", func(c *gin.Context) {
		// single file
		file, _ := c.FormFile("file")
		log.Println(file.Filename)

		c.String(http.StatusOK, fmt.Sprintf("'%s' uploaded!", file.Filename))
	})

	router.LoadHTMLGlob("templates/*")
	router.GET("/upload", func(c *gin.Context) {
		c.HTML(http.StatusOK, "upload.html", gin.H{})
	})
	router.GET("/index", func(c *gin.Context) {
		c.HTML(http.StatusOK, "index.tmpl", gin.H{
			"title": "Main website",
		})
	})

	router.GET("/redict/google", func(c *gin.Context) {
		c.Redirect(http.StatusMovedPermanently, "https://google.com")
	})

	v1 := router.Group("/v1")

	v1.GET("/login", func(c *gin.Context) {
		c.String(http.StatusOK, "v1 login")
	})

	v2 := router.Group("/v2")

	v2.GET("/login", func(c *gin.Context) {
		c.String(http.StatusOK, "v2 login")
	})

	router.Use(MiddleWare())

	router.GET("/before", MiddleWare(), func(c *gin.Context) {
		request := c.MustGet("request").(string)
		c.JSON(http.StatusOK, gin.H{
			"middile_request": request,
		})
	})

	router.GET("/sync", func(c *gin.Context) {
		time.Sleep(5 * time.Second)
		log.Println("Done! in path" + c.Request.URL.Path)
	})

	router.GET("/async", func(c *gin.Context) {
		cCp := c.Copy()
		go func() {
			time.Sleep(5 * time.Second)
			log.Println("Done! in path" + cCp.Request.URL.Path)
		}()
	})

	router.GET("/user/:name", func(c *gin.Context) {
		name := c.Param("name")
		c.String(http.StatusOK, "Hello %s", name)
	})

	router.GET("/welcome", func(c *gin.Context) {
		firstname := c.DefaultQuery("firstname", "Guest")
		lastname := c.Query("lastname") // shortcut for     c.Request.URL.Query().Get("lastname")

		c.String(http.StatusOK, "Hello %s %s", firstname, lastname)
	})

	router.GET("/User/:name/*action",func (c *gin.Context){
		name:= c.Param("name")
		action := c.Param("action")
		message := name + "is" + action
		c.String(http.StatusOK,message)
	})

	router.GET("/welcome2", func(c *gin.Context) {
		firstname := c.DefaultQuery("firstname", "Guest")
		lastname := c.Query("lastname") // shortcut for     c.Request.URL.Query().Get("lastname")

		c.String(http.StatusOK, "Hello %s %s", firstname, lastname)
	})

	router.Static("/assets", "./assets")
	router.StaticFS("/more_static", http.Dir("my_file_system"))
	router.StaticFile("/favicon.ico", "./resources/favicon.ico")

	router.GET("/testing", startPage)

	if v, ok := binding.Validator.Engine().(*validator.Validate); ok {
		v.RegisterValidation("bookabledate", bookableDate)
	}

	router.GET("/bookable", getBookable)

	router.Run(":8001")
}

func MiddleWare() gin.HandlerFunc {
	return func(c *gin.Context) {
		fmt.Println("before middleware")
		c.Set("request", "clinet_request")
		c.Next()
		fmt.Println("before middleware")
	}
}

func startPage(c *gin.Context) {
	var person Person
	if c.ShouldBind(&person) == nil {
		log.Println(person.Name)
		log.Println(person.Address)
		log.Println(person.Birthday)
	}

	c.String(200, "Success")
}

type Person struct {
	Name     string    `form:"name"`
	Address  string    `form:"address"`
	Birthday time.Time `form:"birthday" time_format:"2006-01-02" time_utc:"1"`
}

type Booking struct {
	CheckIn  time.Time `form:"check_in" binding:"required,bookabledate" time_format:"2006-01-02"`
	CheckOut time.Time `form:"check_out" binding:"required,gtfield=CheckIn" time_format:"2006-01-02"`
}

func bookableDate(
	v *validator.Validate, topStruct reflect.Value, currentStructOrField reflect.Value,
	field reflect.Value, fieldType reflect.Type, fieldKind reflect.Kind, param string,
) bool {
	if date, ok := field.Interface().(time.Time); ok {
		today := time.Now()
		if today.Year() > date.Year() || today.YearDay() > date.YearDay() {
			return false
		}
	}
	return true
}

func getBookable(c *gin.Context) {
	var b Booking
	if err := c.ShouldBindWith(&b, binding.Query); err == nil {
		c.JSON(http.StatusOK, gin.H{"message": "Booking dates are valid!"})
	} else {
		c.JSON(http.StatusBadRequest, gin.H{"error": err.Error()})
	}
}


```
