# Laravel5设计json api时候的一些道道

# 对于返回数据格式没规整的问题

在开发api的时候，这个问题是和客户端交涉最多的问题，比如一个user结构，返回的字段原本是个user_name的，它应该是string类型。但是呢，由于数据库设计这个字段的时候允许为null，那么这个字段获取回来，就可能返回null，这个对于弱类型语言是没什么问题的，但是对于强类型的语言，可能就要增加字符的类型判断了。

或者是数据库中的text字段，里面存放的是json数据，现在取出来的时候我要做一些转换等。

所以对这个问题，我们会想到做一个类的格式方法。这个方法自然就是写在model中，在laravel5中，我使用这种方法：

model中：

```
class Inbox extends Model {

    protected $table = 'inbox';

    public static function format()
    {
        return function($item) {
            $tmp = $item->toArray();
            unset($tmp['deleted_at']);
            $tmp['sort'] = $tmp['id'];
            $tmp['uid'] = $tmp['sender'];
            $content = json_decode($item->content, true);
            $tmp['content'] = json_decode($content['content'], true);

            return $tmp;
        };
    }

    public static function formatRaw()
    {
        return function($item) {
            $tmp = $item->toArray();
            unset($tmp['deleted_at']);
            $tmp['sort'] = $tmp['id'];
            $tmp['uid'] = $tmp['sender'];

            return $tmp;
        };
    }
}
```

这里的Inbox设计了两种返回格式，每种返回格式都是返回的闭包函数，然后在controller中

```
$builder = Inbox::where('receiver', $uid)->where('sender', 0);

if ($max) {
    $builder->where('id', '<', $max);
}
$inboxs = $builder->orderBy('id', 'desc')->get();
$data = $inboxs->transform(Inbox::formatRaw());

```
好了，这样返回的$data就是进行结构化好的数据了。

这里主要想说laravel的Collection中的transfrom函数太好用了，参数是一个闭包，然后这个闭包封装在model中，好处是一旦客户端想要修改或者更新某个输出字段，我们就可以只要修改format函数就行。

# 对于返回外带外键的问题

比如上面一个例子中，往往inbox有个uid，接口需要它返回user的信息。那么这个时候，model的foreign key就起到很好的作用

```
class Inbox extends Model {

    protected $table = 'inbox';

    public function user()
    {
        return $this->hasOne('User', 'id', 'uid');
    }

    public static function format()
    {
        return function($item) {
            $user = $item->user;
            $tmp = $item->toArray();
            unset($tmp['deleted_at']);
            $tmp['sort'] = $tmp['id'];
            $tmp['uid'] = $tmp['sender'];
            $content = json_decode($item->content, true);
            $tmp['content'] = json_decode($content['content'], true);
            $tmp['user'] = $user;
            return $tmp;
        };
    }
}
```

但是这里切记别让每个数据循环获取user，我们应该显示地使用load或者with:
```
$builder = Inbox::where('receiver', $uid)->where('sender', 0);

if ($max) {
    $builder->where('id', '<', $max);
}
$inboxs = $builder->orderBy('id', 'desc')->get();
$inboxs->load('user');
$data = $inboxs->transform(Inbox::formatRaw());

```

# 总结

基本将这样两个问题解决，设计好orm，使用laravel来写接口就很快了。