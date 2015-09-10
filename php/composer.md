# composer install 为什么这么慢？

下面是一个composer install（在没有composer cache的情况下）做的所有事情：

    [vagrant@localhost composer]$ ../composer_git/bin/composer install -vvv
    Reading ./composer.json
    Loading config file /home/vagrant/.composer/config.json
    Loading config file /home/vagrant/.composer/auth.json
    Loading config file ./composer.json
    Executing command (CWD): git describe --exact-match --tags
    Executing command (CWD): git branch --no-color --no-abbrev -v
    Executing command (CWD): hg branch
    Executing command (CWD): svn info --xml
    Failed to initialize global composer: Composer could not find the config file: /home/vagrant/.composer/composer.json
    To initialize a project, please create a composer.json file as described in the https://getcomposer.org/ "Getting Started" section
    Loading composer repositories with package information
    Downloading http://packagist.org/packages.json
    Writing /home/vagrant/.composer/cache/repo/http---packagist.org/packages.json into cache
    Installing dependencies (including require-dev)
    Downloading http://packagist.org/p/provider-2013$8e290f3d47387c614761a9dc40a2ef6fb7dafb0cfe2264296e8fab2c6ee36bff.json
    Writing /home/vagrant/.composer/cache/repo/http---packagist.org/p-provider-2013.json into cache
    Downloading http://packagist.org/p/provider-2014$c0d0e03ec56584b3bf3148ac1565d89e789a57b90d72f83a7a54a55fbfc4f083.json
    Writing /home/vagrant/.composer/cache/repo/http---packagist.org/p-provider-2014.json into cache
    Downloading http://packagist.org/p/provider-2014-07$9b2d66a77e2f17ca1c18602419a2b53b00d42e0010d0a64fbbdcc1a01bbe092b.json
    Writing /home/vagrant/.composer/cache/repo/http---packagist.org/p-provider-2014-07.json into cache
    Downloading http://packagist.org/p/provider-2014-10$ed15097a7afa5a3f48b27f0ce38c5e3e8943514a0bdfd1898af31c9f8f913edb.json
    Writing /home/vagrant/.composer/cache/repo/http---packagist.org/p-provider-2014-10.json into cache
    Downloading http://packagist.org/p/provider-2015-01$3180dce46ea79fa77320185df239a62c07f6dbdeb21bc8ac6cd85b5d911a21ea.json
    Writing /home/vagrant/.composer/cache/repo/http---packagist.org/p-provider-2015-01.json into cache
    Downloading http://packagist.org/p/provider-2015-04$7e98f73b92b237ae4f6b07c8b8bd2e754357c86214cddf53cfafe8554b30f8b4.json
    Writing /home/vagrant/.composer/cache/repo/http---packagist.org/p-provider-2015-04.json into cache
    Downloading http://packagist.org/p/provider-archived$dfa1d92d2697fc375a1d522ab573634ee18807646f4abc322b6933157a07b829.json
    Writing /home/vagrant/.composer/cache/repo/http---packagist.org/p-provider-archived.json into cache
    Downloading http://packagist.org/p/provider-latest$115a50bcbcb32507b9b7b41a1d44b80ddd4848fb12cefee5769e9eb71769f7a8.json
    Writing /home/vagrant/.composer/cache/repo/http---packagist.org/p-provider-latest.json into cache
    Downloading http://packagist.org/p/monolog/monolog$c1954eb1d33e701ea323b97ff003a6495c79b138fe68a9087a9dce1d06e90ebc.json
    Writing /home/vagrant/.composer/cache/repo/http---packagist.org/provider-monolog$monolog.json into cache
      - Installing monolog/monolog (1.0.0)
    Downloading https://api.github.com/repos/Seldaek/monolog/zipball/433b98d4218c181bae01865901aac045585e8a1a
        Downloading: 100%
    Writing /home/vagrant/.composer/cache/files/monolog/monolog/433b98d4218c181bae01865901aac045585e8a1a.zip into cache
        Extracting archive
    Executing command (CWD): unzip '/vagrant/composer/vendor/monolog/monolog/2db4c7a59b236e77c15ff6a4f279a2c6' -d '/vagrant/composer/vendor/composer/abaad4e5' && chmod -R u+w '/vagrant/composer/vendor/composer/abaad4e5'

        REASON: Required by root: Install command rule (install monolog/monolog 1.0.0)

    Writing lock file
    Generating autoload files

composer 在install的时候会做这几个事情:
* 去packagist.org中寻找对应需要的包的版本信息和下载地址
* 循环下载对应的包
* 解压安装对应的包

我们平时使用composer慢就可能在第一步和第二步出现慢。而第三步，由于php的版本或者依赖限制，也有可能安装失败。
第一步中的packagist.org保存了所有的第三方包的信息。要把这个信息文件从国外的网站拉取下来，这个本身就可能非常慢。
第二步获取了包信息之后，我们就需要把相关的包获取下来，这个时候如果包所在的地址(现在大多数包都放在github上了)访问非常慢，那么这一步就会非常慢了。

解决慢的办法有几个：
1 使用国内镜像。

* (http://pkg.phpcomposer.com/repo/packagist/)[http://pkg.phpcomposer.com]
* (http://comproxy.cn/repo/packagist)[https://phphub.org/topics/57]
* (https://toran.reimu.io/repo/packagist/)[https://toran.reimu.io/]

2 我们可以不可以自己搭建镜像呢？

可以的，这里有个开源项目(toran proxy)[https://toranproxy.com/]可以配合nginx很方便搭建属于自己的composer镜像。

# 自己的第三方包

还有一种需求，公司现在开发了一个第三方包，但是不希望开源到packagist.org上，只希望给自己公司内部使用。怎么办？

这个可以使用composer代理(satis)[https://github.com/composer/satis]来创建。搭建的方式也是非常简单的。其实上面说的toran proxy就是基于satis来创建的。

satis和toran的区别就是，satis只是做了代理，即将composer install的第一步做了替换，而toran则是将composer install的第一步和第二步都进行了替换。

# 结论

至此之后，再无composer install 慢的问题。