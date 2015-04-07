# postgres中的中文分词zhparser

## postgres中的中文分词方法

基本查了下网络，postgres的中文分词大概有两种方法：

* Bamboo
* zhparser

其中的Bamboo安装和使用都比较复杂，所以我选择的是zhparser

## zhparse基于scws

[scws](http://www.xunsearch.com/scws/)是简易中文分词系统的缩写，它的原理其实很简单，基于词典，将文本中的内容按照词典进行分词，提取关键字等。github上的地址在[这里](https://github.com/hightman/scws)。它是xunsearch的核心分词系统。

而[zhparser](https://github.com/amutu/zhparser)是基于scws来做的postgres的扩展。

## 安装

基本按照[zhparser](https://github.com/amutu/zhparser) 中的步骤就可以了。

## 使用

在postgres.conf中你可以设置下面的参数：

```
zhparser.punctuation_ignore = f

zhparser.seg_with_duality = f

zhparser.dict_in_memory = f

zhparser.multi_short = f

zhparser.multi_duality = f

zhparser.multi_zmain = f

zhparser.multi_zall = f
```

还可以设置自有词典
```
zhparser.extra_dicts = 'dict_extra.txt,mydict.xdb'
```

虽然项目文档说用txt也是可以的，但是我自己尝试过的时候，自有词典只能使用xdb

## sql使用

按照文档说明

```
CREATE EXTENSION zhparser;
CREATE TEXT SEARCH CONFIGURATION testzhcfg (PARSER = zhparser);
ALTER TEXT SEARCH CONFIGURATION testzhcfg ADD MAPPING FOR n,v,a,i,e,l WITH simple;
```

在这三步之后，你就创建了一个testzhcfg的解析器

to_tsvector， to_tsquery 其实都是有第一个参数的，第一个参数表示解析器是什么。比如你想要进行文本搜索，可以使用下面的语句：

```
SELECT id FROM question_view
            WHERE to_tsvector('testzhcfg', content) @@ to_tsquery('testzhcfg', '宝马') AND status = 1  ORDER BY id DESC
```

这个语句是基于视图question_view的