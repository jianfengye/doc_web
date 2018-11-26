# 从零开始搭建运维体系 - ulimit

我们现在需要修改操作系统的limit限制，我们主要要修改company这个用户的最大进程数和最大文件打开数。

这个限制是放在/etc/security/limits.conf文件中，每种限制分为soft和hard类型，他们的区别就是软限制可以在进程的程序过程中自行改变，而硬限制不行。硬限制是指资源和节点的绝对限制，在任何情况下都不能超过这个限制，软限制则是在一定时间范围内可以超过限制额度。软限制一定小于或者等于硬限制。

我们可以写ansible-playbook修改limits.conf这个文件。但是更为稳妥的做法是使用pam_limits模块。

play-book如下：
```
- hosts: all
  tasks:
  - name: config system setting
    pam_limits:
      domain: company
      limit_type: "{{ item.limit_type }}"
      limit_item: "{{ item.limit_item }}"
      value: "{{ item.value }}"
    with_items:
      - { limit_type: '-', limit_item: 'nofile', value: 65536 }
      - { limit_type: '-', limit_item: 'nproc', value: 65536 }
  - name: reload settings from all system configuration files
    shell: sysctl --system
```

执行操作，切换到company账号

```
[root@192-168-34-2 ~]# su company
[company@192-168-34-2 root]$ ulimit -a
core file size          (blocks, -c) 0
data seg size           (kbytes, -d) unlimited
scheduling priority             (-e) 0
file size               (blocks, -f) unlimited
pending signals                 (-i) 3901
max locked memory       (kbytes, -l) 64
max memory size         (kbytes, -m) unlimited
open files                      (-n) 65536
pipe size            (512 bytes, -p) 8
POSIX message queues     (bytes, -q) 819200
real-time priority              (-r) 0
stack size              (kbytes, -s) 8192
cpu time               (seconds, -t) unlimited
max user processes              (-u) 65536
virtual memory          (kbytes, -v) unlimited
file locks                      (-x) unlimited

看到的open files和max user processes都变化了。
