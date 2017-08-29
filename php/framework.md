# PHP框架谈[未完成]

估摸每个PHPer在职业生涯过程中接触的PHP框架不下10个（包括大版本改动的话）。时常有人会问到，CI和Yii哪个好，比较轻的PHP框架用哪个？在我看来，PHP框架就分两种，一种是追求运行性能，一种是追求开发效率。追求运行性能的框架大都用C来直接编写，因为没有什么比直接写C代码会更快的了，这类的框架比如yaf，phalcon等。还有一类框架以追求开发效率为主，往往赋予很强大的ORM和功能类，各种模版引擎等。为代表的可能就是laravel，symfony，yii等了。

# 关于PHP框架的重和轻

不站在中庸的角度，实话说，我在我经历过的项目中，90%的PHP项目是不会遇到语言性能瓶颈的，最多碰到的是，业务的性能瓶颈，但是那往往罪魁祸首并不是框架。更多的PHP业务的难点是在于业务逻辑上。

PHP业务的发展第一个要经受的考验是怎么用最简单的代码写出最复杂的逻辑，以及逻辑的变更。这点估计做业务的人深有体会。不管你代码逻辑封装得再好，总会有类似“把所有出现的真实姓名都用昵称替换”，或者“原先某个帖子每个人只能回复一个回答，换成每个人能回复多个回答”。这样的极具破坏力的需求。这个时候，我往往总是很懊悔，这个地方我为什么之前没想到封装呢？回到框架，所以，在这种考验面前，我认为，很多说的“简单”的框架，都败下阵来。好的框架应该是会引导你使用框架的特性，然后呢，框架在“可预估”的需求变化面前，让你发现，原来这个特性是考虑到了这个需求变化的，我只需要这样这样稍微改改就能达到这个需求了。所以，我不推崇所谓的“简单”框架。

PHP遇到的第二个所经受的考验就是性能，业务量上来以后，能否扛住压力？这里扛住压力我认为说的不只是单机扛住多少rps，而是一个整体扛请求量的概念。或者极端点说，能堆机器解决的问题就不是问题了。在这个阶段，只要不是代码写的太挫的框架，性能应该都差不了多少，在这个阶段更在意的是框架的可扩展性，是否支持数据库的平行扩展，是否支持web服务的增加等。这点感觉大部分框架都能够考虑到满足。

第三个需要经受的考验就是安全性了。当然安全性更多是在应用层的代码编写的时候要注意，但是框架层面应该也可以有很多值得做的事情的。

其实一个PHP框架最重要的是几个部分，代码结构，路由，ORM，模版引擎，配置文件，自动加载等。

# 关于composer

现在国外的PHP项目已经离不开composer了，甚至很多官网的第三方插件已经直接不提供代码下载链接了，只提供composer地址了。关于一个PHP项目是否需要使用composer的问题，我觉得毋庸质疑。composer是现在php包管理的大流，所以如果一个框架还不支持composer的话，我只能说，还不够符合潮流。

但是composer中的包也不尽然都是所谓的“优秀的包”。我认为既然有了composer这个包管理工具了，那么除了框架性的包之外，功能性的包应该功能单一化。比如guzzle包，是提供http请求的服务，在3.x的版本中，要下载这个包，竟然自动需要下载一个zend框架！好在现在6.x已经不依赖zend了。而且我认为guzzle包中也不应该要加载日志包，这个包实现的功能就是单一化的，只提供组建http请求的服务。其他的，应该由使用者决定是否要加载才对。

所以我认为，一个好的框架不能丢掉composer，但是所依赖的包却不应该太重，甚至于对于一些要依赖第三方包才能实现的功能，是不是直接考虑框架自己实现更好。

# 关于自动加载

这个其实没有什么好评估的了，psr-4已经将自动加载的功能做了一个很详细的标准，composer也自带支持了。只需要在框架的composer中夹带：

    "autoload": {
       "files": ["src/functions_include.php"],
       "psr-4": {
           "GuzzleHttp\\": "src/"
       }
    },

就可以支持自动加载了。差不多自动加载这块支持命名空间＋psr-4的自动寻址就很足够了。

# 关于路由

路由这块就很有的思考了。现在大部分的框架都是支持restful风格的请求的。然后做法也都如出一辙，将请求url做个rewrite映射到某个index.php。然后由这个index.php做url解析，再分配到具体的action中去。

这个方法没什么不好，其实就是将路由的功能下放给php了，如果按照“php本身就是最好的web框架”的观点来看，访问用户信息，请求就应该是/user/name.php，并且请求直接访问到/user/name.php中。也便没有了什么路由的概念了。

但是大部分的框架都是把路由的功能下放给PHP层面，这样做当然有好处，比如路由的修改就变的尤为方便了。但是对于路由配置的写法，各个主流框架又大有不同：

## Laravel:

* 在路由配置文件route.php中配置
* Route::{method}($path, $callback) 按照post方法和路径来定位
* Route::controller($controller)，然后controller对应的method使用方法名getA, postA来指示
* Route::resource($controller)统一定义controller中的增删改查的方法。

## Yii:

* 在路由配置文件中配置
* 配置数组
    array(
        'posts'=>'post/list',   // http://test.com/posts 对应postController的listAction
        'post/<id:\d+>'=>'post/read',
        'post/<year:\d{4}>/<title>'=>'post/read',
        '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
    )
* 提供一种默认的路由
    /index.php/post/read/id/100 // 对应postController的readAction，参数是id

## CI:

* 在路由配置文件中配置
* 配置数组

    $route['greet/hello'] = "greeting/helloword"; // 对应greetController的hellowordAction
    $route['greet/hi'] = "greeting/hiword";  // 对应greetController的hiwordAction
    $route['product/(:any)'] = "catalog/product_lookup";
    $route['product/(:num)'] = "catalog/product_lookup_by_id/$1";
    $route['products/([a-z]+)/(\d+)'] = "$1/id_$2";

* 提供保留路由
    $route['default_controller'] = "greeting";
    $route['404_override'] = '';

## ThinkPHP:

    Think\Route::get('New/:id','New/read'); // 定义GET请求路由规则
    Think\Route::post('New/:id','New/update'); // 定义POST请求路由规则
    Think\Route::put('New/:id','New/update'); // 定义PUT请求路由规则
    Think\Route::delete('New/:id','New/delete'); // 定义DELETE请求路由规则
    Think\Route::any('New/:id','New/read'); // 所有请求都支持的路由规则

基本现在比较热门的就是符合rest的url路由解析，所谓的rest风格的路由解析，基本就是需要HTTP METHOD + HTTP REQUEST_URI 定位一个controller的action。
把GET+/user/add 和 POST + /user/add 看作是一个请求并不是明智的。所以laravel和TP的这种路由定义比较符合这种需求的设计。
