# hdfs 架构

hadoop的文档官网为：http://hadoop.apache.org/docs/r2.7.2/。 我们研究的版本是2.7.2。

# hadoop的命令

格式：
```
hadoop [command] ....
```

* archive 归档的相关操作
* checknative 本地库是否可以使用（有一些操作可能并适合使用java，所以以其他语言开发的库以本地库的形式做加载）
* classpath 查看hadoop的classpath，如果jar包中有引用其他包，可以放在这里
* credential 权限相关命令
* distcp 递归操作hdfs的目录
* fs 文件系统的相关操作
* jar 执行一个hadoop的jar文件
* key 管理hadoop中的key
* trace 改编hadoop的追踪纪录
* version 版本
