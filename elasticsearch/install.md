# 安装elasticsearch中文IK和近义词配置

## 安装java环境

java环境是elasticsearch安装必须的
```
yum install java-1.8.0-openjdk
```

## 安装elasticsearch

其实es的安装非常简单了

```
https://www.elastic.co/downloads/elasticsearch
cd /tmp
wget https://download.elastic.co/elasticsearch/elasticsearch/elasticsearch-1.7.1.tar.gz
tar -xf elasticsearch-1.7.1.tar.gz
mv /tmp/elasticsearch-1.7.1 /usr/local/
ln -s /usr/local/elasticsearch-1.7.1 /usr/local/elasticsearch
```

# 安装head插件

head插件让我们能更简单管理elasticsearch

```
cd /usr/local/elasticsearch
./bin/plugin --install mobz/elasticsearch-head
```

访问 http://192.168.33.10:9200/_plugin/head/ 可以访问


# 安装IK插件

去rtf项目中获取对应插件，建议别去自己找plugin下，medcl大已经为我们准备好了一切

```
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
```

# 配置近义词

近义词组件已经是elasticsearch自带的了，所以不需要额外安装插件，但是想要让近义词和IK一起使用，就需要配置自己的分析器了。

首先创建近义词文档

在config目录下

```
mkdir analysis
vim analysis/synonym.txt

编辑：

i-pod, i pod, i pad => ipod,
sea biscuit, sea biscit => seabiscuit,
中文,汉语,汉字
```

这里可以看到近义词的写法有两种：


```
a,b => c
a,b,c
```

第一种在分词的时候，a,b都会解析成为c，然后把c存入索引中
第二种在分词的时候，有a的地方，都会解析成a,b,c，把a,b,c存入索引中
第一种方法相比之下有个主词，比较省索引。

配置elasticsearch.yml中的自定义索引，和前面的ik结合，可以这么设置：

```
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
      my_synonyms:
          tokenizer: standard
      ik_syno:
          type: custom
          tokenizer: ik
          filter: [my_synonym_filter]
      ik_syno_smart:
          type: custom
          tokenizer: ik
          filter: [my_synonym_filter]
          use_smart: true
    filter:
      my_synonym_filter:
          type: synonym
          synonyms_path: analysis/synonym.txt
```

上面的配置文件创建了一个filter： my_synonym_filter, 然后创建了两个自定义analyzer: ik_syno和ik_syno_smart

# 启动elasticsearch:

```
bin/elasticsearch
```

# 案例测试

按照上面的配置，我们使用一个具体的句子进行测试：

120.55.72.158:9700/elasticsearchtest2
{
    "index" : {
        "analysis" : {
            "analyzer" : {
                "ik_syno" : {
                    "tokenizer" : "ik",
                    "filter" : ["my_synonym_filter"]
                }
            }
        }
    }
}

```
curl -XPOST "192.168.33.10:9200/elasticsearchtest/_analyze?analyzer=ik_syno" -d 'we are eng man i pad 汉语文字'
```

返回json结构：

```
{
    "tokens": [
        {
            "token": "we",
            "start_offset": 0,
            "end_offset": 2,
            "type": "ENGLISH",
            "position": 1
        },
        {
            "token": "eng",
            "start_offset": 7,
            "end_offset": 10,
            "type": "ENGLISH",
            "position": 2
        },
        {
            "token": "man",
            "start_offset": 11,
            "end_offset": 14,
            "type": "ENGLISH",
            "position": 3
        },
        {
            "token": "ipod",
            "start_offset": 15,
            "end_offset": 20,
            "type": "SYNONYM",
            "position": 4
        },
        {
            "token": "中文",
            "start_offset": 21,
            "end_offset": 23,
            "type": "SYNONYM",
            "position": 5
        },
        {
            "token": "汉语",
            "start_offset": 21,
            "end_offset": 23,
            "type": "SYNONYM",
            "position": 5
        },
        {
            "token": "汉字",
            "start_offset": 21,
            "end_offset": 23,
            "type": "SYNONYM",
            "position": 5
        },
        {
            "token": "文字",
            "start_offset": 23,
            "end_offset": 25,
            "type": "CN_WORD",
            "position": 6
        }
    ]
}
```

这里就可以看到我们之前配置的东西都成功了：

* are字被过滤，是由于are字是stop_words
* i pad这个词语被转化为了ipod是由于近义词字典中我们设置了 i pad=>ipod
* “文字”两个中文字是被分成一个中文词切割，是因为ik的默认main.dic里面有文字两个字
* “中文”“汉字”“汉语”三个词出现是由于近义词字典中我们设置了这三个为同等级的近义词
