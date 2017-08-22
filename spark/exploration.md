# spark初探

# spark安装

spark的安装没有遇到什么问题，先安装hadoop 2.7.2，然后安装spark 1.6.2，修改下conf/spark-env.sh就可以了

# spark认识

spark比mr优势的地方就是它的所有中间计算结果都是存放在内存中的。比如mr的中间结果，各种都会放在hdfs上，第一步完成了，结果放hdfs，第二步从hdfs上获取输入，计算，然后算出结果放在hdfs上。这样频繁的硬盘操作导致计算效率底下。spark则所有计算中间结果都是在内存中的。所以效率非常快。

# RDD

RDD是spark的核心，它是一种数据结构（Resilient Distributed Datasets 弹性分布式数据集）。这个数据集是一个容错的，并行的数据结构，可以让用户显式地将数据存储到磁盘和内存中，并能控制数据的分区。

//TODO: 总觉得对RDD的理解还不够深入，这里TODO

# spark运行

spark支持scala，python，R语言的运行，所以它的运行命令和方法有多种多样。

我们可以直接运行
```
./bin/run-example SparkPi 10
```
查看run-example我们可以看到实际上是运行/bin/spark-submit

也就是说，spark是一个计算平台，我们可以提交一段代码，告诉spark，你帮我运行下这个代码。

```
./bin/spark-submit examples/src/main/python/pi.py 10
```

就是提交一个python代码到spark。

spark也提供了一个shell控制台，我们可以在控制台执行运行脚本，能很方便查看交互。

```
./bin/spark-shell --master local[2]
```
我们看到有个master操作，指定我们在哪个spark上运行，后面的2是开启多少线程运行这个命令。

spark为python专门提供一个交互工具

```
./bin/pyspark --master local[2]
```

为R提供的交互工具：
```
./bin/sparkR --master local[2]
```

# spark的python示例

# 参考

http://www.infoq.com/cn/articles/spark-core-rdd
