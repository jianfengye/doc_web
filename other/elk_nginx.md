# elk+redis 搭建nginx日志分析平台

logstash,elasticsearch,kibana 怎么进行nginx的日志分析呢？首先，架构方面，nginx是有日志文件的，它的每个请求的状态等都有日志文件进行记录。其次，需要有个队列，redis的list结构正好可以作为队列使用。然后分析使用elasticsearch就可以进行分析和查询了。

我们需要的是一个分布式的，日志收集和分析系统。logstash有agent和indexer两个角色。对于agent角色，放在单独的web机器上面，然后这个agent不断地读取nginx的日志文件，每当它读到新的日志信息以后，就将日志传送到网络上的一台redis队列上。对于队列上的这些未处理的日志，有不同的几台logstash indexer进行接收和分析。分析之后存储到elasticsearch进行搜索分析。再由统一的kibana进行日志web界面的展示。

下面我计划在一台机器上实现这些角色。

# 准备工作

* 安装了redis,开启在6379端口
* 安装了elasticsearch, 开启在9200端口
* 安装了kibana, 开启了监控web
* logstash安装在/usr/local/logstash

# 开启logstash agent


