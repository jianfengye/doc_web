# slice全解析

昨天组内小伙伴做分享，给出了这么一段代码：
```
package main

import (
  "fmt"
)


func fun1(x int) {
  x = x + 1
}

func fun2(x *int) {
  *x = *x + 1
}

func fun3(x []int) {
  x = append(x, 3)
}

func fun4(x *[]int) {
  *x = append(*x, 3)
}

func fun5(x [4]int) {
  x[3] = 100
}

func fun6(x *[4]int) {
  (*x)[3] = 200
}


// 传值，传指针
func main() {
  x1 := 10
  fmt.Printf("%+v\n", x1)
  fun1(x1)
  fmt.Printf("%+v\n\n", x1)

  fmt.Printf("%+v\n", x1)
  fun2(&x1)
  fmt.Printf("%+v\n\n", x1)

  var x3 []int
  x3 = append(x3, 0, 1, 2)
  fmt.Printf("%+v\n", x3)
  fun3(x3)
  fmt.Printf("%+v\n\n", x3)

  fmt.Printf("%+v\n", x3)
  fun4(&x3)
  fmt.Printf("%+v\n\n", x3)

  var x4 [4]int
  for i := 0; i < 4; i++ {
    x4[i] = i
  }
  fmt.Printf("%+v\n", x4)
  fun5(x4)
  fmt.Printf("%+v\n\n", x4)

  fmt.Printf("%+v\n", x4)
  fun6(&x4)
  fmt.Printf("%+v\n\n", x4)
}
```

可以放在play上运行一下，实际输出的是
```
10
10

10
11

[0 1 2]
[0 1 2]

[0 1 2]
[0 1 2 3]

[0 1 2 3]
[0 1 2 3]

[0 1 2 3]
[0 1 2 200]
```

得出的结论是：slice是引用传递，数组是值传递，但是要想修改slice和数组，都需要把slice或者数组的地址传递进去。

这个结论中的数组是值传递，要在调用函数内部修改数组值，必须传递数组指针，我没有什么意见。但是slice的部分，却并没有那么简单。基本上，需要明确下面几点才能解释上面的代码。

# slice的结构是uintptr+len+cap

