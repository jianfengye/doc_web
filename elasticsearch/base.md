# elasticsearch 基础漫谈[未发布]

elasticsearch是一款基于lucence的搜索引擎。
值得称道的是，lucence只有1MB左右大小，但是却富含了非常全的搜索功能，包括分词，近义词等。
lucence是java编写的，功能和[API](https://lucene.apache.org/core/4_0_0/core/)也非常复杂，不易于使用。

elasticsearch在lucence之外封装了一层，使得请求可以通过最简单的HTTP Restful风格的请求对索引进行增删改查等操作。

elasticsearch一出来就和solr进行比较。solr也是基于lucence的分布式搜索引擎。但是要比较的话，elasticsearch还是更胜一筹。
elasticsearch一出来就是支持分布式的。es的集群管理并不需要使用zookeeper等第三方工具的介入，而是内置的。
当你在多台服务器上配置好了，他们会进行自动发现并组成集群的。

elasticsearch中的索引数据是json形式保存的。它自带id，你也可以给他配置文档中某个字段为id。
它有索引，类型，文档，字段的概念。相对应于关系数据库中的表，类型，数据，属性的概念。
他的文档更新是使用乐观锁的机制处理并发请求的。

elasticsearch提供了丰富的HTTP API，大致分为下面几种类型：

* 文档API: 关于文档的操作，增删改查等
* 搜索API: 关于搜索文档相关的
* 索引API: 关于索引的增删改查
* 查看API: 可以输出控制台
* 集群API: 对集群状态的查看和操作等
