# 路由

PHP中的路由几个框架使用的方式都是不一样的。

Laravel:

1 在路由配置文件route.php中配置
2 Route::{method}($path, $callback) 按照post方法和路径来定位
3 Route::controller($controller)，然后controller对应的method使用方法名getA, postA来指示
4 Route::resource($controller)统一定义controller中的增删改查的方法。

Yii:

1 在路由配置文件中配置
2 配置数组
    array(
        'posts'=>'post/list',   // http://test.com/posts 对应postController的listAction
        'post/<id:\d+>'=>'post/read',
        'post/<year:\d{4}>/<title>'=>'post/read',
        '<controller:\w+>/<action:\w+>' => '<controller>/<action>',
    )
3 提供一种默认的路由
    /index.php/post/read/id/100 // 对应postController的readAction，参数是id

CI:

1 在路由配置文件中配置
2 配置数组

    $route['greet/hello'] = "greeting/helloword"; // 对应greetController的hellowordAction
    $route['greet/hi'] = "greeting/hiword";  // 对应greetController的hiwordAction
    $route['product/(:any)'] = "catalog/product_lookup";
    $route['product/(:num)'] = "catalog/product_lookup_by_id/$1";
    $route['products/([a-z]+)/(\d+)'] = "$1/id_$2";

3 提供保留路由
    $route['default_controller'] = "greeting";
    $route['404_override'] = '';

ThinkPHP:

    Think\Route::get('New/:id','New/read'); // 定义GET请求路由规则
    Think\Route::post('New/:id','New/update'); // 定义POST请求路由规则
    Think\Route::put('New/:id','New/update'); // 定义PUT请求路由规则
    Think\Route::delete('New/:id','New/delete'); // 定义DELETE请求路由规则
    Think\Route::any('New/:id','New/read'); // 所有请求都支持的路由规则

基本现在比较热门的就是符合rest的url路由解析，所谓的rest风格的路由解析，基本就是需要HTTP METHOD + HTTP REQUEST_URI 定位一个controller的action。
所以laravel和TP的这种路由定义比较符合这种需求的设计。

