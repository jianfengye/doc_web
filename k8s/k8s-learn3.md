# k8s学习 - 概念 - Pod

这篇继续看概念，主要是 Pod 这个概念，这个概念非常重要，是 k8s 集群的最小单位。

怎么才算是理解好 pod 了呢，基本上把 pod 的所有 describe 和配置文件的配置项都能看懂就算是对 pod 比较了解了。

# Pod

我们通过调用一个`kubectl describe pod xxx` 可以查看某个 pod 的具体信息。

describe 的信息我们用注释的形式来解读。

```
Name:         task-pv-pod
Namespace:    default // 没有指定namespace的就是default
Node:         docker-for-desktop/192.168.65.3 // Pod所在的节点
Start Time:   Mon, 08 Jul 2019 14:05:52 +0800 // pod启动的时间
Labels:       <none> // 说明没有设置标签
Annotations:  kubectl.kubernetes.io/last-applied-configuration={"apiVersion":"v1","kind":"Pod","metadata":{"annotations":{},"name":"task-pv-pod","namespace":"default"},"spec":{"containers":[{"image":"nginx","name":... // 注释信息
Status:       Running // pod的状态
IP:           10.1.0.103 // pod的集群ip
Containers: // 其中包含的容器
  task-pv-container:
    Container ID:   docker://3e9a2ee6b0a13ccee534ec3ffe781adcbff42a7f1851d57e3b374a047a654590
    Image:          nginx // 容器镜像名称
    Image ID:       docker-pullable://nginx@sha256:96fb261b66270b900ea5a2c17a26abbfabe95506e73c3a3c65869a6dbe83223a
    Port:           80/TCP
    Host Port:      0/TCP
    State:          Running
      Started:      Mon, 08 Jul 2019 14:05:58 +0800
    Ready:          True
    Restart Count:  0
    Environment:    <none>
    Mounts: // 这个容器挂载的两个volume
      /usr/share/nginx/html from task-pv-storage (rw)
      /var/run/secrets/kubernetes.io/serviceaccount from default-token-tw8wk (ro)
Conditions:
  Type           Status
  Initialized    True
  Ready          True
  PodScheduled   True
Volumes:
  task-pv-storage: // 挂载的数据卷
    Type:       PersistentVolumeClaim (a reference to a PersistentVolumeClaim in the same namespace) // 这个数据卷是共享持久卷
    ClaimName:  task-pv-claim // 使用的声明
    ReadOnly:   false // 数据卷是否只读
  default-token-tw8wk:
    Type:        Secret (a volume populated by a Secret) // 这个数据卷是保存密钥
    SecretName:  default-token-tw8wk
    Optional:    false
QoS Class:       BestEffort // Qos的三个级别，Guaranteed/Burstable/BestEffort，分别对pod的资源限制从严到弱
Node-Selectors:  <none> // pod是可以选择部署在哪个node上的，比如部署在有ssd的node上。
Tolerations:     node.kubernetes.io/not-ready:NoExecute for 300s  // 节点亲和性，它使得pod能有倾向性地分配到不同节点上。
                 node.kubernetes.io/unreachable:NoExecute for 300s
Events: // 这个pod发生的一些事件
  Type    Reason                 Age   From                         Message
  ----    ------                 ----  ----                         -------
  Normal  Scheduled              21s   default-scheduler            Successfully assigned task-pv-pod to docker-for-desktop
  Normal  SuccessfulMountVolume  20s   kubelet, docker-for-desktop  MountVolume.SetUp succeeded for volume "task-pv-volume"
  Normal  SuccessfulMountVolume  20s   kubelet, docker-for-desktop  MountVolume.SetUp succeeded for volume "default-token-tw8wk"
  Normal  Pulling                19s   kubelet, docker-for-desktop  pulling image "nginx"
  Normal  Pulled                 15s   kubelet, docker-for-desktop  Successfully pulled image "nginx"
  Normal  Created                15s   kubelet, docker-for-desktop  Created container
  Normal  Started                14s   kubelet, docker-for-desktop  Started container
```

下面我们就看 pod 的配置文件。有的时候我们可能会忘记了我们启动的pod的yaml配置文件地址，我们可以通过`kubectl get pod task-pv-pod -o=yaml`命令来获取某个已经启动的 pod 的配置文件，这里的配置文件会比我们配置的配置项全很多，因为我们写配置文件的时候，很多配置项没有设置实际上就是使用默认的配置值来实现。


