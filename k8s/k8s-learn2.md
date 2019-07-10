# k8s学习 - 概念 - master/node

在k8s中，有各种各样的概念和术语。这些概念是必须要学习和掌握的。我们先罗列下所有概念，然后再一个个看具体实例。

大概说一下这些概念：

* Master: k8s的主控组件，对应的对象是node。
* Node: 是k8s集群的机器节点，相当于master-node。一个node就对应一个具体的物理机或者虚拟机。
* Container: 是一个镜像容器，一个container是一个镜像实例
* Pod: 是k8s集群的最小单元，一个pod可以包含一个或者多个container
* Service: 多个相同的pod组成一个服务，统一对外提供服务。
* Volume: 存储卷，pod对外暴露的共享目录，它可以挂载在宿主机上，这样就能让同node上多个pod共享一个目录。
* Replication Controller: 用于控制pod集群的控制器，可以制定各种规则来让它控制一个service中的多个pod的创建和消亡, 很多地方简称为rc。
* Namespace: 命名空间，用于将一个k8s集群隔离成不同的空间，pod, service, rc, volume 都可以在创建的时候指定其namespace。
* StatefulSet: 有状态集群，比如一个主从的mysql集群就是有状态集群，需要先启动主再启动从，这就是一种有状态的集群。
* Persistent Volume: 持久存储卷。之前说的volume是挂载在一个pod上的，多个pod(非同node)要共享一个网络存储，就需要使用持久存储卷，简称为pv。
* Persistent Volume Claim: 持久存储卷声明。他是为了声明pv而存在的，一个持久存储，先申请空间，再申明，才能给pod挂载volume，简称为pvc。
* Label: 标签。我们可以给大部分对象概念打上标签，然后可以通过selector进行集群内标签选择对象概念，并进行后续操作。
* Secret: 私密凭证。密码保存在pod中其实是不利于分发的。k8s支持我们创建secret对象，并将这个对象打到pod的volume中，pod中的服务就以文件访问的形式获取密钥。
* EndPoint: 用于记录 service 和 pod 访问地址的对应关系。只有 service 配置了 selector, endpoint controller 才会自动创建endpoint对象。

如果不理解没啥关系，看一遍有印象下，下面我们一个个琢磨琢磨。

# master

k8s的master节点上有三个进程，它们都是以docker的形式存在的。我们在k8s的master节点看`docker ps` 就可以看到这几个进程：

```
8824aad1ee95        e851a7aeb6e8                     "kube-apiserver --ad…"   3 days ago          Up 3 days                               k8s_kube-apiserver_kube-apiserver-docker-for-desktop_kube-system_f23c0965aad6df9f61b1c9c4bb953cf5_0
a9ce81ec9454        978cfa2028bf                     "kube-controller-man…"   3 days ago          Up 3 days                               k8s_kube-controller-manager_kube-controller-manager-docker-for-desktop_kube-system_1dc44822f21a9cbd68cc62b1a4684801_0
85da3f6e700f        d2c751d562c6                     "kube-scheduler --ad…"   3 days ago          Up 3 days                               k8s_kube-scheduler_kube-scheduler-docker-for-desktop_kube-system_b6155a27330304c86badfef38a6b483b_0
```

其中的 apiserver 是提供 k8s 的 rest api 服务的进程。当然它也包括了 restapi 的权限认证机制。 k8s 的 apiserver 提供了三种权限认证机制：

* https
* http + token
* http + base（username + password）

我们也可以通过使用`kubectl proxy` 在 master 上来创建一个代理，从而外部可以访问这个 k8s 集群。

kube-controller-manager 是用来管理所有的 controller 的。之前我们说的 Replication Controller 就是一种管控 Pod 副本的Controller, 其他相关的 Controller 还有：

* Replication Controller
* Node Controller: 实时获取Node的相关信息，实现管理和监控集群中的各个Node节点的相关控制功能
* ResourceQuota Controller: 确保指定的资源对象在任何时候都不会超量占用系统物理资源
* NameSpace Controller: 定时通过API Server读取这些Namespace信息
* ServiceAccount Controller: 监听Service变化，如果是一个LoadBalancer类型的Service，则确保外部的云平台上对该Service对应的LoadBalancer实例被相应地创建、删除及更新路由转发表
* Token Controller
* Service Controller
* EndPoint Controller : Service 和选择 Pod 的对应关系。

kube-scheduler 负责 Pod 调度，接收 Controller Manager 创建的新的Pod，为其选择一个合适的Node，并且在Node上创建Pod。

一个k8s集群只有一个master节点，所以 master 节点的高可用性是一个问题，一旦 master 节点挂了，整个集群也就挂了。这点真有点神奇。所以网上关于搭建高可用的k8s Master 节点的方案有很多：

https://jishu.io/kubernetes/kubernetes-master-ha/
https://blog.51cto.com/ylw6006/2164981
https://jimmysong.io/kubernetes-handbook/practice/master-ha.html

# Node

Node 是 k8s 的工作节点，Node 一般是一个虚拟机或者物理机，每个 node 上都运行三个服务：

* docker
* kubelet
* kube-proxy

docker 就是 docker server，它提供

kubelet 是一个管理系统，它管理本个node上的容器的生命周期。它主要功能就是定时从某个地方获取节点上pod/container的期望状态，并调用对应的容器平台接口，来达成这个状态。它可以设置 http 监控，命令行监控等方式。

kube-proxy 是管理 service 的访问入口，包括集群内 pod 到 service 的访问，以及集群外访问service。


# 可用性

其实k8s自身的可用性是比较弱的，如果master挂了，那么master上的三个服务也就挂了。node挂了，如果node上的pod是被 controller控制住的话，controller会在其他node上启动对应的pod。
