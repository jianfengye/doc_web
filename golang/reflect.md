# 一篇理解什么是CanSet, CanAddr？

# 什么是可设置（ CanSet ）

首先需要先明确下，可设置是针对 reflect.Value 的。普通的变量要转变成为 reflect.Value 需要先使用 reflect.ValueOf() 来进行转化。

那么为什么要有这么一个“可设置”的方法呢？比如下面这个例子：
``` golang
var x float64 = 3.4
v := reflect.ValueOf(x)
fmt.Println(v.CanSet()) // false
```

golang 里面的所有函数调用都是值复制，所以这里在调用 reflect.ValueOf 的时候，已经复制了一个 x 传递进去了，这里获取到的 v 是一个 x 复制体的 value。那么这个时候，我们就希望知道我能不能通过 v 来设置这里的 x 变量。就需要有个方法来辅助我们做这个事情： CanSet()

但是, 非常明显，由于我们传递的是 x 的一个复制，所以这里根本无法改变 x 的值。这里显示的就是 false。

那么如果我们把 x 的地址传递给里面呢？下面这个例子：
``` golang
var x float64 = 3.4
v := reflect.ValueOf(&x)
fmt.Println(v.CanSet()) // false
```

我们将 x 变量的地址传递给 reflect.ValueOf 了。应该是 CanSet 了吧。但是这里却要注意一点，这里的 v 指向的是 x 的指针。所以 CanSet 方法判断的是 x 的指针是否可以设置。指针是肯定不能设置的，所以这里还是返回 false。

那么我们下面需要可以通过这个指针的 value 值来判断的是，这个指针指向的元素是否可以设置，所幸 reflect 提供了 Elem() 方法来获取这个“指针指向的元素”。

``` golang
var x float64 = 3.4
v := reflect.ValueOf(&x)
fmt.Println(v.Elem().CanSet()) // true
```

终于返回 true 了。但是这个 Elem() 使用的时候有个前提，这里的 value 必须是指针对象转换的 reflect.Value。（或者是接口对象转换的 reflect.Value）。这个前提不难理解吧，如果是一个 int 类型，它怎么可能有指向的元素呢？所以，使用 Elem 的时候要十分注意这点，因为如果不满足这个前提，Elem 是直接触发 panic 的。

在判断完是否可以设置之后，我们就可以通过 SetXX 系列方法进行对应的设置了。
``` golang
var x float64 = 3.4
v := reflect.ValueOf(&x)
if v.Elem().CanSet() {
    v.Elem().SetFloat(7.1)
}
fmt.Println(x)
```

# 更复杂的类型

对于复杂的 slice， map， struct， pointer 等方法，我写了一个例子：

``` golang
package main

import (
	"fmt"
	"reflect"
)

type Foo interface {
	Name() string
}

type FooStruct struct {
	A string
}

func (f FooStruct) Name() string {
	return f.A
}

type FooPointer struct {
	A string
}

func (f *FooPointer) Name() string {
	return f.A
}

func main() {
	{
		// slice
		a := []int{1, 2, 3}
		val := reflect.ValueOf(&a)
		val.Elem().SetLen(2)
		val.Elem().Index(0).SetInt(4)
		fmt.Println(a) // [4,2]
	}
	{
		// map
		a := map[int]string{
			1: "foo1",
			2: "foo2",
		}
		val := reflect.ValueOf(&a)
		key3 := reflect.ValueOf(3)
		val3 := reflect.ValueOf("foo3")
		val.Elem().SetMapIndex(key3, val3)
		fmt.Println(val) // &map[1:foo1 2:foo2 3:foo3]
	}
	{
		// map
		a := map[int]string{
			1: "foo1",
			2: "foo2",
		}
		val := reflect.ValueOf(a)
		key3 := reflect.ValueOf(3)
		val3 := reflect.ValueOf("foo3")
		val.SetMapIndex(key3, val3)
		fmt.Println(val) // &map[1:foo1 2:foo2 3:foo3]
	}
	{
		// struct
		a := FooStruct{}
		val := reflect.ValueOf(&a)
		val.Elem().FieldByName("A").SetString("foo2")
		fmt.Println(a) // {foo2}
	}
	{
		// pointer
		a := &FooPointer{}
		val := reflect.ValueOf(a)
		val.Elem().FieldByName("A").SetString("foo2")
		fmt.Println(a) //&{foo2}
	}
}
```

上面的例子如果都能理解，那基本上也就理解了 CanSet 的方法了。

特别可以关注下，map，pointer 在修改的时候并不需要传递指针到 reflect.ValueOf 中。因为他们本身就是指针。

所以在调用 reflect.ValueOf 的时候，我们必须心里非常明确，我们要传递的变量的底层结构。比如 map， 实际上传递的是一个指针，我们没有必要再将他指针化了。而 slice， 实际上传递的是一个 SliceHeader 结构，我们在修改 Slice 的时候，必须要传递的是 SliceHeader 的指针。这点往往是需要我们注意的。

# CanAddr

在 reflect 包里面可以看到，除了 CanSet 之外，还有一个 CanAddr 方法。它们两个有什么区别呢？

