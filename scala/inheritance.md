# scala中的多继承

在各个语言中，多继承都是一个很好的话题。

在面向对象领域，两个最常用的技巧就是类继承和组合。其实基本上，对于同一个需求，两种方式都可以实现。继承又分为单继承和多继承的方式。

# java

java中的类就是一种单继承的方式，它只能一个类继承另一个类，如果你需要一个类拥有多个类的方法，你只能使用继承嵌套的方式来处理。
```
class Animal {

    腿;

    头;

    身体；
}

class Cat extends Animal {

    胡子；
}

public Kitty extends Cat {

    可爱
}
```
单继承的方式其实是限制了代码的灵活性，比如上图最后一个要描述kitty是一个可爱的，有胡子的，有身体，头部，腿的动物，就需要有这样的继承逻辑。比较复杂。但是它降低了代码的复杂度。就和git的分支一样，单继承只允许你在master的分支不断开发，这样我们阅读和查找的时候，只需要根据这个继承关系，一层层查找，总会查找出这个特性在哪个层级的。但是呢，Java在java8中引入了接口多继承的逻辑，允许对接口实现多继承，但是也仅仅局限于接口。

```
interface FlyAnimal {
    void fly();
}

interface SwimAnimal {
    void swim();
}


interface FlySwimAnimal extends FlyAnimal, SwimAnimal{
}

public class Dragon implement FlySwimAnimal {
    public void fly() {
        // Implement
    }

    public void swim() {
        // Implement
    }
}

```

上面这个例子从语义上就不大适合使用单继承的方式来实现了。

这样实现的缺点在于：

首先，interface毕竟是有局限的，局限就在于interface中不能写具体的逻辑。在子类中“必须”实现父接口的所有方法。这个其实没有办法将一些通用的逻辑放在，比如除了Dragon，我又有一个类要实现FlaySwimAnimal，我的fly和Dragon的fly方法其实是一样的，这样，我就无法把这个通用的fly方法放在一个地方了。

其次，父类的方法对子类不透明。这就属于继承和组合的区别。java的接口是实现了多继承，但是多继承的缺点也在这里，父类的方法对子类不透明。比如父类的SwimAnimal的Swim方法签名修改了，改成Swam，那么子类的Dragon也就必须进行修改定义，swim的名字也需要修改了。这里可以扩展，所有的多继承的方法都会遇到这个问题。

这个问题，在组合中就会得到很好的解决。

# scala

在scala中就使用特性（Trait）来实现组合。

```
trait FlyAnimal {
    fly()  = {
        "i can fly"
    }
}

trait SwimAnimal {
    swim() = {
        "i can swim"
    }
}

class Animal {
    name() = {
        "i am an animal"
    }
}

public class Dragon extends Animal with FlyAnimal with SwimAnimal {
    override fly() = {
        "i can fly very high"
    }
}
```

Dragon更像是一个组合，它将FlyAnimal, SwimAnimal组合起来了, 这个类有了fly()方法，也有了swim()方法。并且它们都在特性中实现了，如果你要复写这两个方法中的一个方法，你可以使用override来实现。

上个例子中，如果SwimAnimal的swim方法修改了，那么Dragon类定义不需要做任何修改。不可否认，组合比继承灵活很多，在使用的时候，组合更像是一个扁平结构，继承更像是竖直结构。

其实特性的功能现在在很多语言中都会提供，用于替换多继承的逻辑。比如php，golang。

# 菱形结构

不管是多继承还是组合，难免碰到的一个问题是菱形结构。scala是使用override关键字来解决的。

```
trait Animal {
  def talk: String
}

trait Cat extends Animal {
  override def talk: String = "I am Cat"
}

trait Dog extends Animal {
   def talk: String = "I am Dog"
}

class Kitty extends Animal with Dog with Cat {

}

val kittyDog = new Kitty
kittyDog.talk

输出：
kittyDog: Kitty = Kitty@1baa67f0
res0: String = I am Cat
```

# 总结

总而言之，灵活性 组合 > 多继承 > 单继承。一门语言更倾向于给程序员更多的自由，往往会选择使用组合的模型，如果倾向于更加工程化，会选择启用组合模型。

# 参考文章

https://www.ibm.com/developerworks/cn/java/j-scala05298.html
https://www.zhihu.com/question/21862257
https://www.zhihu.com/question/49094001