```
kubectl get pod task-pv-pod -o=yaml
apiVersion: v1
kind: Pod
metadata:
  annotations:
    kubectl.kubernetes.io/last-applied-configuration: |
      {"apiVersion":"v1","kind":"Pod","metadata":{"annotations":{},"name":"task-pv-pod","namespace":"default"},"spec":{"containers":[{"image":"nginx","name":"task-pv-container","ports":[{"containerPort":80,"name":"http-server"}],"volumeMounts":[{"mountPath":"/usr/share/nginx/html","name":"task-pv-storage"}]}],"volumes":[{"name":"task-pv-storage","persistentVolumeClaim":{"claimName":"task-pv-claim"}}]}}
  creationTimestamp: 2019-07-08T06:05:51Z
  name: task-pv-pod
  namespace: default
  resourceVersion: "1439249"
  selfLink: /api/v1/namespaces/default/pods/task-pv-pod
  uid: 7090642e-a146-11e9-89ff-025000000001
spec:
  containers:
  - image: nginx
    imagePullPolicy: Always
    name: task-pv-container
    ports:
    - containerPort: 80
      name: http-server
      protocol: TCP
    resources: {}
    terminationMessagePath: /dev/termination-log
    terminationMessagePolicy: File
    volumeMounts:
    - mountPath: /usr/share/nginx/html
      name: task-pv-storage
    - mountPath: /var/run/secrets/kubernetes.io/serviceaccount
      name: default-token-tw8wk
      readOnly: true
  dnsPolicy: ClusterFirst
  nodeName: docker-for-desktop
  restartPolicy: Always
  schedulerName: default-scheduler
  securityContext: {}
  serviceAccount: default
  serviceAccountName: default
  terminationGracePeriodSeconds: 30
  tolerations:
  - effect: NoExecute
    key: node.kubernetes.io/not-ready
    operator: Exists
    tolerationSeconds: 300
  - effect: NoExecute
    key: node.kubernetes.io/unreachable
    operator: Exists
    tolerationSeconds: 300
  volumes:
  - name: task-pv-storage
    persistentVolumeClaim:
      claimName: task-pv-claim
  - name: default-token-tw8wk
    secret:
      defaultMode: 420
      secretName: default-token-tw8wk
status:
  conditions:
  - lastProbeTime: null
    lastTransitionTime: 2019-07-08T06:05:52Z
    status: "True"
    type: Initialized
  - lastProbeTime: null
    lastTransitionTime: 2019-07-08T06:05:58Z
    status: "True"
    type: Ready
  - lastProbeTime: null
    lastTransitionTime: 2019-07-08T06:05:51Z
    status: "True"
    type: PodScheduled
  containerStatuses:
  - containerID: docker://3e9a2ee6b0a13ccee534ec3ffe781adcbff42a7f1851d57e3b374a047a654590
    image: nginx:latest
    imageID: docker-pullable://nginx@sha256:96fb261b66270b900ea5a2c17a26abbfabe95506e73c3a3c65869a6dbe83223a
    lastState: {}
    name: task-pv-container
    ready: true
    restartCount: 0
    state:
      running:
        startedAt: 2019-07-08T06:05:58Z
  hostIP: 192.168.65.3
  phase: Running
  podIP: 10.1.0.103
  qosClass: BestEffort
  startTime: 2019-07-08T06:05:52Z
```

几个比较复杂的配置项我们单独伶出来理解。

## spec.container.imagePullPolicy
spec.container.imagePullPolicy: 这个是容器的镜像获取策略，有几种策略：
* IfNotPresent: 如果本地没有，就去远程 pull 镜像
* Always: 每次pod启动都去远程pull镜像
* Never: 只去本地获取镜像

在局域网本地化搭建镜像的时候，应该设置为 Never 。如果 imagePullPolicy 没有设置，如果设置了镜像的 tag，且 tag 不为 :lastest ，就是相当于使用 IfNotPresent。如果 imagePullPolicy 没有设置，且tag为 :lastest，就相当于是 Always.

## spec.container.terminationMessagePath
spec.container.terminationMessagePath: 容器的终止日志文件。

## spec.container.volumeMounts

spec.container.volumeMounts 其中一个 /usr/share/nginx/html根据 task-pv-storage 挂载到 task-pv-claim 这个共享存储中。这个pvc 是对应哪个共享存储呢？
我们可以查看 `kubectl get pvc`
```
NAME            STATUS    VOLUME           CAPACITY   ACCESS MODES   STORAGECLASS   AGE
task-pv-claim   Bound     task-pv-volume   1Gi        RWO            manual         5h
```
再通过 `kubectl get pv` 对应到 pv：
```
NAME             CAPACITY   ACCESS MODES   RECLAIM POLICY   STATUS    CLAIM                   STORAGECLASS   REASON    AGE
task-pv-volume   1Gi        RWO            Retain           Bound     default/task-pv-claim   manual                   5h
```
再查看这个 pv 的详细情况：`kubectl describe pv task-pv-volume`
```
Name:            task-pv-volume
Labels:          type=local
Annotations:     kubectl.kubernetes.io/last-applied-configuration={"apiVersion":"v1","kind":"PersistentVolume","metadata":{"annotations":{},"labels":{"type":"local"},"name":"task-pv-volume","namespace":""},"spec":{"ac...
                 pv.kubernetes.io/bound-by-controller=yes
Finalizers:      [kubernetes.io/pv-protection]
StorageClass:    manual
Status:          Bound
Claim:           default/task-pv-claim
Reclaim Policy:  Retain
Access Modes:    RWO
Capacity:        1Gi
Node Affinity:   <none>
Message:
Source:
    Type:          HostPath (bare host directory volume)
    Path:          /Users/yejianfeng/Documents/workspace/kubernets_example/data
    HostPathType:
Events:            <none>
```

