# Laravel5做权限管理

# 关于权限管理的思考

最近用laravel设计后台，后台需要有个权限管理。权限管理实质上分为两个部分，首先是认证，然后是权限。认证部分非常好做，就是管理员登录，记录session。这个laravel中也有自带Auth来实现这个。最麻烦就是权限认证。

权限认证本质上就是谁有权限管理什么东西。这里有两个方面的维度，谁，就是用户维度，在用户维度，权限管理的粒度可以是用户一个人，也可以是将用户分组，如果将用户分组，则涉及到的逻辑是一个用户可以在多个组里面吗？在另外一方面，管理什么东西，这个东西是物的维度，一个页面是一个东西，一个页面上的一个元素也是一个东西，或者往大了说，一个功能是一个东西。所以做权限管理最重要的是确认这两个维度的粒度。这个已经不是技术的事情了，这个是需要需求讨论的了。

基于上面的思考，我这次想做的权限管理，在用户维度，是基于个人的。就是每个人的权限不一样。在东西的维度，我设置路由为最小的单位，即可以为单个路由设置权限管理。

下面的思考就是使用什么来标记权限，可以使用位，也可以使用字符，也可以使用整型。后来我选择了字符，基于两点考虑：1 字符浅显易懂，在数据库中查找也比较方便 2 我没有按照某个权限查找有这个权限的人的需求，即没有反查需求，使用位，整型等都意义不大。

接下来考虑如何和laravel结合，既然要为每个路由设置访问权限，那么我当然希望能在laravel的route.php路由管理中配置。最好就是在Route::get的时候有个参数能设置permission。这样做的好处是权限设置简易了。在决定路由的时候，就顺手写了权限控制。坏处呢，也很明显，laravel路由的三种方式只能写一种了。就是Route::(method)这样的方式了。

基本决定好了就开干。

# 路由设计

基本的路由是这样的

    Route::post('/admin/validate', ['uses' => 'AdminController@postValidate', 'permissions'=>['admin.validate', 'admin.index']]);

这里在基本的制定路由action之后设置了一个permissions的属性，这个属性设计成数组，因为比如一个post请求，它可能在某个页面会触发，也可能在另外一个页面触发，那么这个post请求就需要同时拥有两个页面路由的权限。

这里使用admin.validate的权限控制，这样，可以将权限分组，admin都是关于admin相关的分组，在数据库中，我就会存储一个二维数组，[admin] => ['validate', 'index']; 存储成二维数组而不是一维的好处呢，一般后台展示是有两个维度的，一个是头部的tab栏，一个是左边的nav栏，就是说这个二维的数组和后台的tab,nav栏是一一对应的。

# 中间件设计

好了，下面我们就挂上中间件，并且设置所有的路由都走这个中间件

    <?php namespace App\Http\Middleware;

    use Illuminate\Support\Facades\Session;
    use Closure;

    class Permission {

        /**
         * Handle an incoming request.
         *
         * @param  \Illuminate\Http\Request  $request
         * @param  \Closure  $next
         * @return mixed
         */
        public function handle($request, Closure $next)
        {
            $permits = $this->getPermission($request);

            $admin = \App\Http\Middleware\Authenticate::getAuthUser();

            // 只要有一个有权限，就可以进入这个请求
            foreach ($permits as $permit) {
                if ($permit == '*') {
                    return $next($request);
                }
                if ($admin->hasPermission($permit)) {
                    return $next($request);
                }
            }

            echo "没有权限，请联系管理员";exit;
        }

        // 获取当前路由需要的权限
        public  function getPermission($request)
        {
            $actions = $request->route()->getAction();
            if (empty($actions['permissions'])) {
                echo "路由没有设置权限";exit;
            }
            return $actions['permissions'];
        }
    }

这里最关键的就getPermission函数，从$request->route()->getAction()来获取出这个路由的action定义，然后从其中的permissions字段中获取route.php中定义的路由权限。

然后上面的middleware有个$admin->hasPermission($permit); 这个就涉及到model的设计。

# model设计

    <?php namespace App\Models\Admin;

    use App\Models\Model as BaseModel;

    class Admin extends BaseModel {

        protected $table = 'admin';

        // 判断是否有某个权限
        public function hasPermission($permission)
        {
            $permission_db = $this->permissions;
            if(in_array($permission, $permission_db)) {
                return true;
            }

            return false;
        }

        // permission 是一个二维数组
        public function getPermissionsAttribute($value)
        {
            if (empty($value)) {
                return [];
            }
            $data = json_decode($value, true);
            $ret = [];
            foreach ($data as $key => $value) {
                $ret[] = $key;
                foreach ($value as $value2) {
                    $ret[] = "{$key}.{$value2}";
                }
            }
            return array_unique($ret);
        }

        // 全局设置permission
        public function setPermissionsAttribute($value)
        {
            $ret = [];
            foreach ($value as $item) {
                $keys = explode('.', $item);
                if (count($keys) != 2) {
                    continue;
                }
                $ret[$keys[0]][] = $keys[1];
            }

            $this->attributes['permissions'] = json_encode($ret);
        }
    }

在数据库中，我将二维数组存储为json，利用laravel的Attribute的get和set方法，完成了数据库中json和外界程序逻辑的连接。然后hasPermission就显得很轻松了，直接判断in_array就ok了。

# 后续

这个权限认证的逻辑就清晰了。然后如果页面中某个tab或者nav需要对不同权限的用户展示，只需要在view中判断

    @if ($admin->hasPermission('admin.index')) 
    @endif

就可以判断这个用户是否可以看到这个tab了。

# 总结

这个是一个不算复杂的用户权限实现，但是我感觉已经能满足大部分的后台需求了。当然可以优化的点可能很多，
比如permission是不是可以支持正则，hasPermission如果存储在nosql或者pg中，是不是不用进行json的数据解析，直接一个DB请求就能判断是否有permission之类的？
