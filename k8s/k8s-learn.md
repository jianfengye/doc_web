# k8s学习 - API

之前对k8s并没有很深入的了解，最近想把手头一个项目全部放到k8s上，以方便部署，需要研究。这里记录一下自己研究过程中头脑中的理解。

# k8s 和 docker

首先，需要先理解下docker。镜像把你要的应用和环境打包在一个容器里面，有了容器之后，部署，扩容等操作就很方便了。但是，随着微服务化，服务一多，容器就多了，多了之后，就需要对容器进行管理。需要有一套很完善的管理系统。那么 k8s 就出现了。

k8s 全称就是kubernets，和 i10n 的名字类似，中间的数字就是英文单词的数字。它的官网是[k8s官网](https://kubernetes.io)。

在 k8s 机器上同时应该安装docker-server，因为k8s的基础是docker镜像，这些镜像需要通过docker-server来从远端获取和实例化。

k8s 就是一个分布式系统，你可以在一台机器上安装k8s集群，也可以在多台机器上安装k8s 集群。它是master-node形式的有一个k8s节点充当master，其他k8s节点充当node。这整个分布式系统，就相当于一个服务集群。我们可以在这个集群上启动多个服务，每个服务都有自己的虚拟IP，虚拟端口，各个服务可以通过这些IP和端口进行交互，最终由一个出口IP和出口端口对外提供服务。

# k8s API

k8s是分布式系统，它本身有各个组件，各个组件之间的通信，对外提供的都是rest接口的http服务。这些接口就统称为 k8s API。k8s的api也很有特点，首先它是分组的，它有很多api组。这些api组都有不同的功能，有的api组负责权限，有的api组负责存储。

每个api组还有版本的区分，它其实也有大小版本区分，但是不是我们常用的1.1.1这种版本号，k8s api 大的版本都是以v1, v2 这种为迭代的，每个大的版本里面区别三个等级，一种是Alpha等级，这个等级就是还在调试的，基本我们不作为开发者的话，这种等级的接口版本不会接触到。它会在大版本后面直接跟着alpha，比如v1alpha2, 就代表是v1大版本的alpha等级第2小版本。第二个等级就是Beta等级，这个等级说明接口基本可以使用了，也经过完整测试了。会比正常的稳定版本有更多的功能。它的版本格式如v1beta2。第三个等级就是Stable版本，这个等级说明这个是个稳定版，可以放心使用。

所以，我们通过 `kubectl api-versions` 可以看到很多api组和版本号：
```
admissionregistration.k8s.io/v1beta1
apiextensions.k8s.io/v1beta1
apiregistration.k8s.io/v1
apiregistration.k8s.io/v1beta1
apps/v1
apps/v1beta1
apps/v1beta2
authentication.k8s.io/v1
authentication.k8s.io/v1beta1
authorization.k8s.io/v1
authorization.k8s.io/v1beta1
autoscaling/v1
autoscaling/v2beta1
batch/v1
batch/v1beta1
certificates.k8s.io/v1beta1
compose.docker.com/v1beta1
compose.docker.com/v1beta2
events.k8s.io/v1beta1
extensions/v1beta1
networking.k8s.io/v1
policy/v1beta1
rbac.authorization.k8s.io/v1
rbac.authorization.k8s.io/v1beta1
storage.k8s.io/v1
storage.k8s.io/v1beta1
v1
```

比如 `authorization.k8s.io/v1beta1` 就代表authorization.k8s.io这个api组的v1大版本下的beta等级1小版本。

我们后续在yaml中写的apiVerison就是这个版本意思。

那这些版本的api怎么调用呢？官方使用swagger对接口进行管理和说明，首先我们可以启动`kubectl proxy` 来启动k8s server的http代理。默认打开地址是`http://127.0.0.1:8001/`。使用`http://127.0.0.1:8001/swagger.json` 就可以看到k8s的全部api说明了。如果你有swagger-editor的话，你还可以把这个json放到editor里面，就能看到所有的接口了。（接口特别多，加载比较慢）![k8s-swagger](images/2019/07/k8s-swagger.png)。

现在有了k8s接口，我们完全可以自己写一个客户端来调用。[客户端库](https://k8smeetup.github.io/docs/reference/client-libraries/)。但是我们最常用的客户端命令是kubectl。
