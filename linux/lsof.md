# lsof查看工具[未发布]

今天遇到一个问题，报警显示的文件描述符过多，然后也顺便温习了下lsof这个命令。

## 查看进程打开的fd数量

进入lsof -p [pid] 查看，发现确实有大量的fd被这个进程打开着。但是他们里面的描述却是下面这个样子

```
wave_last 23200 test *079u     sock                0,5         0t0   99234170 can't identify protocol
wave_last 23200 test *080u     sock                0,5         0t0   99234485 can't identify protocol
```

lsof的can't identify protocol是什么意思呢？这个是说操作系统内核已经把信息从/proc/net/\*中删除了，导致lsof无法获取到这个fd的信息。

这个通常是说明进程打开了一个网络连接，但是并没有关闭它，它由网络连接的另一端进行关闭操作了。比如，mysql的连接并没有开启。

## lsof

lsof返回的结构如下：

```
# lsof

COMMAND  PID       USER   FD      TYPE     DEVICE  SIZE/OFF       NODE NAME
init       1       root  cwd       DIR        8,1      4096          2 /
init       1       root  txt       REG        8,1    124704     917562 /sbin/init
init       1       root    0u      CHR        1,3       0t0       4369 /dev/null
init       1       root    1u      CHR        1,3       0t0       4369 /dev/null
init       1       root    2u      CHR        1,3       0t0       4369 /dev/null
init       1       root    3r     FIFO        0,8       0t0       6323 pipe

```

COMMAND是指开启这个fd的命令

PID指的是这个进程的进程ID

USER指的是这个进程的操作用户

FD指的是这个FD的名字，它可以有下面的类型

* cwd  current working directory;
* Lnn  library references (AIX);
* err  FD information error (see NAME column);
* jld  jail directory (FreeBSD);
* ltx  shared library text	(code and data);
* Mxx  hex	memory-mapped type number xx.
* m86  DOS	Merge mapped file;
* mem  memory-mapped file;
* mmap memory-mapped device;
* pd   parent directory;
* rtd  root directory;
* tr   kernel trace file (OpenBSD);
* txt  program text (code and data);
* v86  VP/ix mapped file;

如果不是上面类型，就是直接显示`fd的号+fd读写类型`, 比如

35u 就代表这个fd号为35，用于读写的

* r for read access;
* w for write access;
* u for read and write access;

TYPE是代表FD类型。常见类型有：

* IPv4
* IPv6
* socket
* unix
* inet
* DIR
* CHR
* FIFO

DEVICE代表驱动名字

# 参考

[seeing-too-many-lsof-cant-identify-protocol](http://stackoverflow.com/questions/7911840/seeing-too-many-lsof-cant-identify-protocol)

[lsof-command-examples](http://www.thegeekstuff.com/2012/08/lsof-command-examples/)

[lsof man](https://www.freebsd.org/cgi/man.cgi?query=lsof&sektion=8&manpath=linux)
