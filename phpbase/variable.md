# php变量

php变量的规则有：

- 一个有效的变量名由字母或者下划线开头，后面跟上任意数量的字母，数字，或者下划线
- $this 是一个特殊的变量，它不能被赋值。
- 未初始化的变量会设置默认值。
- 变量的作用域是局部优于全局。

php的变量有几种类型：

## 普通变量

普通变量设置如下：

	$a = 1;	

## 类变量

	class A {
		public $var1 = 1;	//类公共属性
		protected $var2 = 2; //类继承属性
		private $var3 = 3;  //类私有属性

		public static $var4 = 4; //类静态属性
	}

## 预定义变量

PHP提供了大量的预定义变量这些变量将所有的外部变量转化成内部环境变量。

- $GLOBALS — 引用全局作用域中可用的全部变量
- $_SERVER — 服务器和执行环境信息
- $_GET — HTTP GET变量
- $_POST — HTTP POST 变量
- $_FILES — HTTP 文件上传变量
- $_REQUEST — HTTP Request 变量
- $_SESSION — Session 变量
- $_ENV — 环境变量
- $_COOKIE — HTTP Cookies
- $php_errormsg — 前一个错误信息
- $HTTP_RAW_POST_DATA — 原生POST数据
- $http_response_header — HTTP 响应头
- $argc — 传递给脚本的参数数目
- $argv — 传递给脚本的参数数组

### $_GET,$_POST,$_COOKIE和$_REQUEST的关系？

默认情况下$_REQUEST包含了$_GET,$_POST,和$_COOKIE的数组。这个数组的项目以及顺序是依照php.ini中的[request_order](http://www.php.net/manual/zh/ini.core.php#ini.request-order)来做设置的。

