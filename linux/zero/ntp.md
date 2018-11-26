# 从零开始搭建运维体系 - ntp

后续我们需要统一修改一些通用的参数，和一些通用的软件。

# yum安装ntp

把所有服务器的时间都设置相同是很有必要的，这里我们就需要搭建一个ntp服务器, 其他机器从这个ntp服务器校准软件时间。这样所有程序才都会获取到同样的时间。

首先我们需要所有机器都安装ntp模块，yum安装

```
ansible all -m yum -a 'name=ntp state=present'
```

使用ansible的yum模块确实很爽，直接写成可幂等的命令了。下面就需要配置ntp服务器。

# 说说ntp

ntp是RFC定义的一种网络时间同步协议。我们一般使用服务端客户端模式进行同步，我们将我手头三台机器的第一台192.168.34.2作为ntp服务器。其他的两台作为ntp的客户端。

在所有操作之前，我们需要确保下我们所有机器的时区是一样的。设置时区也有一个ansible模块timezone

```
ansible all -m timezone -a "name=Asia/Shanghai"
```

是否修改成功我们可以通过shell模块执行timedatectl来确定`ansible all -m shell -a "timedatectl"`

下面，我们需要配置ntp服务器，我们设定我们的34.2作为ntp服务器。首要任务就是决定哪个作为这个ntp服务器的上游时间服务器（上个阶层）。如果我们这个机器能连接外网，一般我们最常用的就是[cn.pool.ntp.org](https://www.ntppool.org/zh/use.html)。

```
/etc/ntp.conf

server 0.cn.pool.ntp.org
server 1.cn.pool.ntp.org
server 2.cn.pool.ntp.org
server 3.cn.pool.ntp.org
```

ntpd服务器的时间同步机制并不是一下子把你当前机器的时间切换为所要同步的时间服务器时间，而是慢慢的经过时间布长的调整将时间调整为所同步的时间。这主要是为了不会让当前服务器的时间出现事件导流，闪跳等现象。当然，如果我机器的时间和时间服务器时间差别很大，这个可能就需要很久时间才能进行时间同步。当我们启动了ntpd服务的时候，我们发现ntpd的进程默认有个-g参数。如果不加这个参数是有可能有问题的，不加这个参数，当ntpd发现当前机器的时间和时间服务器时间差别大于1000s的时候，觉得自己无能为力了，因为需要很久才能进行同步好。所以就会自动退出。希望你先用ntpdate进行时间同步，再启动ntpd。

网上很多时候说可以设置ntpdate为crontab来进行时间同步，这个其实是非常不建议的。特别当业务已经在运行的时候，一旦出现时间问题，再强制ntpdate，你的业务，比如插入数据库的数据可能出现乱序的情况，后插入的数据插入时间比先插入的早了。

当然这里明确一点，ntpd同步的是软件时间，硬件时间并没有进行同步和修改，所以很有可能，当服务器重启的时候，硬件时间又读取进入到软件时间，和时间服务器的时间差别很大，那么就需要你进行ntpdate先强制修改。

使用`service ntpd start`就可以启动ntp的服务器了。

我们可以使用
```
[root@192-168-34-2 ~]# ntpq -pn
     remote           refid      st t when poll reach   delay   offset  jitter
==============================================================================
-193.228.143.13  194.58.202.148   2 u    8   64  367  414.438  -22.479  57.348
+37.187.100.18   131.188.3.221    2 u    2   64  377  376.349  -15.123  27.936
+85.199.214.100  .GPS.            1 u    -   64  377  361.734   19.565  44.108
*85.199.214.101  .GPS.            1 u    3   64  377  356.713    9.497  45.230
```

看到34-2这个机器已经和前面带*号的GPS(第一层级)的时间服务器（85.199.214.101）的机器同步了。（如果还没有，则你需要等待足够长的时间）

下面操作另外两台ntp客户端。他们也是一样启动ntpd服务器，但是它的server就设置为34-2这个机器。（当然如果为了安全起见，可以设置两个局域网内的ntp 服务器）

下面我们就需要修改另外两个机器上的/etc/ntpd.conf，将这几行删除
```
server 0.centos.pool.ntp.org iburst
server 1.centos.pool.ntp.org iburst
server 2.centos.pool.ntp.org iburst
server 3.centos.pool.ntp.org iburst
```
增加下面这行
```
server 192.168.34.2
```
这里要对5行进行操作。
首先增加一个hostgroup
```
[ntp_client]
192.168.34.3    ansible_connection=ssh          ansible_user=root       ansible_ssh_pass=123456
192.168.34.4    ansible_connection=ssh          ansible_user=root       ansible_ssh_pass=123456
```
playbook如下:
```
- hosts: ntp_client
  tasks:
  - name: delete x.centos
    lineinfile:
      dest: /etc/ntp.conf
      state: absent
      regexp: '^server {{ item.server }} iburst'
    with_items:
      - { server : 0.centos.pool.ntp.org }
      - { server : 1.centos.pool.ntp.org }
      - { server : 2.centos.pool.ntp.org }
      - { server : 3.centos.pool.ntp.org }
  - name: add server
    lineinfile:
      dest: /etc/ntp.conf
      state: present
      regexp: '^server 192.168.34.2'
      line: 'server 192.168.34.2'
  - name: start ntp service
    service:
      name: ntpd
      state: started
      enabled: yes
```

这个playbook就有点长了，用了with_items等高级用法。写ansible的playbook是个体力活，调试ansible的playbook是个技术活，ansible提供了很多种调试的方式，其中一种，就是使用 --check 命令来预演会做一些什么操作。而可以使用--diff看有哪些文件进行了修改。

```
ansible-playbook /etc/ansible/playbooks/ntp_client.yml --check --diff[root@192-168-34-2 ~]# ansible-playbook /etc/ansible/playbooks/ntp_client.yml --check --diff

PLAY [ntp_client] **********************************************************************************************************************************************************

TASK [Gathering Facts] *****************************************************************************************************************************************************
ok: [192.168.34.4]
ok: [192.168.34.3]

TASK [delete x.centos] *****************************************************************************************************************************************************
ok: [192.168.34.3] => (item={u'server': u'0.centos.pool.ntp.org'})
--- before: /etc/ntp.conf (content)
+++ after: /etc/ntp.conf (content)
@@ -18,7 +18,6 @@

 # Use public servers from the pool.ntp.org project.
 # Please consider joining the pool (http://www.pool.ntp.org/join.html).
-server 0.centos.pool.ntp.org iburst
 server 1.centos.pool.ntp.org iburst
 server 2.centos.pool.ntp.org iburst
 server 3.centos.pool.ntp.org iburst

changed: [192.168.34.4] => (item={u'server': u'0.centos.pool.ntp.org'})
ok: [192.168.34.3] => (item={u'server': u'1.centos.pool.ntp.org'})
--- before: /etc/ntp.conf (content)
+++ after: /etc/ntp.conf (content)
@@ -19,7 +19,6 @@
 # Use public servers from the pool.ntp.org project.
 # Please consider joining the pool (http://www.pool.ntp.org/join.html).
 server 0.centos.pool.ntp.org iburst
-server 1.centos.pool.ntp.org iburst
 server 2.centos.pool.ntp.org iburst
 server 3.centos.pool.ntp.org iburst


changed: [192.168.34.4] => (item={u'server': u'1.centos.pool.ntp.org'})
--- before: /etc/ntp.conf (content)
+++ after: /etc/ntp.conf (content)
@@ -20,7 +20,6 @@
 # Please consider joining the pool (http://www.pool.ntp.org/join.html).
 server 0.centos.pool.ntp.org iburst
 server 1.centos.pool.ntp.org iburst
-server 2.centos.pool.ntp.org iburst
 server 3.centos.pool.ntp.org iburst

 #broadcast 192.168.1.255 autokey	# broadcast server

 ...

 --- before: /etc/ntp.conf (content)
+++ after: /etc/ntp.conf (content)
@@ -56,3 +56,4 @@
 # CVE-2013-5211 for more details.
 # Note: Monitoring will not be disabled with the limited restriction flag.
 disable monitor
+server 192.168.34.2

changed: [192.168.34.4]

TASK [start ntp service] ***************************************************************************************************************************************************
changed: [192.168.34.4]
changed: [192.168.34.3]

PLAY RECAP *****************************************************************************************************************************************************************
192.168.34.3               : ok=4    changed=1    unreachable=0    failed=0
192.168.34.4               : ok=4    changed=3    unreachable=0    failed=0

```

告诉我会在192.168.34.3机器上执行1个操作，192.168.34.4执行3个操作，这些操作都会成功。

我们不妨在192.168.34.3先执行这个脚本

```
[root@192-168-34-2 ~]# ansible-playbook /etc/ansible/playbooks/ntp_client.yml --limit '192.168.34.3'

PLAY [ntp_client] **********************************************************************************************************************************************************

TASK [Gathering Facts] *****************************************************************************************************************************************************
ok: [192.168.34.3]

TASK [delete x.centos] *****************************************************************************************************************************************************
ok: [192.168.34.3] => (item={u'server': u'0.centos.pool.ntp.org'})
ok: [192.168.34.3] => (item={u'server': u'1.centos.pool.ntp.org'})
ok: [192.168.34.3] => (item={u'server': u'2.centos.pool.ntp.org'})
ok: [192.168.34.3] => (item={u'server': u'3.centos.pool.ntp.org'})

TASK [add server] **********************************************************************************************************************************************************
ok: [192.168.34.3]

TASK [start ntp service] ***************************************************************************************************************************************************
changed: [192.168.34.3]

PLAY RECAP *****************************************************************************************************************************************************************
192.168.34.3               : ok=4    changed=1    unreachable=0    failed=0
```

然后再批量执行：

```
ansible-playbook /etc/ansible/playbooks/ntp_client.yml
```

我们随便登陆一台机器看

```
[vagrant@192-168-34-4 ~]$ ntpq -np
     remote           refid      st t when poll reach   delay   offset  jitter
==============================================================================
*192.168.34.2    119.28.183.184   3 u   12   64  177    0.315   12.492   6.351
```

已经同步了。
