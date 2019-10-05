# k8s学习 - 从零开始搭建单主 k8s 集群

我使用我机器上的三台 vagrant 机器进行搭建，分别计划他们的角色：

```
master: 192.168.34.2

node: 192.168.34.2
      192.168.34.3
      192.168.34.4
```

# 容器 runtime

安装 k8s 集群的第一步是需要安装容器运行（container runtime）。什么是 container runtime 呢。 k8s 并不是一定依赖 docker， docker 只是容器化的一种。随着Kubernetes的进一步发展，以及CNCF和OCI 在标准化方向的努力，市面上可供选择的容器运行时也不再只是Docker一家了。但是他们都符合容器标准，容器的标准由 OCI (Open Containers Initiative) 负责维护。

市面上的容器有哪些呢？ 我们看官网： https://kubernetes.io/docs/setup/production-environment/container-runtimes/#docker

* Docker
* CRI-O
* Containerd
* Other CRI runtimes: frakti

# 安装 Docker

容器，我们这里还是安装 docker, 使用版本 docker-ce，社区版，他是免费使用的，提供给中小企业使用的。另外还有一个 docker-ee，企业版本，是收费的。

我们这里把官网的安装步骤再复制下，主要说下我安装过程中遇到的问题，或许你有可能遇到，也有可能不会遇到。

```

# Install Docker CE
## Set up the repository
### Install required packages.
yum install yum-utils device-mapper-persistent-data lvm2

### Add Docker repository.
yum-config-manager \
  --add-repo \
  https://download.docker.com/linux/centos/docker-ce.repo

## Install Docker CE.
yum update && yum install docker-ce-18.06.2.ce

## Create /etc/docker directory.
mkdir /etc/docker

# Setup daemon.
cat > /etc/docker/daemon.json <<EOF
{
  "exec-opts": ["native.cgroupdriver=systemd"],
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "100m"
  },
  "storage-driver": "overlay2",
  "storage-opts": [
    "overlay2.override_kernel_check=true"
  ]
}
EOF

mkdir -p /etc/systemd/system/docker.service.d

# Restart Docker
systemctl daemon-reload
systemctl restart docker
```

## 问题记录

按照官网的执行之后，发现出现错误:
```
# systemctl restart docker
Job for docker.service failed because start of the service was attempted too often. See "systemctl status docker.service" and "journalctl -xe" for details.
```

遇到这个问题先不要慌张，仔细看下信息：
```
# systemctl status docker.service
● docker.service - Docker Application Container Engine
   Loaded: loaded (/usr/lib/systemd/system/docker.service; disabled; vendor preset: disabled)
   Active: failed (Result: start-limit) since Wed 2019-07-17 11:39:21 CST; 40s ago
     Docs: https://docs.docker.com
  Process: 30393 ExecStart=/usr/bin/dockerd -H fd:// --containerd=/run/containerd/containerd.sock (code=exited, status=1/FAILURE)
 Main PID: 30393 (code=exited, status=1/FAILURE)

Jul 17 11:39:19 192-168-34-2 systemd[1]: docker.service failed.
Jul 17 11:39:21 192-168-34-2 systemd[1]: docker.service holdoff time over, scheduling restart.
Jul 17 11:39:21 192-168-34-2 systemd[1]: Stopped Docker Application Container Engine.
Jul 17 11:39:21 192-168-34-2 systemd[1]: start request repeated too quickly for docker.service
Jul 17 11:39:21 192-168-34-2 systemd[1]: Failed to start Docker Application Container Engine.
Jul 17 11:39:21 192-168-34-2 systemd[1]: Unit docker.service entered failed state.
Jul 17 11:39:21 192-168-34-2 systemd[1]: docker.service failed.
Jul 17 11:39:58 192-168-34-2 systemd[1]: start request repeated too quickly for docker.service
Jul 17 11:39:58 192-168-34-2 systemd[1]: Failed to start Docker Application Container Engine.
Jul 17 11:39:58 192-168-34-2 systemd[1]: docker.service failed.
```

其实 status看不出多少信息的。

使用 `journalctl -u docker` 看看

