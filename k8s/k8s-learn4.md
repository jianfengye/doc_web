# k8s学习 - 概念 - ReplicationController

我们有了 pod，那么就需要对 pod 进行控制，就是同一个服务的 podv我需要启动几个？如果需要扩容了，怎么办？这里就有个控制器，ReplicationController（简称rc）。

不过我们看官网：

![rc-note](images/2019/07/rc-note.png)

这里告诉我们，ReplicationController 现在已经过时了，现在建议使用 Deployment 配合ReplicaSet。ReplicationController的主要功能是保证Pod的数量、健康，弹性收缩等。但是Deployment除了有这些功能之外，还增加了回滚功能（当升级 pod 镜像或者相关参数的时候，如果有错误，可以回滚到上一个稳定版本），版本记录（每一次对 Deployment 的操作都能保存下来）。暂停和启动（升级的时候，能随时暂停和启动）。

估计不久的将来，ReplicationController 就不会有人用了。不过我们还是基本了解下 ReplicationController 的一些配置。

下面是官方的一份ReplicationController的配置文件：
```
apiVersion: v1
kind: ReplicationController
metadata:
  name: nginx
spec:
  replicas: 3
  selector:
    app: nginx
  template:
    metadata:
      name: nginx
      labels:
        app: nginx
    spec:
      containers:
      - name: nginx
        image: nginx
        ports:
        - containerPort: 80
```

其中spec.template是spec中必须填写的，它就是一个pod的配置。pod的配置全集在上一篇我们看到了。

其中.spec.replicas表示这个pod需要维持几份。如果没有配置的话，它就是为1。比如上面那个例子，就保持3份nginx服务。

## 标签选择器

其中的selector我们这里可以好好研究下，这个是我们第一次见到。

标签选择器在很多概念都是会使用到的，比如pod在哪个node上，ReplicationController作用在哪个pod上，service作用在哪个pod上，等等。tag标注的系统化也是k8s应用集群必要的设计之一。

标签选择器理解起来倒是很简单，就是一堆的key:value。比如我可以给pod设置3个label:

```
metadata:
  labels:
    key1: value1,
    key2: value2,
    key3: value3
```
 key1=value1, key2=value2, key3=value3。

然后在ReplicationController的selector里面，有两种写法，一种是简单写法，一种高级写法。（好像网上没有这种说法，但是我理解就是这样的）

简单写法：

```
selector:
  key1: value1
```

代表这个ReplicationController选择labels有key1标签，且标签值为value1的pod进行控制。

高级写法：（这个高级写法里面的matchExpressions其实ReplicationController是不支持的，ReplicaSet才开始支持。不知道后续会不会支持个正则匹配）
```
selector:
  matchLabels:
    key1: value1
  matchExpressions:
    - {key: key2, operator: In, values: [value2, value4]}
```

代表这个ReplicationController选择labels有标签和标签值，key1:value1，且key2在value2和value4集合中的pod进行控制。

我们可以在查看资源的时候带上`--show-labels`来获取labels，比如：

```
kubectl get pod --show-labels
NAME                            READY     STATUS    RESTARTS   AGE       LABELS
busybox                         1/1       Running   26         3d        <none>
busybox1                        1/1       Running   26         3d        name=busybox
busybox2                        1/1       Running   26         3d        name=busybox
frontend-5c548f4769-l9cts       1/1       Running   0          1h        app=guestbook,pod-template-hash=1710490325,tier=frontend
frontend-5c548f4769-nnp2b       1/1       Running   0          1h        app=guestbook,pod-template-hash=1710490325,tier=frontend
frontend-5c548f4769-zjwwm       1/1       Running   0          1h        app=guestbook,pod-template-hash=1710490325,tier=frontend
redis-master-55db5f7567-929np   1/1       Running   0          1h        app=redis,pod-template-hash=1186193123,role=master,tier=backend
redis-slave-584c66c5b5-dsbcc    1/1       Running   0          1h        app=redis,pod-template-hash=1407227161,role=slave,tier=backend
redis-slave-584c66c5b5-kfhnq    1/1       Running   0          1h        app=redis,pod-template-hash=1407227161,role=slave,tier=backend
task-pv-pod                     1/1       Running   0          1d        <none>
```

虽然官网有推荐了一些labels

```
"release" : "stable", "release" : "canary"
"environment" : "dev", "environment" : "qa", "environment" : "production"
"tier" : "frontend", "tier" : "backend", "tier" : "cache"
"partition" : "customerA", "partition" : "customerB"
"track" : "daily", "track" : "weekly"
```

但是我感觉大家写集群的时候也并没有按照这些建议的labels。基本上一个集群有自己的一套设计。

# 总结

最后在总结下，ReplicationController这个已经是被淘汰的了，连k8s官网的demo已经都切换到deployment+replicaset了，所以遇到有用ReplicationController的书和文章，可以弃读了。

-- 当前日期：2019年7月9日
