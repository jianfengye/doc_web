# PHP源码阅读之_PHP的启动周期

PHP的SAPI指的是PHP的对外通用接口，只要满足了SAPI的接口，你就可以在任何地方执行。

比如Apache的php_mod模块就实现了这个，在apache启动的时候，实例化PHP的全局变量。

PHP的启动结束的主要有几个方法：

* PHP_MINIT_FUNCTION 模块加载的时候调用
* PHP_RINIT_FUNCTION 请求进入的时候调用
* PHP_RSHUTDOWN_FUNCTION 请求结束的时候调用
* PHP_MSHUTDOEN_FUNCTION 模块关闭的时候调用

# CLI/CGI模式

CLI和CGI模式相比于Apache的php_mod的进程不一样的是，它是单进程，这类请求的模块加载，请求加载的过程在处理一次请求后就结束。

在模块加载之前，会有几个初始化的操作，合并到PHP流程里面就是：

* 初始化全局
* PHP_MINIT_FUNCTION 模块加载
* 初始化请求
* PHP_RINIT_FUNCTION 请求进入
* 执行具体操作
* PHP_RSHUTDOWN_FUNCTION 请求结束
* 请求关闭
* PHP_MSHUTDOEN_FUNCTION 模块关闭


初始化全局包括:

* 初始化全局变量
* 初始化全局常量（比如PHP_VERSION）
* 初始化Zend引擎和核心组件（比如内置函数strlen,define等）
* 解析php.ini
* 全局操作函数和变量的初始化（比如GET,POST）
* 初始化共享模块（比如/ext/standard/目录的模块）
* 禁用函数和类

初始化请求包括：

* 激活Zend引擎
* 激活SAPI
* 环境变量初始化
* 模块请求初始化

请求关闭包括：

* 调用所有的register_shutdown_function
* 执行所有的__destruct函数
* 将所有输出flush
* 发送HTTP响应头
* 遍历每个模块的关闭请求方法
* 销毁全局变量
* 关闭词法分析器，语法分析器，中间代码执行器
* 调用每个扩展的post-shutdown
* 关闭SAPI
* 关闭流的包装器，关闭流的过滤器
* 关闭内存管理
* 重新设置最大执行时间
