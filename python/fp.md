# 函数式编程

函数式编程（Functional Programming）之前都只是听说过，没有使用过所谓的函数式编程思想。不大理解这个概念。最近弄python的时候遇到了这个概念。

函数式编程对应的是命令式编程（imperative programming）。我们平时写的程序大都属于这种编程方式：如果a大于b，就执行这个操作，否则的话，就执行这样的操作。我们一直研究的面向对象的编程也便是属于命令式编程的一种。

函数式编程最重要的是一切都是函数。这里的一切包括什么？函数可以是变量么？可以。函数可以是返回值么？可以。

# 高阶函数

如果函数是变量，那么这个把函数当作变量的函数就是高阶函数。比如python中的这个函数：
```
#!/usr/bin/python
#coding:utf-8

def g(f, a, b):
    return f(a, b)

def add(a, b):
    return a + b

val = g(add, 1, 2)
print val
```
其中g这个函数它就是高阶函数，他有一个参数为f。

在python中，高阶函数的代表就是map和reduce。比如可以看下下面的例子：
```
#!/usr/bin/python
#coding:utf-8

def f(a):
    return a * a

def add(a, b):
    return a + b

val1 = map(f, [1,2])
val2 = reduce(add, val1)
print val2

```
在上面例子里面，map和reduce都是高阶函数，它们的第一个参数传递进去的是函数。但是当然上面的例子写的有点low，一点都不python，一般来说，我们使用匿名函数lambda来替换传递进来的参数。

```
#!/usr/bin/python
#coding:utf-8

val1 = map(lambda x: x*x, [1,2])
val2 = reduce(lambda x,y: x+y, val1)
print val2

```
这里引申的匿名函数的意思就很明显了。它就是函数的扩展。

# 闭包

高阶函数除了可以接受函数作为参数之外，还可以把函数作为返回值返回。

```
#!/usr/bin/python
#coding:utf-8

def multi(a, b):
    def f():
        return a * a + b * b
    return f

val = multi(1, 2)
print val()

```

把函数作为返回值，上面的例子在multi的时候其实并没有执行对应的计算，而是在print的时候才执行计算，这个是一种延迟行为。我们称之为惰性求职（lazy evaluation）。

我们看到，在multi函数定义的a和b这两个局部变量，并不随着multi调用而销毁，在print这行，才具体执行的时候，a=1和b=2 还继续存在着。这就是“闭包”的威力。

# 函数式编程

回归到函数式编程，虽然说函数式编程是一种思维，和语言无关。但是只有实现了上述匿名函数，函数闭包等功能的语言才有函数式编程的能力。它最本质的观点是，所有的编程思想都是以函数为基本思想。

首先，函数式编程认为每个函数都是封闭的，不依赖外部的数据，也不改变外部的数据。比如我们常用的OO里面的方法，函数式编程就觉得不合理。
```
class A {
    public $isAuth = true;

    public isAuth(){
        return $this->isAuth;
    }
}
```

这里面的isAuth依赖外部的变量$isAuth，所以它是不符合函数式编程的函数封闭原理的。

其次，函数像变量一样使用，这一点我们上面的部分已经说明清楚了。经常说的一句话是，函数是一等公民。

比如像很多算法里面使用的递归算法，就算是函数式思维和非函数式思维的区别。比如计算斐波那契数列，从函数式思维开始思考，我们很容易思考到递归算法。而从其它思维出发，我们常常考虑的就是非递归解法。
```
public static long fib(int n)
{
  if(n <= 1)
     return n;
  else
      return fib(n-1) + fib(n-2);
}
```
从这个例子我们可以看出，使用函数式思想的一个好处，非常符合我们人类直观的理解。我们是要告诉程序要去做什么，而不是怎么去做。

# 参考

http://yangcongchufang.com/%E9%AB%98%E7%BA%A7python%E7%BC%96%E7%A8%8B%E5%9F%BA%E7%A1%80/python-functional.html#dir2

https://www.zhihu.com/question/28292740

https://www.ibm.com/developerworks/cn/linux/l-cn-closure/index.html

http://coolshell.cn/articles/10822.html
