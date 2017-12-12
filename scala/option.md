# scala的Option

当一个函数既要返回对象，又要返回null的时候，使用Option[]

http://www.runoob.com/scala/scala-options.html

Option是scala的选项，用来表示一个键是可选的（有值或者无值），比如判断一个map是否有值，可以直接使用get(xxx) ，返回的就是Option[String]

Option[]有两个衍生值，一个是Some[],一个是None
```
final case class Some[+A](x: A) extends Option[A] {
  def isEmpty = false
  def get = x
}


case object None extends Option[Nothing] {
  def isEmpty = true
  def get = throw new NoSuchElementException("None.get")
}
```
实际上他们就是对isEmpty和get进行了分别的设置。

一般在获取出来的时候使用switch方法
```
   def show(x: Option[String]) = x match {
      case Some(s) => s
      case None => "?"
   }
```
Option有getOrElse()的方法，如果有数据，返回get里面的方法，如果没有数据就返回默认的数值。
