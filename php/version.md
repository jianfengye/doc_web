# php版本历史

php最初就是为了快速构建一个web页面而迅速被大家广为接受的。它的好处是在代码中能内嵌html的代码，从而让程序员能再一个页面中同时写html代码和php代码就能生成一个web页面。

这篇文章用时间轴的角度来记录一下php的历史。

## PHP版本历史

### 1995年初

PHP1.0诞生

Rasmus Lerdof发明了PHP，这是简单的一套Perl脚本，用来跟踪访问者的信息。这个时候的PHP只是一个小工具而已，它的名字叫做“Personal Home Page Tool”（个人主页小工具）。

### 1995年6月

PHP2.0诞生

Rasmus Lerdof用C语言来重新开发这个工具，取代了最初的Perl程序。这个新的用C写的工具最大的特色就是可以访问数据库，可以让用户简单地开发动态Web程序了。这个用C写的工具又称为PHP/FI。它已经有了今天php的一些基本功能了。

自Rasmus在1995年6月将PHP/FI发布源码之后，到1997年，全世界大约有几千个用户（估计）和大约50000个域名安装。

### 1998年6月

PHP3.0诞生

虽然说98年6月才正式发布php3.0,但是在正式发布之前，已经经过了9个月的公开测试了。

Andi Gutmans和Zeev Suraski加入了PHP开发项目组。这是两个以色列工程师，他们在使用PHP/FI的时候发现了PHP的一些缺点，然后决定重写PHP的解析器。注意，在这个时候，PHP就不再称为Personal Home Page了。而改称为PHP：Hypertext Preprocessor。

PHP3是最像现在使用的php的第一个版本，这个重写的解释器也是后来Zend的雏形。PHP3.0的最强大的功能就是它的可扩展性。它提供给第三方开发者数据库，协议，和API的基础结构之外，还吸引了大量的开发人员加入并提交新的模块。

### 2000年5月

PHP4.0发布

Andi Gutmans和Zeev Suranski在4.0做的最大的动作就是重写了PHP的代码，发明了Zend引擎来增强程序运行时的性能和PHP的模块性。这个Zend实际上就是Andi和Zeev名字缩写的合称。

使用了Zend引擎，PHP获得了更高的性能之外，也有其他一些关键的功能，包括支持更多的web服务器；HTTP Session的支持；输出缓冲等。

### 2004年7月

PHP5.0发布

PHP5.0的核心是Zend引擎2代。它引入了新的对象模型和大量的新功能。比如引入了PDO（PHP Data Object）

### 现在（2014年2月）

