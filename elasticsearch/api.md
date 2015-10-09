# elasticsearch中的API

es中的API按照大类分为下面几种：

* 文档API: 提供对文档的增删改查操作
* 搜索API: 提供对文档进行某个字段的查询
* 索引API: 提供对索引进行操作
* 查看API: 按照更直观的形式返回数据，更适用于控制台请求展示
* 集群API: 对集群进行查看和操作的API

# 文档API

* Index API: 创建并建立索引
* Get API: 获取文档
* DELETE API: 删除文档
* UPDATE API: 更新文档
* Multi Get API: 一次批量获取文档
* Bulk API: 批量操作，批量操作中可以执行增删改查
* DELETE By Query API: 根据查询删除
* Term Vectors: 词组分析，只能针对一个文档
* Multi termvectors API: 多个文档的词组分析


multiGet的时候内部的行为是将一个请求分为多个，到不同的node中进行请求，再将结果合并起来。
如果某个node的请求查询失败了，那么这个请求仍然会返回数据，只是返回的数据只有请求成功的节点的查询数据集合。

词组分析的功能能查出比如某个文档中的某个字段被索引分词的情况。

对应的接口[说明和例子](https://www.elastic.co/guide/en/elasticsearch/reference/current/docs.html)

# 搜索API

* 基本搜索接口: 搜索的条件在url中
* DSL搜索接口: 搜索的条件在请求的body中
* 搜索模版设置接口: 可以设置搜索的模版，模版的功能是可以根据不同的传入参数，进行不同的实际搜索
* 搜索分片查询接口: 查询这个搜索会使用到哪个索引和分片
* Suggest接口: 搜索建议接口，输入一个词，根据某个字段，返回搜索建议。
* 批量搜索接口: 把批量请求放在一个文件中，批量搜索接口读取这个文件，进行搜索查询
* Count接口: 只返回符合搜索的文档个数
* 文档存在接口: 判断是否有符合搜索的文档存在
* 验证接口: 判断某个搜索请求是否合法，不合法返回错误信息
* 解释接口: 使用这个接口能返回某个文档是否符合某个查询，为什么符合等信息
* 抽出器接口: 简单来说，可以用这个接口指定某个文档符合某个搜索，事先未文档建立对应搜索

对应的接口[说明和例子](https://www.elastic.co/guide/en/elasticsearch/reference/current/search.html)

# 索引API

* 创建索引接口(POST my_index)
* 删除索引接口(DELETE my_index)
* 获取索引信息接口(GET my_index)
* 索引是否存在接口(HEAD my_index)
* 打开/关闭索引接口(my_index/\_close, my_index/\_open)
* 设置索引映射接口(PUT my_index/\_mapping)
* 获取索引映射接口(GET my_index/\_mapping)
* 获取字段映射接口(GET my_index/\_mapping/field/my_field)
* 类型是否存在接口(HEAD my_index/my_type)
* 删除映射接口(DELTE my_index/\_mapping/my_type)
* 索引别名接口(\_aliases)
* 更新索引设置接口(PUT my_index/\_settings)
* 获取索引设置接口(GET my_index/\_settings)
* 分析接口(\_analyze): 分析某个字段是如何建立索引的
* 建立索引模版接口(\_template): 为索引建立模版，以后新创建的索引都可以按照这个模版进行初始化
* 预热接口(\_warmer): 某些查询可以事先预热，这样预热后的数据存放在内存中，增加后续查询效率
* 状态接口(\_status): 索引状态
* 批量索引状态接口(\_stats): 批量查询索引状态
* 分片信息接口(\_segments): 提供分片信息级别的信息
* 索引恢复接口(\_recovery): 进行索引恢复操作
* 清除缓存接口(\_cache/clear): 清除所有的缓存
* 输出接口(\_flush)
* 刷新接口(\_refresh)
* 优化接口(\_optimize): 对索引进行优化
* 升级接口(\_upgrade): 这里的升级指的是把索引升级到lucence的最新格式

对应的接口[说明和例子](https://www.elastic.co/guide/en/elasticsearch/reference/current/indices.html)

# 查看API

* 查看别名接口(\_cat/aliases): 查看索引别名
* 查看分配资源接口(\_cat/allocation)
* 查看文档个数接口(\_cat/count)
* 查看字段分配情况接口(\_cat/fielddata)
* 查看健康状态接口(\_cat/health)
* 查看索引信息接口(\_cat/indices)
* 查看master信息接口(\_cat/master)
* 查看nodes信息接口(\_cat/nodes)
* 查看正在挂起的任务接口(\_cat/pending_tasks)
* 查看插件接口(\_cat/plugins)
* 查看修复状态接口(\_cat/recovery)
* 查看线城池接口(\_cat/thread_pool)
* 查看分片信息接口(\_cat/shards)
* 查看lucence的段信息接口(\_cat/segments)

对应的接口[说明和例子](https://www.elastic.co/guide/en/elasticsearch/reference/current/cat.html)

# 集群API

* 查看集群健康状态接口(\_cluster/health)
* 查看集群状况接口(\_cluster/state)
* 查看集群统计信息接口(\_cluster/stats)
* 查看集群挂起的任务接口(\_cluster/pending_tasks)
* 集群重新路由操作(\_cluster/reroute)
* 更新集群设置(\_cluster/settings)
* 节点状态(\_nodes/stats)
* 节点信息(\_nodes)
* 节点的热线程(\_nodes/hot_threads)
* 关闭节点(\nodes/\_master/\_shutdown)

对应的接口[说明和例子](https://www.elastic.co/guide/en/elasticsearch/reference/current/cluster.html)