```
Jul 17 10:23:19 192-168-34-2 dockerd[4507]: time="2019-07-17T10:23:19.169683393+08:00" level=info msg="pickfirstBalancer: HandleSubConnStateChange: 0x
Jul 17 10:23:19 192-168-34-2 dockerd[4507]: time="2019-07-17T10:23:19.172535064+08:00" level=warning msg="Using pre-4.0.0 kernel for overlay2, mount f
Jul 17 10:23:19 192-168-34-2 dockerd[4507]: Error starting daemon: error initializing graphdriver: overlay2: the backing xfs filesystem is formatted w
Jul 17 10:23:19 192-168-34-2 systemd[1]: docker.service: main process exited, code=exited, status=1/FAILURE
Jul 17 10:23:19 192-168-34-2 systemd[1]: Failed to start Docker Application Container Engine.
Jul 17 10:23:19 192-168-34-2 systemd[1]: Unit docker.service entered failed state.
```

看到错误了，graphdriver错误，网上查了一下，由于 /etc/docker/daemon.json 里面设置了 storage-driver 为overlay2。这里的存储驱动大致作用就是协调镜像 docker 文件系统如何在宿主机的文件系统中存在。有几种：

* overlay
* overlay2
* devicemapper
* aufs
* btrfs
* vfs

docker 官网上也有说明：https://docs.docker.com/storage/storagedriver/select-storage-driver/

但是 overlay2 需要 linux 内核版本大于4.0。我查看了下我虚拟机的配置：

```
[root@192-168-34-2 ~]# uname -a
Linux 192-168-34-2 3.10.0-229.el7.x86_64 #1 SMP Fri Mar 6 11:36:42 UTC 2015 x86_64 x86_64 x86_64 GNU/Linux
```

所以报错。

于是修改 `/etc/docker/daemon.json ` 配置如下：

```
{
  "exec-opts": ["native.cgroupdriver=systemd"],
  "log-driver": "json-file",
  "log-opts": {
    "max-size": "100m"
  },
  "registry-mirrors": ["https://<my mirror>.mirror.aliyuncs.com"],
  "storage-driver": "devicemapper"
}
```

我一方面修改了存储驱动，一方面增加了一个 docker 仓库镜像。我这里的仓库镜像使用的是阿里云的，阿里云的仓库镜像需要自己去阿里云的容器镜像服务获取 https://cr.console.aliyun.com/cn-hangzhou/instances/mirrors。 需要登陆，会为你的账户生成一个唯一的容器镜像。

当然国内还有很多其他的容器镜像，网上一搜一大把。

配置完成之后，重启`systemctl restart docker`。没有任何错误异常了。

# kubeadm 安装k8s 集群

kubeadm 能帮助您建立一个小型的符合最佳实践的 Kubernetes 集群。

## 安装kubeadmin

按照官方的文章（https://kubernetes.io/zh/docs/setup/independent/install-kubeadm/）在每台机器都安装 kubeadmin， kubelet, kubectl。

## 使用kubeadmin 在master 上安装

在 master 节点上启动 kubeadm init。

发现错误

```
[root@192-168-34-2 ~]# kubeadm init
W0717 14:44:16.577129    6643 version.go:98] could not fetch a Kubernetes version from the internet: unable to get URL "https://dl.k8s.io/release/stable-1.txt": Get https://dl.k8s.io/release/stable-1.txt: net/http: request canceled while waiting for connection (Client.Timeout exceeded while awaiting headers)
W0717 14:44:16.577208    6643 version.go:99] falling back to the local client version: v1.15.0
[init] Using Kubernetes version: v1.15.0
[preflight] Running pre-flight checks
	[WARNING Firewalld]: firewalld is active, please ensure ports [6443 10250] are open or your cluster may not function correctly
	[WARNING Service-Docker]: docker service is not enabled, please run 'systemctl enable docker.service'
	[WARNING Hostname]: hostname "192-168-34-2" could not be reached
	[WARNING Hostname]: hostname "192-168-34-2": lookup 192-168-34-2 on 10.0.2.3:53: server misbehaving
error execution phase preflight: [preflight] Some fatal errors occurred:
	[ERROR NumCPU]: the number of available CPUs 1 is less than the required 2
	[ERROR FileContent--proc-sys-net-bridge-bridge-nf-call-iptables]: /proc/sys/net/bridge/bridge-nf-call-iptables contents are not set to 1
	[ERROR Swap]: running with swap on is not supported. Please disable swap
[preflight] If you know what you are doing, you can make a check non-fatal with `--ignore-preflight-errors=...`
```

第一个问题： hostname "192-168-34-2" could not be reached。

我的机器名称都是用 192-168-34-2 来进行配置，所以需要能解析这个hostname。配置一下 /etc/hosts 来进行配置。

问题：docker service is not enabled, please run 'systemctl enable docker.service'

按照要求运行下即可

第三个问题： the number of available CPUs 1 is less than the required 2

