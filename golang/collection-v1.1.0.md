# collection包1.1.0都升级了什么功能

jianfengye/collection（https://github.com/jianfengye/collection）这个包喜迎第一个子版本升级，从1.0.1升级到了1.1.0。这次还是做了不少改动的。

# 支持int32

这个需求是这个issue提出的： https://github.com/jianfengye/collection/issues/10

主要是在protobuf 生成的go代码里面是int32,int64的。

增加一个类型的数组其实是很方便的事情了，只需要写一个Int32Collection的struct, 基于AbsCollection，实现几个必要的函数就可以了。

```
package collection

import (
	"errors"
	"fmt"
)

type Int32Collection struct {
	AbsCollection
	objs []int32
}

func compareInt32(i interface{}, i2 interface{}) int {
	int1 := i.(int32)
	int2 := i2.(int32)
	if int1 > int2 {
		return 1
	}
	if int1 < int2 {
		return -1
	}
	return 0
}

// NewInt32Collection create a new Int32Collection
func NewInt32Collection(objs []int32) *Int32Collection {
	arr := &Int32Collection{
		objs: objs,
	}
	arr.AbsCollection.Parent = arr
	arr.SetCompare(compareInt32)
	return arr
}

...
```

# 实现延迟复制

这个是有一个读者在公众号留言提醒的。之前1.0.1版本的collection在new的时候直接将slice进行copy一份，是出于安全的考虑，Collection的使用一定不能修改到原有的slice。现在1.1.0在newCollection的时候并不复制slice，而是在需要对slice进行乱序或者变更操作的时候进行一次Copy操作。而我把Copy操作的时间也放到各个具体实现类中了。

于是ICollection多实现了一个Copy方法，它会把当前Collection的Slice复制一份出来。然后在AbsCollection中记录一个是否已经拷贝的标记，isCopied，对于那些对原数组进行操作的方法会根据这个标记，如果之前没有复制，就复制一份，再进行操作

```
func (arr *AbsCollection) Insert(index int, obj interface{}) ICollection {
	if arr.Err() != nil {
		return arr
	}
	if arr.Parent == nil {
		panic("no parent")
	}

	if arr.isCopied == false {
		arr.Copy()
		arr.isCopied = true
	}

	return arr.Parent.Insert(index, obj)
}
```

这样就实现了延迟拷贝的功能。

# 实现了SetIndex的方法

这个方法和Index方法是对应的，将数组的某个元素进行设置。

这个方法的具体实现也在实现类中实现了，特别是对ObjCollection的SetIndex实现还是需要reflect进行绕的，其他的COllection不需要使用反射。

```
func (arr *ObjCollection) SetIndex(i int, val interface{}) ICollection {
	arr.objs.Index(i).Set(reflect.ValueOf(val))
	return arr
}
```

# Sort实现了快速排序

这个是这个issue提出的 https://github.com/jianfengye/collection/issues/9

之前的Sort我是使用冒泡排序实现的，确实效率有欠考虑。

这次将Sort进行了快排实现。由于已经又了SetIndex, Index, 等方法，所以可以这个快排可以直接在AbsCollection中实现就行了。

```
func (arr *AbsCollection) qsort(left, right int, isAscOrder bool) {
	tmp := arr.Index(left)
	p := left
	i, j := left, right
	for i <= j {
		for j >= p {
			c, err := arr.Index(j).Compare(tmp)
			if err != nil {
				arr.SetErr(err)
				return
			}
			if isAscOrder && c >= 0 {
				j--
				continue
			}
			if !isAscOrder && c <= 0 {
				j--
				continue
			}

			break
		}

		if j >= p {
			t, _ := arr.Index(j).ToInterface()
			arr.SetIndex(p, t)
			p = j
		}

		for i <= p {
			c, err := arr.Index(i).Compare(tmp)
			if err != nil {
				arr.SetErr(err)
				return
			}
			if isAscOrder && c <= 0 {
				i++
				continue
			}
			if !isAscOrder && c >= 0 {
				i++
				continue
			}
			break
		}

		if i <= p {
			t, _ := arr.Index(i).ToInterface()
			arr.SetIndex(p, t)
			p = i
		}
	}

	t, _ := tmp.ToInterface()
	arr.SetIndex(p, t)

	if p-left > 1 {
		arr.qsort(left, p-1, isAscOrder)
	}

	if right-p > 1 {
		arr.qsort(p+1, right, isAscOrder)
	}
}

func (arr *AbsCollection) Sort() ICollection {

	if arr.Err() != nil {
		return arr
	}
	if arr.compare == nil {
		return arr.SetErr(errors.New("sort: compare must be set"))
	}

	if arr.isCopied {
		arr.qsort(0, arr.Count()-1, true)
		return arr
	}
	arr.Copy()
	return arr.Sort()
}
```

# compare函数进行传递

之前IMix的compare函数一定都需要调用SetCompare才能设置，现在如果这个IMix是从Collection进行创建的，比如Collection.Index(xx) IMix， 返回的IMix就直接将Collection中设置的compare函数直接传递过来。

这样在使用过程中方便了不少。

我也将各个类型的compare函数都整理在具体实现类的头部

```
func compareInt32(i interface{}, i2 interface{}) int
func compareInt64(i interface{}, i2 interface{}) int
func compareInt(i interface{}, i2 interface{}) int
func compareString(a interface{}, b interface{}) int
...
```


# 总结

1.1.0版本主要是根据issue反馈修复了一些使用和性能上的优化点。总体觉得已经可以发一个小版本了，于是打上了1.1.0的tag。

该项目目前也有277个star了，欢迎在业务上试用 jianfengye/collection（https://github.com/jianfengye/collection） 这个包，有问题请直接提issue，我会尽快响应。 