看到这个pv对应的是宿主机 HostPath 中的 /Users/yejianfeng/Documents/workspace/kubernets_example/data 这个目录。

所以共享存储的映射关系是 pod -- volume -- pvc -- pv。

其实这里我们之所以说是共享存储，就是说这个存储应该是一个共享网盘，比如 cephFS，而不应该仅仅只是宿主机上的一个目录。宿主机上的目录只是为了调试方便而已。

说说另外一个 volumeMounts： /var/run/secrets/kubernetes.io/serviceaccount

这个serviceaccount里面存储的是什么呢？我们可以直接`kubectl exec -it task-pv-pod -- /bin/sh` 到 pod 里面查看
```
~  kubectl exec -it task-pv-pod -- /bin/sh
# cd /var/run/secrets/kubernetes.io/serviceaccount
# ls
ca.crt	namespace  token
```
里面存放的就是 token 。这个 token 是每个 namespace 一个，它表示的是在这个命名空间的用户权限。这个是给 Pod 里面的进程使用的，如果 pod 里面的进程需要调用这个命名空间里面的 K8s 的 api 或者其他服务，它就可以通过获取这个 token 来发起 http/https 的调用。这个 service account 里面存储的 token/ca.crt 就代表这个命名空间的管理员用户。

## dnsPolicy

这个是 pod 的 dns 策略，可以设置有如下值：
* Default : 和宿主机的DNS完全一致
* ClusterFirst: 把集群的DNS写入到Pod的DNS配置，但是如果设置了HostNetwork=true，就会强制设置为Default
* ClusterFirstWithHostNet: 把集群的DNS写入到Pod的DNS配置，不管是否设置HostNetwork
* None: 忽略所有的DNS配置，一般来说，设置了None之后会自己手动再设置dnsConfig

这里需要了解，k8s 中的服务发现机制，有几种方式，一种是通过 service ip，一个服务在集群中统一存在一个 clusterIP, 所有其他服务需要使用这个服务的时候，就通过调用这个 clusterIP 来进行访问。另外一种是通过 dns，通过给service 设置 subdomain 来给一个服务设置一个域名，而这个域名解析就需要全集群一致。而这里的 dnsPolicy 就是做这个用处的。


## restartPolicy

pod 的状态一共有五种，挂起/运行中/成功/失败/未知。

![pod-cycle](images/2019/07/pod-cycle.png)


前一节说node上有个进程 kubelet 会检测当前 node 上的 pod 是否存活，如果检测到容器的状态为失败，那么就会启动重启策略，这个重启策略就是这里 restartPolicy 设置的。

* always: 容器失效时，自动重启容器
* OnFailure: 容器终止运行，且退出码不为0时候重启
* Never： 不重启

这里提到一个退出码，如果这个pod是处于失败状态，那么通过 `kubectl describe pod` 也是能看到 pod 的退出状态的。这个退出码0为正常，非0是不正常。
![exitcode](images/2019/07/exitcode.png)

## schedulerName

上节说过，调度器是在 master 节点，控制 pod 应该在哪个 node 上启动等。这里就是指定调度器的名字。默认是 default。

## securityContext

定义容器 pod 或者 container 的权限和访问控制。比如可以控制 pod 使用某个用户来访问其内部的文件，是否开 selinux 等。

## serviceAccount

这个概念在 volumeMounts 说过了，存储的是 pod 要访问集群内其他服务的时候的 token。

## terminationGracePeriodSeconds

优雅重启，如果 k8s 要升级，或者由于某个原因要重启 pod，那么会先启动新的P od，然后发送 SIGTERM 信号，并且等待 terminationGracePeriodSeconds 个时长，再结束老的 pod。

## tolerations

前面也说过，这个是设置 pod 的亲缘性，让调度器把 pod 更好的分配到 node 上去。

## status.conditions

这里再提一下 pod 的生命周期，pod 在初始化，到 pending，到分配到 node 的所有过程，都有个记录，这里的 status.conditions 就是这个记录，记录各种状态变更的时间节点：

type字段是一个包含以下可能值的字符串：

* PodScheduled：Pod 已被安排到一个节点;
* Ready：Pod 能够提供请求，应该添加到所有匹配服务的负载均衡池中;
* Initialized：所有 init 容器 都已成功启动;
* Unschedulable：调度程序现在无法调度 Pod，例如由于缺少资源或其他限制;
* ContainersReady：Pod 中的所有容器都已准备就绪。

lastTransitionTime 字段提供 Pod 最后从一个状态转换到另一个状态的时间戳。

## qosClass

Qos的三个级别，Guaranteed/Burstable/BestEffort，分别对pod的资源限制从严到弱。