错误很明显，cpu 核数至少需要2个，于是我需要调整下虚拟机的 cpu 核数。重启后就可以了。

第四个问题： /proc/sys/net/bridge/bridge-nf-call-iptables contents are not set to 1

执行命令： `echo "1" >/proc/sys/net/bridge/bridge-nf-call-iptables`

第五个问题： running with swap on is not supported. Please disable swap

`swapoff -a`


解决了以后执行命令：`kubeadm init --apiserver-advertise-address=192.168.34.2 --ignore-preflight-errors=swap`

这个命令首先是指定我的 apiserver 地址，我的虚拟机有多个网卡。然后指定忽略 swap 的错误。

```
[root@192-168-34-2 lib]# kubeadm init --apiserver-advertise-address=192.168.34.2 --ignore-preflight-errors=swap
W0717 16:32:21.801747   18256 version.go:98] could not fetch a Kubernetes version from the internet: unable to get URL "https://dl.k8s.io/release/stable-1.txt": Get https://dl.k8s.io/release/stable-1.txt: net/http: request canceled while waiting for connection (Client.Timeout exceeded while awaiting headers)
W0717 16:32:21.801825   18256 version.go:99] falling back to the local client version: v1.15.0
[init] Using Kubernetes version: v1.15.0
[preflight] Running pre-flight checks
	[WARNING Firewalld]: firewalld is active, please ensure ports [6443 10250] are open or your cluster may not function correctly
[preflight] Pulling images required for setting up a Kubernetes cluster
[preflight] This might take a minute or two, depending on the speed of your internet connection
[preflight] You can also perform this action in beforehand using 'kubeadm config images pull'
[kubelet-start] Writing kubelet environment file with flags to file "/var/lib/kubelet/kubeadm-flags.env"
[kubelet-start] Writing kubelet configuration to file "/var/lib/kubelet/config.yaml"
[kubelet-start] Activating the kubelet service
[certs] Using certificateDir folder "/etc/kubernetes/pki"
[certs] Generating "etcd/ca" certificate and key
[certs] Generating "etcd/healthcheck-client" certificate and key
[certs] Generating "apiserver-etcd-client" certificate and key
[certs] Generating "etcd/server" certificate and key
[certs] etcd/server serving cert is signed for DNS names [192-168-34-2 localhost] and IPs [192.168.34.2 127.0.0.1 ::1]
[certs] Generating "etcd/peer" certificate and key
[certs] etcd/peer serving cert is signed for DNS names [192-168-34-2 localhost] and IPs [192.168.34.2 127.0.0.1 ::1]
[certs] Generating "ca" certificate and key
[certs] Generating "apiserver" certificate and key
[certs] apiserver serving cert is signed for DNS names [192-168-34-2 kubernetes kubernetes.default kubernetes.default.svc kubernetes.default.svc.cluster.local] and IPs [10.96.0.1 192.168.34.2]
[certs] Generating "apiserver-kubelet-client" certificate and key
[certs] Generating "front-proxy-ca" certificate and key
[certs] Generating "front-proxy-client" certificate and key
[certs] Generating "sa" key and public key
[kubeconfig] Using kubeconfig folder "/etc/kubernetes"
[kubeconfig] Writing "admin.conf" kubeconfig file
[kubeconfig] Writing "kubelet.conf" kubeconfig file
[kubeconfig] Writing "controller-manager.conf" kubeconfig file
[kubeconfig] Writing "scheduler.conf" kubeconfig file
[control-plane] Using manifest folder "/etc/kubernetes/manifests"
[control-plane] Creating static Pod manifest for "kube-apiserver"
[control-plane] Creating static Pod manifest for "kube-controller-manager"
[control-plane] Creating static Pod manifest for "kube-scheduler"
[etcd] Creating static Pod manifest for local etcd in "/etc/kubernetes/manifests"
[wait-control-plane] Waiting for the kubelet to boot up the control plane as static Pods from directory "/etc/kubernetes/manifests". This can take up to 4m0s
[apiclient] All control plane components are healthy after 28.509842 seconds
[upload-config] Storing the configuration used in ConfigMap "kubeadm-config" in the "kube-system" Namespace
[kubelet] Creating a ConfigMap "kubelet-config-1.15" in namespace kube-system with the configuration for the kubelets in the cluster
[upload-certs] Skipping phase. Please see --upload-certs
[mark-control-plane] Marking the node 192-168-34-2 as control-plane by adding the label "node-role.kubernetes.io/master=''"
[mark-control-plane] Marking the node 192-168-34-2 as control-plane by adding the taints [node-role.kubernetes.io/master:NoSchedule]
[bootstrap-token] Using token: fjqex8.kvtwk17o7w9sbdgd
[bootstrap-token] Configuring bootstrap tokens, cluster-info ConfigMap, RBAC Roles
[bootstrap-token] configured RBAC rules to allow Node Bootstrap tokens to post CSRs in order for nodes to get long term certificate credentials
[bootstrap-token] configured RBAC rules to allow the csrapprover controller automatically approve CSRs from a Node Bootstrap Token
[bootstrap-token] configured RBAC rules to allow certificate rotation for all node client certificates in the cluster
[bootstrap-token] Creating the "cluster-info" ConfigMap in the "kube-public" namespace
[addons] Applied essential addon: CoreDNS
[addons] Applied essential addon: kube-proxy

Your Kubernetes control-plane has initialized successfully!

To start using your cluster, you need to run the following as a regular user:

  mkdir -p $HOME/.kube
  sudo cp -i /etc/kubernetes/admin.conf $HOME/.kube/config
  sudo chown $(id -u):$(id -g) $HOME/.kube/config

You should now deploy a pod network to the cluster.
Run "kubectl apply -f [podnetwork].yaml" with one of the options listed at:
  https://kubernetes.io/docs/concepts/cluster-administration/addons/

Then you can join any number of worker nodes by running the following on each as root:

kubeadm join 192-168-34-2:6443 --token fjqex8.kvtwk17o7w9sbdgd \
    --discovery-token-ca-cert-hash sha256:32e2f6ab81e560aefeb279a775d03a0639ffc3deff8546ecb3ef0092cd8d0ac3
```
通过docker ps 就可以看到现在在master 上启动的服务

