# elk收集分析nginx access日志

首先elk的搭建按照这篇文章[使用elk+redis搭建nginx日志分析平台](http://www.cnblogs.com/yjf512/p/4199105.html)说的，使用redis的push和pop做队列，然后有个logstash_indexer来从队列中pop数据分析插入elasticsearch。这样做的好处是可扩展，logstash_agent只需要收集log进入队列即可，比较可能会有瓶颈的log分析使用logstash_indexer来做，而这个logstash_indexer又是可以水平扩展的，我可以在单独的机器上跑多个indexer来进行日志分析存储。

好了，现在进一步配置了。

# nginx中的日志存储格式

nginx由于有get请求，也有post请求，get请求的参数是会直接显示在日志的url中的，但是post请求的参数呢，却不会在access日志中体现出来。那么我想要post的参数也进行存储纪录下来。就需要自己定义一个log格式了。

    log_format logstash '$http_host $server_addr $remote_addr [$time_local] "$request" $request_body $status $body_bytes_sent "$http_referer" "$http_user_agent" $request_time $upstream_response_time';

这里的$request_body里面存放的就是POST请求的body了，然后GET请求的参数在$request里面。具体怎么分析，我们在indexer中再想。

这里的server_addr存放的是当前web机器的IP，存这个IP是为了分析日志的时候可以分析日志的原始来源。

下面是一个GET请求的例子：

    api.yejianfeng.com 10.171.xx.xx 100.97.xx.xx [10/Jun/2015:10:53:24 +0800] "GET /api1.2/qa/getquestionlist/?limit=10&source=ios&token=12343425324&type=1&uid=304116&ver=1.2.379 HTTP/1.0" - 200 2950 "-" "TheMaster/1.2.379 (iPhone; iOS 8.3; Scale/2.00)" 0.656 0.654

下面是一个POST请求的例子：

    api.yejianfeng.com 10.171.xx.xx 100.97.xx.xx [10/Jun/2015:10:53:24 +0800] "POST /api1.2/user/mechanicupdate/ HTTP/1.0" start_time=1276099200&lng=110.985723&source=android&uid=328910&lat=35.039471&city=140800 200 754 "-" "-" 0.161 0.159

顺便说下，这里知识在nginx.conf中定义了一个日志格式，还要记得在具体的服务中加入日志存储。比如

    listen       80;
    server_name api.yejianfeng.com;
    access_log /mnt/logs/api.yejianfeng.com.logstash.log logstash;

# log_agent的配置

这个配置就是往redis队列中塞入日志就行。output的位置设置为redis就行。

    input {
            file {
                    type => "nginx_access"
                    path => ["/mnt/logs/api.yejianfeng.com.logstash.log"]
            }
    }
    output {
            redis {
                    host => "10.173.xx.xx"
                    port => 8001
                    password => pass
                    data_type => "list"
                    key => "logstash:redis"
            }
    }

# log_indexer的配置

log_indexer的配置就比较麻烦了，需要配置的有三个部分

* input: 负责从redis中获取日志数据
* filter:  负责对日志数据进行分析和结构化
* output: 负责将结构化的数据存储进入elasticsearch

## input部分

    input {
            redis {
                    host => "10.173.xx.xx"
                    port => 8001
                    password => pass
                    data_type => "list"
                    key => "logstash:redis"
            }
    }

其中的redis配置当然要和agent的一致了。

## filter部分

解析文本可以使用[grok](https://www.elastic.co/guide/en/logstash/current/plugins-filters-grok.html)进行分析，参照着之前的log格式，需要一个个进行日志分析比对。这个grok语法写的还是比较复杂的，还好有在线[grok比对工具](http://grokconstructor.appspot.com/do/match)可以使用。比对前面的GET和POST的日志格式，修改出来的grok语句如下：

    %{IPORHOST:http_host} %{IPORHOST:server_ip} %{IPORHOST:client_ip} \[%{HTTPDATE:timestamp}\] \"%{WORD:http_verb} (?:%{PATH:baseurl}\?%{NOTSPACE:params}(?: HTTP/%{NUMBER:http_version})?|%{DATA:raw_http_request})\" (%{NOTSPACE:params})?|- %{NUMBER:http_status_code} (?:%{NUMBER:bytes_read}|-) %{QS:referrer} %{QS:agent} %{NUMBER:time_duration:float} %{NUMBER:time_backend_response:float}

这里使用了一点小技巧，params的使用，为了让GET和POST的参数都反映在一个参数上，在对应的GET和POST的参数的地方，都设计使用params这个参数进行对应。

好了，现在params中是请求的参数。比如source=ios&uid=123。但是呢，最后做统计的时候，我希望得出的是“所有source值为ios的调用”，那么就需要对参数进行结构化了。而且我们还希望如果接口中新加入了一个参数，不用修改logstash_indexer就可以直接使用，方法就是使用[kv](http://logstash.net/docs/1.4.0.rc1/filters/kv)，kv能实现对一个字符串的结构进行k=v格式的拆分。其中的参数prefix可以为这个key在统计的时候增加一个前缀，include_keys可以设置有哪些key包含在其中，exclude_keys可以设置要排除哪些key。

    kv {
        prefix => "params."
        field_split => "&"
        source => "params"
    }

好了，现在还有一个问题，如果请求中有中文，那么日志中的中文是被urlencode之后存储的。我们具体分析的时候，比如有个接口是/api/search?keyword=我们，需要统计的是keyword被查询的热门顺序，那么就需要解码了。logstash牛逼的也有[urldecode命令](http://logstash.net/docs/1.4.0.rc1/filters/urldecode)，urldecode可以设置对某个字段，也可以设置对所有字段进行解码。

    urldecode {
        all_fields => true
    }

看起来没事了，但是实际上在运行的时候，你会发现一个问题，就是存储到elasticsearch中的timestamp和请求日志中的请求时间不一样。原因是es中的请求日志使用的是日志结构存放进入es的时间，而不是timestamp的时间，这里想要吧es中的时间和请求日志中的时间统一怎么办呢？使用[date命令](http://logstash.net/docs/1.4.0.rc1/filters/date)。具体设置如下：
    
    date {
            locale => "en"
            match => ["timestamp" , "dd/MMM/YYYY:HH:mm:ss Z"]
    }

具体的logstash_indexer中的全部配置如下：

    filter {
            grok {
                match => [
                            "message", "%{IPORHOST:http_host} %{IPORHOST:server_ip} %{IPORHOST:client_ip} \[%{HTTPDATE:timestamp}\] \"%{WORD:http_verb} (?:%{PATH:baseurl}\?%{NOTSPACE:params}(?: HTTP/%{NUMBER:http_version})?|%{DATA:raw_http_request})\" (%{NOTSPACE:params})?|- %{NUMBER:http_status_code} (?:%{NUMBER:bytes_read}|-) %{QS:referrer} %{QS:agent} %{NUMBER:time_duration:float} %{NUMBER:time_backend_response:float}"
                ]
            }
            kv {
                prefix => "params."
                field_split => "&"
                source => "params"
            }
            urldecode {
                      all_fields => true
            }
            date {
                    locale => "en"
                    match => ["timestamp" , "dd/MMM/YYYY:HH:mm:ss Z"]
            }

    }


## output部分

这里就是很简单往es中发送数据

    output {
            elasticsearch {
                    embedded => false
                    protocol => "http"
                    host => "localhost"
                    port => "9200"
                    user => "yejianfeng"
                    password => "yejianfeng"
            }
    }

这里有个user和password，其实elasticsearch加上[shield](https://www.elastic.co/downloads/shield)就可以强制使用用户名密码登录了。这里的output就是配置这个使用的。

# 查询elasticsearch

比如上面的例子，我要查询某段时间的params.source(其实是source参数，但是前面的params是前缀)调用情况

    $url = 'http://xx.xx.xx.xx:9200/logstash-*/_search';
    $filter = '
    {
      "query": {
            "range" : {
                "@timestamp" : {
                    "gt" : 123213213213,
                    "lt" : 123213213213
                }
            }
       },
       "aggs" : {
            "group_by_source" : {"terms" : {"field" : "params.source"}}
        },
        "size": 0
    }';

具体使用参考[elasticsearch的文档](http://es.xiaoleilu.com/010_Intro/00_README.html)
