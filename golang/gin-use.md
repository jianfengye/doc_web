# gin框架使用注意事项[未完成]

本文就说下在使用gin框架过程中遇到的问题和要注意的事情

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