```
[root@192-168-34-2 lib]# docker ps
CONTAINER ID        IMAGE                  COMMAND                  CREATED             STATUS              PORTS               NAMES
5d0622bd424e        d235b23c3570           "/usr/local/bin/kube…"   3 minutes ago       Up 3 minutes                            k8s_kube-proxy_kube-proxy-74xqc_kube-system_21d99634-3571-44bd-8158-33474d107b46_0
c31c4b1f8761        k8s.gcr.io/pause:3.1   "/pause"                 3 minutes ago       Up 3 minutes                            k8s_POD_kube-proxy-74xqc_kube-system_21d99634-3571-44bd-8158-33474d107b46_0
5c6df0793ee8        2c4adeb21b4f           "etcd --advertise-cl…"   3 minutes ago       Up 3 minutes                            k8s_etcd_etcd-192-168-34-2_kube-system_35b8c05861aaf9f0b22afde6e2936a35_0
ea85937b2f18        2d3813851e87           "kube-scheduler --bi…"   3 minutes ago       Up 3 minutes                            k8s_kube-scheduler_kube-scheduler-192-168-34-2_kube-system_31d9ee8b7fb12e797dc981a8686f6b2b_0
8f302c7fa8ef        8328bb49b652           "kube-controller-man…"   3 minutes ago       Up 3 minutes                            k8s_kube-controller-manager_kube-controller-manager-192-168-34-2_kube-system_b1276b18324cdf0ed8a33bf60ce6409e_0
499b300cfed8        201c7a840312           "kube-apiserver --ad…"   3 minutes ago       Up 3 minutes                            k8s_kube-apiserver_kube-apiserver-192-168-34-2_kube-system_e1aa21a04326def87f2af5d33b1a596a_0
93e683bf81e7        k8s.gcr.io/pause:3.1   "/pause"                 3 minutes ago       Up 3 minutes                            k8s_POD_kube-scheduler-192-168-34-2_kube-system_31d9ee8b7fb12e797dc981a8686f6b2b_0
7bedb1f5eeea        k8s.gcr.io/pause:3.1   "/pause"                 3 minutes ago       Up 3 minutes                            k8s_POD_etcd-192-168-34-2_kube-system_35b8c05861aaf9f0b22afde6e2936a35_0
382b3657591e        k8s.gcr.io/pause:3.1   "/pause"                 3 minutes ago       Up 3 minutes                            k8s_POD_kube-controller-manager-192-168-34-2_kube-system_b1276b18324cdf0ed8a33bf60ce6409e_0
304aeb14a136        k8s.gcr.io/pause:3.1   "/pause"                 3 minutes ago       Up 3 minutes                            k8s_POD_kube-apiserver-192-168-34-2_kube-system_e1aa21a04326def87f2af5d33b1a596a_0
```