比如我定义了一个slice, 不管是什么方法定义的
```
var a []int
a = make([]int, 1)
a := []int{1,2}
```
这里的a都是由一个固定的数据结构赋值的
![](http://tuchuang.funaio.cn/18-8-24/61691030.jpg)

这个数据结构有三个，一个是指向一个定长数组的指针，一个是len，表示我这个slice包含了几个值，还有一个是cap，表示我申请定长数组的时候申请了多大的空间。

# slice的append操作是根据cap和len的关系判断是否申请新的空间

在内存看空间中，没有不定长的数组，所有不定长数组的语法都是语言本身封装了。比如golang中的slice。slice可以在初始化的时候就定义好我需要使用多大的空间（cap）
```
a := make([]int, 1, 10)
```
这里的10也就是cap，1是len，说明我已经创建了10个int空间给这个slice。
当不断往a中append数据的时候，首先是len不断增加，当len和cap一样的时候，这个时候再append数据，就会新开辟一个数组空间，这个数组空间长度为多大呢？2*cap。
举例说，如果上述的a后续又append了9个数据，这个时候如果再append一个数据，就会发现cap变成20了。

当然，如果扩容了，那么我们说的slice的第一个元素，指向定长数组的地址就会变化。


理解下下面这个代码：
```
package main

import "fmt"
import "unsafe"

func main() {
    var a []int

    a = append(a, 0)
    printSlice("a", a)

    a = append(a, 1)
    printSlice("a", a)

    a = append(a, 2, 3, 4)
    printSlice("a", a)
}

func printSlice(s string, x []int) {
    fmt.Printf("%s len=%d cap=%d ptr=%p %v\n",s, len(x), cap(x), unsafe.Pointer(&x[0]), x)
}       


输出：
a len=1 cap=2 ptr=0x10414020 [0]
a len=2 cap=2 ptr=0x10414020 [0 1]
a len=5 cap=8 ptr=0x10458020 [0 1 2 3 4]
```
# 函数参数是slice的时候传递的是“slice结构”的值拷贝

我们说的slice为参数传递的时候传递是引用传递，实际上，它传递的是Slice结构（uintptr+len+cap）的一个复制，但是由于uintptr对应的是一个定长的数组，所以基本上当slice作为参数传递的时候，返回回来的slice结构是不会变的，对应的定长数组的大小是不会变的，但是这个定长数组里面的具体值是有可能变的。

看下面几个例子：
```
package main

import (
  "fmt"
  "unsafe"
)

func fun3(x []int) {
  fmt.Printf("%p\n", unsafe.Pointer(&x[0]))
  x = append(x, 3)
  x[2] = 100
  fmt.Printf("%p\n", unsafe.Pointer(&x[0]))
}

func main() {

  var x3 []int
  x3 = append(x3, 0, 1, 2)
  fmt.Printf("%+v\n", x3)
  fmt.Printf("%p\n", unsafe.Pointer(&x3[0]))
  fun3(x3)
  fmt.Printf("%p\n", unsafe.Pointer(&x3[0]))
  fmt.Printf("%+v\n\n", x3)

}

输出：
[0 1 2]
0x10414020
0x10414020
0x10414020
0x10414020
[0 1 100]

```

这里的x3[2]的的值变化了。但是slice的指针地址没有变化。

如果在f3里面修改x[3]的值：

```
package main

import (
  "fmt"
  "unsafe"
)

func fun3(x []int) {
  fmt.Printf("%p\n", unsafe.Pointer(&x[0]))
  x = append(x, 3)
  x[3] = 100
  fmt.Printf("%p\n", unsafe.Pointer(&x[0]))
}

func main() {
  var x3 []int
  x3 = append(x3, 0, 1, 2)
  fmt.Printf("%+v\n", x3)
  fmt.Printf("%p\n", unsafe.Pointer(&x3[0]))
  fun3(x3)
  fmt.Printf("%p\n", unsafe.Pointer(&x3[0]))
  fmt.Printf("%+v\n\n", x3)
}
输出：
[0 1 2]
0x10414020
0x10414020
0x10414020
0x10414020
[0 1 2]
```
这里的x3的值就不会变化，虽然不会变化，但是实际上slice指向的定长数组的索引为3的值已经变化了。

如果f3是append两个呢？

```
package main

import (
  "fmt"
  "unsafe"
)

func fun3(x []int) {
  fmt.Printf("%p\n", unsafe.Pointer(&x[0]))
  x = append(x, 3, 4)
  x[3] = 100
  fmt.Printf("%p\n", unsafe.Pointer(&x[0]))
}

func main() {
  var x3 []int
  x3 = append(x3, 0, 1, 2)
  fmt.Printf("%+v\n", x3)
  fmt.Printf("%p\n", unsafe.Pointer(&x3[0]))
  fun3(x3)
  fmt.Printf("%p\n", unsafe.Pointer(&x3[0]))
  fmt.Printf("%+v\n\n", x3)
}
输出：
[0 1 2]
0x10414020
0x10414020
0x10458000
0x10414020
[0 1 2]
```

我们看到在fun3里面append之后的x这个slice指向的地址变化了。但是由于这个x实际上是我们传递进去的x3的值拷贝，所以这个x3并没有被修改。最后输出的时候还是没有变化。

# 总结
基本上记住了这几个点就明白了slice:
* slice的结构是uintptr+len+cap
* slice的append操作是根据cap和len的关系判断是否申请新的空间
* 函数参数是slice的时候传递的是“slice结构”的值拷贝

# 参考
https://halfrost.com/go_slice/
