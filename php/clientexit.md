# 客户端浏览器退出之后都发生了什么[未发布]

前提：这里说的是典型的lnmp结构，nginx+php-fpm的模式

今天遇到的提问就是，如果我有个php程序执行地非常慢，甚至于在代码中sleep()，然后浏览器连接上服务的时候，会启动一个php-fpm进程，但是这个时候，如果浏览器关闭了，那么请问，这个时候服务端的这个php-fpm进程是否还会继续运行呢？

最简单的方法就是做实验，我们写一个程序：在sleep之前和之后都用file_put_contents来写入日志：

```
<?php
file_put_contents('/tmp/test.log', '11111' . PHP_EOL, FILE_APPEND | LOCK_EX);
sleep(3);
file_put_contents('/tmp/test.log', '2222' . PHP_EOL, FILE_APPEND | LOCK_EX);
```

好了，实际操作的结果是，我们在服务器sleep的过程中，关闭客户端浏览器，2222还是会被写入日志中的。即后台的php还是会继续运行的。

# ignore_user_abort

事情并没有这样完结，在老王和diogin的提醒下，说这个可能会和php的ignore_user_abort相关。

于是我就把代码稍微改成这样的：

```
<?php
ignore_user_abort(false);
file_put_contents('/tmp/test.log', '11111' . PHP_EOL, FILE_APPEND | LOCK_EX);
sleep(3);
file_put_contents('/tmp/test.log', '2222' . PHP_EOL, FILE_APPEND | LOCK_EX);
```

发现并没有任何软用，不管设置ignore_user_abort为何值，都是会继续执行的。

由此就有一个疑问了user_abort是什么？

