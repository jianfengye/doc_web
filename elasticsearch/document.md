# elasticsearch 文档[未发布]

# 文档格式

索引中最基本的单元叫做文档 document. 在es中文档的示例如下：

```
{
    "_index": "questions",
    "_type": "baichebao",
    "_id": "4",
    "_score": 1,
    "_version" : 1,
    "_source": {
            "id": 4,
            "content": "汽车常见故障的解决办法有哪些？",
            "uid": 1,
            "all_answer_count": 2,
            "series_id": 0,
            "score": 0,
            "answer_count": 2
        }
}
```

文档中下划线开头的是es自带的字段

* \_index 代表索引名
* \_type 代表类型
* \_id 代表文档id，如果插入文档的时候没有设置id的话，那么es会自动生成一个唯一id
* \_score 这个不是文档自带的，而是进行搜索的时候返回的，代表这个文档和搜索的相关匹配分值
* \_source 储存原始文本及分类好的字段
* \_version 代表这个文档的版本

这里的索引，类型，文档，字段的概念很多文章都做一个关系型数据的对比。

我现在有一个user表，这个user表有个type字段，0/1代表是男还是女，这个表的每条数据就代表一个人，它拥有名称，电话等属性。

对应于es，表就相当于索引，男女的字段相当于type，每条数据就是一个document，名称电话等属性就是一个字段。

# 版本控制

// TODO:
