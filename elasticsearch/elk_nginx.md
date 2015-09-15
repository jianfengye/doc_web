# elk+redis 搭建nginx日志分析平台

logstash,elasticsearch,kibana 怎么进行nginx的日志分析呢？首先，架构方面，nginx是有日志文件的，它的每个请求的状态等都有日志文件进行记录。其次，需要有个队列，redis的list结构正好可以作为队列使用。然后分析使用elasticsearch就可以进行分析和查询了。

我们需要的是一个分布式的，日志收集和分析系统。logstash有agent和indexer两个角色。对于agent角色，放在单独的web机器上面，然后这个agent不断地读取nginx的日志文件，每当它读到新的日志信息以后，就将日志传送到网络上的一台redis队列上。对于队列上的这些未处理的日志，有不同的几台logstash indexer进行接收和分析。分析之后存储到elasticsearch进行搜索分析。再由统一的kibana进行日志web界面的展示。

下面我计划在一台机器上实现这些角色。

# 准备工作

* 安装了redis,开启在6379端口
* 安装了elasticsearch, 开启在9200端口
* 安装了kibana, 开启了监控web
* logstash安装在/usr/local/logstash
* nginx开启了日志，目录为：/usr/share/nginx/logs/test.access.log

# 设置nginx日志格式

在nginx.conf 中设置日志格式：logstash

    log_format logstash '$http_host $remote_addr [$time_local] "$request" $status $body_bytes_sent "$http_referer" "$http_user_agent" $request_time $upstream_response_time';

在vhost/test.conf中设置access日志：

    access_log  /usr/share/nginx/logs/test.access.log  logstash;

# 开启logstash agent

创建logstash agent 配置文件

    vim /usr/local/logstash/etc/logstash_agent.conf

代码如下：

    input {
            file {
                    type => "nginx_access"
                    path => ["/usr/share/nginx/logs/test.access.log"]
            }
    }
    output {
            redis {
                    host => "localhost"
                    data_type => "list"
                    key => "logstash:redis"
            }
    }

启动logstash agent
    
    /usr/local/logstash/bin/logstash -f /usr/local/logstash/etc/logstash_agent.conf

这个时候，它就会把test.access.log中的数据传送到redis中，相当于tail -f。

# 开启logstash indexer

创建 logstash indexer 配置文件
    
    vim /usr/local/logstash/etc/logstash_indexer.conf

代码如下：

    input {
            redis {
                    host => "localhost"
                    data_type => "list"
                    key => "logstash:redis"
                    type => "redis-input"
            }
    }
    filter {
            grok {
                    type => "nginx_access"
                    match => [
                            "message", "%{IPORHOST:http_host} %{IPORHOST:client_ip} \[%{HTTPDATE:timestamp}\] \"(?:%{WORD:http_verb} %{NOTSPACE:http_request}(?: HTTP/%{NUMBER:http_version})?|%{DATA:raw_http_request})\" %{NUMBER:http_status_code} (?:%{NUMBER:bytes_read}|-) %{QS:referrer} %{QS:agent} %{NUMBER:time_duration:float} %{NUMBER:time_backend_response:float}",
                            "message", "%{IPORHOST:http_host} %{IPORHOST:client_ip} \[%{HTTPDATE:timestamp}\] \"(?:%{WORD:http_verb} %{NOTSPACE:http_request}(?: HTTP/%{NUMBER:http_version})?|%{DATA:raw_http_request})\" %{NUMBER:http_status_code} (?:%{NUMBER:bytes_read}|-) %{QS:referrer} %{QS:agent} %{NUMBER:time_duration:float}"
                    ]
            }
    }
    output {
            elasticsearch {
                    embedded => false
                    protocol => "http"
                    host => "localhost"
                    port => "9200"
            }
    }

这份配置是将nginx_access结构化以后塞入elasticsearch中

好了，现在的结构就完成了，你可以访问一次test.dev之后就在kibana的控制台看到这个访问的日志了。而且还是结构化好的了，非常方便查找。