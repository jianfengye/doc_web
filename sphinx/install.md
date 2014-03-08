# Coreseek + Sphinx + Mysql + PHP构建中文检索引擎

##首先明确几个概念

[Sphinx](http://sphinxsearch.com/docs/)是开源的搜索引擎，它支持英文的全文检索。所以如果单独搭建Sphinx，你就已经可以使用全文索引了。但是往往我们要求的是中文索引，怎么做呢？国人提供了一个可供企业使用的，基于Sphinx的中文全文检索引擎。也就是说Coreseek实际上的内核还是Sphinx。那么他们的版本对应呢？

 

Coreseek发布了3.2.14版本和4.1版本，其中的3.2.14版本是2010年发布的，它是基于Sphinx0.9.9搜索引擎的。而4.1版本是2011年发布的，它是基于Sphinx2.0.2的。Sphinx从0.9.9到2.0.2还是有改变了很多的，有很多功能，比如sql_attr_string等是在0.9.9上面不能使用的。所以在安装之前请判断清楚你需要安装的是哪个版本，在google问题的时候也要弄清楚这个问题的问题和答案是针对哪个版本的。我个人强烈建议使用4.1版本。

 

网上有一篇[文章](http://wenku.baidu.com/link?url=NOIn-B2efDBl-YnXlczcnGx8F5twKwxgoMIqUs4oo3w_ho5FJbmI7jrdwOf4QonuTaspLcxN6AeRqfvEq1Un8obsezeRZjyQUHyUGPxZMoW)说的是Sphinx和Coreseek是怎么安装的，其中它的coreseek安装这部分使用[coreseek-4.1](http://www.coreseek.cn/products-install/)来替换就可以使用了。

 

## 详细步骤看上面篇文章就理解了，这里说一下我在安装过程中遇到的几个问题：

### 安装mmseg的时候，./configure出现错误：config.status: error: cannot find input file: src/Makefile.in

这个时候需要先运行下automake

结果我运行的时候竟然提示automake的版本不对

所以这个时候，你可能需要去官网下个对应的版本（有可能是需要老版本）再来运行

### 在安装csrf的时候，文档提示需要指定mysql，但是我的mysql是yum安装的，找不到安装路径

        ./configure 

        --prefix=/usr/local/coreseek --with-mysql=/usr/local/mysql

         --with-mmseg=/usr/local/mmseg --with-mmseg-includes=/usr/local/mmseg/include/mmseg/ --with-mmseg-libs=/usr/local/mmseg/lib/
 

 
yum安装的mysql的include和libs文件夹一般是安装在/usr/include/mysql和/usr/lib64/mysql下面

所以这里的--with-mysql可以使用--with-mysql-includes和--with-mysql-libs来进行替换。

        ./configure 

        --prefix=/usr/local/coreseek --with-mysql-includes=/usr/includes/mysql --with-mysql-libs=/usr/lib64/mysql/

         --with-mmseg=/usr/local/mmseg --with-mmseg-includes=/usr/local/mmseg/include/mmseg/ --with-mmseg-libs=/usr/local/mmseg/lib/
 

### 配置文件提示unknown key: sql_attr_string

如上文，就需要检查下自己的sphinx版本了

### 如何安装php的sphinx扩展

可以在这里（http://pecl.php.net/package/sphinx）找到sphinx的php扩展源码

注意，使用phpize，configure的时候可能会要求要安装libsphinxclient，它在coreseek-4.1-beta/csft-4.1/api/libsphinxclient/里面能找到，编译安装它以后就可以configure，make，生成动态so文件了。

### 如何配置sphinx.conf配置文件

最复杂的部分就是sphinx.conf配置文件的配置了，里面的注释代码非常多，我建议使用的时候把注释代码去掉，我贴出自己使用的最简单的一个成功的配置文件：

        source src1
        {
                type                    = mysql

                sql_host                = localhost
                sql_user                = yejianfeng
                sql_pass                = test
                sql_db                  = mysite
                sql_port                = 3306  # optional, default is 3306

                sql_query_pre           = SET NAMES utf8
                sql_query_pre           = SET SESSION query_cache_type=OFF

                sql_query               = select id, id AS id_new,name, name AS name_query,descr, descr AS descr_query,city FROM account
                sql_attr_string = name
                sql_attr_string = descr

                sql_query_info          = SELECT * FROM account WHERE id=$id
        }

        source src1throttled : src1
        {
                sql_ranged_throttle     = 100
        }

        index test1
        {
                source                  = src1
                path                    = /home/yejianfeng/instance/coreseek/var/data/test1
                docinfo                 = extern
                mlock                   = 0
                morphology              = none
                min_word_len            = 1
                charset_type = zh_cn.utf-8
                charset_dictpath  = /home/yejianfeng/instance/mmseg/etc/
                html_strip              = 0
        }



        indexer
        {
                mem_limit               = 256M
        }

        searchd
        {
                listen                  = 9312
                listen                  = 9306:mysql41

                log                     = /home/yejianfeng/instance/coreseek/var/log/searchd.log
                query_log               = /home/yejianfeng/instance/coreseek/var/log/query.log
                read_timeout            = 5
                client_timeout          = 300
                max_children            = 30
                pid_file                = /home/yejianfeng/instance/coreseek/var/log/searchd.pid
                max_matches             = 1000
                seamless_rotate         = 1
                preopen_indexes         = 1
                unlink_old              = 1
                mva_updates_pool        = 1M
                max_packet_size         = 8M
                max_filters             = 256
                max_filter_values       = 4096
        }
### php调用SphinxClient的例子如下：

首先要确保已经启动了searchd

        [yejianfeng@AY130416142121702aac etc]$ ps aux|grep searchd
        501      30897  0.0  0.0  60824  1396 pts/2    S    17:19   0:00 /home/yejianfeng/instance/coreseek/bin/searchd -c /home/yejianfeng/instance/coreseek/etc/sphinx.conf
        501      30999  0.0  0.0 103232   856 pts/2    S+   18:10   0:00 grep searchd

php提供的调用SphinxClient的接口

        <?php
        $s = new SphinxClient;
        $s->setServer("localhost", 9312);
        $s->setArrayResult(true);
        $s->setSelect();
        $s->setMatchMode(SPH_MATCH_ALL);

        $result = $s->query('美女', 'test1');
        print_r($result);

## 参考文章：

[Coreseek 4.1 参考手册](http://www.coreseek.cn/docs/coreseek_4.1-sphinx_2.0.1-beta.html)

http://www.coreseek.cn/