# 从零开始搭建运维体系 - open-falcon监控

我们首先缺少的是监控系统，一个完备的监控系统是很有必要的。小米开源的open-falcon是非常好用的监控系统。open-falcon是典型的agent-server模式，agent需要安装在所有机器上，server只需要安装在一台机器上，agent定时向server不断发送收集信息，server根据发送的信息，判断规则，报警等。它需要基于mysql和redis。

# 安装

安装基本按照[官方文档](https://github.com/open-falcon/falcon-plus)

首先安装mysql，建议照着这篇[文章](https://linode.com/docs/databases/mysql/how-to-install-mysql-on-centos-7/)一步步安装。
我们先不设置root的密码

然后安装redis，建议照着这篇[文章](http://sharadchhetri.com/2014/10/04/install-redis-server-centos-7-rhel-7/)

mysql和redis都安装完成了，需要安装golang环境，其实也可以使用yum install来安装。

切记设置好GOPATH

然后按照官方教程一步步安装，先安装falcon-plus，再安装dashboard,安装完成之后如下

![](http://tuchuang.funaio.cn/18-11-13/93911411.jpg)

在centos7上安装需要额外注意下端口是不是被限制了
```
firewall-cmd --zone=public --add-port=8081/tcp --permanent

systemctl restart firewalld.service
```
进入后最开始只能看到本机
![](http://tuchuang.funaio.cn/18-11-13/21279886.jpg)

# 如何做监控

监控分为三个层级，首先是系统监控，其次是