按照安装成功的提示，我们需要使用普通用户执行下面的动作：

```
mkdir -p $HOME/.kube
sudo cp -i /etc/kubernetes/admin.conf $HOME/.kube/config
sudo chown $(id -u):$(id -g) $HOME/.kube/config
```

才能使用 kubectl：

```
[vagrant@192-168-34-2 home]$ kubectl get cs
NAME                 STATUS    MESSAGE             ERROR
scheduler            Healthy   ok
controller-manager   Healthy   ok
etcd-0               Healthy   {"health":"true"}
```

## 在 master 上安装网络组件

k8s 的网络模型是设计为每个 pod 都在一个扁平网络空间中，那么我们需要搭建这么一个网络，将不同节点之间的 Docker 容器之间的相互访问打通，然后运行 k8s。

目前已经有多个开源组件支持搭建容器的网络模型了，这里我们使用 flannel。它的主要作用是：
* 它能协助 k8s，给每个 Node 上的 docker 容器分配相互不冲突的 IP 地址。
* 它能在这些 IP 地址之间建立一个覆盖网络（Overlay Network）,通过这个覆盖网络，将数据包原封不动地传递到目标容器里面。

首先我们需要在设置 kubeadm 的时候带上 --pod-network-cidr=10.244.0.0/16 的参数

我们先使用 `kubeadm reset` 来重置kubeadm 之前的安装。

然后使用 `kubeadm init --apiserver-advertise-address=192.168.34.2 --ignore-preflight-errors=swap --pod-network-cidr=10.244.0.0/16` 来安装集群

### 可能出现问题

如果错误提醒
```
/var/lib/etcd is not empty
```

直接删除这个目录，或者 mv 到另外一个地方 `mv /var/lib/etcd /var/lib/etcd.bak`

重新执行。

如果出现错误
```
[kubelet-check] It seems like the kubelet isn't running or healthy.
[kubelet-check] The HTTP call equal to 'curl -sSL http://localhost:10248/healthz' failed with error: Get http://localhost:10248/healthz: dial tcp [::1]:10248: connect: connection refused.
```

执行命令：
```
sudo swapoff -a
sudo sed -i '/ swap / s/^/#/' /etc/fstab

然后重启
```

kubeadm init 再次成功之后，执行

```
mkdir -p ~/k8s/
cd ~/k8s
wget https://raw.githubusercontent.com/coreos/flannel/master/Documentation/kube-flannel.yml
kubectl apply -f  kube-flannel.yml
```

显示：
```
[vagrant@192-168-34-2 k8s]$ kubectl apply -f  kube-flannel.yml
podsecuritypolicy.extensions/psp.flannel.unprivileged created
clusterrole.rbac.authorization.k8s.io/flannel created
clusterrolebinding.rbac.authorization.k8s.io/flannel created
serviceaccount/flannel created
configmap/kube-flannel-cfg created
daemonset.extensions/kube-flannel-ds-amd64 created
daemonset.extensions/kube-flannel-ds-arm64 created
daemonset.extensions/kube-flannel-ds-arm created
daemonset.extensions/kube-flannel-ds-ppc64le created
daemonset.extensions/kube-flannel-ds-s390x created
```

我们看下现在启动的 pod：

```
[vagrant@192-168-34-2 k8s]$ kubectl get pods --namespace=kube-system
NAME                                   READY   STATUS    RESTARTS   AGE
coredns-797455887b-87ftg               0/1     Pending   0          5m58s
coredns-797455887b-ddhwn               0/1     Pending   0          5m58s
etcd-192-168-34-2                      1/1     Running   0          68m
kube-apiserver-192-168-34-2            1/1     Running   0          68m
kube-controller-manager-192-168-34-2   1/1     Running   0          68m
kube-flannel-ds-amd64-rmjzg            1/1     Running   0          67m
kube-proxy-9rngs                       1/1     Running   0          69m
kube-scheduler-192-168-34-2            1/1     Running   0          68m
```

如果有长期失败的 pod，可以使用 kubectl describe pod xxx 来进行查看

你有可能看到这个错误：

```
 0/1 nodes are available: 1 node(s) had taints that the pod didn't tolerate.
```
意思现在没有node，处于安全考虑，k8s 默认不会在master上部署 pod，于是可以使用下面方法解决：

```
kubectl taint nodes --all node-role.kubernetes.io/master-
```



# 参考
https://kubernetes.io/zh/docs/setup/independent/install-kubeadm/
https://kubernetes.io/zh/docs/setup/independent/create-cluster-kubeadm/
