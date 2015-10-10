# elasticsearch 集群

搭建elasticsearch的集群

现在假设我们有3台es机器，想要把他们搭建成为一个集群

# 基本配置

每个节点都要进行这样的配置：

    cluster.name: baichebao-cluster

这个是配置集群的名字，为了能进行自动查找

    node.name: "baichebao-node-1"

这个是配置当前节点的名字，当然每个节点的名字都应该是唯一的

    node.master: false
    node.data: true

这两个配置有4种配置方法，表示这个节点是否可以充当主节点，这个节点是否充当数据节点。
如果你的节点数目只有两个的话，为了防止脑裂的情况，需要手动设置主节点和数据节点。其他情况建议直接不设置，默认两个都为true.

    network.host: "0.0.0.0"

绑定host，0.0.0.0代表所有IP，为了安全考虑，建议设置为内网IP

    transport.tcp.port: 10800

节点到节点之间的交互是使用tcp的，这个设置设置启用的端口

    http.port: 9700

这个是对外提供http服务的端口，安全考虑，建议修改，不用默认的9200

    discovery.zen.ping.multicast.enabled: false
    discovery.zen.fd.ping_timeout: 100s
    discovery.zen.ping.timeout: 100s
    discovery.zen.minimum_master_nodes: 2
    discovery.zen.ping.unicast.hosts: ["12.12.12.12:10801"]

这几个是集群自动发现机制

    discovery.zen.ping.multicast.enabled 这个设置把组播的自动发现给关闭了，为了防止其他机器上的节点自动连入。
    discovery.zen.fd.ping_timeout和discovery.zen.ping.timeout是设置了节点与节点之间的连接ping时长
    discovery.zen.minimum_master_nodes 这个设置为了避免脑裂。比如3个节点的集群，如果设置为2，那么当一台节点脱离后，不会自动成为master。
    discovery.zen.ping.unicast.hosts 这个设置了自动发现的节点。

    action.auto_create_index: false

这个关闭了自动创建索引。为的也是安全考虑，否则即使是内网，也有很多扫描程序，一旦开启，扫描程序会自动给你创建很多索引。

在bin/elasticsearch里面增加两行：

    ES_HEAP_SIZE=4g
    MAX_OPEN_FILES=65535

这两行设置了节点可以使用的内存数和最大打开的文件描述符数。

好了，启动三个节点他们就会互相自己连起来成为集群了。

## 自动选举

elasticsearch集群一旦建立起来以后，会选举出一个master，其他都为slave节点。
但是具体操作的时候，每个节点都提供写和读的操作。就是说，你不论往哪个节点中做写操作，这个数据也会分配到集群上的所有节点中。

这里有某个节点挂掉的情况，如果是slave节点挂掉了，那么首先关心，数据会不会丢呢？不会。如果你开启了replicate，那么这个数据一定在别的机器上是有备份的。
别的节点上的备份分片会自动升格为这份分片数据的主分片。这里要注意的是这里会有一小段时间的yellow状态时间。

如果是主节点挂掉怎么办呢？当从节点们发现和主节点连接不上了，那么他们会自己决定再选举出一个节点为主节点。
但是这里有个脑裂的问题，假设有5台机器，3台在一个机房，2台在另一个机房，当两个机房之间的联系断了之后，每个机房的节点会自己聚会，推举出一个主节点。
这个时候就有两个主节点存在了，当机房之间的联系恢复了之后，这个时候就会出现数据冲突了。
解决的办法就是设置参数：

    discovery.zen.minimum_master_nodes

为3(超过一半的节点数)，那么当两个机房的连接断了之后，就会以大于等于3的机房的master为主，另外一个机房的节点就停止服务了。

对于自动服务这里不难看出，如果把节点直接暴露在外面，不管怎么切换master，必然会有单节点问题。所以一般我们会在可提供服务的节点前面加一个负载均衡。

## 自动发现

elasticsearch的集群是内嵌自动发现功能的。

意思就是说，你只需要在每个节点配置好了集群名称，节点名称，互相通信的节点会根据es自定义的服务发现协议去按照多播的方式来寻找网络上配置在同样集群内的节点。
和其他的服务发现功能一样，es是支持多播和单播的。多播和单播的配置分别根据这几个参数：

    discovery.zen.ping.multicast.enabled: false
    discovery.zen.fd.ping_timeout: 100s
    discovery.zen.ping.timeout: 100s
    discovery.zen.minimum_master_nodes: 2
    discovery.zen.ping.unicast.hosts: ["12.12.12.12:10801"]

多播是需要看服务器是否支持的，由于其安全性，其实现在基本的云服务（比如阿里云）是不支持多播的，所以即使你开启了多播模式，你也仅仅只能找到本机上的节点。
单播模式安全，也高效，但是缺点就是如果增加了一个新的机器的话，就需要每个节点上进行配置才生效了。

# 参考文档
http://kibana.logstash.es/content/elasticsearch/principle/auto-discovery.html
https://www.elastic.co/guide/en/elasticsearch/reference/current/modules-discovery-zen.html
