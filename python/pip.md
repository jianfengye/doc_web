# python中的pip

python有两个著名的包管理工具，其中，pip是一个。它对python的包进行管理和升级等操作。

问题一：pip本地的模块安装在哪里？

使用pip install numpy安装numpy，再安装一次，pip install numpy，就可以查看到本地的pip包安装地址了。

```
python pip install numpy
Requirement already satisfied: numpy in /System/Library/Frameworks/Python.framework/Versions/2.7/Extras/lib/python
```

问题二：如何安装git上的项目？

```
pip install git+https://github.com/kennethreitz/requests.git
```

问题三：安装删除包？

```
pip install <package>    // 安装
pip uninstall <package>  // 删除
pip install -U <package> // 更新
```

问题四：pip的包说明在哪里？

pip的所有包都可以在 https://pypi.python.org/packages 里面找到。


问题五：如何安装pip包的指定版本？

通过使用==, >=, <=, >, <来指定一个版本号
```
$ pip install SomePackage            # latest version
$ pip install SomePackage==1.0.4     # specific version
$ pip install 'SomePackage>=1.0.4'     # minimum version

```


问题六：如何迁移一个机器上的所有包到另外一个机器？

```
先查找本地使用了那些包
pip freeze > requirements.txt

然后再安装包
pip install -r requirements.txt
```
