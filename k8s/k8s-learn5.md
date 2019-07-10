# k8s学习 - 概念 - ReplicaSet

首先，ReplicaSet 和 ReplicationController 基本上一样，除了上篇说到的selector有不同之外，没有啥区别。（官网也是这么说的）。但是为什么官方建议的不是ReplicaController + Deployment的集合呢？咋们也不敢说，咋们也不敢问。反正我就知道，用 ReplicationController 的值得被鄙视，用ReplicationSet +deployment 的现在是正统。

# ReplicaSet

它和ReplicationController一样用来控制pod的。ReplicationSet + deployment中，ReplicationSet都是由deployment自动生成的，我们不需要再写一个replicaset.yaml。因为我们看中的是deployment的回滚、版本记录等功能，deployment依赖的ReplicationSet自动生成会好过我们手动生成ReplicationSet。否则我们手动配置的哪个地方和deployment要求的不一样，就很难查了。

下面是我用deployment自动生成的一个ReplicationSet的yaml配置文件：

```
kubectl get rs frontend-5c548f4769 -o=yaml
apiVersion: extensions/v1beta1
kind: ReplicaSet
metadata:
  annotations:
    deployment.kubernetes.io/desired-replicas: "3"
    deployment.kubernetes.io/max-replicas: "4"
    deployment.kubernetes.io/revision: "1"
  creationTimestamp: 2019-07-09T06:28:38Z
  generation: 1
  labels:
    app: guestbook
    pod-template-hash: "1710490325"
    tier: frontend
  name: frontend-5c548f4769
  namespace: default
  ownerReferences:
  - apiVersion: extensions/v1beta1
    blockOwnerDeletion: true
    controller: true
    kind: Deployment
    name: frontend
    uid: c970981e-a212-11e9-89ff-025000000001
  resourceVersion: "1471299"
  selfLink: /apis/extensions/v1beta1/namespaces/default/replicasets/frontend-5c548f4769
  uid: c972e269-a212-11e9-89ff-025000000001
spec:
  replicas: 3
  selector:
    matchLabels:
      app: guestbook
      pod-template-hash: "1710490325"
      tier: frontend
  template:
    metadata:
      creationTimestamp: null
      labels:
        app: guestbook
        pod-template-hash: "1710490325"
        tier: frontend
    spec:
      containers:
      - env:
        - name: GET_HOSTS_FROM
          value: dns
        image: gcr.io/google-samples/gb-frontend:v4
        imagePullPolicy: IfNotPresent
        name: php-redis
        ports:
        - containerPort: 80
          protocol: TCP
        resources:
          requests:
            cpu: 100m
            memory: 100Mi
        terminationMessagePath: /dev/termination-log
        terminationMessagePolicy: File
      dnsPolicy: ClusterFirst
      restartPolicy: Always
      schedulerName: default-scheduler
      securityContext: {}
      terminationGracePeriodSeconds: 30
status:
  availableReplicas: 3
  fullyLabeledReplicas: 3
  observedGeneration: 1
  readyReplicas: 3
  replicas: 3
```

spec.template就完全是一个pod的配置，前面章节已经说过了。所以整个配置文件缩略下来就是下面这个样子：

```
kubectl get rs frontend-5c548f4769 -o=yaml
apiVersion: extensions/v1beta1
kind: ReplicaSet
metadata:
  ...
  generation: 1
  ...
  ownerReferences:
  - apiVersion: extensions/v1beta1
    blockOwnerDeletion: true
    controller: true
    kind: Deployment
    name: frontend
    uid: c970981e-a212-11e9-89ff-025000000001
  resourceVersion: "1471299"
  selfLink: /apis/extensions/v1beta1/namespaces/default/replicasets/frontend-5c548f4769
  uid: c972e269-a212-11e9-89ff-025000000001
spec:
  replicas: 3
  selector:
    matchLabels:
      app: guestbook
      pod-template-hash: "1710490325"
      tier: frontend
  template:
    ...
status:
  availableReplicas: 3
  fullyLabeledReplicas: 3
  observedGeneration: 1
  readyReplicas: 3
  replicas: 3
```

我们就一个个研究这些没有见过的配置：

## metadata.generation & status.observedGeneration

这两个是对应的，metadata.generation 就是这个 ReplicationSet 的元配置数据被修改了多少次。这里就有个版本迭代的概念。每次我们使用 kuberctl edit 来修改 ReplicationSet 的配置文件，或者更新镜像，这个generation都会增长1，表示增加了一个版本。

这个版本迭代是配置文件只要有改动就进行版本迭代。observedGeneration就是最近观察到的可用的版本迭代。这两个只有在镜像升级的时候有可能不同，当我们使用 `kubectl rollout status ` 来探测一个deployment的状态的时候，就是检查observedGeneration是否大于等于generation。

## metadata.ownerReferences

这个字段就需要说到 owner 对象的概念了。文章中这个 ReplicaSet 是通过 Deployment 自动生成的，所以这个 ReplicaSet 是属于某个 Deployment的，那么那个 Deployment 就叫做它的 Owner，当前这个 ReplicaSet 就叫做那个 Deployment 的 Dependent。这个字段就是标注这个 ReplicaSet 的 Owner 信息。

blockOwnerDeletion 这个字段表示在删除 Owner 对象的时候，是否要先删除当前这个 Dependent。删除一个 Owner 对象有两种模式，一种是后台模式，就是先把 Owner 对象删除，再在后台删除它的 Dependent。另外一种是前台模式，先把所有的 Dependent 删除（标记删除），然后再删除 Owner 对象。这里的 blockOwnerDeletion 就是表示当它的 Owner 的删除策略是前台删除的时候，是否需要考虑先删除它。

controller 表示这个对象是否有管理它的控制器。

name, uid, kind 都是指向 Owner 对象的唯一标识。

## metadata.resourceVersion

每个资源在底层数据库都有版本的概念，我们可以使用 watch 来看某个资源，某个版本之后的操作。这些操作是存储在 etcd 中的。当让，并不是所有的操作都会永久存储，只会保留有限的时间的操作。这个 resourceVersion 就是这个资源对象当前的版本号。

## status

replicas 实际的 pod 副本数
availableReplicas 现在可用的 Pod 的副本数量，有的副本可能还处在未准备好，或者初始化状态
readyReplicas 是处于 ready 状态的 Pod 的副本数量
fullyLabeledReplicas 意思是这个 ReplicaSet 的标签 selector 对应的副本数量，不同纬度的一种统计
