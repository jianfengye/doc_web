# laravel Scout包在elasticsearch中的应用

laravel的Scout包是针对自身的Eloquent模型开发的基于驱动的全文检索引擎。意思就是我们可以像使用ORM一样使用检索功能。不管你用的是什么搜索引擎，scout包给你封装好了几个方法
```
use Laravel\Scout\Builder;

abstract public function update($models);
abstract public function delete($models);
abstract public function search(Builder $builder);
abstract public function paginate(Builder $builder, $perPage, $page);
abstract public function map($results, $model);
```

你只需要创建一个引擎（比如es引擎）就可以使用orm来操作search了。

可惜laravel的scout包只提供了Algolia的驱动。我个人很喜欢的es并没有提供驱动。好在网上有人分享了驱动，比如 https://github.com/ErickTamayo/laravel-scout-elastic

看里面的代码，其实很简单，两个类，一个类ElasticsearchEngine用于实现Scout定义的几个方法，一个类ElasticsearchProvider用于做服务注册。我们使用composer require就能用好这个类了。这里就不说了。

# scout包在es中的存储

说说laravel的scout包在es里面是怎么存储的。首先，在配置文件里面elasticsearch创建一个index
```
    'driver' => env('SCOUT_DRIVER', 'elasticsearch'),

    ...    
    'elasticsearch' => [
        'index' => env('ELASTICSEARCH_INDEX', 'laravel'),
        'hosts' => [            
            env('ELASTICSEARCH_HOST', 'http://localhost'),
            ],
        ],
    ...
```

然后每个对应的model都是不同的type。这个type的名字是在model里面定义的。
```
class Post extends Model
use Laravel\Scout\Searchable;

class Post extends Model
{
    use Searchable;

    protected $table = "posts";

    /*
     * 搜索的type
     */
    public function searchableAs()
    {
        return 'posts_index';
    }

    public function toSearchableArray()
    {
        return [
            'title' => $this->title,
            'content' => $this->content,
        ];
    }
```

所以它里面每个条目实际上是这么一个结构：


# 我自己用的几点体验：

1 scout会很聪明把表的主键作为es的_id

这个真是非常赞，不需要存储一个id，做_id和id的关联了

2 scout在model做增删改查的时候会自动更新索引

这个也是我们最需要的，索引数据和数据库数据的同步使用代码进行保证了。当然，用代码保证可能并不是什么很好的方法，但是对于小型的网站来说，这个无疑增加了便捷性。

3 scout的建立索引方法是一个网站统一一个index, 不同的model使用不同的type

这种一个index多type的形式是否适用你的项目呢？不一定，如果你的model各不相同，可能多个index更好点。关于index和type的选择，https://www.elastic.co/blog/index-vs-type 可以参考这篇。所以这种方式可能更适合的是存储到es的都是文本的搜索。

4 search函数里面不能指定搜索字段

比如我的Post索引存入了title和content。那么我使用Post::search("china")的时候，搜索出来的结果就是title和content中包含有china的。如果我想搜索content中包含有"china"的，没办法，scout做不到，只能自己做扩展了。

5 分页指定了查询的字段名必须是query

这个意思是在搜索接口，你上交上来的查询接口必须是query=xx，形如http://127.0.0.1:8000/posts/search?query=china
看了源码发现这个query字段是由Scout/Builder写死在代码里面的...这个估计很多人用到这个分页的时候会踩进去

6 搜索的query强制使用通配符

这个是laravel-scout-elasticsearch的问题了，它在query的时候强制在搜索的前后使用上了通配符*，这个在标准分词器中文搜索的时候会出现问题，会变成一个词，具体问题可以看这个帖子：http://elasticsearch.cn/question/228

所以如果要使用标准分词器，需要把query的前后两个*都去掉，具体代码在vendor/tamayo/laravel-scout-elastic/src/ElasticsearchEngine.php中。

# 总结

scout还是主要偏向于统一搜索接口，如果你的网站很小，并且搜索只是作为文本搜索的话，那么用这个是非常合适的，但是如果你的搜索功能占你的网站大部分功能的话，那么我建议我们可以使用scout做搜索和数据库的同步，其他的搜索请求，我们使用elasticsearch/elasticsearch自己写比较好。
