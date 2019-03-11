# go get golang.org被墙问题解决

今天在下载golang.org/x/image/tiff的时候出错
```
> go get -v golang.org/x/image/tiff
Fetching https://golang.org/x/image/tiff?go-get=1
https fetch failed: Get https://golang.org/x/image/tiff?go-get=1: dial tcp 216.239.37.1:443: i/o timeout
package golang.org/x/image/tiff: unrecognized import path "golang.org/x/image/tiff" (https fetch: Get https://golang.org/x/image/tiff?go-get=1: dial tcp 216.239.37.1:443: i/o timeout)
```

网上看了一下，大致是由于墙的问题。

网上的方式大致有两种，一种是使用golang.org在github上的镜像，比如https://github.com/golang/image

在gopath目录下手动创建src/golang.org/x/, 然后直接使用`git clone https://github.com/golang/image` 到 src/golang.org/x/image目录

但是由于我最终希望下的是qor项目，它依赖非常多的golang.org/x/的项目，一个个这么做也是够累人的。

于是选择了第二个方法，设置proxy。

# 设置session的http_proxy

翻墙软件搭建在自己机器上， 使用端口61745, 先设置当前session使用的http_proxy

```
export http_proxy="http://127.0.0.1:61745" export https_proxy=$http_proxy
```

设置完成之后，但是还是出现问题：
```
go get -v golang.org/x/image/tiff
Fetching https://golang.org/x/image/tiff?go-get=1
Parsing meta tags from https://golang.org/x/image/tiff?go-get=1 (status code 200)
get "golang.org/x/image/tiff": found meta tag get.metaImport{Prefix:"golang.org/x/image", VCS:"git", RepoRoot:"https://go.googlesource.com/image"} at https://golang.org/x/image/tiff?go-get=1
get "golang.org/x/image/tiff": verifying non-authoritative meta tag
Fetching https://golang.org/x/image?go-get=1
Parsing meta tags from https://golang.org/x/image?go-get=1 (status code 200)
golang.org/x/image (download)
# cd .; git clone https://go.googlesource.com/image /Users/yejianfeng/Documents/gopath/src/golang.org/x/image
Cloning into '/Users/yejianfeng/Documents/gopath/src/golang.org/x/image'...
fatal: unable to access 'https://go.googlesource.com/image/': Failed to connect to go.googlesource.com port 443: Operation timed out
package golang.org/x/image/tiff: exit status 128
```

# 设置git的http_proxy

大致再搜了一下，是git的http_proxy没有设置好。

于是做了一下设置：
```
git config --global http.proxy http://127.0.0.1:61745
```

终于可以了
```
go get -v golang.org/x/image/tiff
Fetching https://golang.org/x/image/tiff?go-get=1
Parsing meta tags from https://golang.org/x/image/tiff?go-get=1 (status code 200)
get "golang.org/x/image/tiff": found meta tag get.metaImport{Prefix:"golang.org/x/image", VCS:"git", RepoRoot:"https://go.googlesource.com/image"} at https://golang.org/x/image/tiff?go-get=1
get "golang.org/x/image/tiff": verifying non-authoritative meta tag
Fetching https://golang.org/x/image?go-get=1
Parsing meta tags from https://golang.org/x/image?go-get=1 (status code 200)
golang.org/x/image (download)
golang.org/x/image/tiff/lzw
golang.org/x/image/tiff
```

也可以很方便使用 go get -u 来下载项目的依赖了。


# 取消

session的http_proxy是关闭session之后会取消的。

但是git的http.proxy这里是设置的全局的，我们下载完成之后，希望进行取消的话，执行操作：
```
git config --global --unset http.proxy
```

# 后记

据说现在有

# 参考文档
[git 设置和取消代理](https://gist.github.com/laispace/666dd7b27e9116faece6)
