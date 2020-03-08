# Vscode-Go插件，你不知道的几个技巧

# 前言

本期的视频，我想分享的是vscode中go插件的几个高级用法。工欲善其事，必先利其器。掌握这些高级用法，能很好地加快我们的编码速度，而且能提升我们的代码质量。在视频的最后，我也会介绍下vscode中go插件的原理。

# 介绍

现在go最主流的IDE有两个：Goland和VsCode。这两个IDE的优劣每个人判断不同。但是你要是一个全栈开发工程师，你的项目里面有各种语言，那么我觉得vscode应该是最优选择。

开始演示前，我先申明下这个视频使用的vscode版本和go插件版本：

vscode版本：Version: 1.42.1
vscode-go插件版本：0.13.1
本地go版本：1.13

后续的演示我打开了键盘显示功能，能方便大家看到我的实际操作。

# 安装

vscode的go插件的安装步骤我们这里就直接跳过。

这里要说的一点是，在安装vscode的go插件的机器必须安装了golang，并且设置了gopath，插件会自动寻找gopath。

vscode的插件只是包含了基本的功能，它是依赖其他的第三方命令行工具来实现一些高级的功能。我们也可以在具体使用到这些高级功能的时候再安装这些第三方命令行工具，但是我们实际也能一次性全部安装。

（演示：安装全部工具）

![20200308103733.png](http://tuchuang.funaio.cn/md/20200308103733.png)

这些安装完的工具会保存在我们的GOPATH/bin/下面

下面进入到一些高级用法演示：

# 用法

假设我们现在要开发一个vscodeshow的库，创建一个vscodeshow的目录，使用go mod init。



## 自动生成测试用例

写了一个函数 add

下面我们需要生成这个add方法的测试用例。

![20200308093047.png](http://tuchuang.funaio.cn/md/20200308093047.png)

马上自动生成了可以运行的测试用例。

测试用例这里我补充一点，我们最好讲测试用例的t.Log打印出来的东西也同时显示。可以通过为go test 增加一个-v 来进行展示。

![20200308101150.png](http://tuchuang.funaio.cn/md/20200308101150.png)

## 自动生成结构实例化

我们下面要写单元测试，那么就遇到一个问题，这里定义了一个testcase的结构，有name,args,want等字段

我们可以手动打，但是也可以使用vscode-go插件的fill struct功能

![20200308094118.png](http://tuchuang.funaio.cn/md/20200308094118.png)

![20200308094225.png](http://tuchuang.funaio.cn/md/20200308094225.png)

## 自动实现接口

下面我们做的更复杂一些，定义一个User接口，然后定义两个struct实现这个User接口

![20200308094522.png](http://tuchuang.funaio.cn/md/20200308094522.png)

下面定义一个Student结构，我们希望它实现User接口，除了可以一行一行实现接口的每个函数，也可以通过

![20200308094652.png](http://tuchuang.funaio.cn/md/20200308094652.png)

![20200308094733.png](http://tuchuang.funaio.cn/md/20200308094733.png)

来实现，它会自动生成这个接口的方法，并且将注释也同时copy过来。

## 自动增加Tag

我们的struct经常要作为json输出，我们一般是通过设置struct的tag来进行输出的。

比如Student这个结构，我们就需要输出设置json的tag。

![20200308095044.png](http://tuchuang.funaio.cn/md/20200308095044.png)

如果我们觉得它的tag增加的不对，我们可以通过插件的setting.json来进行设置

![20200308095212.png](http://tuchuang.funaio.cn/md/20200308095212.png)

这里的每个配置都是对应gomodifytags的参数

https://github.com/fatih/gomodifytags

![20200308095552.png](http://tuchuang.funaio.cn/md/20200308095552.png)

（演示修改成为json,xml, 和snakecase和camelcase）

## 查找接口实现

我们可以使用peek implement来查看某个接口的实现类有那些。

![20200308095846.png](http://tuchuang.funaio.cn/md/20200308095846.png)

![20200308095905.png](http://tuchuang.funaio.cn/md/20200308095905.png)

也可以使用Go to Implement来跳转到具体实现（当然前提是只有一个具体实现） 

## 重构

golang的代码重构的时候少不了的几个操作vscode插件也是都有的。

### rename

我们可以对Student中的Name字段进行重命名。
（演示）

### 变量提取

我们写了一个complex方法，假设里面有一个判断是非常复杂的。我们可以将某个复杂的代码提取出来成为一个变量
![20200308100447.png](http://tuchuang.funaio.cn/md/20200308100447.png)

当然我们也可以将某一段代码提取出来成为一个函数。

但是用提取的功能必须要注意一个点，就是要提取的东西必须可单独执行。如果你要提取的东西不完整，或者依赖某个结构，提取的功能要不就是失败，要不就会非常难看。

![20200308100715.png](http://tuchuang.funaio.cn/md/20200308100715.png)

总之，我认为提取变量的功能实用性大于提取函数的功能。

## 第三方包加入到当前workspace

vscode中导入一个项目的时候，并没有像Goland一样把这个项目引用的包都放在当前的workspace下面，但是往往很经常，我们需要阅读第三方包。

这个将第三方包加入到当前workspace下就显得很重要了。

![20200308102027.png](http://tuchuang.funaio.cn/md/20200308102027.png)

## 保存

下面这个问题就需要比较系统梳理，golang一个文件在保存的时候有几个事情可以做？

1 运行测试用例

这个是通过go test来进行设置的，我们还可以设置运行测试用例的时候是否同时运行代码覆盖率，是否输出info等。

2 整个包是否可运行检查

这个是通过go vet来进行设置的

3 代码风格检查

这个是通过go lint进行设置的

4 build

这个是通过 go build进行保存的

我们可以通过在go扩展中搜索save关键字，就能设置我在保存文件的时候要做什么操作？

![20200308101229.png](http://tuchuang.funaio.cn/md/20200308101229.png)

## 更快捷的调用方法

上面我们提及到的所有命令都可以通过 cmd + shift + p的方式调用出来。
也可以通过右键->show all command调用出来

但是还有更快的调用方法，就是把这些命令加在右键中。

我们可以通过设置setting中的go.editorContextMenuCommands来进行

当然注意，并不是所有的命令都可以在editorContextMenuCommands中进行设置。

# vscode-go插件原理

最后我们简单讲一下vscode-go的插件原理

现在版本的vscode-go主要是使用gopls，也就是go的language server来进行查找和跳转的。

Go的Language server我们可以想象成为是一个单独的进程，它维护一个大json格式，这个json格式把我们整个项目中的每个函数，变量，接口等都定义关联好，当我们执行一次跳转命令的时候，当前的IDE就发送一个请求到这个进程，这个进程返回当前IDE，你应该去哪个文件哪个行进行查找。

这个language-server-protocol是微软定义出的一个通用协议，它的说明网站在这里：
https://microsoft.github.io/language-server-protocol/

![20200308103249.png](http://tuchuang.funaio.cn/md/20200308103249.png)

它最主要的作用是把开发工具和具体的语言解析给分割开了。

我们可以使用任何工具解析语言（go这边使用的就是gopls）来进行语言解析，建立go语言的langurage server。所以不仅仅在vscode，而在vim，sublime等IDE中，只要支持了language server机制，就能支持go语言的跳转，定义，补全等功能了。

# 结束

本期视频的所有链接和文字稿我会放在我的公众号中。所有用到的链接我会放到视频说明中。

希望大家看完这个视频，对vscode-go的使用能有所收获。

谢谢。