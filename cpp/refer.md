# 引用和指针

大家好, 这里是轩脉刃的刀光剑影公众号.

# C++ 的指针和引用

很多语言都同时存在引用和指针, 比如c++:

```c++
int a = 1;
int& b = a;
int* c = &a;
```

网上的说法各种各样, 综合下来说说我的解释:

1 指针比较好理解, 创建一个指针类型的变量(它有实际的内存空间),存储的是变量地址. 当然这个指针类型的变量存储的值是可以变化的.且不会影响原有的变量地址指向的变量.

2 引用官方的说法是“一个变量的别名”. 这里的别名不好理解. 这个别名可以有两个层面的理解, 在程序层面, 可以理解为这个别名是一个不占用内存空间的符号. 这个符号b和符号a一样存储在一个地方(比如运行的时候的堆栈中).在系统层面, 可以理解为这个别名实际上也是开辟了一个存储变量地址的地方.但是这个变量地址是个匿名的.它不允许更改.且也不存在内存泄漏等问题.

![20210426213118](http://tuchuang.funaio.cn/md/20210426213118.png)

所以粗暴地理解“别名”就是一个不能修改具体存储值的指针能理解网上的大部分问题.

3 关于为什么c++又要有引用, 又要有指针的问题?

指针是c中有的, c++ 作为c的升级语言,继承了指针. 但是它更推荐使用引用.
所以有一句话: 能尽量使用引用的时候就使用引用.

4 一些网上说的指针和引用的区别:
* 引用只能在定义时被初始化一次，之后不可变；指针可变；引用“从一而终”，指针可以“见异思迁”；
* 引用没有const，指针有const，const的指针不可变
* 引用不能为空，指针可以为空
* “sizeof 引用”得到的是所指向的变量(对象)的大小，而“sizeof 指针”得到的是指针本身的大小
* 指针和引用的自增(++)运算意义不一样
* 引用是类型安全的，而指针不是 (引用比指针多了类型检查）

# GO的指针和引用

Go也是一个同时有指针和引用的对象. 但是go中的引用就是生成一个指针. 它和指针是一样的使用.

所以说, Go中的所谓“引用” 就是获取某个变量的地址. 它没有所谓的“别名”概念,每个变量申明都是占用一个内存空间.

```golang
package main

import "fmt"

func main() {
        var a, b, c int
        fmt.Println(&a, &b, &c) // 0x1040a124 0x1040a128 0x1040a12c
}
```

而在c++中, 是有办法让两个“别名”的地址是同一个的:

```
int main() {
        int a = 10;
        int &b = a;
        int &c = b;

        printf("%p %p %p\n", &a, &b, &c); // 0x7ffe114f0b14 0x7ffe114f0b14 0x7ffe114f0b14
        return 0;
}
```

所以回到golang中, 所谓的函数的“引用传递”实际上可以等同为变量地址的“值传递”.

```golang
package main

import "fmt"

func main() {
   /* local variable definition */
   var a int = 100
   var b int = 200

   fmt.Printf("Before swap, value of a : %d\n", a )
   fmt.Printf("Before swap, value of b : %d\n", b )

   /* calling a function to swap the values.
   * &a indicates pointer to a ie. address of variable a and 
   * &b indicates pointer to b ie. address of variable b.
   */
   swap(&a, &b)

   fmt.Printf("After swap, value of a : %d\n", a )
   fmt.Printf("After swap, value of b : %d\n", b )
}
func swap(x *int, y *int) {
   var temp int
   temp = *x    /* save the value at address x */
   *x = *y    /* put y into x */
   *y = temp    /* put temp into y */
}

/*
Before swap, value of a :100
Before swap, value of b :200
After swap, value of a :200
After swap, value of b :100
*/
```
golang中的slice和map是引用类型.比如我们说slice是引用类型的意思是, 我拿着一个变量,它存储的是一个指针, 这个指针指向了slice的三元组结构.

```golang
package main

import (
	"fmt"
)

func main() {
	a := []int{7, 8, 9}
	fmt.Printf("len: %d cap:%d data:%+v addr:%p\n", len(a), cap(a), a, &a)
	ap(a)
	fmt.Printf("len: %d cap:%d data:%+v addr:%p\n", len(a), cap(a), a, &a)
}

func ap(b []int) {
	fmt.Printf("len: %d cap:%d data:%+v addr:%p\n", len(b), cap(b), b, &b)
	b[0] = 10
}

/*
len: 3 cap:3 data:[7 8 9] addr:0xc0000b6018
len: 3 cap:3 data:[7 8 9] addr:0xc0000b6048
len: 3 cap:3 data:[10 8 9] addr:0xc0000b6018
*/
```

解读上面那个程序, a是一个slice的引用,就是一个指针,指向了slice三元组结构体(len+cap+dataptr).
在传递进入ap的时候, golang中所有函数的传递都是“值传递”, 所以它复制了一个slice三元组结构体. 并且把它的引用(新结构体的指针)给了ap里面的变量b. 这个时候, 修改b[0] 就是修改这个新三元组里面的dataptr (新旧的三元组结构体的dataptr指向的存储数组的数组是一致的) 等同于修改 a这个三元组里面的dataptr指向的数组了.

## 总结
* go 里面的引用和 c++ 的引用不同, 它没有“别名” 的概念, 直接是内存地址
* go 里面没有所谓的引用传递, 只有 “值传递”

参考文章:
https://www.zhihu.com/question/266846728
https://isocpp.org/wiki/faq/references#pointers-and-references
https://www.cnblogs.com/hoodlum1980/archive/2012/06/19/2554270.html
https://dave.cheney.net/2017/04/29/there-is-no-pass-by-reference-in-go

