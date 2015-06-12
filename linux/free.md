# 内存那些事

linux中的free -m可以查看当前的内存使用情况

    [yejianfeng@iZ23fsd ~]$ free -m
                 total       used       free     shared    buffers     cached
    Mem:          7869       7737        132          0        489       4419
    -/+ buffers/cache:       2828       5040
    Swap:            0          0          0

这里会让人奇怪的就是为什么除了used和free，还有个buffers，cached的使用。

buffer是系统把将要写入磁盘的数据先存放起来，然后一次性写入磁盘。cache则是将要从磁盘读取的数据先缓存起来，等待下次读取或者一次性读取。对于人来说，我们认为这一部分的缓存是已经使用了的，但是对于操作系统来说，这一部分的内存是“没有使用”的。因为这部分的内存随时可以释放。所以这部分意思是：

total: 总共有的内存
used: 已经使用的内存数（人的角度看的，既: 进程实际使用的内存＋buffers+cached）
free: 空闲内存（人的角度看的）
shared: 进程共享内存
buffers: 操作系统预留的为进程IO读取使用的缓冲区
cached: 操作系统为最近打开的文件进行的缓存
-/+ buffer: 操作系统角度看的实际使用的内存数

# 虚拟内存和物理内存

我们在使用top命令看进程的时候，会看到下面的几个东西：
      PID USER      PR  NI  VIRT  RES  SHR S %CPU %MEM    TIME+  COMMAND
     2344 vagrant   20   0 98276 2020 1052 S  0.0  0.4   0:00.49 sshd
     2345 vagrant   20   0  105m 1868 1488 S  0.0  0.4   0:00.08 bash
     1136 nginx     20   0 45676 1740  440 S  0.0  0.4   0:00.00 nginx
     1022 root      20   0  308m 1604  820 S  0.0  0.3   0:05.10 VBoxService

这里会看到几个属性：

VIRT  RES  SHR

这三个值都是表示这个进程的内存使用状况，其中的VIRT指的是进程使用的虚拟内存，RES指的是进程使用的物理内存，SHR指的是进程使用的共享内存。

## 关于虚拟内存和物理内存

打一个形象的比喻，一列车从北京到上海，有1500公里，我们应该要铺设1500公里的铁轨。但是，我们想了个取巧的办法，我们实际只需要三公里的铁轨，当列车快行驶完成铁轨的时候，我们把已经走过的铁轨铺设到列车前方，这样，列车就可以使用3公里的铁轨就进行行驶了。在这个例子中，虚拟内存就是代表1500公里，而物理内存就是代表3公里。我们一个进程在操作系统中实际使用的物理内存会远远小于分配的虚拟内存。比如一个php-fpm进程实际使用大概20-30M的物理内存，而你看他的虚拟内存，大概会有150M左右。

我们机器的内存是恒定的，那么这些大出来的虚拟内存是存放在哪里的呢？当然是硬盘。对于计算机来说，处理信息查找会先在L1缓存中找需要的数据，如果没有，在L2缓存中查找，如果还没有，在内存中查找，如果还没有，先去硬盘中的虚拟内存区域找，如果还没有，再去硬盘中找，如果都没有，就跳过这次处理（可能程序崩溃或者蓝屏）。

引入虚拟内存技术的好处是程序不需要再管物理内存中哪块空闲，哪块有用了。这些全部交给操作系统来管理，再程序面前，就像是有一块连续的，未使用的内存空间一样。当程序启动的时候，系统会为每个程序分配一定的内存空间。在32bit的机器上，这个内存空间的上限是4G（0x00000000 - 0xffffffff），而其中分为两个部分，用户态使用的内存，内核态使用的内存。其中3G（0x00000000 - 0xbfffffff）是用户态可以使用的内存，而1G（0xc0000000 - 0xffffffff）是内核态使用的内存。所以在许多windows的x86机器上，安装的内存条大小最多是4G的，因为安装再大的内存，可以使用的内存大小也是有限制的。

而对于64bit的机器，这个内存是没有4G的上限的，理论上可以支持2^64的大小的内存地址的。所以一般服务器上对内存的最大上限都不做限制，这点可以使用ulimit -a 得到验证：

    [vagrant@localhost /]$ ulimit -a
    core file size          (blocks, -c) 0
    data seg size           (kbytes, -d) unlimited
    scheduling priority             (-e) 0
    file size               (blocks, -f) unlimited
    pending signals                 (-i) 3528
    max locked memory       (kbytes, -l) 64
    max memory size         (kbytes, -m) unlimited
    open files                      (-n) 1024
    pipe size            (512 bytes, -p) 8
    POSIX message queues     (bytes, -q) 819200
    real-time priority              (-r) 0
    stack size              (kbytes, -s) 10240
    cpu time               (seconds, -t) unlimited
    max user processes              (-u) 1024
    virtual memory          (kbytes, -v) unlimited
    file locks                      (-x) unlimited

对于虚拟内存和物理内存，这里其实最复杂的是他们的映射机制。这个机制是操作系统内核要实现的东东，这里就不继续看下去了。


## 共享内存

很多进程可能需要加载动态库，比如libc，而这些动态库就可以默认存放到共享内存中，而不用每个进程都进行加载了。还有进程也可以存放数据到共享内存中，这样他们的子进程就可以到共享内存中进行操作，比如php的[shmop](http://php.net/manual/en/book.shmop.php)系列的命令就是操作共享内存的。

好了，这里有个奇怪的现象，明明top中在SHR中看到很多共享内存，为什么free -m中的shared为0呢？--实际上free命令中的shared已经被废弃了，没有进行计算了。具体参考[man](http://linux.die.net/man/1/free)