最新的PHP 已经发布到5.6了，据说PHP6.0已经在开发过程中了。所有php的历史代码可以在[PHP 博物馆](http://museum.php.net/)找到。

## php最近几个版本的功能描述

### php4.0

以Zend引擎作为解析器

### php4.1

加入超全局变量功能，包括$_GET,$_POST,$_SESSION等

### php4.2

从网络接收的数据将不会设置成全局变量，增加程序的安全性。

### php4.3

加入命令档，成为CLI

### php4.4

加入phpize和php-config的man页面

### php5.0

- 使用了Zend 2 引擎。
- 增加了新关键字，包括this,try,catch,public,private,protected等
- strrpos() 和 strripos() 如今使用整个字符串作为 needle。
- 非法使用字符串偏移量会导致 E_ERROR 而不是 E_WARNING。一个非法使用的例子：$str = 'abc'; unset($str[0]);.
- array_merge() 被改成只接受数组。如果传递入非数组变量，对每个此类参数都会发出一条 E_WARNING 信息。要小心因为你的代码有可能疯狂发出 E_WARNING。
- 如果 variables_order 包括“S”，$_SERVER 应该带有 argc 和 argv 被产生。如果用户特别配制系统不创建 $_SERVER，那此变量当然就不存在了。改变的地方是不管 variables_order 怎么设定，在 CLI 版本中 argc 和 argv 总是可用的。本来 CLI 版不是总会产生全局变量 $argc 和 $argv 的。
- 没有属性的对象不再被当成“empty”。
- get_class()，get_parent_class() 和 get_class_methods() 如今返回的类／方法名和定义时的名字一致（区分大小写），对于依赖以前行为（类／方法名总是返回小写的）的老脚本可能产生问题。一个可能的解决方法是在脚本中搜索所有这些函数并使用 strtolower()。 区分大小写的改变也适用于魔术常量 __CLASS__，__METHOD__ 和 __FUNCTION__。其值都会严格按照定义时的名字返回（区分大小写）。
- ip2long() 在传递入一个非法 IP 作为参数时返回 FALSE，不再是 -1。
- 如果有函数定义在包含文件中，则这些函数可以在主文件中使用而与是否在 return 指令之前还是之后无关。如果文件被包含两次，PHP 5 会发出致命错误，因为函数已经被定义，而 PHP 4 不管这个。因此推荐使用 include_once 而不要去检查文件是否已被包含以及在包含文件中有条件返回。
- include_once 和 require_once 在 Windows 下先将路径规格化，因此包含 A.php 和 a.php 只会把文件包含一次。

更多参考资料[php5的新特性](http://www.php.net/manual/zh/migration5.incompatible.php)

### php 5.1

- 重写了数据处理部分的代码
- PDO扩展默认启动
- 性能优化
- 超过30个新函数
- 超过400个bug修复

### php5.2

- CLI SAPI不再从php.ini和php-cli.ini中获取当前目录信息。这是从安全角度考虑的。
- 对0取模的时候会提示Warning信息。
- 对象可以通过__toString()函数被当做字符串调用。
- 禁止设置抽象类的静态方法
- 增加RFC（data:stream）的支持。

更多参考资料[从5.1迁移到5.2](http://www.php.net/manual/zh/migration52.incompatible.php)

### php5.3

改动：

- realpath() 现在是完全与平台无关的. 结果是非法的相对路径比如 __FILE__ . "/../x" 将不会工作.
- call_user_func() 系列函数即使被调用者是一个父类也使用 $this.
- 数组函数 natsort(), natcasesort(), usort(), uasort(), uksort(), array_flip(), 和 array_unique() 将不再接受对象作为参数. 在将这些函数应用于对象时, 请首先将对象转换为数组.
- 按引用传递参数的函数在被按值传递调用时行为发生改变. 此前函数将接受按值传递的参数, 现在将抛出致命错误. 之前任何期待传递引用但是在调用时传递了常量或者字面值 的函数, 需要在调用前改为将该值赋给一个变量。
- __toString 魔术方法不再接受参数.
- 魔术方法 __get, __set, __isset, __unset, and __call 应该总是公共的(public)且不能是静态的(static). 方法签名是必须的.
- 现在 __call 魔术方法在访问私有的(private)和被保护的(protected)方法时被调用.
- 函数内 include 或者 require 一个文件时，文件内 将不能使用 func_get_arg(), func_get_args() 和 func_num_args() 函数。
- goto，namespace关键词被保留。

新功能：

- 添加了命名空间的支持.
- 添加了静态晚绑定支持.
- 增加了goto支持。
- 增加了闭包支持。
- 新增了两个魔术方法, __callStatic 和 __invoke.
- 添加了 Nowdoc 语法支持, 类似于 Heredoc 语法, 但是包含单引号.就是<<'EOF'这样的语法。
- 可使用双引号声明 Heredoc, 补充了 Nowdoc 语法.
- 可在类外部使用 const 关键词声明 常量.
- 三元运算操作符有了简写形式: ?:.
- HTTP 流包裹器将从 200 到 399 全部的状态码都视为成功。
- 允许动态访问静态方法。
- 异常可以被内嵌
- 新增了循环引用的垃圾回收器并且默认是开启的.
- mail() 现在支持邮件发送日志. (注意: 仅支持通过该函数发送的邮件.)

更多参考资料[从5.2迁移到5.3](http://www.php.net/manual/zh/migration53.changes.php)

### php5.4

改动：

- 不再支持 安全模式 。任何依赖安全模式的应用在安全方面都需要进行调整。
- 移除 魔术引号 。为避免出现安全问题，依赖此特性的应用可能需要升级。 get_magic_quotes_gpc() 和 get_magic_quotes_runtime() 现在总是返回 FALSE 。 调用 set_magic_quotes_runtime() 将产生一个 E_CORE_ERROR 级别的错误。
- register_globals 和 register_long_arrays php.ini 指令被移除。
- 调用时的引用传递 被移除。就是不能有f(&$a)这样的形式。
- break 和 continue 语句不再接受可变参数（ 比如： break 1 + foo() * $bar; ）。像类似 break 2; 这样的固定参数仍可使用。受此变化影响，不再允许出现 break 0; 和 continue 0; 。
- 在 日期与时间扩展 中，不再支持时区使用 TZ（TimeZone）环境变量设置。必须使用 date.timezone php.ini 配置选项或 date_default_timezone_set() 函数来指定时区。PHP 将不再尝试猜测时区，而是回退到“UTC”并发出一条 E_WARNING 错误。
- 非数字的字符串偏移量，比如 $a['foo'] 此处 $a 是一个字符串，现在使用 isset() 时返回 false，使用 empty() 时返回 true，并产生一条 E_WARNING 错误。偏移量类型是布尔和 null 则产生一条 E_NOTICE 错误。 数字字符串（比如 $a['2'] ）仍像以前一样运行。注意像类似 '12.3' 和 '5 foobar' 这样的偏移量将被视为非数字并产生一条 E_WARNING 错误，但因为向后兼容的原因它们会被分别转换成 12 和 5 。 注意：下列代码返回不同的结果。 $str='abc';var_dump(isset($str['x'])); // 在 PHP 5.4 或更新版本返回 false，但在 PHP 5.3 或更低版本返回 true
- 数组转换成字符串将产生一条 E_NOTICE 级别的错误，但返回的结果仍是字符串 "Array" 。
- NULL 、FALSE 、或 一个空字符串被添加成一个对象的属性时将发出一条 E_WARNING 级别的错误，而不是 E_STRICT 。
- 现在参数名使用全局变量将会导致一个致命错误。禁止类似 function foo($_GET, $_POST) {} 这样的代码。
- Salsa10 和 Salsa20 哈希算法 被移除。
- 当使用两个空数组作为参数时， array_combine() 现在返回 array() 而不是 FALSE 。
- htmlentities() 将像 htmlspecialchars() 一样处理亚洲字符集，这是以前 PHP 版本的处理情况，但现在将会发出一条 E_STRICT 错误。
- 强烈建议不要再使用 eregi() ，此特性在最新版本中被移除。
- trait,callable,insteadof关键词被保留。

新特性：

- 新增支持 traits 。
- 新增短数组语法，比如 $a = [1, 2, 3, 4]; 或 $a = ['one' => 1, 'two' => 2, 'three' => 3, 'four' => 4]; 。
- 新增支持对函数返回数组的成员访问解析，例如 foo()[0] 。
- 现在 闭包 支持 $this 。
- 现在不管是否设置 short_open_tag php.ini 选项，<?= 将总是可用。
- 新增在实例化时访问类成员，例如： (new Foo)->bar() 。
- 现在支持 Class::{expr}() 语法。
- 新增二进制直接量，例如：0b001001101 
- 改进解析错误信息和不兼容参数的警告。
- SESSION 扩展现在能追踪文件的 上传进度 。
- 内置用于开发的 CLI 模式的 web server 。

更多参考资料[从5.3迁移到5.4](http://www.php.net/manual/zh/migration54.changes.php)

### php5.5

改动：

- 已放弃对 Windows XP 和 2003 的支持。构建 Windows 版本的 PHP 需要 Windows Vista 或更新的系统。
- pack() 和 unpack() 函数的变化 
- 移除 PHP logo GUIDs

新特性：

- 新增 Generators，包括yield关键字
- 新增 finally 关键字
- foreach 现在支持 list()
- empty() 现在支持传入一个任意表达式，而不仅是一个变量。
- 非变量array和string也能支持下标获取了
- 类名通过::class可以获取
- 增加了opcache扩展

更多参考资料[从5.4迁移到5.5](http://www.php.net/manual/zh/migration55.changes.php)

## 参考文章
[PHP 的历史](http://www.php.net/manual/zh/history.php.php)
