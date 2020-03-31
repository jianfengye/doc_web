# 你还在用官方的error库么，那就弱爆了

# 开始

在一个项目中，你是否还在为出现error在哪里，定位error而苦恼？你是否还在为error中的信息太少而苦恼；那么我告诉你，本文会介绍给你一个非常好用的error库，让你追查error起来不再痛苦。

我们今天的结构分为四个部分：

1 介绍
2 演示
3 源码解说
4 总结

本篇文章视频版地址：https://www.bilibili.com/video/BV1hE411c7Ze/  需要复制链接打开bilibili观看。

# 介绍

其实我们可以思考一下，我们在一个项目中使用错误机制，最核心的几个需求是什么？
1 附加信息：我们希望错误出现的时候能附带一些描述性的错误信息，甚至于这些信息是可以嵌套的。
2 附加堆栈：我们希望错误不仅仅打印出错误信息，也能打印出这个错误的堆栈信息，让我们可以知道错误的信息。

在Go的语言演进过程中，error传递的信息太少一直是被诟病的一点。这个情况在GO1.13之后有了些许改变，我们今天要推荐的库是：

github.com/pkg/errors.

这个库是由Dave Cheney开发的。他是VMWare的一名开发工程师，也是go语言的开源贡献者。

![20200322173047.png](http://tuchuang.funaio.cn/md/20200322173047.png)

这个是他的个人网站： https://dave.cheney.net/

# 演示
dsf
假设我们有一个项目叫errdemo，他有sub1,sub2两个子包。sub1和sub2两个包都有Diff和IoDiff两个函数。

![20200322142229.png](http://tuchuang.funaio.cn/md/20200322142229.png)

```
// sub2.go

package sub2

import (
	"errors"
	"io/ioutil"
)

func Diff(foo int, bar int) error {
	return errors.New("sub2 diff error")
}

func IoDiff(foo int, bar int) ([]byte, error) {
	b, err := ioutil.ReadFile("filename")
	return b, err
}

// sub1.go

package sub1

import (
	"errdemo/sub1/sub2"
	"fmt"

	"errors"
)

func Diff(foo int, bar int) error {
	if foo < 0 {
		return errors.New("diff error")
	}
	if err := sub2.Diff(foo, bar); err != nil {
		return fmt.Errorf("sub1 error")
	}
	return nil
}

func IoDiff(foo, bar int) error {
	_, err := sub2.IoDiff(foo, bar)
	return err
}

// main.go

package main

import (
	"errdemo/sub1"
	"fmt"
)

func main() {
	err := sub1.Diff(1, 2)
	fmt.Println(err)
}

```

我们可以看到，这里的sub1.go里面

```
	if err := sub2.Diff(foo, bar); err != nil {
		return fmt.Errorf("sub1 error")
    }
```
这种是最差的写法，它在返回sub1 error的同时，把sub2返回的错误信息完全掩盖掉了。

那么我们就遇到了我们两个需求点里面的第一个需求点，应该尽可能多的返回错误信息。

于是我们或许就会这么修改：

```
	if err := sub2.Diff(foo, bar); err != nil {
		return errors.New("sub1 error:" + err.Error())
	}
```

或许你也会想到用fmt的errorf来进行修改
```
	if err := sub2.Diff(foo, bar); err != nil {
		return fmt.Errorf("sub1 error: %s", err.Error())
	}
```
![20200322143056.png](http://tuchuang.funaio.cn/md/20200322143056.png)

显然第二种比第一种好很多，但是第二种方法有个最大的问题，它丢失了sub2的error的类型信息。

同样的写法我们放到IoDiff中我能感受出来。

```
func IoDiff(foo int, bar int) ([]byte, error) {
	b, err := ioutil.ReadFile("filename")
	return b, err
}
```
我们在main中想根据这个错误类型进行判断，如果是文件不存在的错误，我希望做一些诸如文件路径修改的操作。现在的情况是做不到的。

```
func main() {
	err := sub1.IoDiff(1, 2)
	if err == os.PathError {
		...
	}
	fmt.Println(err)
}
```

于是在GO1.13中也考虑到了这个事情，引入了error的wrap机制。简要来说，就是我们上面的例子中，sub1我们就可以修改为这样：

```
func IoDiff(foo, bar int) error {
	_, err := sub2.IoDiff(foo, bar)
	return fmt.Errorf("sub1 error: %w", err)
}

```

这样我们在main中可以进行这样判断：

```
func main() {
	err := sub1.IoDiff(1, 2)
	var perr *os.PathError
	if errors.As(err, &perr) {
		fmt.Println("do some fix")
		return
	}
	fmt.Println(err)
}
```

![20200322144803.png](http://tuchuang.funaio.cn/md/20200322144803.png)

但是不管error的消息怎么封装，如果我们想打印堆栈信息，还是无法顺利打印出来。

所以我们今天介绍的包就派上用场了： https://github.com/pkg/errors

它的使用也是非常简单，只需要将官方import error包的地方直接替换成它就可以了。完全无缝对接。

替换完成之后，我们发现，还是一样的，可以运行，而且和替换之前的形态没有什么不同。

但是现在我们使用errors.New出来的包，是一个github.com/pkg/errors 库中的一个foundation实例了。

它的format方法可以使用fmt.Printf("%+v", err) 的方式给打印出来。

```
// sub2.go
func Diff(foo int, bar int) error {
	return errors.New("sub2 diff error")
}


// sub1.go
func Diff(foo int, bar int) error {
	if foo < 0 {
		return errors.New("diff error")
	}
	if err := sub2.Diff(foo, bar); err != nil {
		return errors.Wrap(err, "sub1 error:")

	}
	return nil
}

// main.go
func main() {
	err := sub1.Diff(1, 2)
	fmt.Printf("%+v", err)
}
```

我们可以看下这个例子，在sub2中我们使用New来创建了一个带有堆栈和错误信息的error。
然后在sub1中，我们使用了Wrap封装了一个带有堆栈和错误信息的error，同时也带着sub2的堆栈和错误信息给了main。

![20200322154618.png](http://tuchuang.funaio.cn/md/20200322154618.png)

我们研究下这里的Wrap，除了这个Wrap之外，其实我们还可以使用:
* WithMessage
* WithStack

这两个函数和Wrap的区别是在fmt.Printf的时候，是否带了错误信息，和堆栈。WithMessage是只带了错误信息，WithStack是只带了堆栈。

![20200322155231.png](http://tuchuang.funaio.cn/md/20200322155231.png)

这几个方法都可以看实际的情况来决定。

# 原理

其实 github.com/pkg/errors 的原理也是非常简单，它利用了fmt包的一个特性：

在 https://golang.org/pkg/fmt/ 里面有具体的说明fmt的打印顺序：

![20200322160235.png](http://tuchuang.funaio.cn/md/20200322160235.png)

其中在打印error之前会判断当前打印的对象是否实现了Formatter接口

![20200322160353.png](http://tuchuang.funaio.cn/md/20200322160353.png)

formatter这个接口的两个参数其实就是表示了我们打印的时候需要的“占位符”，“宽度”，“精度” 和“标记”。

比如我们使用fmt.Printf的时候，format中的 "%+3.2f" 中的 + 为标记，3为宽度，2为精度，%f为占位符

errors里面的错误类就实现了这个Formatter接口。

但是其实github.com/pkg/errors里面的错误类是实现了三种

![20200322160509.png](http://tuchuang.funaio.cn/md/20200322160509.png)

* fundamental （标准，同时带有message和stack）
* withStack （只带有stack）
* withMessage （只带有message）

而只要带有message的错误类型，又都同时实现了Error()方法，也就是实现了errors包的error接口，可以完全当作error来使用。

# 总结

从这个包里面，我们就可以看到go的鸭子类型接口的优势了。只需要我们在函数调用的时候使用的是接口，那么就能很方便的进行扩展。

比如这里的github.com/pkg/errors包中的三种错误类型

他们一方面实现了fmt的Formatter接口，另一方面只要有错误消息的接口，又实现了内置的error接口

```
// fmt.print.go
type Formatter interface {
	Format(f State, c rune)
}
```

```
// buildin.go
type error interface {
	Error() string
}
```

甚至于在1.13出现了之后，这个库还实现了Unwrap

```
// errors/wrap.go
interface {
    Unwrap() error
}
```

方法，也是支持wrap和unwrap的。

我们从go2 的draft（https://go.googlesource.com/proposal/+/master/design/go2draft.md） 中可以看到，在go2中的标准error也会支持使用 %+v 来打印堆栈了。
到时候这个库应该就没有使用的必要了。
作者在readme文档中也说了，1.0是最后的release版本，后续不再更新了。不过这个不妨碍它现在是最好用的error处理库。