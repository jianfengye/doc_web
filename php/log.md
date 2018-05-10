# 业务日志实践

这几天在整理组内日志使用规范。对业务日志的使用又有了一些思考，这里记录下。

# 日志级别

这个是首先困惑的事情，日志分为几个级别？这个问题在不同的著名日志组件中都是不一样的：

比如 java 中的 log4j 使用的日志组件是：

* fatal：非常严重的错误，导致系统中止。期望这类信息能立即显示在状态控制台上。
* error：其它运行期错误或不是预期的条件。期望这类信息能立即显示在状态控制台上。
* warn：使用了不赞成使用的API、非常拙劣使用API, ‘几乎就是’错误, 其它运行时不合需要和不合预期的状态但还没必要称为 “错误”。期望这类信息能立即显示在状态控制台上。
* info：运行时产生的有意义的事件。期望这类信息能立即显示在状态控制台上。
* debug：系统流程中的细节信息。期望这类信息仅被写入log文件中。
* trace：更加细节的信息。期望这类信息仅被写入log文件中。

还有一个比较流行的标准，ietf（国际互联网工程任务组）提出的 syslog 协议，其中就有定义日志级别：

* 0       Emergency: system is unusable
* 1       Alert: action must be taken immediately
* 2       Critical: critical conditions
* 3       Error: error conditions
* 4       Warning: warning conditions
* 5       Notice: normal but significant condition
* 6       Informational: informational messages
* 7       Debug: debug-level messages

PHP 的 PSR-3 就遵照这个错误级别来定义[日志接口规范](https://github.com/php-fig/fig-standards/blob/master/accepted/PSR-3-logger-interface.md)

```
interface LoggerInterface
{
    /**
     * 系统不可用
     *
     */
    public function emergency($message, array $context = array());

    /**
     * **必须**立刻采取行动
     *
     * 例如：在整个网站都垮掉了、数据库不可用了或者其他的情况下，**应该**发送一条警报短信把你叫醒。
     *
     */
    public function alert($message, array $context = array());

    /**
     * 紧急情况
     *
     * 例如：程序组件不可用或者出现非预期的异常。
     *
     */
    public function critical($message, array $context = array());

    /**
     * 运行时出现的错误，不需要立刻采取行动，但必须记录下来以备检测。
     *
     */
    public function error($message, array $context = array());

    /**
     * 出现非错误性的异常。
     *
     * 例如：使用了被弃用的API、错误地使用了API或者非预想的不必要错误。
     *
     */
    public function warning($message, array $context = array());

    /**
     * 一般性重要的事件。
     *
     */
    public function notice($message, array $context = array());

    /**
     * 重要事件
     *
     * 例如：用户登录和SQL记录。
     *
     */
    public function info($message, array $context = array());

    /**
     * debug 详情
     *
     */
    public function debug($message, array $context = array());

    /**
     * 任意等级的日志记录
     *
     */
    public function log($level, $message, array $context = array());
}
```

但是确实，syslog这个规定的日志级别有的区分不是很大，区分不是很大对于使用者就很有问题。当使用者无法很快判断应该使用哪个日志级别的话，这个日志级别的制定就是一个不好的规定了。（比如 info 和 notice 不好判断有什么差别）。

日志级别这个话题我觉得针对不同的团队完全可以进行裁剪，即使是使用PSR-3, 也可以规定哪些日志级别是不使用的，哪些是使用的。

我建议使用下面的级别：

* FATAL: 严重错误日志,出现模块无法服务的错误,比如导致这个http服务挂掉了
* ERROR：普通错误日志,请求处理中出现非预期错误,比如http服务没有挂掉，但是这次请求挂掉了
* WARNING: 警告日志，请求处理中出现预期错误,比如http服务没有挂掉，但是这次请求也能继续，但是这里走到了异常处理的错误分支
* STRACE：特定日志级别，记录采样逻辑。这个级别的日志做采样记录。
* INFO(NOTICE): 通知日志，希望这类日志以某种形式呈现，例如系统的启动、停止、词表配置重载等这种操作
* TRACE: 请求跟踪日志，比如第三方调用，等记录，与strace不同的是这个是全量日志记录
* DEBUG: 代码调试日志, 只做调试使用，一般在线上不开启


上面的级别优先级是从上到下增加的。日志级别控制某个级别以下的所有级别是否需要记录。


# 日志使用
