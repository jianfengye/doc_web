# 函数评分查询

function_score允许你修改查询的评分。一个评分查询虽然消耗比较大，但是却能在一个过滤集中计算出评分，这是非常有用的。

function_score除了提供custom_boost_factor, custom_score和custom_filters_score一样的功能以外，额外提供的一些功能比如距离，近因得分等。

## 使用函数评分

要使用function_score, 用户首先要定义一个query和一个或多个的函数，这个query返回的文档会被机器根据函数计算出一个新的分数。

function_score只包含一个函数的事例如下：

```
"function_score": {
    "(query|filter)": {},
    "boost": "boost for the whole query",
    "FUNCTION": {},  
    "boost_mode":"(multiply|replace|...)"
}
```

当然，几个函数也可以合在一起提供。这种情况下，document被哪个filter过滤，就会执行哪个filter下的那个函数。

```
"function_score": {
    "(query|filter)": {},
    "boost": "boost for the whole query",
    "functions": [
        {
            "filter": {},
            "FUNCTION": {},
            "weight": number
        },
        {
            "FUNCTION": {}
        },
        {
            "filter": {},
            "weight": number
        }
    ],
    "max_boost": number,
    "score_mode": "(multiply|max|...)",
    "boost_mode": "(multiply|replace|...)",
    "min_score" : number
}
```

如果没有filter提供，那就相当于制定了filter: "match_all": {}

首先，每个文档由定义好的函数来制定评分。参数score_mode制定了如何结合计算的评分。

* multiply 评分相乘
* sum 评分想加
* avg 评分的平均值
* first 第一个满足filter的function
* max 最大的评分数
* min 最小的评分数

默认情况下，修改评分不影响匹配的文档数。如果你需要把不满足某些评分的文档过滤掉，就使用min_score函数来做这个。

## 评分函数

function_score提供下面几种评分的函数:

* script_score
* weight
* random_score
* field_value_factor
* 衰变函数： gauss，linear，exp

### script_score

script_score允许你封装一个请求，并且使用一个脚本表达式将其他的数字字段加到原先的分值得出新的文档得分。示例如下：

```
"script_score" : {
    "script" : "_score * doc['my_numeric_field'].value"
}
```

在不同的脚本字段值和表达式中，\_score 参数被封装起来用于获取计算新的分值。

脚本可以被缓存，这样能执行更快。
