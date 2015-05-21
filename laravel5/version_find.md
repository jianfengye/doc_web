# l5如何通过路由走api版本回退查找设置

# 具体需求

当前遇到的问题是使用laravel写接口，但是接口是有版本号的，我们把版本号放在url中，比如：

```
http://yejianfeng.com/api/user/info/?uid=1
http://yejianfeng.com/api1.1/user/info/?uid=1
http://yejianfeng.com/api1.2/user/info/?uid=1
```

但是实际上api1.1的user/info和api的user/info的action是一样的，但是api1.2的user/info是不一样的

本来路由应该这么写：

```
<?php
Route::group(array('prefix' => 'api'), function() {
    Route::get('/user/info', ['uses' => 'UserController@userinfo']);
});


Route::group(array('prefix' => 'api1.1'), function() {
    Route::get('/user/info', ['uses' => 'UserController@userinfo']);
});


Route::group(array('prefix' => 'api1.2'), function() {
    Route::get('/user/info', ['uses' => 'UserController@userinfo1_2']);
});

```

这个感觉还是丑了点，我不希望路由会这么复杂，我希望的是进行版本衰退寻找，api1.1中的user/info那个不需要写，它能自动去寻找api1.1中有没有这个路由，没有的话，去寻找比它版本低的路由。

# 解决方法

这里当然要使用到middleware，希望路由是：

```
<?php
Route::group(array('prefix' => 'api'), function() {
    Route::get('/user/info', ['uses' => 'UserController@userinfo']);
});


Route::group(array('prefix' => 'api1.1', 'middleware' => 'downgrade'), function() {
});


Route::group(array('prefix' => 'api1.2', 'middleware' => 'downgrade'), function() {
    Route::get('/user/info', ['uses' => 'UserController@userinfo1_2']);
});

```

但是非常可惜，这样写的话
```
http://yejianfeng.com/api1.1/user/info/?uid=1
```
是进不了middleware的。

我们需要的是有个“匹配所有”的路由能将路由定位定到prefix 1.1的这个里面

所以改成这样：

```
<?php
Route::group(array('prefix' => 'api'), function() {
    Route::get('/user/info', ['uses' => 'UserController@userinfo']);
});


Route::group(array('prefix' => 'api1.1', 'middleware' => 'downgrade'), function() {
    Route::any('/{c}/{a}', function(){});
});


Route::group(array('prefix' => 'api1.2', 'middleware' => 'downgrade'), function() {

    Route::get('/user/info', ['uses' => 'UserController@userinfo1_2']);

    Route::any('/{c}/{a}', function(){});
});

```

这里就能将所有的/{version}/{controller}/{action}这样的请求经过downgrade中间件了。

但是中间件怎么写呢？

＃ downgrade中间件的编写

```
<?php namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Routing\Middleware;


class DownGradeMiddleware implements Middleware {

    public function handle($request, Closure $next)
    {
        $routeAction = $request->route()->getAction();
        $routes = \Route::getRoutes()->getRoutes();
        
        $requestUri = $_SERVER['REQUEST_URI'];
        $querys = explode('?', $requestUri);
        $queryPath = trim($querys[0], '/');
        $querySecs = explode('/', $queryPath);

        // 没有对应的，进行api版本回找
        $versions = ['api', 'api1.1', 'api1.2'];

        $apiversion = $querySecs[0];
        $key = array_search($apiversion, $versions);
        while (1) {
            if ($key < 0) {
                break;
            }
            $querySecs[0] = $versions[$key];
            $queryPath = trim(implode('/', $querySecs), '/');

            foreach ($routes as $route) {
                if ($route->getUri() == $queryPath) {
                    $action = $route->getAction();
                    $routeAction['uses'] = $action['uses'];
                    $request->route()->setAction($routeAction);
                    return $next($request);
                }
            }

            $key--;
        }

        $response = $next($request);

        return $response;
    }
}
```

这里最重要的点就是将$routeAction的uses字段修改之后，调用
```
$request->route()->setAction($routeAction);
```
就可以修改路由对应的action了

其他的就是业务逻辑的问题了。

至于如何挂载middleware，可以参考[laravel文档:路由](http://laravel.com/docs/master/middleware)进行挂载

# 总结

laravel4把匹配全路由的函数去掉了，但是其实使用中间件＋any("{a}/{b}/{c}") 的方法也可以近似实现一个这样的功能的。

so，总是有路通向罗马的。