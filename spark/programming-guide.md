# spark编程指南阅读-python版本

阅读的文档：https://spark.apache.org/docs/1.6.2/programming-guide.html

# 概述

spark是一个并行计算框架。它提供了两个抽象概念：RDD(resilient distributed dataset) 和 共享变量(shared variables)。

RDD是计算集的概念。每个partition用于计算的数据结构就是RDD了。RDD可以由程序自身创建，也可以由hdfs上的文件创建。用户也可以把RDD存储在内存中，以便被重用。

共享变量是在不同任务之间共享的数据。Spark提供两种共享变量：broadcast variables（它可以在所有节点中缓存）accumulators（只用于做计数器）

# 如何链接spark

spark 1.6.2可以和python2.6+ 及python 3.4+。

我们使用 bin/spark-submit 脚本来执行spark目录下的某个脚本。然后我们需要建立一个pyspark链接链接到我们版本的HDFS上。

最后我们import 一些Spark类到我们的程序中。
```
from pyspark import SparkContext, SparkConf
```

# 初始化spark

```
conf = SparkConf().setAppName(appName).setMaster(master)
sc = SparkContext(conf=conf)
```

我们需要先创建一个SparkContext对象，然后才能操作spark集群。

其中的
* appName是你的应用显示在集群UI上的应用名称。
* master是spark地址，mesos，yarn集群的url地址。一般来说，如果我们使用spark-submit提交脚本，我们一般不会在代码里面写死这个master值的。

# 使用shell来进行交互

我们可以使用PySpark来进行shell交互。在shell中，已经初始化一个SparkContext变量叫sc。
```
$ ./bin/pyspark --master local[4] --py-files code.py
```

这里有个master参数，表示初始化spark的setMaster的值。py-files参数，表示调用的是code.py这个脚本。通常，pyspark比spark-submit更常用。

# Resilient Distributed Datasets (RDDs)

RDD是spark中最核心的概念，可以并行操作的元素的集合。它可以由两个地方初始化：
* 程序中使用parallelize函数来初始化
* 文件系统或者外部输入获取的数据集合

## 内部获取RDD方法

内部使用parallelize函数获取RDD的方法如下：
```
data = [1, 2, 3, 4, 5]
distData = sc.parallelize(data)
```
生成了RDD之后，对RDD的所有操作，比如distData.reduce(lambda a, b: a + b) 都是并行执行的。

parallelize还有第二个参数，指定这个输入逻辑上分为多少份，这是partition的概念。理论上，spark集群使用一个CPU（逻辑CPU）来处理一个partition。但是如果CPU数量不够的话，也有可能一个CPU处理多个partition。
