# fastcgi的错误处理[未发布]

其实说fastcgi的错误处理，在nginx+fastcgi+php的模型中，实际上是php中抛出错误，然后由fastcgi将这个错误打出到nginx中，再由nginx进行错误日志收集。在这个模型中，我们需要研究下有哪几个开关会影响日志错误的收集，以及有几个地方会产生日志。

## 日志开关配置

### nginx中

#### access_log

配置http 请求日志log，所有请求都会在access_log中产生一条记录。

#### error_log

配置nginx请求中所有的错误日志。对于fastcgi来说，如果php中有抛出错误，会由fastcgi转发给nginx，nginx就将这个错误日志记录到error_log中。

### php代码中

#### error_reporting(E_ALL)

可以在代码中直接使用这样的代码来控制是否输出错误。

#### display_error

TODO:

### php.ini中

#### display_error

是否显示错误，和php代码中得display_error一样效果。

## 日志存储

### nginx中

#### access.log

存放地址由access_log命令进行配置。

#### error.log

存放地址由error_log命令进行配置。

### php中

#### 慢日志

// TODO:
