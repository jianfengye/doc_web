# elasticsearch 集群[未发布]

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


# 参考文档
http://kibana.logstash.es/content/elasticsearch/principle/auto-discovery.html
https://www.elastic.co/guide/en/elasticsearch/reference/current/modules-discovery-zen.html
