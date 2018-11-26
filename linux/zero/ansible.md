# 从零开始搭建运维体系 - ansible

基本配置好了局域网内的机器后，第一个遇到的问题就是如何批量操作这么多台机器，ansible就是这么一个自动化运维工具。

ansible是一个基于ssh的批量远程操作命令工具。它有分管理端和被管理端，管理端安装ansible，被管理端什么都不需要安装。这个是非常方便的。只要能远程登陆上就可以。

ansible有两种模式，ansible-hoc和ansible-playbook。简单来说,ansible-hoc就是可以在console上一次执行多个命令。ansible-playbook就是预先编写一个执行步骤，然后在不同机器上执行这个执行步骤。

# 配置好ssh的用户名密码

我选择使用root账号直接操作ssh的用户，这样我可以站在上帝视角做任何操作，当然这个也是一个双刃剑，也附带一定的危险性。

去每个机器上配置root密码：
```
passwd root
输入-123456
```

# 安装ansible的管理机

ansible的安装比较简单。我选用的是[yum安装](http://www.ansible.com.cn/docs/intro_installation.html#yum)

安装完成之后，所有的配置文件都在
```
/etc/ansible/
```
下面。

# ansible配置

修改ansible的默认host配置，在这个配置里面就可以配置上用户名和密码

```
[local]
localhost ansible_connection=local

[test]
192.168.34.3    ansible_connection=ssh          ansible_user=root       ansible_ssh_pass=123456

[all]
localhost       ansible_connection=local
192.168.34.3    ansible_connection=ssh          ansible_user=root       ansible_ssh_pass=123456
192.168.34.4    ansible_connection=ssh          ansible_user=root       ansible_ssh_pass=123456
"/etc/ansible/hosts" 50L, 1261C
```
我配置了三个组，其中一个组叫local，它只有ansible的管理机local，直接使用ansible_connection=local就表示是操作的本机。
另外一个组叫做all，这个all包含了三台机器，34.2～4（local）这个机器是本地机器的IP。
还有一个组叫做test，就代表我发布之前先执行这台程序，算是灰度发布。

# 测试执行第一条命令

第一条命令长这样：
```
ansible all -m ping
```

这个就是ansible-hoc模式，其中all 表示ansible操作在all这个组，-m表示调用的是什么模块，ansible有很多模块，这个ping是最常用的模块之一。其他的常用模块使用可以参考[ansible常用模块介绍](http://www.ywnds.com/?p=6051)

你有可能返回这个错误
```
192.168.34.3 | FAILED | rc=-1 >>
Using a SSH password instead of a key is not possible because Host Key checking is enabled and sshpass does not support this.  Please add this host's fingerprint to your known_hosts file to manage this host.
```

这个就是说需要你把远程服务器加入到known_hosts中，当然你可以不需要加入，修改ansible配置文件`/etc/ansible/ansible.cfg`

修改这个配置
```
host_key_checking = False
```
就可以不需要known_hosts就行了

```
[root@localhost var]# ansible all -m ping
localhost | SUCCESS => {
    "changed": false,
    "ping": "pong"
}
192.168.34.3 | SUCCESS => {
    "changed": false,
    "ping": "pong"
}
192.168.34.4 | SUCCESS => {
    "changed": false,
    "ping": "pong"
}
```

# 使用ansible创建公司用户

我们希望创建一个公司用户，以后业务应用都用这个用户进行运行，而不是使用root。那么用ansible如何操作？

我们可以使用user模块，使用模块的好处是很容易写出幂等的命令。可以多次执行。如果你不知道user模块有哪些可以配置，就使用命令`ansible-doc -s user` 来查看，或者去[官网](https://docs.ansible.com/ansible/latest/modules/user_module.html#user-module)查看

```
ansible test -m user -a 'name=company shell=/bin/bash home=/home/company state=present password=$6$mysecretsalt$RGyLyUuTb8ssDdlZDeLV0gn3khYBQkdIAuyRwAVr9WcH5FpUH6V7qBd4ZI59DXaAuL9Zmift0CTv8mCsQG3Ws.'
```

这里的password是怎么生成的呢？

可以使用这个命令：
```
ansible all -i localhost, -m debug -a "msg={{ 'company123' | password_hash('sha512', 'mysecretsalt') }}"
```
将输出的密码复制到用户里面

```
192.168.34.3 | CHANGED => {
    "append": false,
    "changed": true,
    "comment": "",
    "group": 1001,
    "home": "/home/company",
    "move_home": false,
    "name": "company",
    "password": "NOT_LOGGING_PASSWORD",
    "shell": "/bin/bash",
    "state": "present",
    "uid": 1001
}
```

创建成功了。这里的state=present代表如果这个用户不存在就创建，如果这个用户存在就不创建。所以这个命令是幂等的。

然后我就可以对所有机器执行这个操作了。

```
[root@localhost var]# ansible all -m user -a 'name=company shell=/bin/bash home=/home/company state=present password=$6$mysecretsalt$RGyLyUuTb8ssDdlZDeLV0gn3khYBQkdIAuyRwAVr9WcH5FpUH6V7qBd4ZI59DXaAuL9Zmift0CTv8mCsQG3Ws.'
```

# 批量修改hostname

我希望修改每个机器的hostname, 根据它的ip（ansible定义给它的host）修改为xx-xx-xx-xx，把ip中的点换成-

这个时候就需要用到playbook了。其实playbook本质就是yml。你可以在yml中定义你这个脚本需要执行在哪个机器，执行哪些命令。

比如修改hostname的playbook如下：
```
- hosts: test
  tasks:
  - hostname : name={{ ansible_host.split('.') | join('-') }}
```

操作在test机器组，执行hostname的命令，其中的ansible_host是我ansible调用的host，先用.切割成数组，再用-拼接起来。

如果你还对这些变量和命令比较不熟悉，建议先使用debug模块在ansible-hoc上尝试一下：
```
[root@localhost ~]# ansible test  -m debug -a "msg={{ansible_host.split('.') | join('-')}}"
192.168.34.3 | SUCCESS => {
    "msg": "192-168-34-3"
}
```

好，执行这个playbook:

```
[root@localhost ~]# ansible-playbook /etc/ansible/playbooks/change_hostname.yml

PLAY [test] ******************************************************************************************************************************************

TASK [Gathering Facts] *******************************************************************************************************************************
ok: [192.168.34.3]

TASK [hostname] **************************************************************************************************************************************
changed: [192.168.34.3]

PLAY RECAP *******************************************************************************************************************************************
192.168.34.3               : ok=2    changed=1    unreachable=0    failed=0
```

登陆到机器上，或者直接使用shell模块看，成功了
```
[root@localhost ~]# ansible test -m shell -a 'hostname'
192.168.34.3 | CHANGED | rc=0 >>
192-168-34-3
```

对所有机器执行这个操作，修改/etc/ansible/playbooks/change_hostname.yml里面的hosts为all。

# 需要把这个company用户加入到sudoer列表中, 并且把我管理机上的company用户设置为授信用户

首先我管理机上company用户先创建公钥，切换到company用户上，命令ssh-keygen生成公钥和私钥，公钥在`/home/company/.ssh/id_rsa.pub`

授信就是把公钥放到需要授权的机器上的authroized_key中。

增加用户到sudoers呢就是在/etc/sudoers中增加一行用户信息，并且设置NO PASSWORD。

ansible中最爽的就是有模块的概念，这两个操作都有模块提供，一个是[linefile模块](http://blog.51cto.com/zouqingyun/1882367)，一个是authorized_key模块

```
- hosts: test
  tasks:
  - name: 确保存在company这个group
    group: name=company state=present
  - name: 允许company组的人可以无密码切换sudo
    linefile:
      dest: /etc/sudoers
      state: present
      regexp: '^%company'
      line: '%company ALL=(ALL) NOPASSWD: ALL'
      validate: 'visudo -cf %s'
  - name: 为company用户设置authorized_key
    authorized_key: user=company key="{{item}}"
    with_file:
      - /home/company/.ssh/id_rsa.pub
```
完整playbook如上。
