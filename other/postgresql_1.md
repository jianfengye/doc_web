# 初识postgresql

postgresql源码[安装](http://www.postgresql.org/download/)，我使用9.3.3版本。

安装部分没有什么好说的，最基本的configure和make操作。

# bin文件

安装完成之后bin文件夹下面有这么多命令：
    [root@localhost bin]# ll
    total 8432
    -rwxr-xr-x 1 root root   59302 Jan  7 10:39 clusterdb
    -rwxr-xr-x 1 root root   59812 Jan  7 10:39 createdb
    -rwxr-xr-x 1 root root   64684 Jan  7 10:39 createlang
    -rwxr-xr-x 1 root root   63462 Jan  7 10:39 createuser
    -rwxr-xr-x 1 root root   58781 Jan  7 10:39 dropdb
    -rwxr-xr-x 1 root root   64578 Jan  7 10:39 droplang
    -rwxr-xr-x 1 root root   58751 Jan  7 10:39 dropuser
    -rwxr-xr-x 1 root root  769199 Jan  7 10:39 ecpg
    -rwxr-xr-x 1 root root  102105 Jan  7 10:39 initdb
    -rwxr-xr-x 1 root root   70620 Jan  7 10:39 pg_basebackup
    -rwxr-xr-x 1 root root   30620 Jan  7 10:39 pg_config
    -rwxr-xr-x 1 root root   29934 Jan  7 10:39 pg_controldata
    -rwxr-xr-x 1 root root   45052 Jan  7 10:39 pg_ctl
    -rwxr-xr-x 1 root root  352131 Jan  7 10:39 pg_dump
    -rwxr-xr-x 1 root root   82653 Jan  7 10:39 pg_dumpall
    -rwxr-xr-x 1 root root   32521 Jan  7 10:39 pg_isready
    -rwxr-xr-x 1 root root   47385 Jan  7 10:39 pg_receivexlog
    -rwxr-xr-x 1 root root   36041 Jan  7 10:39 pg_resetxlog
    -rwxr-xr-x 1 root root  151471 Jan  7 10:39 pg_restore
    -rwxr-xr-x 1 root root 5854655 Jan  7 10:38 postgres
    lrwxrwxrwx 1 root root       8 Jan  7 10:38 postmaster -> postgres
    -rwxr-xr-x 1 root root  440366 Jan  7 10:39 psql
    -rwxr-xr-x 1 root root   60518 Jan  7 10:39 reindexdb
    -rwxr-xr-x 1 root root   63108 Jan  7 10:39 vacuumdb

TODO: 写下这些命令都是干什么的。

pgsql的源码安装可以参照[这篇文章](http://www.cnblogs.com/jlzhou/archive/2013/02/05/2893173.html)

