# fastcgi概述[未发布]
## cgi
cgi是通用网关接口，是连接web服务器和应用程序的接口。

web服务器负责接收http请求，但是http请求从request到response的过程需要有应用程序的逻辑处理，web服务器一般是使用C写的，比如nginx，apache。而应用程序则是由各种语言编写，比如php，java，python等。这两种语言要进行交互就需要有个协议进行规定，而cgi就是这么个网关协议。

拿nginx+fastcgi+php为例子，nginx里面的fastcgi模块实现cgi的客户端，php的cgi-sapi实现cgi的服务端。

## cgi的多进程模型
cgi一般是使用多进程模型来实现的，web服务器接收http请求以后，转发到cgi进程，一个http请求会使用一个cgi进程，而这个进程一般都是单线程的，就意味着一个进程只能同时处理一个请求，只有请求执行完成了，才会进行下个请求的处理。这就是基本的web服务器+cgi+php的模型。

cgi使用多个单线程的进程有什么好处呢？
- 每个请求和其他请求是隔离的。意味着如果这个请求如果失败了，那么不会影响在其他cgi进程正在处理的请求。

## cgi的衍生

- fastcgi (PHP)
- PSGI (Perl)
- WSGI (Python)

## fastcgi
fastcgi是以独立的进程池来运行cgi的。

fastcgi既然有独立进程池，那么它就需要有个master进程，master进程分配和管理进程池。每个fastcgi子进程都负责接收请求转到应用程序中进行处理。但是每个fastcgi子进程处理完请求以后，不是立刻就杀死自己，而是可以持续使用，等待接收下个cgi请求。fastcgi可以设置：
- 开启多少个子进程
- 每个子进程处理过多少请求后重启

## fastcgi相比于cgi有哪些优势
- 可以进行分布式分布。可以有这种模型，web机器在一台机器上，fastcgi运行在不同机器上，这台web机器接收请求分发到不同的后端fastcgi机器上。
- 比cgi可以进行更多在web服务器上的处理。cgi只是将请求发送到后端，而fastcgi可以做更多的事情，比如权限和角色认证，比如把一个数据类型转换为另一个数据类型等。

## fastcgi的客户端语言
- C，C++，Perl，JAVA，有提供fastcgi的库供处理
- D语言：FastCGI4D
- Perl(Perl FCGI等)
- PHP：Fastcgi Process manager
- Ruby
- Python
- TCL
- Common Lisp
- Smalltalk

## 支持fastcgi的web服务端
- Apache：mod_fastcgi是Apache的内嵌模块之一
- Microsoft IIS
- SunOne
- Lighttpd
- Premium thttpd
- MyServer
- Pi3Web
- WebSTAR (Mac OS)
- [Nginx](http://wiki.nginx.org/NginxHttpFcgiModule)
- Cherokee

##参考文章
[fastcgi官网](http://www.fastcgi.com/drupal/node/2)
