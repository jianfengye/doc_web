# nginx源码安装

nginx使用1.6.2版本安装

## 下载源码

nginx的源码下载地址为：这里[下载](http://nginx.org/en/download.html)
pcre的源码下载地址为：[pcre源码](ftp://ftp.csx.cam.ac.uk/pub/software/programming/pcre/)

## pcre安装
下载最新的pcre的源码

pcre的源码下载地址为：[pcre源码](ftp://ftp.csx.cam.ac.uk/pub/software/programming/pcre/)
<code>
tar -xf pcre-8.36.tar.gz
cd pcre-8.36
./configure
make
make install
</code>

## zlib安装
下载 [zlib 1.2.8版本](http://zlib.net/zlib-1.2.8.tar.gz)

<code>
tar -xf zlib-1.2.8.tar.gz
cd zlib-1.2.8
./configure
make
make install
</code>

## nginx 安装

<code>
tar -xf nginx-1.6.2.tar.gz
cd nginx-1.6.2
mkdir -p /usr/local/nginx
./configure --prefix=/usr/local/nginx --with-pcre=/home/yejianfeng/soft/pcre-8.36 --with-zlib=/home/yejianfeng/soft/zlib-1.2.8
make
make install

说明：
这里的pcre和zlib的地址指的是源码路径
</code>

## 开机启动nginx

nginx脚本设置到/home/yejianfeng/soft/nginx_script

<code>
#!/bin/sh
#
# nginx - this script starts and stops the nginx daemon
#
# chkconfig:   - 85 15
# description:  Nginx is an HTTP(S) server, HTTP(S) reverse \
#               proxy and IMAP/POP3 proxy server
# processname: nginx
# config:      /etc/nginx/nginx.conf
# config:      /etc/sysconfig/nginx
# pidfile:     /var/run/nginx.pid

# Source function library.
. /etc/rc.d/init.d/functions

# Source networking configuration.
. /etc/sysconfig/network

# Check that networking is up.
[ "$NETWORKING" = "no" ] && exit 0

nginx="/usr/local/nginx/sbin/nginx"
prog=$(basename $nginx)

NGINX_CONF_FILE="/usr/local/nginx/conf/nginx.conf"

[ -f /etc/sysconfig/nginx ] && . /etc/sysconfig/nginx

lockfile=/var/lock/subsys/nginx

start() {
    [ -x $nginx ] || exit 5
    [ -f $NGINX_CONF_FILE ] || exit 6
    echo -n $"Starting $prog: "
    daemon $nginx -c $NGINX_CONF_FILE
    retval=$?
    echo
    [ $retval -eq 0 ] && touch $lockfile
    return $retval
}

stop() {
    echo -n $"Stopping $prog: "
    killproc $prog -QUIT
    retval=$?
    echo
    [ $retval -eq 0 ] && rm -f $lockfile
    return $retval
}

restart() {
    configtest || return $?
    stop
    sleep 1
    start
}

reload() {
    configtest || return $?
    echo -n $"Reloading $prog: "
    killproc $nginx -HUP
    RETVAL=$?
    echo
}

force_reload() {
    restart
}

configtest() {
  $nginx -t -c $NGINX_CONF_FILE
}

rh_status() {
    status $prog
}

rh_status_q() {
    rh_status >/dev/null 2>&1
}

case "$1" in
    start)
        rh_status_q && exit 0
        $1
        ;;
    stop)
        rh_status_q || exit 0
        $1
        ;;
    restart|configtest)
        $1
        ;;
    reload)
        rh_status_q || exit 7
        $1
        ;;
    force-reload)
        force_reload
        ;;
    status)
        rh_status
        ;;
    condrestart|try-restart)
        rh_status_q || exit 0
            ;;
    *)
        echo $"Usage: $0 {start|stop|status|restart|condrestart|try-restart|reload|force-reload|configtest}"
        exit 2
esac
</code>

注意里面的几个路径设置

<code>
cp nginx_script /etc/init.d/nginx
chmod o+x /etc/init.d/nginx
/etc/init.d/nginx start
</code>

## 设置虚拟路径

一般来说都要设置一个虚拟路径文件夹
<code>
cd /usr/local/nginx/conf

修改nginx.conf
在倒数第二行增加：
include conf.d/*.conf;

/etc/init.d/nginx restart
</code>
