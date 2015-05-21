# shell的历史

# shell概况
人想要和操作系统进行交互，传送指令给操作系统，就需要使用到shell。宏义的shell是人与机器交互的页面，它分为两种，一种是有界面的，比如GUI，另外一种是没有界面的，完全是指令操作的(CLI)。我们一般说的shell指的就是命令行界面。

## Bourne shell
最早Ken Thompson设计Unix的时候，使用的是命令解释器，命令解释器接受用户的命令，然后解释他们并执行。

后来出现了Bourne shell(通称为sh)，顾名思义，就是一个叫Bourne shell创建的。对，它就是现在我们机器上面的/bin/sh这个可执行文件。这个老哥创建的sh一直沿用至今，现在的UNIX操作系统都配置有sh，而且各种新的shell都会向后兼容sh的语法。

Bourne shell 带来了：

* 脚本可以写在文件里被调用，比如sh a.sh可以执行a.sh里面的shell命令
* 可以交互或者非交互的方式调用
* 可以同步执行也可以异步执行
* 支持输入输出的pipeline，就是管道方式
* 支持输入输出的重定向，就是现在使用的> 和 >>
* 提供一系列内置命令
* 提供流程控制基本的函数和结构
* 弱类型变量，就是可以直接 a=1，不需要指定a为int
* 提供本地和全局的变量作用域
* 脚本执行前不需要编译
* 去掉goto功能
* 使用``进行命令执行替换
* 增加了for~do~done的循环
* 增加了case~in~esac的条件选择
* 文件描述符2>代表错误信息导出

## csh, ksh

Bourne老爷子创造的sh非常强大，后来引入的争议是Unix系统是C写的，为什么你的shell的语法不像C呢？然后Bill Joy就编写了C Shell(csh)。它用最类似C的语法来编写shell。后来csh演化成了tchsh，但是csh后面的路途就比较坎坷了，最终未能流行起来。但是现在比如在Mac系统上还保留csh。

Korn Shell(ksh)是1983年出现的，它向后兼容Bourne shell。同时吸取了C shell的一些优点，比如job control。

## bash

在1989年，现在最广泛使用的Bash出现了，它的全称叫做Bourne-Again shell。目的是为了与POSIX的标准保持一致，同时保持对sh的兼容。其实现在很多机器上的/bin/sh往往都链接到bash，我们以为我们是使用Bourne shell，实际上我们使用的是Bourne-Again shell。

文件/etc/shells给出了系统中所有已知的shell

    [root@localhost vagrant]# cat /etc/shells
    /bin/sh
    /bin/bash
    /sbin/nologin
    /bin/dash
    /bin/tcsh
    /bin/csh
    /bin/ksh

## shell的设置和查找

我们可以为每个用户指定不同的默认shell，在/etc/passwd中设置就可以了

    postgres:x:503:503::/home/postgres:/bin/bash

如何查看自己的默认shell

    echo $SHELL

如何查看当前的shell

    echo $0
