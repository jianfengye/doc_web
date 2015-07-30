# PHP 中的Closure

Closure，匿名函数，又称为Anonymous functions，是php5.3的时候引入的。匿名函数就是没有定义名字的函数。这点牢牢记住就能理解匿名函数的定义了。

比如下面的代码
```
function test() {
    return 100;
};

function testClosure(Closure $callback)
{
    return $callback();
}

$a = testClosure(test());
print_r($a);exit;

```

这里的test()永远没有办法用来作为testClosure的参数，因为它并不是“匿名”函数。

所以应该改成这样：

```
$f = function () {
    return 100;
};

function testClosure(Closure $callback)
{
    return $callback();
}

$a = testClosure($f);
print_r($a);exit;

```

好，如果要调用一个类里面的匿名函数呢？
```
class C {
    public static function testC() {
        return function($i) {
            return $i+100;
        };
    }
}

$f = function ($i) {
    return $i + 100;
};

function testClosure(Closure $callback)
{
    return $callback(13);
}

$a = testClosure(C::testC());
print_r($a);exit;
```

应该这么写，其中的C::testC()返回的是一个funciton。

# 绑定的概念

上面的例子的Closure只是全局的的匿名函数，好了，我现在想指定一个类有一个匿名函数。也可以理解说，这个匿名函数的访问范围不再是全局的了，是一个类的访问范围。

那么我们就需要将“一个匿名函数绑定到一个类中”。

```
<?php

class A {

    public $base = 100;

}

class B {

    private $base = 1000;
}

$f = function () {
    return $this->base + 3;
};


$a = Closure::bind($f, new A);
print_r($a());

echo PHP_EOL;

$b = Closure::bind($f, new B , 'B');
print_r($b());

echo PHP_EOL;
```

上面的例子中，$f这个匿名函数中莫名奇妙的有个$this,这个this关键词就是说明这个匿名函数是需要绑定在类中的。

绑定之后，就好像A中有这么个函数一样，但是这个函数是public还是private，bind的最后一个参数就说明了这个函数的可调用范围。

对于bindTo，看例子：

```
<?php

class A {

    public $base = 100;

}

class B {

    private $base = 1000;
}

class C {

    private static $base = 10000;
}

$f = function () {
    return $this->base + 3;
};

$sf = static function() {
    return self::$base + 3;
};


$a = Closure::bind($f, new A);
print_r($a());

echo PHP_EOL;

$b = Closure::bind($f, new B , 'B');
print_r($b());

echo PHP_EOL;

$c = $sf->bindTo(null, 'C');
print_r($c());

echo PHP_EOL;

```


