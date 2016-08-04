# https问答

# SSL和TLS有什么区别？

可以说，TLS是SSL的升级版本，SSL是网景公司设计的，为了最早期的网络安全而生，它的全名叫做“安全套接层”。后来，IETF在1999年把SSL标准化，名称改名为TLS，“传输层安全协议”。所以说，这两个东西就是同一个东西的不同阶段。

具体可以参考[TLS与SSL的区别](http://seanlook.com/2015/01/07/tls-ssl/)

# 客户端和服务端交互

首先从粗力度来看，客户端先是通过“STL握手”过程，获取服务端的证书，并验证证书的合法性。在这个握手过程中，会产生三个随机串。后续的数据交互使用这三个随机串进行对称加密传输的数据。

STL具体的握手协议可以参考这篇(HTTPS协议说明)[http://www.cnblogs.com/yjf512/p/5216045.html]

好了，这个是一个session的交互，如果session结束了，另外开启一个session，客户端和服务端还是要重新进行握手协议，获取一遍服务端证书和随机串的，如果想要重新使用上次的随机串的话，需要服务端实现SSL session 重用（SSL session resumption）。

SSL的session重用有两种机制，一种是session id的方式，一种是session ticket的方式。

session id的方式简单来说，其实和session id通过cookie进行传递很像，这种方式的session id是在握手的第二个环节，服务端返回给客户端一个session。客户端保存住这个session id, 下次session开启的时候，客户端在握手的第一个请求的时候，直接把session id传递给服务端，服务端直接返回连接建立成功。

session id的方式是需要服务端根据session id存储session信息。而session ticket方式则是使用把TLS交互过程中使用的数据加密成为“Session ticket”, 客户端存储这个session ticket, 在下次session开启的时候直接传递这个ticket给服务端，从而来绕开完整的握手协议。

# 客户端如何验证证书的合法性

客户端的证书是从服务端获取的，客户端要验证证书的：

1 证书是否在有效期内

这个只需要查看证书里面的起始日期和结束日期，看当前的时间点是否在有效期内就行了。

2 证书是否被吊销了

这个验证有两种方式：
a CRL: 定期从CA上获取证书吊销列表。
b OCSP: 直接发起请求去CA上对某个证书进行查询。

3 正式是否是上级CA签发的

一致追述到根证书，就是CA的证书，CA是自己颁发给自己，一半浏览器会保留下CA的证书，所以这个过程一般不需要去CA服务器上进行验证。

# 参考文章
[浏览器如何验证HTTPS证书的合法性？](https://www.zhihu.com/question/37370216)
[SSL session resumption原理](http://nil-zhang.iteye.com/blog/1279199)
[Speeding up SSL: enabling session reuse](https://vincent.bernat.im/en/blog/2011-ssl-session-reuse-rfc5077.html)