![](http://i3.piimg.com/e5aa1ad98005660d.png)

文档对cli模式的abort说的很清楚，当php脚本执行的时候，用户终止了这个脚本的时候，就会触发abort了。然后脚本根据ignore_user_abort来判断是否要继续执行。

但是对cgi模式的abort并没有说清楚。感觉过去，即使客户端断开连接了，在cgi模式的php是不会收到abort的。

# 是不是心跳问题呢？

首先想到的是不是心跳问题呢？我们断开浏览器客户端，等于在客户端没有close而断开了连接，服务端是需要等待tcp的keepalive到达时长之后才会检测出来的。

好，那么我们就先排除浏览器问题，写一个client程序，连接上http服务之后，发送一个header头，sleep1秒就主动close连接。

程序如下：

```
package main

import "net"
import "fmt"
import "time"

func main() {
	conn, _ := net.Dial("tcp", "192.168.33.10:10011")
	fmt.Fprintf(conn, "GET /index.php HTTP/1.0\r\n\r\n")
	time.Sleep(1 * time.Second)
	conn.Close()
	return
}
```

使用这个模拟浏览器发送请求，发现仍然还是一样，php还是不管是否设置ignore_user_abort，会继续执行完成整个脚本。

# 如何触发ignore_user_abort

问题就聚焦到我们如何触发ignore_user_abort了，那么服务端这边怎么知晓这个socket不能使用了呢？自然就会想到是不是需要服务端主动和socket进行交互，才会判断出这个socket是否可以使用。

我们还发现，php提供了connection_status和connection_aborted两个方法，这两个方法都能检测出当前的连接状态。于是我们的打日志的程序其实就可以改成：

```
file_put_contents('/tmp/test.log', '1 connection status: ' . connection_status() . "abort:" . connection_aborted() . PHP_EOL, FILE_APPEND | LOCK_EX);
```

根据手册[连接处理](http://php.net/manual/zh/features.connection-handling.php)显示我们可以打印出当前连接的状态了。

下面还缺少一个和socket交互的程序，我们使用echo，后面也顺带记得带上flush，排除了flush的影响。

程序就改成：

```
<?php
ignore_user_abort(true);
file_put_contents('/tmp/test.log', '1 connection status: ' . connection_status() . "abort:" . connection_aborted() . PHP_EOL, FILE_APPEND | LOCK_EX);

sleep(3);

for($i = 0; $i < 10; $i++) {
        echo "22222";
        flush();
        sleep(1);
        file_put_contents('/tmp/test.log', '2 connection status: ' . connection_status() . "abort:" . connection_aborted(). PHP_EOL, FILE_APPEND | LOCK_EX);
}
```

很好，执行我们前面写的client。观察日志：

```
1 connection status: 0abort:0
2 connection status: 0abort:0
2 connection status: 1abort:1
2 connection status: 1abort:1
2 connection status: 1abort:1
2 connection status: 1abort:1
2 connection status: 1abort:1
2 connection status: 1abort:1
2 connection status: 1abort:1
2 connection status: 1abort:1
2 connection status: 1abort:1
```

终于制造出了abort。日志也显示后面几次的abort状态都是1。

但是这里有个奇怪的地方，为什么第一个2 connection status的状态还是0呢（NORMAL）。

# RST

我们抓包看

![](http://i3.piimg.com/eca4e0ba0e96e77a.png)

这整个过程只有发送14个包，我们看下服务端第一次发送22222的时候，客户端返回的是RST。后面就没有进行后续的包请求了。

大概的交互流程是：

当服务端在循环中第一次发送2222的时候，客户端由于已经断开连接了，返回的是一个RST，但是这个发送过程算是请求成功了。直到第二次服务端再次想往这个socket中进行write操作的时候，这个socket就不进行网络传输了，直接返回说connection的状态已经为abort。所以就出现了上面的情况，第一次222是status为0，第二次的时候才出现abort。


# strace进行验证

我们也可以使用strace php -S XXX来进行验证

整个过程strace的日志如下：

```
。。。
close(5)                                = 0
lstat("/tmp/test.log", {st_mode=S_IFREG|0644, st_size=49873651, ...}) = 0
open("/tmp/test.log", O_WRONLY|O_CREAT|O_APPEND, 0666) = 5
fstat(5, {st_mode=S_IFREG|0644, st_size=49873651, ...}) = 0
lseek(5, 0, SEEK_CUR)                   = 0
lseek(5, 0, SEEK_CUR)                   = 0
flock(5, LOCK_EX)                       = 0
write(5, "1 connection status: 0abort:0\n", 30) = 30
close(5)                                = 0
sendto(4, "HTTP/1.0 200 OK\r\nConnection: clo"..., 89, 0, NULL, 0) = 89
sendto(4, "111111111", 9, 0, NULL, 0)   = 9
rt_sigprocmask(SIG_BLOCK, [CHLD], [], 8) = 0
rt_sigaction(SIGCHLD, NULL, {SIG_DFL, [], 0}, 8) = 0
rt_sigprocmask(SIG_SETMASK, [], NULL, 8) = 0
nanosleep({3, 0}, 0x7fff60a40290)       = 0
sendto(4, "22222", 5, 0, NULL, 0)       = 5
open("/tmp/test.log", O_WRONLY|O_CREAT|O_APPEND, 0666) = 5
fstat(5, {st_mode=S_IFREG|0644, st_size=49873681, ...}) = 0
lseek(5, 0, SEEK_CUR)                   = 0
lseek(5, 0, SEEK_CUR)                   = 0
flock(5, LOCK_EX)                       = 0
write(5, "2 connection status: 0abort:0\n", 30) = 30
close(5)                                = 0
rt_sigprocmask(SIG_BLOCK, [CHLD], [], 8) = 0
rt_sigaction(SIGCHLD, NULL, {SIG_DFL, [], 0}, 8) = 0
rt_sigprocmask(SIG_SETMASK, [], NULL, 8) = 0
nanosleep({1, 0}, 0x7fff60a40290)       = 0
sendto(4, "22222", 5, 0, NULL, 0)       = -1 EPIPE (Broken pipe)
--- SIGPIPE {si_signo=SIGPIPE, si_code=SI_USER, si_pid=2819, si_uid=0} ---
open("/tmp/test.log", O_WRONLY|O_CREAT|O_APPEND, 0666) = 5
fstat(5, {st_mode=S_IFREG|0644, st_size=49873711, ...}) = 0
lseek(5, 0, SEEK_CUR)                   = 0
lseek(5, 0, SEEK_CUR)                   = 0
flock(5, LOCK_EX)                       = 0
write(5, "2 connection status: 1abort:1\n", 30) = 30
close(5)                                = 0
rt_sigprocmask(SIG_BLOCK, [CHLD], [], 8) = 0
rt_sigaction(SIGCHLD, NULL, {SIG_DFL, [], 0}, 8) = 0
rt_sigprocmask(SIG_SETMASK, [], NULL, 8) = 0
nanosleep({1, 0}, 0x7fff60a40290)       = 0
open("/tmp/test.log", O_WRONLY|O_CREAT|O_APPEND, 0666) = 5
fstat(5, {st_mode=S_IFREG|0644, st_size=49873741, ...}) = 0
lseek(5, 0, SEEK_CUR)                   = 0
lseek(5, 0, SEEK_CUR)                   = 0
flock(5, LOCK_EX)                       = 0
write(5, "2 connection status: 1abort:1\n", 30) = 30
close(5)  
。。。
```

我们照中看status从0到1转变的地方。

```
...
sendto(4, "22222", 5, 0, NULL, 0)       = 5
...
write(5, "2 connection status: 0abort:0\n", 30) = 30
close(5)                                = 0
rt_sigprocmask(SIG_BLOCK, [CHLD], [], 8) = 0
rt_sigaction(SIGCHLD, NULL, {SIG_DFL, [], 0}, 8) = 0
rt_sigprocmask(SIG_SETMASK, [], NULL, 8) = 0
nanosleep({1, 0}, 0x7fff60a40290)       = 0
sendto(4, "22222", 5, 0, NULL, 0)       = -1 EPIPE (Broken pipe)
--- SIGPIPE {si_signo=SIGPIPE, si_code=SI_USER, si_pid=2819, si_uid=0} ---
open("/tmp/test.log", O_WRONLY|O_CREAT|O_APPEND, 0666) = 5
fstat(5, {st_mode=S_IFREG|0644, st_size=49873711, ...}) = 0
lseek(5, 0, SEEK_CUR)                   = 0
lseek(5, 0, SEEK_CUR)                   = 0
flock(5, LOCK_EX)                       = 0
write(5, "2 connection status: 1abort:1\n", 30) = 30
close(5)                                = 0
```

第二次往socket中发送2222的时候显示了Broken pipe，就是程序告诉我们，这个socket已经不能使用了，顺便php中的connection_status就会被设置为1了。后续的写操作也都不会再执行了。
