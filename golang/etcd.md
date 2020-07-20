# etcd学习笔记

# etcd是什么？

官网对etcd的定义：
“A distributed, reliable key-value store for the most critical data of a distributed system”

etcd不仅仅是一个键值对存储引擎。它的目标更是为了解决分布式系统的关键数据。

键值对很好理解，但是它为解决分布式系统的关键数据存储提供了哪些东西则是这个系统最重要的一个东西了。

etcd的官网：https://etcd.io/

# etcd的安装

etcd的安装使用docker安装是最简单的了： https://hub.docker.com/r/elcolio/etcd

进入docker，我们可以直接使用etcdctl进行本地etcd的键值存储操作

![20200720103823](http://tuchuang.funaio.cn/md/20200720103823.png)

