# scala语法学习

scala在语言地位上算是由spark提带起来的。在spark生态中，scala还是首选的语言。最近也有项目在使用scala，所以需要学习。

# scala 的IDE使用

我使用的是Eclipse的Scala来写scala代码。http://scala-ide.org/ 它的安装就很容易了。

scala-ide有两种模式可以写scala代码

## worksheet

一种是worksheet，在scala的worksheet中，我们可以实现保存执行，即我们写一部分代码，保存的时候就显示了结果。这种环境对学习scala和测试的情景是非常好用的。

在项目中，new -- scala worksheet，worksheet自动会创建以sc为后缀的文件，并且创建一个object，在object中定义的scala语言可以立即在右侧看到执行效果。

## scala app

可以执行的叫做scala app，在项目中，new -- scala app 创建一个object，继承自App，则这个app就是可以执行的。

## 变量

scala有三种变量：

* val
* var
* lazy val

这三种变量类型中的val是代表后面的变量是不可变变量，var代表的是可变变量，lazy val则这个变量在定义的时候不会求值，在第一次使用的时候才会求值。

看下面的worksheet的示例：
```
object worksheet {
  val x = 10                                      //> x  : Int = 10

	//x = 11

	val y:Int = 10                            //> y  : Int = 10

	var x1 = 1                                //> x1  : Int = 1

	x1 = 12

	x1                                        //> res0: Int = 12

	lazy val z = x1 * x1                      //> z: => Int

	val ret = z + 1                           //> ret  : Int = 145
}
```

# scala的数据类型

在scala中，所有的事物都是对象。

![](http://tuchuang.funaio.cn/17-9-7/45158167.jpg)

这张图是scala所有数据类型的分类。

Any是scala所有类的父类，Any由派生为AnyVal和AnyRef两种类型，AnyVal代表所有值类型，AnyRef代表所有引用类型。

所有java或者scala的引用类型的父类都是AnyRef。

Null是所有引用类型的子类。

Nothing是所有类型的子类。

## 所有的值类型：

![](http://tuchuang.funaio.cn/17-9-7/66201545.jpg)

低精度类型往高精度数值类型转换的时候不需要额外定义，这点和java一样。

## unit

Scala的Unit就相当于Java中的void，主要不同的是Scala可以有一个Unit类型的值()。

Unit一般是用在返回值的定义上。

## Nothing, Null

一般抛出异常，函数的返回值就是Nothing。 我们只要判断函数的返回值就知道函数是否返回异常了。
