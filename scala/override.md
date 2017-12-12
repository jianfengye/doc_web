# Scala中的override

override是覆盖的意思，在很多语言中都有，在scala中，override是非常常见的，在类继承方面，它和java不一样，不是可写可不写的了，而是必须写的。如果不写而覆盖了对应的属性或者方法的话，编译器就会报错了。今天把scala中的override的各种地方都整理了一遍，以方便以后翻阅。

# 基础用法
```
/*
基本的override特性
*/
class A {
  val nameVal = "A"
  var nameVar = "A"

  def foo: String = {
    "A.foo"
  }
}

class B extends A {
  override val nameVal = "B"
  //override var nameVar = "B"  "variable nameVar cannot override a mutable variable"
  override def foo: String = {
    "B.foo"
  }
}

val b1 = new B
b1.foo
b1.nameVal
b1.nameVar

val b2 : A = new B
b2.foo
b2.nameVal
b2.nameVar = "B"
b2.nameVar


输出：


defined class A


defined class B


b1: B = B@9825fab
res0: String = B.foo
res1: String = B
res2: String = A

b2: A = B@c46c4a1
res3: String = B.foo
res4: String = B
b2.nameVar: String = B
res5: String = B
```
当一个类extends另外一个类的时候，override的规则基本如下：

* 子类中的方法要覆盖父类中的方法，必须写override（参见foo）
* 子类中的属性val要覆盖父类中的属性，必须写override（参见nameVal）
* 父类中的变量不可以覆盖（参见nameVar）

# 在抽象类中可以不用写override
```
/*
trait的extent不需要override
*/
trait T {
  def foo : String
  def bar : String
}

class TB extends T {
  def foo: String = {
    "TB.foo"
  }

  def bar: String = "TB.bar"
}

val tb = new TB
tb.foo
tb.bar


trait TT  extends T {
  def bar :String = "TT.bar"
}

class TTB extends TT {
  def foo: String = "TTB.foo"
}
val ttb = new TTB
ttb.foo
ttb.bar

输出：

defined trait T


defined class TB


tb: TB = TB@2fb497ea
res6: String = TB.foo
res7: String = TB.bar


defined trait TT



defined class TTB



ttb: TTB = TTB@346c06af
res8: String = TTB.foo
res9: String = TT.bar
```
T是特性类，它定义了两个抽象方法，foo和bar。TB的类继承和实现了T特性类，这个时候，TB类中的foo和bar前面的override是可写可不写的。这里初步看下TB类中的foo和bar前面的override写和不写感觉都一样，但是一旦有钻石结构的类继承，这个override的作用就体现出来了。这个我们后续说。

TT和TTB的例子也是说明了下trait继承trait是不需要使用override的。

# abstrct class 也不需要使用override
```
/*
abstrct class 不需要override
*/
abstract class PA(name: String) {
  def hello: String
}

class PB(name: String) extends PA(name) {
  def hello : String = s"hello ${name}"
}

val pb = new PB("yejianfeng")
pb.hello

输出：

defined class PA

defined class PB

pb: PB = PB@62840167
res10: String = hello yejianfeng

abstract class和trait的特性主要是在是否有构造参数，在override方面都是一样的。
```
# 钻石结构

所谓的钻石结构就是一个菱形的结构，一个基类，两个子类，最后一个类又继承这两个子类。那么如果这两个子类都包含一个基类的方法，那么最后的这个类也有这个方法，选择继承那个子类呢？
```
/*
钻石结构
*/
trait Animal {
  def talk: String
}

trait Cat extends Animal {
  def talk: String = "I am Cat"
}

trait Monkey extends Animal {
  def talk: String = "I am monkey"
}

trait Dog extends Animal {
  override def talk: String = "I am Dog"
}

val kittyDog = new Cat with Dog
kittyDog.talk

class MonkeyCat extends Monkey with Cat {
  override def talk: String = "I am monkeyCat"
}

val monkeyCat = new MonkeyCat
monkeyCat.talk


输出：

defined trait Animal



defined trait Cat



defined trait Monkey



defined trait Dog



kittyDog: Cat with Dog = $anon$1@5378ef6d
res11: String = I am Dog

defined class MonkeyCat



monkeyCat: MonkeyCat = MonkeyCat@1e444ce6
res12: String = I am monkeyCat

```
在这个例子中，Animal是基类，Cat和Dog是子类，kittyDog是继承了Cat和Dog，那么kittyDog里面的talk使用的是Cat和Dog中有标示override的那个方法。这个时候override的作用就体现出来了。

# 参数复写使用override

我们可以直接在构造函数里面使用override重写父类中的一个属性。我理解这个更多是语法糖的一个功能。
```
/*
参数复写
*/
class Person(val age : Int){
  val name = "no name"
}

class XiaoMing(age: Int, override val name: String) extends Person(age){

}
val xiaoming = new XiaoMing(12, "xiaoming")
xiaoming.name


输出：
defined class Person



defined class XiaoMing


xiaoming: XiaoMing = XiaoMing@2eef0f3c
res13: String = xiaoming
```

# 总结

scala中的override基本是强制性的。这个我比较赞同，这样就减少了思维逻辑的负担，看到一个类中的一个方法的时候，就明白了这个方法是否是覆写父类的方法。但是感觉由于scala类的继承的灵活性，比如钻石结构里面，要知道最终的类使用的方法是什么，就需要了解每个父类的情况，这个还是有点纠结的。
