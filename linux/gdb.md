# gdb常用命令

最近在研究nginx源码，gdb用于调试非常方便，之前这篇有研究过如何使用gdb调试nginx：https://www.cnblogs.com/yjf512/archive/2012/05/10/2494635.html
最近使用的时候gdb的命令又忘记了。这里复习一下。

这里有很全的资料：https://www.gitbook.com/book/wizardforcel/100-gdb-tips

# 常用命令

## 启动项目并断点

start

## 打临时断点

tb <line_number>

## 打断点

b <line_number>

## 列出代码

l

## 单步运行

n

## 进入函数调试

step

## 跳出函数

finish

## 继续运行

c

## 查看断点信息

info b

## 去掉某个断点

delete <break_number>

## fork的时候进入子进程

set follow-fork-mode child

## fork的时候进入父进程

set follow-fork-mode parent
