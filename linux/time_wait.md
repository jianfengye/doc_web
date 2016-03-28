# 也说说TIME_WAIT状态

一个朋友问到，自己用go写了一个简单的HTTP服务端程序，为什么压测的时候服务端会出现一段时间的TIME_WAIT超高的情况，导致压测的效果不好呢？
记得老王有两篇文章专门说这个，当时粗粗看了一遍，正好碰上这个问题，又翻出来细细搂了。

第一个要弄懂的，是TIME_WAIT是怎么产生的。

# TIME_WAIT状态是怎么产生的

要弄懂TIME_WAIT要从TCP的四次握手的分手协议说起。

![](http://ww2.sinaimg.cn/large/74311666jw1f2cc3saeoej20d40iygnj.jpg)

上面这个图片展示了TCP从连接建立到连接释放的过程中，客户端和服务端的状态变化图。如果只看连接释放阶段，四次握手

* 客户端先发送FIN，进入FIN_WAIT1状态
* 服务端收到FIN，发送ACK，进入CLOSE_WAIT状态，客户端收到这个ACK，进入FIN_WAIT2状态
* 服务端发送FIN，进入LAST_ACK状态
* 客户端收到FIN，发送ACK，进入TIME_WAIT状态，服务端收到ACK，进入CLOSE状态
* 客户端TIME_WAIT持续2倍MSL时长，在linux体系中大概是60s，转换成CLOSE状态

当然在这个例子和上面的图片中，使用客户端和服务端来描述是不准确的，TCP主动断开连接的一方可能是客户端，也可能是服务端。所以使用主动断开的一方，和被动断开的一方替换上面的图可能更为贴切。

不管怎么说，TIME_WAIT的状态就是主动断开的一方，发送完最后一次ACK之后进入的状态。并且持续时间还挺长的。

能不能发送完ACK之后不进入TIME_WAIT就直接进入CLOSE状态呢？不行的，这个是为了TCP协议的可靠性，由于网络原因，ACK可能会发送失败，那么这个时候，被动一方会主动重新发送一次FIN，这个时候如果主动方在TIME_WAIT状态，则还会再发送一次ACK，从而保证可靠性。那么从这个解释来说，2MSL的时长设定是可以理解的，MSL是报文最大生存时间，如果重新发送，一个FIN＋一个ACK，再加上不定期的延迟时间，大致是在2MSL的范围。

所以从理论上说，网上调试参数降低TIME_WAIT的持续时间的方法是一种以可靠性换取性能的一种方式。嗯，质量守恒定理还是铁律。

# 服务端TIME_WAIT过多

回到上面的问题，go写了一个HTTP服务，压测发现TIME_WAIT过多。

首先判断是不是压测程序放在服务的同一台机器...当然不会犯这么低级的错误...

那么这个感觉就有点奇怪了，HTTP服务并没有依赖外部mysql或者redis等服务，就是一个简单的Hello world，而TIME_WAIT的是主动断开方才会出现的，所以主动断开方是服务端？

答案是是的。在HTTP1.1协议中，有个 Connection 头，Connection有两个值，close和keep-alive，这个头就相当于客户端告诉服务端，服务端你执行完成请求之后，是关闭连接还是保持连接，保持连接就意味着在保持连接期间，只能由客户端主动断开连接。还有一个keep-alive的头，设置的值就代表了服务端保持连接保持多久。

HTTP默认的Connection值为close，那么就意味着关闭请求的一方几乎都会是由服务端这边发起的。那么这个服务端产生TIME_WAIT过多的情况就很正常了。

虽然HTTP默认Connection值为close，但是现在的浏览器发送请求的时候一般都会设置Connection为keep-alive了。所以，也有人说，现在没有必要通过调整参数来使TIME_WAIT降低了。

# 解决方法

按照HTTP协议的头，我们在压测程序发出的HTTP协议头里面加上connection:keep-alive当然能解决这个问题。

还有的方法就是[系统参数调优](https://www.kernel.org/doc/Documentation/networking/ip-sysctl.txt):
```
sysctl net.ipv4.tcp_tw_reuse=1

sysctl net.ipv4.tcp_tw_recycle=1
sysctl net.ipv4.tcp_timestamps=1
```

## tcp_tw_reuse

这个参数作用是当新的连接进来的时候，可以复用处于TIME_WAIT的socket。默认值是0。

## tcp_tw_recycle和tcp_timestamps

默认TIME_WAIT的超时时间是2倍的MSL，但是MSL一般会设置的非常长。如果tcp_timestamps是关闭的，开启tcp_tw_recycle是没用的。但是一般情况下tcp_timestamps是默认开启的，所以直接开启就有用了。

# 参考文章

[记一次TIME_WAIT网络故障](http://huoding.com/2012/01/19/142)
[再叙TIME_WAIT](http://huoding.com/2013/12/31/316)
[Time-wait状态(2MSL)一些理解](http://blog.csdn.net/overstack/article/details/8833894)
[tcp_tw_recycle和tcp_timestamps导致connect失败问题](http://blog.sina.com.cn/s/blog_781b0c850100znjd.html)
