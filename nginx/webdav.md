# webdav 概览

WebDav(Web Distributed Authoring and Versioning) 是一个控制远端Web资源的协议，它基于HTTP1.1。它的定义在RFC 4918(https://tools.ietf.org/html/rfc4918)。这个协议的场景可以是分布式协同办公，也可以是一个文件存储服务器。WebDav的语义是基于XML的。微软的sharepoint，Dropbox, iCloud，offic365等都使用了这个协议。

# webdav支持哪些方法？

首先必然支持普通的HTTP1.1的一些方法：

* GET
* POST
* PUT
* DELETE
* PROPFIND
* PROPPATCH
* MKCOL
* COPY
* MOVE
* LOCK
* UNLOCK


## PROPFIND 和 GET

PROPFIND 是查找资源的信息，并不包括资源的内容。而 GET 方法是直接返回资源的具体内容。在PROPFIND中，你可以指定返回资源的哪些属性信息，也可以返回资源的所有属性信息（allprop）。

## PROPPATCH

对资源的某个或者某些属性进行操作，这个操作可以是增加，删除，修改等。

## MKCOL

就是创建Collection，Collection的意思就是文件夹，它对应一个URI路径。这个命令创建的Collection如果事先存在，或者前置的路径不存在，都会报错。

## POST

POST方法的实际作用在Webdav的协议中没有做强制定义，一般都是由具体的应用场景的Server进行定义的。

## DELETE

DELETE 就是删除操作，它可以删除资源，也可以删除一个Collection。但是删除资源的时候，要求被删除的资源不能有锁。
如果是删除一个Collection，要求这个Collection。

## LOCK 和 UNLOCK

对一个已经存在的资源加锁或者解锁操作。

## COPY

COPY复制一个文件到目标文件夹，目标文件夹必须存在

## PUT

PUT是用来更新服务器上的一个文件的，它不能作用于文件夹。

# 搭建nginx服务支持webdav

使用nginx就能很简单搭建一个支持webdav的文件服务，但是这个webdav只支持几个方法：PUT，DELETE，MKCOL，COPY，MOVE。基于这几个操作，你可以很方便操作这个文件服务器。但是需要使用PROPFIND等命令，你就需要加上[nginx-dav-ext-module](https://github.com/arut/nginx-dav-ext-module)这个模块，只有支持了PROFIND命令，mac的finder才能打开webdav服务文件。

nginx-dav-ext-module里面还有一个OPTIONS方法，这个是HTTP1.1的方法，服务端返回它支持的方法。

## nginx 里面的PUT

nginx原生的代码里面有实现PUT命令
