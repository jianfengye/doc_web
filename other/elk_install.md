＃ logstash,elasticsearch,kibana三件套

elk是指logstash,elasticsearch,kibana三件套，这三件套可以组成日志分析和监控工具

注意：

关于安装文档，网络上有很多，可以参考，不可以全信，而且三件套各自的版本很多，差别也不一样，需要版本匹配上才能使用。推荐直接使用官网的这一套：[elkdownloads](http://www.elasticsearch.org/overview/elkdownloads/)。

比如我这里下载的一套是logstash 1.4.2 + elasticsearch 1.4.2 + kibana 3.1.2

# 安装elasticsearch

下载[elasticsearch 1.4.2](http://www.elasticsearch.org/overview/elkdownloads/)

<code>
tar -xf elasticsearch-1.4.2.tar.gz
mv elasticsearch-1.4.2 /usr/local/
ln -s /usr/local/elasticsearch-1.4.2 /usr/local/elasticsearch
</code>

安装[elasticsearch-servicewrapper](https://github.com/elasticsearch/elasticsearch-servicewrapper)
<code>
下载解压到/usr/local/elasticsearch/bin文件夹下
/usr/local/elasticsearch/bin/service/elasticsearch start
</code>

测试elasticsearch
<code>
[root@localhost service]# curl -X GET http://localhost:9200/
{
  "status" : 200,
  "name" : "Fury",
  "cluster_name" : "elasticsearch",
  "version" : {
    "number" : "1.4.2",
    "build_hash" : "927caff6f05403e936c20bf4529f144f0c89fd8c",
    "build_timestamp" : "2014-12-16T14:11:12Z",
    "build_snapshot" : false,
    "lucene_version" : "4.10.2"
  },
  "tagline" : "You Know, for Search"
}
</code>

安装到自启动项
<code>
下载解压到/usr/local/elasticsearch/bin文件夹下
/usr/local/elasticsearch/bin/service/elasticsearch install
</code>


# 安装logstash

下载[logstash 1.4.2](http://www.elasticsearch.org/overview/elkdownloads/)

<code>
tar -xf logstash-1.4.2
mv logstash-1.4.2 /usr/local/
ln -s /usr/local/logstash-1.4.2 /usr/local/elasticsearch
</code>

测试logstash

<code>
bin/logstash -e 'input { stdin { } } output { stdout {} }'
</code>

配置logstash

<code>
创建配置文件目录：
mkdir -p /usr/local/elasticsearch/etc

vim /usr/local/elasticsearch/etc/hello_search.conf

输入下面：

input {
  stdin {
    type => "human"
  }
}

output {
  stdout {
    codec => rubydebug
  }

  elasticsearch {
        host => "192.168.33.10"
        port => 9200
  }
}

启动：
/usr/local/elasticsearch/bin/logstash -f /usr/local/elasticsearch/etc/hello_search.conf
</code>

# 安装kibana

注：logstash 1.4.2中也自带了kabana，但是你如果使用自带的kibana安装完之后会发现有提示“Upgrade Required Your version of Elasticsearch is too old. Kibana requires Elasticsearch 0.90.9 or above.”。根据这个[帖子](https://github.com/elasticsearch/logstash/issues/2056)这个是自带的Kibana 3.0.1的问题。所以还是自己安装kibana靠谱。

* 下载[kibana 3.1.2](http://www.elasticsearch.org/overview/elkdownloads/)
* 解压到nginx可以访问的目录，比如/vagrant/kabana
* 配置nginx配置文件
<code>
server {
    listen       80;
    server_name  kibana.yejianfeng.dev;


    access_log  /usr/share/nginx/logs/kibana.access.log  main;
    error_log  /usr/share/nginx/logs/kibana.error.log;
    sendfile off;

    location / {
        root   /vagrant/kibana;
        index  index.html index.htm;
    }

    error_page  404              /404.html;
    location = /404.html {
        root   /usr/share/nginx/html;
    }

    error_page   500 502 503 504  /50x.html;
    location = /50x.html {
        root   /usr/share/nginx/html;
    }

}
</code>

* 重启nginx
* 浏览器访问kibana.yejianfeng.dev，发现提示要修改eleasearch.yaml
* vim /usr/local/elasticsearch/config/elasticsearch.yml 在最后面增加两行：
<code>
http.cors.enabled: true
http.cors.allow-origin: http://kibana.yejianfeng.dev
</code>


# 后记
安装过程碰到很多问题，最多的是版本问题，如果使用不配套的版本，可能会遇到很多问题。所以注意版本一致能绕过很多弯。
