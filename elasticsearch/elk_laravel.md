# elk和laravel的结合[未发布]

elk是指logstash,elasticsearch,kibana三件套，这三件套可以组成日志分析和监控工具。laravel是现在非常火的php框架，两者如何结合呢？

# 方案

laravel的日志系统是使用monolog，打开[monolog](https://github.com/Seldaek/monolog)的github项目看可以看到关于elk相关的东西大概有几个：

* RedisHandler 将日志直接写到redis中去
* ElasticSearchHandler 将日志直接写到elasticSearch中去
* ElasticaFormatter 将日志的信息按照Elastica\Document的格式记录，只能在ElasticSearchHandler中使用
* LogstashFormatter 将日志的信息按照logstash需要的格式记录，它可以用于任何logstash在监听的input渠道中

好了，有了这些就很有意思了，我们的方案可以有好多种做法了：

## 方案一: laravel + ElasticSearchHandler + ElasticaFormatter + elasticsearch ＋ kibana

这个方案，laravel中的日志按照elasticsearch需要的格式直接往elasticSearch中写，然后elasticsearch接收日志信息，就可以进行分析和查看了。这个方案的好处就是简单直接，完全没有logstash的什么事情了。写入－－分析，最直接的一条线。不好的地方也很明显，每次写日志都要往search中多写一条，影响业务，并且没有本地日志存储备份，万一数据search中的日志数据没有了，啥都没了。而且也没有使用logstash的坏处就是没有进行日志过滤处理。

## 方案二: laravel + redisHandler + logstash_indexer + elasticsearch + kibanan

这个方案，laravel中的日志写到redis队列中，然后logstash_indexer去redis中获取数据，进行分析过滤，再写到es中。这么做的好处就是laravel相当于一个队列，写redis的操作出现问题的可能性不是很大，而且有logstash的日志分析过滤，也不错。缺点和前一个方案一样，没有本地日志备份。

## 方案三: laravel + logstash.log + logstash_agent + redis + logstash_indexer + elasticsearch + kibana

这个方案，laravel只负责往日志文件中存储日志，存储完成之后就继续业务逻辑。然后每个日志服务器上的logstash_agent异步的读取日志，写入到redis队列中，接着，logstash_indexer 从另外一台机器上
