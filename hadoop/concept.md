# hadoop的概念

网上会经常遇到各种hadoop的概念，Hive，HBase，Hdfs都各是什么呢？

首先从hdfs说起，hdfs是分布式文件系统，它把集群当作单机一样做文件操作，文件可能存在于多个机器上，具体的存储细节会对使用者隐藏。

[map_reduce](https://zh.wikipedia.org/zh/MapReduce)是一个计算框架，google提出的，用于大规模数据计算，它们的主要思想，是从函数式编程中借来的特性。

hdfs和map_reduce统称为我们常说的Hadoop架构，这个架构能存储PB级别的数据，也能进行成千上万的独立计算。

好，现在已经有了这个框架了，这个框架包含了底层的存储结构，但是却并不是那么好用，我们大家还是擅长于使用sql语句来进行数据精炼，查询和分析的。这个时候，就出现了[Hive](https://zh.wikipedia.org/wiki/Apache_Hive)。Hive的功能是把sql语句解析成map_reduce的计算任务，当然这样的拆分会导致查询变慢，可能一个sql查询需要分钟甚至小时级别的，不像mysql那样秒级以内查询出结果。

基于Hadoop框架，Powerset公司提出了另外一种非关系行分布式数据库[HBase](https://zh.wikipedia.org/wiki/Apache_HBase)。它是使用JAVA实现的，最大的特点是基于列存储的。列存储的好处是什么？列存储就是把不同行相同的数据存储在一起，这样比如有的行没有的属性，在行存储中还需要留空余空间，但是在列存储中就完全不需要。列存储也能把相同属性的字段存储在一起，这样对数据压缩也有好处。所以列存储很适合大数据领域。

我们经常看到文章比较HBase和Hive，一般都是比较他们的查询效率，其实他们并不是一个维度的东西。HBase的查询效率会优于Hive，而Hive一般用于做离线的数据分析。

# 参考

[大数据存取的选择：行存储还是列存储？](http://www.infoq.com/cn/articles/bigdata-store-choose)
[hive 、hbase区别分析](http://www.chinahadoop.cn/group/8/thread/1624)
