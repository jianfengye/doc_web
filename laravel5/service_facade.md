# laravel5如何创建service provider和facade

laravel5创建一个facade，可以将某个service注册个门面，这样，使用的时候就不需要麻烦地use 了。文章用一个例子说明怎么创建service provider和 facade。

# 目标

我希望我创建一个AjaxResponse的facade，这样能直接在controller中这样使用：

```
class MechanicController extends Controller {
    
    public function getIndex()
    {
        \AjaxResponse::success();
    }
}
```

它的作用就是规范返回的格式为
```
{
    code: "0"
    result: {

    }
}
```

# 步骤

## 创建Service类

在app/Services文件夹中创建类

```
<?php namespace App\Services;

class AjaxResponse {

    protected function ajaxResponse($code, $message, $data = null)
    {
        $out = [
            'code' => $code,
            'message' => $message,
        ];

        if ($data !== null) {
            $out['result'] = $data;
        }

        return response()->json($out);
    }

    public function success($data = null)
    {
        $code = ResultCode::Success;
        return $this->ajaxResponse(0, '', $data);
    }

    public function fail($message, $extra = [])
    {
        return $this->ajaxResponse(1, $message, $extra);
    }
}
```

这个AjaxResponse是具体的实现类，下面我们要为这个类做一个provider

## 创建provider

在app/Providers文件夹中创建类

```
<?php namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AjaxResponseServiceProvider extends ServiceProvider {

    public function register()
    {
        $this->app->singleton('AjaxResponseService', function () {
            return new \App\Services\AjaxResponse();
        });
    }
}
```
这里我们在register的时候定义了这个Service名字为AjaxResponseService

下面我们再定义一个门脸facade

## 创建facade

在app/Facades文件夹中创建类

```
<?php namespace App\Facades;

use Illuminate\Support\Facades\Facade;

class AjaxResponseFacade extends Facade {

    protected static function getFacadeAccessor() { return 'AjaxResponseService'; }

}
```

## 修改配置文件

好了，下面我们只需要到app.php中挂载上这两个东东就可以了

```
<?php

return [

    ...

    'providers' => [
        ...
        'App\Providers\RouteServiceProvider',

        'App\Providers\AjaxResponseServiceProvider',

    ],


    'aliases' => [
        ...

        'Validator' => 'Illuminate\Support\Facades\Validator',
        'View'      => 'Illuminate\Support\Facades\View',

        'AjaxResponse' => 'App\Facades\AjaxResponseFacade',

    ],

];

```

# 总结

laravel5中使用facade还是较为容易的，基本和4没啥区别。