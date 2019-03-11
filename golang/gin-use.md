# gin框架使用注意事项[未完成]

本文就说下这段时间我在使用gin框架过程中遇到的问题和要注意的事情。

# 错误处理请求返回要使用c.Abort，不要只是return

当在controller中进行错误处理的时候，发现一个错误，往往要立即返回，这个时候要记得使用gin.Context.Abort 或者其相关的函数。

类似于：
```
if err != nil {
		c.AbortWithStatus(500)
		return
	}
```
这个Abort函数本质是提前结束后续的handler链条，（通过将handler的下标索引直接变化为 math.MaxInt8 / 2 ）但是前面已经执行过的handler链条（包括middleware等）还会继续返回。

gin的Abort系列的几个函数为：
```
func (c *Context) Abort()
func (c *Context) AbortWithStatus(code int)
func (c *Context) AbortWithStatusJSON(code int, jsonObj interface{})
func (c *Context) AbortWithError(code int, err error)
```

# gin的错误处理

gin本身默认加载了Recovery()的中间件，所以在不知道如何处理error的时候，可以直接panic出去

# 如何获取response的body

需求来源于我要做个gin的中间件，请求进来的时候记录一下请求参数，请求出去的时候记录一下请求返回值。在记录请求返回值的时候，我就需要得到请求的返回内容。但是context里面只有一个结构：
```
Writer    gin.ResponseWriter
```

所以这里基本思路就是创建一个Writer，它继承gin.ResponseWriter。同时，它又有一个byte.buffer来copy一份数据。

```
// bodyLogWriter是为了记录返回数据到log中进行了双写
type bodyLogWriter struct {
	gin.ResponseWriter
	body *bytes.Buffer
}

func (w bodyLogWriter) Write(b []byte) (int, error) {
	w.body.Write(b)
	return w.ResponseWriter.Write(b)
}

```

所以，在middleware中就应该这么写

```
sTime := time.Now()

blw := &bodyLogWriter{body: bytes.NewBufferString(""), ResponseWriter: c.Writer}
c.Writer = blw

c.Next()

// 请求结束的时候记录
duration := fmt.Sprintf("%fms", float64(time.Now().Sub(sTime).Nanoseconds()) / 1000000.0)
handler.Tracef(c.Request.Context(), logger.DLTagRequestOut,
	"proc_time=%s||response=%s",
	duration,
	blw.body.String())
```

主要就是在Next之前吧context.Writer用我们定义的Writer给替换掉，让它输出数据的时候写两份。

# 如何获取所有的请求参数

这个其实和gin框架没有啥关系，我刚开始使用的时候以为使用request.ParseForm，然后在request.Form中就能得到了。

结果发现当我的Content-type为multipart/form-data的时候，竟然解析不到数据。

追到ParseForm里面发现，http/request.go里面有这么一个部分代码
```
case ct == "multipart/form-data":
	// handled by ParseMultipartForm (which is calling us, or should be)
	// TODO(bradfitz): there are too many possible
	// orders to call too many functions here.
	// Clean this up and write more tests.
	// request_test.go contains the start of this,
	// in TestParseMultipartFormOrder and others.
}
```
我的golang版本是1.11.4。当content-type为multipart/form-data的时候是空的调用的。

当然注释也写很清楚了，建议使用ParseMultipartForm

所以获取http参数的函数我就写成这个样子：
```
// 这个函数只返回json化之后的数据，且不处理错误，错误就返回空字符串
func getArgs(c *gin.Context) []byte {
	if c.ContentType() == "multipart/form-data" {
		c.Request.ParseMultipartForm(defaultMemory)
	} else {
		c.Request.ParseForm()
	}
	args, _ := json.Marshal(c.Request.Form)
	return args
}
```
