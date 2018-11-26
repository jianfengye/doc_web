# 从零开始搭建运维体系 - 开始篇

如果给你一批机器，并且这一批机器在和外部不通的局域网内部，让你从零开始搭建一套运维体系，应该怎么做呢？有哪些开源工具和项目可以使用呢？这个系列的文章就想带大家一起讨论这个问题。

# 重现场景

首先我们需要使用vagrant搭建3台centos7的机器，这三台机器使用的配置大致如下：

```
config.vm.network "private_network", ip: "192.168.34.2"
```

对应的ip为 192.168.34.2 ～ 192.168.34.4  (我们应该避免使用34.1的ip)

vagrant的private_network其实是搭建了两个网络：NAT 和 Host-Only，所以它可以访问外网，也可以访问内网。

# yum源怎么办

如果你的局域网可以访问外网，那么就很简单，直接安装yum源就行了。如果你的局域网不能访问外网，那么如何进行软件安装呢？

具体可以参考这篇文章[如何在外网使用yum下载好rpm包并在局域网使用](https://www.cnblogs.com/wangbaobao/p/6674272.html) 本质就是在一台可以访问外部网络的机器上下载好rpm源(包括依赖)，然后在局域网内部安装。
```
yum install --downloadonly --downloaddir=/home/java java
```

当然，[在局域网内安装yum源](https://www.tecmint.com/setup-local-http-yum-repository-on-centos-7/)可能是更好的方法。
安装yum源基本分为几个步骤：
* 搭建web服务器
* 下载或者同步rpm包
* 使用createrepo创建comps.xml文件
* 在客户端修改本地repos文件