CanAddr 方法和 CanSet 方法不一样的地方在于：对于一些结构体内的私有字段，我们可以获取它的地址，但是不能设置它。

比如下面的例子：

``` golang
package main

import (
	"fmt"
	"reflect"
)

type FooStruct struct {
	A string
	b int
}


func main() {
	{
		// struct
		a := FooStruct{}
		val := reflect.ValueOf(&a)
		fmt.Println(val.Elem().FieldByName("b").CanSet())  // false
		fmt.Println(val.Elem().FieldByName("b").CanAddr()) // true
	}
}


```

所以，CanAddr 是 CanSet 的必要不充分条件。一个 Value 如果 CanAddr, 不一定 CanSet。但是一个变量如果 CanSet，它一定 CanAddr。

# 源码

假设我们要实现这个 Value 元素 CanSet 或者 CanAddr，我们大概率会相到使用标记位标记。事实也确实是这样。

我们先看下 Value 的结构：

``` golang
type Value struct {
	typ *rtype
	ptr unsafe.Pointer
	flag
}

```

这里要注意的就是，它是一个嵌套结构，嵌套了一个 flag，而这个 flag 本身就是一个 uintptr。

``` golang
type flag uintptr
```

这个 flag 非常重要，它既能表达这个 value  的类型，也能表达一些元信息（比如是否可寻址等）。flag虽然是uint类型，但是它用位来标记表示。

首先它需要表示类型，golang 中的类型有27个：

``` golang
const (
	Invalid Kind = iota
	Bool
	Int
	Int8
	Int16
	Int32
	Int64
	Uint
	Uint8
	Uint16
	Uint32
	Uint64
	Uintptr
	Float32
	Float64
	Complex64
	Complex128
	Array
	Chan
	Func
	Interface
	Map
	Ptr
	Slice
	String
	Struct
	UnsafePointer
)

```

所以使用5位（2^5-1=63）就足够放这么多类型了。所以 flag 的低5位是结构类型。

第六位 flagStickyRO: 标记是否是结构体内部私有属性
第七位 flagEmbedR0: 标记是否是嵌套结构体内部私有属性
第八位 flagIndir: 标记 value 的ptr是否是保存了一个指针
第九位 flagAddr: 标记这个 value 是否可寻址
第十位 flagMethod: 标记 value 是个匿名函数

![20201026181333](http://tuchuang.funaio.cn/md/20201026181333.png)

其中比较不好理解的就是 flagStickyRO,flagEmbedR0 

看下面这个例子：

``` golang

type FooStruct struct {
	A string
	b int
}

type BarStruct struct {
	FooStruct
}

{
    	b := BarStruct{}
        val := reflect.ValueOf(&b)
        c := val.Elem().FieldByName("b")
		fmt.Println(c.CanAddr())
}
```

这个例子中的 c 的 flagEmbedR0 标记位就是1了。

所以我们再回去看 CanSet 和 CanAddr 方法

```golang 
func (v Value) CanAddr() bool {
	return v.flag&flagAddr != 0
}

func (v Value) CanSet() bool {
	return v.flag&(flagAddr|flagRO) == flagAddr
}

```

他们的方法就是把 value 的 flag 和 flagAddr 或者 flagRO (flagStickyRO,flagEmbedR0) 做“与”操作。

而他们的区别就是是否判断 flagRO 的两个位。所以他们的不同换句话说就是“判断这个 Value 是否是私有属性”，私有属性是只读的。不能Set。

# 应用

在开发 collection （https://github.com/jianfengye/collection）库的过程中，我就用到这么一个方法。我希望设计一个方法 `func (arr *ObjPointCollection) ToObjs(objs interface{}) error`，这个方法能将 ObjPointCollection 中的 objs reflect.Value 设置为参数 objs 中。

``` golang
func (arr *ObjPointCollection) ToObjs(objs interface{}) error {
	arr.mustNotBeBaseType()

	objVal := reflect.ValueOf(objs)
	if objVal.Elem().CanSet() {
		objVal.Elem().Set(arr.objs)
		return nil
	}
	return errors.New("element should be can set")
}

```

使用方法：
``` golang
func TestObjPointCollection_ToObjs(t *testing.T) {
	a1 := &Foo{A: "a1", B: 1}
	a2 := &Foo{A: "a2", B: 2}
	a3 := &Foo{A: "a3", B: 3}

	bArr := []*Foo{}
	objColl := NewObjPointCollection([]*Foo{a1, a2, a3})
	err := objColl.ToObjs(&bArr)
	if err != nil {
		t.Fatal(err)
	}
	if len(bArr) != 3 {
		t.Fatal("toObjs error len")
	}
	if bArr[1].A != "a2" {
		t.Fatal("toObjs error copy")
	}
}
```


## 总结

CanAddr 和 CanSet 刚接触的时候是会有一些懵逼，还是需要稍微理解下 reflect.Value 的 flag 就能完全理解了。

# 参考文档
[laws-of-reflection](https://blog.golang.org/laws-of-reflection)
[go addressable 详解](https://colobu.com/2018/02/27/go-addressable/)
[Go语言_反射篇](https://www.cnblogs.com/yjf512/archive/2012/06/10/2544391.html)