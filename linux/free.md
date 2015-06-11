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

