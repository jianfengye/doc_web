# elasticsearch 文档

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

上面可以看到es的文档中有个\_version字段，当两个并发请求要修改文档的时候，es使用的是乐观锁。
在es中，更新请求实际上是分为两个阶段，获取文档，修改文档，然后保存文档。
那么当两个更新请求同时要修改文档的时候，系统乐观的认为不会有两个并发请求对一个系统操作。

文档原本的版本为1，请求A获取了version为1的文档，请求B也获取了version为1的文档，然后请求A修改完文档后，并且先执行了保存操作，这个时候，系统中的文档version变为了2。
这个时候，B再执行保存操作的时候，告诉系统我要修改version为1的文档。系统就会抛出一个错误，说文档版本不匹配。然后这个错误由应用程序自己来进行控制。

这种机制在请求量大的时候会比悲观锁机制好。但是缺点是需要程序处理版本冲突错误，可能一般的方法是封装更新操作，并且设置重复重试次数。

# 增删改查操作

## 增加：

```
POST /website/blog/ -d
{
    id: 123,
    name: "blog123"
}
```

增加操作如果制定的文档已经存在了，就会返回409错误

## 删除:

```
DELETE /website/blog/123
```

如果文档没有存在，则返回404

## 更新:

```
PUT /website/blog/123
{
  "title": "My first blog entry",
  "text":  "I am starting to get the hang of this...",
  "date":  "2014/01/02"
}
```

更新的时候往往有个操作就是“如果有数据，则更新，如果没有数据，则创建”
可以用upsert
```
curl -XPOST 'localhost:9200/test/type1/1/_update' -d '{
    "script" : "ctx._source.counter += count",
    "params" : {
        "count" : 4
    },
    "upsert" : {
        "counter" : 1   // 如果没有id为1的文档，则创建，并且设置counter为1
    }
}'


curl -XPOST 'localhost:9200/test/type1/1/_update' -d '{
    "doc" : {
        "name" : "new_name"
    },
    "doc_as_upsert" : true  // 如果没有文档，则doc就是新的文档
}'
```

更新必须明确的一点是，es中的文档的更新操作实际上是执行了两步，获取文档，更新文档，然后再保存文档。

## 查:

```
GET /website/blog/123
```

如果你已经知道一批文档id了，那么你可以使用批量查的功能

```
GET /_mget
{
   "docs" : [
      {
         "_index" : "website",
         "_type" :  "blog",
         "_id" :    2
      },
      {
         "_index" : "website",
         "_type" :  "pageviews",
         "_id" :    1,
         "_source": "views"
      }
   ]
}
```
