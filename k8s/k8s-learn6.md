# k8s学习 - 概念 - Deployment

有了 ReplicaSet 还需要有 Deployment 的原因是希望有一个控制器能管理部署更新时候的版本控制问题。一个 Deployment 可以管理多个 ReplicaSet, 一个 ReplicaSet 可以管理多个 Pod。最通用的场景是当我们对某个 Pod 里面的镜像进行升级的时候，我们非常迫切需要有一个版本号概念，并且在发现问题的时候可以随时回滚。那么这个就是 Deployment 的使命。

# 使用

官方和很多文章都是使用 nginx 来做示例的。我们也不能免俗。我们来看一下最简易的配置文件：

```
apiVersion: extensions/v1beta1
kind: Deployment
metadata:
  name: nginx-deployment
spec:
  replicas: 3
  template:
    metadata:
      labels:
        app: nginx
    spec:
      containers:
      - name: nginx
        image: nginx:1.11.5
        ports:
        - containerPort: 80
```

很简单吧，template 开始以下是 Pod 的内容，里面有个 name=nginx 的容器，使用镜像 nginx:1.11.5。 sepc.replicas 说明了这个 Deployment 管理了多少个 Pod。

![deployment-rs-pod](images/2019/07/deployment-rs-pod.png)

如图，对应的 deployment:replicaset:pod = 1:1:3


下面我们要升级 pod 里面的 nginx container，使用镜像 nginx:1.10.1

`kubectl set image deployment nginx-deployment nginx=nginx:1.10.1 --record`

这里的 record 表示这次升级记录下命令，否则我们查看这次升级的版本，就是 NONE

```
kubectl rollout history deployment nginx-deployment
deployments "nginx-deployment"
REVISION  CHANGE-CAUSE
2         kubectl set image deployment nginx-deployment nginx=nginx:1.10.1 --record=true
```

如果我们发现1.10.1镜像是有问题，我们可以进行回滚

`kubectl rollout undo deployment nginx-deployment`

```
kubectl rollout history deployment nginx-deployment
deployments "nginx-deployment"
REVISION  CHANGE-CAUSE
2         kubectl set image deployment nginx-deployment nginx=nginx:1.10.1 --record=true
3         <none>
```

我们可以看到这里就有了3个版本，第三个版本就是我们回滚的版本，由于我们没有增加 --record，在CHANGE-CAUSE 就出现了NONE。

# yaml 配置全解析

上述就是 Deployment 的基本用法。惯例，我们还是有必要全部解析一遍 Deployment 的配置。

`kubectl get deployment nginx-deployment -o yaml`

```
apiVersion: extensions/v1beta1
kind: Deployment
metadata:
  annotations:
    deployment.kubernetes.io/revision: "3"
    kubectl.kubernetes.io/last-applied-configuration: |
      {"apiVersion":"extensions/v1beta1","kind":"Deployment","metadata":{"annotations":{},"name":"nginx-deployment","namespace":"default"},"spec":{"replicas":3,"template":{"metadata":{"labels":{"app":"nginx"}},"spec":{"containers":[{"image":"nginx:1.11.5","name":"nginx","ports":[{"containerPort":80}]}]}}}}
  creationTimestamp: 2019-07-15T00:52:16Z
  generation: 4
  labels:
    app: nginx
  name: nginx-deployment
  namespace: default
  resourceVersion: "1638554"
  selfLink: /apis/extensions/v1beta1/namespaces/default/deployments/nginx-deployment
  uid: cad03233-a69a-11e9-ac73-025000000001
spec:
  progressDeadlineSeconds: 600
  replicas: 3
  revisionHistoryLimit: 10
  selector:
    matchLabels:
      app: nginx
  strategy:
    rollingUpdate:
      maxSurge: 1
      maxUnavailable: 1
    type: RollingUpdate
  template:
    metadata:
      creationTimestamp: null
      labels:
        app: nginx
    spec:
      containers:
      - image: nginx:1.11.5
        imagePullPolicy: IfNotPresent
        name: nginx
        ports:
        - containerPort: 80
          protocol: TCP
        resources: {}
        terminationMessagePath: /dev/termination-log
        terminationMessagePolicy: File
      dnsPolicy: ClusterFirst
      restartPolicy: Always
      schedulerName: default-scheduler
      securityContext: {}
      terminationGracePeriodSeconds: 30
status:
  availableReplicas: 3
  conditions:
  - lastTransitionTime: 2019-07-15T00:52:18Z
    lastUpdateTime: 2019-07-15T00:52:18Z
    message: Deployment has minimum availability.
    reason: MinimumReplicasAvailable
    status: "True"
    type: Available
  - lastTransitionTime: 2019-07-15T00:52:16Z
    lastUpdateTime: 2019-07-15T01:01:26Z
    message: ReplicaSet "nginx-deployment-dd74f8bcd" has successfully progressed.
    reason: NewReplicaSetAvailable
    status: "True"
    type: Progressing
  observedGeneration: 4
  readyReplicas: 3
  replicas: 3
  updatedReplicas: 3
```

把 template (Pod 内容)，自身介绍性的字段去掉：

```
apiVersion: extensions/v1beta1
kind: Deployment
metadata:
  ...
spec:
  progressDeadlineSeconds: 600
  replicas: 3
  revisionHistoryLimit: 10
  selector:
    matchLabels:
      app: nginx
  strategy:
    rollingUpdate:
      maxSurge: 1
      maxUnavailable: 1
    type: RollingUpdate
  template:
    ...
status:
  ...
```

## spec.strategy

定义升级策略，Deployment 的升级有两种策略，一种是 RollingUpdate，滚动升级。顾名思义，就是一个一个 pod 进行升级，而不是同时停止整个服务。这个升级能保证整个升级过程中服务的可用性。另外一种就是 Recreate，先将旧 Pod 下线，再启动新 Pod。 默认是使用 RollingUpdate。

所以 sepc.strategy.rollingUpdate 就是滚动升级的一些详细策略：

* maxSurge: 在升级过程中，最多可以创建多少个 Pod。也就是说每次滚动的步长。该值不能为0。
* maxUnavailable: 在升级过程中，最多不可用的 pod 的数量。该值不能为0。

## spec.progressDeadlineSeconds

k8s 在升级过程中有可能由于各种原因升级卡住（这个时候还没有明确的升级失败），比如在拉取被墙的镜像，权限不够等错误。那么这个时候就需要有个 deadline ，在 deadline 之内如果还卡着，那么就上报这个情况，这个时候这个 Deployment 状态就被标记为 False，并且注明原因。但是它并不会阻止 Deployment 继续进行卡住后面的操作。完全由用户进行控制。

这个配置就是设置 deadline 的。单位为秒。

## spec.revisionHistoryLimit

我们做的回滚操作并不是没有代价的，代价就是旧版本的 ReplicaSet 会被保留，但是不会继续提供服务了。当我们执行回滚操作的时候，就直接使用旧版本的 ReplicaSet。

这个配置就是控制保留多少个版本的 ReplicaSet。

# 参考

https://tachingchen.com/tw/blog/kubernetes-rolling-update-with-deployment/
https://www.cnblogs.com/breezey/p/8810094.html
