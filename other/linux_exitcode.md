# linux的exit code

# 为什么要有exit code

在Unix或者Linux系统中，假设一个进程有父进程，子进程自己执行完成之后，如何通知父进程自己执行的结果呢？就是通过exit codes来通知的。一般来说，是使用0代表执行成功推出，1或者更高代表执行失败退出的。

# 怎么获取上一个shell命令的exit code

通过
    echo $?

对于一个shell脚本，如果你没有指定exit code，那么这个shell脚本的exit code就是shell中的最后一个语句的exit相同。

# exit code的默认取值

exit code当然可以自己定义，但是0代表成功，非0代表失败，取值范围为0～255，这几条规则是一定的。

它还有一些潜规则的规定：

* 1 : 一般错误
* 2 : shell内部问题，比如一个函数申明缺少了关键字
* 126 : 权限问题或者命令是不可执行的
* 127 : 命令找不到
* 128 : exit返回的数值不合法，比如 exit 3.14
* 128+n : 由于信号而推出，n就是信号值，比如由于kill -9 PID推出，则exit code为128+9
* 130 : Ctrl + C 退出
* 255 : exit退出的整数超出范围了

# 参考
[Understanding Exit Codes and how to use them in bash scripts](http://bencane.com/2014/09/02/understanding-exit-codes-and-how-to-use-them-in-bash-scripts/)

[Appendix E. Exit Codes With Special Meanings](http://www.tldp.org/LDP/abs/html/exitcodes.html)