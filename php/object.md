# php类[未发布]

php的[类与对象](http://www.php.net/manual/zh/language.oop5.php)是php5.0之后才引进的。

## 获取类名有哪些方法？

获取一个类的类名分为两种情况，一种在类内部获取类名，一种是在类外部获取类名。

主要方法有三种：

- \_\_CLASS\_\_ 魔术常量
- get_class() 预定义方法
- ::class PHP5.5之后才能使用

直接上例子：

	<?php

	class A {
		function className1() {
			echo __CLASS__ , PHP_EOL;
		}

		function className2() {
			echo get_class($this) . PHP_EOL;
		}
	}

	$a = new A();
	$a->className1();
	$a->className2();

	echo get_class($a) . PHP_EOL;
	echo A::class . PHP_EOL;

## 自动加载有哪些方法？

php中的自动加载类有两种方法，\_\_autoload和spl_autoload，[说说PHP的autoLoad](http://www.cnblogs.com/yjf512/archive/2012/09/27/2705260.html)说的很详细了。

### 为什么还要有spl_autoload呢？

\_\_autoload的最大缺陷是无法有多个autoload方法

好了， 想下下面的这个情景，你的项目引用了别人的一个项目，你的项目中有一个\_\_autoload，别人的项目也有一个\_\_autoload,这样两个\_\_autoload就冲突了。解决的办法就是修改\_\_autoload成为一个，这无疑是非常繁琐的。

因此我们急需使用一个autoload调用堆栈，这样spl的autoload系列函数就出现了。你可以使用spl_autoload_register注册多个自定义的autoload函数。

spl_autoload要求你的PHP版本大于5.1。

\_\_autoload的使用：

	<?php

	function __autoload($class_name) {
	    $path = str_replace('_', '/', $class_name);
	    require $path . '.php';
	}

	// 这里会自动加载Http/File/Interface.php 文件

	$a = new Http_File_Interface();

spl_autoload的使用：

	/*http.php*/
	<?php
	class http
	{
	    public function callname(){
	        echo "this is http";
	    }
	}

	/*test.php*/
	<?php
	spl_autoload_register(function($class){
	    if($class == 'http'){
	        require_once("/home/yejianfeng/handcode/http.php");
	    }
	});

	$a = new http();
	$a->callname();

## [trait](http://www.php.net/manual/zh/language.oop5.traits.php)是什么，怎么用？

首先，明白下PHP的类的继承是单继承的，单继承就意味着类只能继承一个类。那么实际上，又确实有需要类继承多个类的方法，怎么办呢？PHP5.4就引入了traits，来实现代码复用。

traits和use一起使用，能达到代码复用的目的，但是这个时候优先级就需要注意了。traits的优先级比继承高。

	<?php
	class Base {
	    public function sayHello() {
	        echo 'Hello ';
	    }
	}

	trait SayWorld {
	    public function sayHello() {
	        parent::sayHello();
	        echo 'World!';
	    }
	}

	class MyHelloWorld extends Base {
	    use SayWorld;
	}

	$o = new MyHelloWorld();
	$o->sayHello();
	?>

## 魔术方法有哪些？

魔术方法        | 意义
------------- |--------------
\_\_construct() | 构造函数
\_\_destruct() | 析构函数
\_\_call(string $name , array $arguments) | 对象中调用不可访问方法
\_\_callStatic(string $name , array $arguments) | 静态方式中调用不可访问方法
\_\_get(string $name) | 读取不可访问属性的值
\_\_set(string $name , mixed $value) | 给不可访问属性赋值
\_\_isset(string $name) | 当对不可访问属性调用 isset() 或 empty() 时，会被调用。
\_\_unset(string $name) | 当对不可访问属性调用 unset() 时，会被调用。
\_\_sleep() | serialize() 函数会检查类中是否存在一个魔术方法 \_\_sleep()。如果存在，该方法会先被调用，然后才执行序列化操作。
\_\_wakeup() | unserialize() 会检查是否存在一个 \_\_wakeup() 方法。如果存在，则会先调用 __wakeup 方法，预先准备对象需要的资源
\_\_toString() | 该方法用于一个类被当成字符串时应怎样回应。
\_\_invoke() | 当尝试以调用函数的方式调用一个对象时，\_\_invoke() 方法会被自动调用。
\_\_set_state( array $properties ) | 自 PHP 5.1.0 起当调用 var_export() 导出类时，此静态 方法会被调用。
\_\_clone() | 如果定义了 \_\_clone() 方法，则新创建的对象（复制生成的对象）中的 \_\_clone() 方法会被调用，可用于修改属性的值

## 后期静态绑定是什么意思？

从php5.3.0开始，PHP增加了后期静态绑定的功能，用于在继承范围内引用静态调用的类。它使用预留关键字static::

	<?php
	class A {
	    public static function who() {
	        echo __CLASS__;
	    }
	    public static function test() {
	        static::who(); // 后期静态绑定从这里开始
	    }
	}

	class B extends A {
	    public static function who() {
	        echo __CLASS__;
	    }
	}

	B::test();  //输出B
	?>

## 对象的克隆是什么意思？

在php中，对象使用等号赋值给一个变量，并不是拷贝了对象，而是增加了一个对象的引用而已。要对对象进行拷贝的话，需要使用clone。

	<?php
	class A {
	    public $prop = "testA";
	}


	$a = new A();
	echo $a->prop, PHP_EOL;

	$b = $a;
	$b->prop = "testB";

	echo $a->prop, PHP_EOL;
	echo $b->prop, PHP_EOL;

	$b = clone $a;
	$b->prop = "testC";

	echo $a->prop, PHP_EOL;
	echo $b->prop, PHP_EOL;

	/*
	testA
	testB
	testB
	testB
	testC
	 */

如果需要克隆的时候做任何操作，则可以在类中定义魔术方法“\_\_clone()”
