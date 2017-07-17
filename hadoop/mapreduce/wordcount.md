# mapreduce基础程序

wordcount是mapreduce的基础程序，它的功能是把一批文章中所有的单词出现次数统计出来。

创建一个mapreduce函数就是创建三个类。一个类是Map类，一个类是Reduce类。还有一个类是主main类。

# Map

Map类继承的是Mapper接口，这个Mapper接口有四个参数：

```
public class Mapper<KEYIN, VALUEIN, KEYOUT, VALUEOUT> {

      public abstract class Context
        implements MapContext<KEYIN,VALUEIN,KEYOUT,VALUEOUT> {
      }

      protected void setup(Context context
                           ) throws IOException, InterruptedException {
        // NOTHING
      }

      @SuppressWarnings("unchecked")
      protected void map(KEYIN key, VALUEIN value,
                         Context context) throws IOException, InterruptedException {
        context.write((KEYOUT) key, (VALUEOUT) value);
      }

      protected void cleanup(Context context
                             ) throws IOException, InterruptedException {
        // NOTHING
      }

      public void run(Context context) throws IOException, InterruptedException {
        setup(context);
        ...
      }
}
```
KEYIN，VALUEIN代表mapper的输入，KEYOUT,VALUEOUT代表mapper的输出。

比如我们这个的输入是一段文本，<行数，文本>，形如<1, "this is sentence1">。
输出是<单词，1>。形如<"word", 1> 这样的。

所以我们的输入类型为<Object, Text>，输出类型为<Text, IntWritable>

# reduce

reduce过程也是一种聚合过程。需要实现org.apache.hadoop.mapreduce.Reducer

同样最核心的要实现一个

```
protected void reduce(KEYIN key, Iterable<VALUEIN> values, Context context
                      ) throws IOException, InterruptedException {
  for(VALUEIN value: values) {
    context.write((KEYOUT) key, (VALUEOUT) value);
  }
}
```

# 流程

我们程序这么写：

```
public static void main(String[] args) throws Exception {
    Configuration conf = new Configuration();

    String[] otherArgs = new GenericOptionsParser(conf, args).getRemainingArgs();
    if (otherArgs.length != 2){
        System.err.println("Usage: wordcount <in> <out>");
        System.exit(2);
    }

    Job job = new Job(conf, "word count");
    job.setJarByClass(WordCount.class);
    job.setMapperClass(TokenizerMapper.class);
    job.setReducerClass(IntSumReducer.class);
    job.setCombinerClass(IntSumReducer.class);
    job.setOutputKeyClass(Text.class);
    job.setOutputValueClass(IntWritable.class);

    FileInputFormat.addInputPath(job, new Path(otherArgs[0]));
    FileOutputFormat.setOutputPath(job, new Path(otherArgs[1]));
    System.exit(job.waitForCompletion(true) ? 0: 1);    
}
```

我们这里设置了Mapper，Reducer，Combiner。wordcount的过程有几个过程:

![](http://tuchuang.funaio.cn/17-5-25/2160750.jpg)

我们两个文本，第一个文本存储hello world, 第二个文本存储hello hadoop hello mapreduce

```
进入mapper的时候，第一个文本输出为
<hello 1>
<world 1>

第二个文本输出为：
<hello 1>
<hadoop 1>
<hello 1>
<mapreduce 1>
```

下面进入Combiner环节

```
第一个文本combiner为
<hello 1>
<world 1>

第二个文本combiner为
<hello 2>
<hadoop 1>
<mapreduce 1>
```

下面进入shuffle环节，对map的输出进行排序合并，这里本身就有一个排序逻辑，这个排序根据map的输出key，如果这个key是IntWritable，则按照数字大小排序，如果是String的Text类型，按照字典顺序排序。

```
<hadoop 1>
<hello <1,2>>
<mapreduce 1>
<world 1>
```

然后进入到reduce环节，对shuffle的结果进行处理

```
<hadoop 1>
<hello 3>
<mapreduce 1>
<world 1>
```
