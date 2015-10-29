# mysql的timeout

很多时候我们连接mysql会在timeout这里跌倒，这里明确下mysql的timeout：

下面是获取timeout的变量：

    mysql> show global variables like "%timeout%";
    +-----------------------------+----------+
    | Variable_name               | Value    |
    +-----------------------------+----------+
    | connect_timeout             | 10       |
    | delayed_insert_timeout      | 300      |
    | innodb_flush_log_at_timeout | 1        |
    | innodb_lock_wait_timeout    | 50       |
    | innodb_rollback_on_timeout  | OFF      |
    | interactive_timeout         | 28800    |
    | lock_wait_timeout           | 31536000 |
    | net_read_timeout            | 30       |
    | net_write_timeout           | 60       |
    | rpl_stop_slave_timeout      | 31536000 |
    | slave_net_timeout           | 3600     |
    | wait_timeout                | 28800    |
    +-----------------------------+----------+
    12 rows in set (0.04 sec)

如果看session的timeout，也是这些参数设置。

[官方文档](http://dev.mysql.com/doc/refman/5.6/en/server-system-variables.html)

* connect_timeout

当一个连接上来，在三次握手的时候出现错误，mysql服务器会等待一段时间客户端进行重新连接，connect_timeout就是服务端等待重连的时间了。

* delayed_insert_timeout

insert delay操作延迟的秒数，这里不是insert操作，而是insert delayed，延迟插入。关于insert delayed，[参考](http://dev.mysql.com/doc/refman/5.6/en/insert-delayed.html)

* innodb_flush_log_at_timeout

这个是5.6中才出现的，是InnoDB特有的参数，每次日志刷新时间。

* innodb_lock_wait_timeout

innodb锁行的时间，就是锁创建最长存在的时间，当然并不是说行锁了一下就不释放了。

* innodb_rollback_on_timeout

在innodb中，当事务中的最后一个请求超时的时候，就会回滚这个事务

* interactive_timeout

对于不活跃的连接，当时间超过这个数值的时候，才关闭连接。

* lock_wait_timeout

获取元数据锁的超时时间。这个适合用于除了系统表之外的所有表。

* net_read_timeout
* net_write_timeout

这两个表示数据库发送网络包和接受网络包的超时时间。

* rpl_stop_slave_timeout

控制stop slave 的执行时间，在重放一个大的事务的时候,突然执行stop slave,命令 stop slave会执行很久,这个时候可能产生死锁或阻塞,严重影响性能，mysql 5.6可以通过rpl_stop_slave_timeout参数控制stop slave 的执行时间

* slave_net_timeout

这是Slave判断主机是否挂掉的超时设置，在设定时间内依然没有获取到Master的回应就认为Master挂掉了

* wait_timeout

交互式和非交互式链接的超时设置,防止客户端长时间链接数据库,什么都不做处于sleep状态，强制关闭长时间的sleep链接。默认情况先两值的都为28800(8h)，一般情况下将两值都设置为1000s就行了
