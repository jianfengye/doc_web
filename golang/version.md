# go mod 能指定 1.xx.x 版本么？

你好，我是轩脉刃。

这是一个小知识点，不过估计不是每个人都知道。

一个读者在群里问到，我想要把 go.mod 中指定 go 版本的`go 1.17` 修改为`go 1.17.1`，希望我的项目最低要求 1.17.1。但是 Goland 老是把版本号修改回 `go 1.17` 是不是我哪里设置有问题？

其实这里不是设置有问题，而是 go.mod 要求就是如此。

指定 go 版本的地方叫 go directive 。它的格式是：

```
GoDirective = "go" GoVersion newline .
GoVersion = string | ident .  /* valid release version; see above */
```

其中所谓的 valid release version 为必须是像 1.17 这样，前面是一个点，前面是正整数（其实现在也只能是 1），后面是非负整数。

```
The version must be a valid Go release version: a positive integer followed by a dot and a non-negative integer (for example, 1.9, 1.14).
```

go 的版本形如 1.2.3-pre。一般最多由两个点组成，其中 1 叫做 major version，主版本，非常大的改动的时候才会升级这个版本。而 2 叫做 minor version，表示有一些接口级别的增加，但是会保证向后兼容，才会升级这个版本。而 3 叫做 patch version。顾名思义，一些不影响接口，但是打了一些补丁和修复的版本。而最后的 pre 叫做 pre-release suffix。我理解和 beta 版本一样的概念，在 release 版本出现之前，预先投放在市场的试用版本。

所以 go mod 中的格式只能允许 major version 和 minor version。它认为，使用者关注这两个版本号就行，他们能保证使用者在使用 golang 标准库的时候源码接口并没有增加和修改，不管你使用什么 patch version，你的业务代码都能跑起来。

所以，结论是，goland 非常智能。
