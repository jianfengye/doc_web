====== 下载postgres ======

postgres使用版本为9.3.3

下载地址为：
<code>
http://www.postgresql.org/ftp/source/

假设解压放在：
/home/yejianfeng/postgresql-9.3.3
</code>

====== 安装编译 ======
<code>
mkdir -p /usr/local/postgres

cd /home/yejianfeng/postgresql-9.3.3
./configure --prefix=/usr/local/postgres
make
make install
</code>

====== 设置用户和数据目录 ======
<code>
增加postgres用户
groupadd postgres
useradd postgres -g postgres

创建postgres的数据文件（可以考虑放在空间大的磁盘）
mkdir -p /usr/local/postgres/data

chown -R postgres /usr/local/postgres

编辑profile，把bin文件夹放入PATH
vim ~/.bash_profile
增加一行：
PATH=/usr/local/postgres/bin:$PATH

source ~/.bash_profile

初始化数据库
su postgres
/usr/local/postgres/bin/initdb --encoding=utf8 -D /usr/local/postgres/data

创建起始脚本
cp /home/yejianfeng/postgresql-9.3.3/contrib/start-scripts/linux /etc/init.d/postgres
chmod +x /etc/init.d/postgres
vim /etc/init.d/postgres
修改下面的部分：
# Installation prefix
prefix=/usr/local/postgres

# Data directory
PGDATA="/usr/local/postgres/data"

修改配置文件：
cd /usr/local/postgres/data/
vim postgresql.conf

确认listen_addresses(没有就加上):
listen_addresses = '*'
</code>

====== 设置权限 ======
<code>
psql -U postgres

postgres=# ALTER USER postgres PASSWORD '123456';
postgres=# \q

编辑配置文件，设置密码md5验证
vim /usr/local/postgres/data/pg_hba.conf

设置为
# TYPE  DATABASE        USER            ADDRESS                 METHOD
local   all             all                                     md5
host    all             all             0.0.0.0/0               md5

service postgresql restart

设置服务开机启动
chkconfig postgresql on
</code>

====== 创建数据库postgres ======

<code>

CREATE USER master;
GRANT ALL PRIVILEGES ON DATABASE master to master;
ALTER ROLE master with PASSWORD 'be5b5f4f3e24ed';

</code>