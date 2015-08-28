＃ 安装elasticsearch

## 安装java环境
yum install java-1.8.0-openjdk

## 安装elasticsearch

```

https://www.elastic.co/downloads/elasticsearch
cd /tmp
wget https://download.elastic.co/elasticsearch/elasticsearch/elasticsearch-1.7.1.tar.gz
tar -xf elasticsearch-1.7.1.tar.gz
mv /tmp/elasticsearch-1.7.1 /usr/local/
ln -s /usr/local/elasticsearch-1.7.1 /usr/local/elasticsearch

```

# 安装head插件

cd /usr/local/elasticsearch
./bin/plugin --install mobz/elasticsearch-head
访问 http://192.168.33.10:9200/_plugin/head/ 可以访问


# 安装IK插件

去rtf项目中获取对应插件

cd /tmp
wget https://github.com/medcl/elasticsearch-rtf/archive/master.zip
unzip elasticsearch-rtf-master.zip
cd elasticsearch-rtf-master
cp -rf config/ik /usr/local/elasticsearch/config/
cp -rf plugins/analysis-ik /usr/local/elasticsearch/plugins/

vim /usr/local/elasticsearch/config/elasticsearch.yml
增加：
index:
  analysis:
    analyzer:
      ik:
          alias: [ik_analyzer]
          type: org.elasticsearch.index.analysis.IkAnalyzerProvider
      ik_max_word:
          type: ik
          use_smart: false
      ik_smart:
          type: ik
          use_smart: true

启动elasticsearch:

bin/elasticsearch

进行中文分词测试：
## 创建索引
```
PUT http://127.0.0.1:9200/index1
{
  "settings": {
     "refresh_interval": "5s",
     "number_of_shards" :   1, // 一个主节点
     "number_of_replicas" : 0 // 0个副本，后面可以加
  },
  "mappings": {
    "_default_":{
      "_all": { "enabled":  false } // 关闭_all字段，因为我们只搜索title字段
    },
    "resource": {
      "dynamic": false, // 关闭“动态修改索引”
      "properties": {
        "title": {
          "type": "string",
          "index": "analyzed",
          "fields": {
            "cn": {
              "type": "string",
              "analyzer": "ik"
            },
            "en": {
              "type": "string",
              "analyzer": "english"
            }
          }
        }
      }
    }
  }
}
```
## 批量加入数据
```
POST http://192.168.159.159:9200/_bulk
{ "create": { "_index": "index1", "_type": "resource", "_id": 1 } }
{ "title": "周星驰最新电影" }
{ "create": { "_index": "index1", "_type": "resource", "_id": 2 } }
{ "title": "周星驰最好看的新电影" }
{ "create": { "_index": "index1", "_type": "resource", "_id": 3 } }
{ "title": "周星驰最新电影，最好，新电影" }
{ "create": { "_index": "index1", "_type": "resource", "_id": 4 } }
{ "title": "最最最最好的新新新新电影" }
{ "create": { "_index": "index1", "_type": "resource", "_id": 5 } }
{ "title": "I'm not happy about the foxes" }
```

## 对IK进行搜索分词
```
POST http://127.0.0.1:9200/index1/_analyze?analyzer=ik
联想召回笔记本电源线
```

## 搜索关键词
```
POST http://127.0.0.1:9200/index1/resource/_search
{
  "query": {
    "multi_match": {
      "type":     "most_fields",
      "query":    "最新",
      "fields": [ "title", "title.cn", "title.en" ]
    }
  }
}
```

# 安装pinyin插件

## 去rtf项目中获取对应插件

cp -rf /tmp/elasticsearch-rtf-master/plugins/analysis-pinyin/ plugins/



# 参考文章
http://keenwon.com/1404.html
