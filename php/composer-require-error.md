# 一次composer错误使用引发的思考

这个思考源自于一个事故。让我对版本依赖重新思考了一下。

# 事故现象

一个线上的管理后台，一个使用laravel搭建的管理后台，之前在线上跑的好好的，今天comopser install之后，出现错误信息：
```
[2019-02-25 16:00:33] production.ERROR: Parse error: syntax error, unexpected '?', expecting variable (T_VARIABLE) {"exception":"[object] (Symfony\\Component\\Debug\\Exception\\FatalThrowableError(code: 0): Parse error: syntax error, unexpected '?', expecting variable (T_VARIABLE) at /xxxx/application/estimate-admin/vendor/symfony/translation/Translator.php:89)
```

# 事故分析

这个是个底层库，基本上，一看就知道是版本兼容问题，进去代码一看，里面有行代码是 `?string`，这个是php7.1引入的一种新特性。

看了下我的composer.json，里面主要引用的是laravel的框架，之前的laravel/framework的版本是"~5.5"

于是想当然以为是laravel的版本升级导致的，于是我把laravel的版本固定到一个子版本
```
"laravel/framework": "5.5.21",
```

发现还是会出现这个错误。估摸可能不是laravel版本升级导致的。于是从laravel的版本依赖追到问题的包"symfony/translation"。

链条如下：

```
我的项目 "laravel/framework": "5.5.21",
  laravel/framework "symfony/http-kernel": "~3.3",
    symfony/http-kernel（3.3.13版本） "symfony/translation": "~2.8|~3.0",
    symfony/http-kernel（3.4版本） "symfony/translation": "~2.8|~3.0|~4.0",
```
symfony/translation3.4版本：
```
public function __construct($locale, $formatter = null, $cacheDir = null, $debug = false)
```

而在4.0的时候加入了7.1的特性
```
 public function __construct(?string $locale, MessageFormatterInterface $formatter = null, string $cacheDir = null, bool $debug = false)
```

我机器上的版本是PHP 7.0。所以导致了在composer升级的时候symfony/http-kernel也升级，带来了symfony/translation升级到4.x,引入了PHP7.1的新特性。


# 解决方法

升级线上机器PHP版本是不可能的事情。于是我只能强制限定版本号。

直接在最上层我的项目中require symfony/translation，并且指定版本号。

```
"symfony/translation" : "3.3.13"
```

重新composer update 就可以了。

# 思考

这是一个典型的依赖包升级导致的业务应用出错的案例。symfony/translation 从 3.3.13 升级到4.\*，需要的PHP版本从7.0升级到7.1。这样的升级，laravel/framework 版本 v5.5.21 是无感知的。

而我们看 laravel/framework v5.5.21 的(comopser.json)[https://github.com/laravel/framework/blob/v5.5.21/composer.json]
```
{
    "name": "laravel/framework",
    "description": "The Laravel Framework.",
    ...
    "require": {
        "php": ">=7.0",
        "ext-mbstring": "*",
        "ext-openssl": "*",
        ...
        "symfony/http-kernel": "~3.3",
    },
    ...
}
```

这里的 PHP >= 7.0 是不是格外扎眼，根本已经不靠谱了。

# 真正解决办法

哈，其实这里并没有结束。这个问题包版本依赖其实各个包都没有问题。

其实这里有一个问题，我打包机器的PHP版本是7.1，但是线上机器是7.0.0，所以会导致这个问题。

其实composer比我们想象的更为强大。它会根据你当前机器的PHP版本，判断你的所有依赖分别使用什么版本，在composer update的时候，会根据所有依赖的版本需求选择一个最好的版本。

所以我把我的打包机器上的PHP切换成7.0，查看生成的composer.lock，里面的symfony/translation就限制到使用3.3.x版本 就不会出现这个问题了。

# composer的正确使用姿势

## 是否要将composer.lock加入到git库

这个是我这次犯的一个错误，没有将composer.lock进入版本库，打包机器composer install的时候就相当于update操作了。对于业务来说，这个是不对的。业务要做的事情是保证业务稳定性，其实任何的库依赖的升级，都需要经过业务的测试和验证才能上线。所以，这里强烈建议在业务项目里面，将composer.lock强制加入git代码库中。

## 是否要使用自动升级

版本依赖的时候，使用～，^符号会在composer udpate的时候根据依赖包已经有的类库。

我理解自动升级的机制有好也有坏处，这个就相当于把主动权（这里已经说的是update的主动权）放在哪里。作为一个基础类库，我当然希望你使用我的时候能相信我，我的每次版本升级都是兼容的，也不会引入bug。所以类库是会希望你会使用自动升级。这样我的一些bug修复，在你update的时候你就会自动下载并且修复了。

但是对于业务来说，业务稳定是死要求。一旦我update的时候，我使用了你的新下载的包，这个实际上就有可能引入一个bug。没有经过完整的测试，是不应该做这种操作的。

但是实际上，我们是无法完全杜绝这个情况，比如你的一个lib包依赖了另外一个lib包的时候，它如果使用了自动升级，你是完全没有办法的。

所以一旦我们使用包依赖，自动升级的事情，是无法杜绝的。

## 慎用update

使用update操作的时候，必须想到会引发什么操作，尽量将composer.lock做下差异比对，明白下前后两个依赖包差别在哪里。

# 总结

包依赖问题，不仅php有，golang也有，基本注意点都是如上，一样的。
