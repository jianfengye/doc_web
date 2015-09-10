# php常量[未发布]

php的常量规则：

- 以字母或者下划线开始，后面跟着字母，数字，下划线。
- 常量的值不能有任何操作，不能包含任何变量等。

php的常量有几种类型：

## 普通常量

普通常量就是使用define来进行的常量设置。比如

	define("FOO",     "something");
	define("FOO2",    "something else");
	define("FOO_BAR", "something more");

在5.3之后，普通常量也支持通过const来进行设置

	<?php
	const FOO = "something";

	echo FOO;

## 类常量

类常量是使用const在类的内部进行设置。

这种常量可以使用类名，也可以使用实例化来获取。

	<?php

	class A
	{
	        const con = 'Acon';
	}

	echo A::con;

	$a = new A();
	echo $a::con;


## 魔术常量

php的魔术常量实际上不是常量，而是一种预定义常量。

有8个魔术常量

常量        | 意义
------------- |--------------
\_\_LINE\_\_ | 文件中的当前行号
\_\_FILE\_\_ | 文件的完整路径和文件名
\_\_DIR\_\_ | 文件所在的目录
\_\_FUNCTION\_\_ | 函数名字
\_\_CLASS\_\_ | 类名
\_\_TRAIT\_\_ | Trait的名字，Trait的概念是5.4之后才加上的，所以这个魔术常量也是5.4之后才有的。
\_\_METHOD\_\_ | 类的方法名
\_\_NAMESPACE\_\_ | 当前命名空间名称，命名空间是5.3之后加上的，所以这个魔术常量是5.3之后才有的。

## 预定义常量

在php的内核中有一些是[预定义常量](http://www.php.net/manual/zh/reserved.constants.php)。

经常使用的一些预定义常量有：

常量        | 意义
------------- |--------------
PHP_VERSION | PHP的版本
PHP_OS | 当前运行php的操作系统（win/linux）
PHP_SAPI | 当前运行php的API环境（cli/cgi）
PHP_EOL | 换行
DEFAULT_INCLUDE_PATH | 默认的include路径
PHP_EXTENSION_DIR  | PHP扩展地址
