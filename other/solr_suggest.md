# solr的suggest模块

solr有个suggest模块，用来实现下拉提醒功能，就是输入了一个文本之后，进行文本建议查找的功能。

# suggest请求的url

```
http://localhost:8983/solr/hotquestions/suggest?suggest=true&suggest.build=true&suggest.dictionary=mySuggester&wt=json&suggest.q=elec
```

这里可以看到有几个参数：

## suggest=true

这个参数必须为true，表示我这次请求是suggest请求。

## suggest.q

进行查询建议的文本

## suggest.dictionary

搜索组建中词典的名字，这个参数必须填写，你可以在请求体中带上这个参数，也可以在请求参数中带上。

## suggest.count

suggest请求返回的suggest数。

## suggest.cfg

这个不是必填的，如果suggester支持，用于内容过滤。

## suggest.build

如果设置为true，这个请求会导致重建suggest索引。
这个字段一般用于初始化的操作中，在线上环境，一般不会每个请求都重建索引，如果线上你希望保持字典最新，最好使用buildOnCommit或者buildOnOptimize来操作。

## suggest.reload

如果设置为true，会重新加载suggest索引。

## suggest.buildAll

如果设置为true，会重建所有suggest索引。

## suggest.reloadAll

如果设置为true，会重新加载所有suggest索引。

# suggest模块的配置

```
<searchComponent name="suggest" class="solr.SuggestComponent">
    <lst name="suggester">
        <str name="name">mySuggester</str>
        <str name="lookupImpl">FuzzyLookupFactory</str>
        <str name="dictionaryImpl">DocumentDictionaryFactory</str>
        <str name="field">cat</str>
        <str name="weightField">price</str>
        <str name="suggestAnalyzerFieldType">string</str>
    </lst>
</searchComponent>
```

## name

suggester的名字，如果设置多个，可以在请求中指定。

## lookupImpl

查找方式的具体实现，有几种方式：

* AnalyzingLookupFactory: 这个查询方式先对查询的输入文本进行分析，构建出一个FST树，然后再进行查询。
* FuzzyLookupFactory: 这个查询方式是AnalyzingLookupFactory的扩展，只不过是一种模糊匹配。
* AnalyzingInfixLookupFactory: 这个查询方式对输入的文本进行分析，然后建议出前缀匹配的索引文本。
* BlendedInfixLookupFactory: 这个查询方式是AnalyzingInfixSuggester的扩展，这个查询方式可以为分析后的文本设置一些权重，你可以设置权重正序或逆序。
* FSTLookupFactory: 基于自动机的查询。这个方式构建比较慢，但是使用内存更少。除非你需要更复杂的结果，否则就不需要使用这种方式。
* TSTLookupFactory: 一个简单的，基于trie树的查找。
* WFSTLookupFactory:
* JaspellLookupFactory:

## dictionaryImpl

字典的具体实现，具体有几种方式：

* DocumentDictionaryFactory: 一个基于词语，权重，和一个有效的索引中的负荷。
* DocumentExpressionDictionaryFactory: 和DocumentDictionaryFactory一样，但是允许用户设置复杂的"weightExpression"标签来设置权重
* HighFrequencyDictionaryFactory: 允许增加一个阀值来修改返回结果
* FileDictionaryFactory: 允许使用一个外部的文件来包含建议的结果。权重也是可以在外部文件中有所加载的。


```
<requestHandler name="/suggest" class="solr.SearchHandler" startup="lazy">
  <lst name="defaults">
    <str name="suggest">true</str>
    <str name="suggest.count">10</str>
  </lst>
  <arr name="components">
    <str>suggest</str>
  </arr>
</requestHandler>
```

suggest的handler，主要设置了建议返回的默认个数，默认使用的suggest组件等。

# 参考文章

[官方文档](https://cwiki.apache.org/confluence/display/solr/Suggester)
