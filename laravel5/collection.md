# 优雅使用 illuminate/database 包中的 Collection

或许你很抵抗使用 Laravel , 但是你没有理由不喜欢使用 illuminate/database。这是一个 ORM 的类库。我个人认为，这个类库你是否用的好，其中很重要的一点就是你是否能用好 Collection 这个数据结构，Collection 这个数据结构的源码在 `Illuminate\\Database\\Eloquent\\Collection`。

Collection 这个数据结构有很多方法。各种方法有很多其实都是重复的。[官方文档](https://laravel.com/docs/5.6/collections) 里面有最全的示例和说明。当不了解一个函数的使用的时候，翻看官方文档是最好的选择。

我这里想介绍一些我在实际工作生活中觉得 Collection 最有用的一些方法。

首先，我把 Collection 的方法做了一下分类。

![](http://tuchuang.funaio.cn/18-2-23/32495545.jpg)

# 前提
## 这篇介绍的 illuminate/database 版本为5.6

## 例子中使用的 user 表的结构和数据如下：
![](http://tuchuang.funaio.cn/18-2-23/49363970.jpg)


# ORM 相关

## load

这里面的 `load` 方法是非常有用的。它的用处在于把一个 Model 关联的集合给一次性关联查询出来。结合illuminate/database 的相关 relation 是非常有用的
```
use App\Comment;

class User extends \Illuminate\Database\Eloquent\Model
{
    protected $table = "user";

    public function comments()
    {
        $this->hasMany(App\Comment)
    }
}


$users = User::all();
$users->load('comments');

```

## find

如果你要按照主键进行查找，那么 `find` 方法是你最好的选择。

```
$users = User::all();
$user = $users->find(1);
```

# 构造类

基本我经常用的只有一个 `make` 方法。

## make

```
$arr = [1,2,3];
$collection = Collection::make($arr);
```

# 提取类

## pluck, unique

这两个用的非常多，比如我需要获取 user 表中 所有的 weight 值:

```
$users = User::all();
$weights = $users->pluck('weight')->unique();
```

## keyBy

如果我需要获取我得到的这批数据以某个字段为key，这种很经常用在有一个非主键的唯一键的情况。
```
$users = User::all();
$users = $users->keyBy('name');
```

# 计算类

计算类的函数就使用非常多了，在各种逻辑中都会用到

## max, min, avg, random

```
$users = User::all();
$maxWeight = $users->max('weight');

$minWeight = $users->min('weight');

$avgWeigth = $users->avg('weight');

$randomUser = $users->random();
```

# 判断类

## contains

我认为 contains 是用来判断这个数组中是否包含最好用的方法，语义啥的都是最好的，没有之一了。

```
$users = User::all();
$isContain = $users->contains(function($user) {
    return $user->weight > 33;
});
```

# 查找过滤类

这一类的各个函数估计有很多都重复了。基本上掌握 where 系列的语句就能满足各种查找过滤需求了。如果再复杂一点的过滤查找逻辑，就使用filter。

```
$users = User::all();

$overWeightUsers = $users->where('weight', '>', 33);

$firstUser = $users->first();

$firstOverWeightUser = $users->firstWhere('weight', '>', 33);

$nameOkUsers = $users->filter(function($user) {
    return ucfirst($user->name) == 'Test4';
});
```

# 重组类

重组类的功能非常好用，这类方法用的好，会让你的代码优美很多。而且会享受到函数式编程的快感。

## map, reduce

这两个函数的使用就是和其他函数式语言的一样。

```
$sumShowWeight = $users->map(function($user) {
    return ['name' => $user->name, 'show_weight' => $user->weight + 10];
})->reduce(function($carry, $item) {
    return $carry + $item['show_weight'];
});
```

我这里获取所有用户经过计算之后的 show_weight 的 sum 值，这么一句就可以搞定了。

## transform

对集合内每个元素都进行操作从而重组一个新的集合：

```
$showUsers = $users->transform(function($user) {
    return ['name' => $user->name, 'show_weight' => $user->weight + 10];
});
```

## groupBy

groupBy也是很常用的，它不仅可以把所需要字段的唯一值获取出来，而且可以获取出每个唯一值有哪些分组。

```

$users = User::all();
$groupUsers = $users->groupBy('weight');

```

# 排序类

排序类一般和其他的函数一起使用。比如获取最轻的用户：

```
$users = User::all();
$lessWeightUser = $users->sortBy('weight')->first();
```

# 思考

illuminate/database 提供的 Collection 函数已经是非常灵活和强大了。如果一定要有一个标准判断你的代码写的好不好，那么一定是代码行数是否够少。而 Collection 使用的函数能让我们的代码行数大大压缩。全面系统思考下这些函数的使用场景是一个能让你的代码越来月优雅的方式。

# 参考文档
https://scotch.io/tutorials/laravel-collections-php-arrays-on-steroids
