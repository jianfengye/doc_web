mapreduce代码中最重要的就是Job类的使用了。

从wordcount，我们已经看到了几个简单的使用：

# setMapperClass

设置Mapper

# setReducerClass

设置Reducer

# setCombinerClass

设置combiner过程，这个过程也是可以不用设置的。如果不设置，就没有combine的过程，那么所有从mapper出来的数据都会传输到reducer中。

# setPartitionerClass

Reduce自动排序的数据仅仅是发送到自己所在节点的数据，在排序面前还有一个partition的过程。默认无法保证分割后各个Reduce的数据整体是有序的。你可以定义自己的Partition累，保证执行Partition过程之后所有Reduce上的数据在整体是有序的。

我们就需要使用这个方法定义Partitioner类。
