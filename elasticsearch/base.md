# elasticsearch 基础

# elasticsearch结构

结构分为几级：
* index 索引（DB）
* type 类型（表）
* document 文档（行）
* field 字段（属性）

# 基本搜索

## /?pretty

    [yejianfeng@iZ23u681ae1Z ~]$ curl -u baichebao_admin 'http://localhost:9200/?pretty'
    Enter host password for user 'baichebao_admin':
    {
      "status" : 200,
      "name" : "Blur",
      "cluster_name" : "elasticsearch",
      "version" : {
        "number" : "1.5.2",
        "build_hash" : "62ff9868b4c8a0c45860bebb259e21980778ab1c",
        "build_timestamp" : "2015-04-27T09:21:06Z",
        "build_snapshot" : false,
        "lucene_version" : "4.10.4"
      },
      "tagline" : "You Know, for Search"
    }

显示elasticsearch的版本和信息

## /_status?pretty

显示的一些字段：

indices 表示有哪些索引
// 表示有多少文档
docs: {
    num_docs: 582829, // 文档数
    max_doc: 582829, // 最大时候的文档数
    deleted_docs: 0 // 删除的文档数
},

index: {
    primary_size_in_bytes: 297103210,
    size_in_bytes: 297103210
},
