# postgres中几个复杂的sql语句编写

# 需求一

需要获取一个问题列表，这个问题列表的排序方式是分为两个部分，第一部分是一个已有的数组[0,579489,579482,579453,561983,561990,562083] 第二个部分是按照id进行排序，但是需要过滤掉前面已有的数组。

最开始的时候我大概是想这么写的：
```
select * from question where id = any(
    array_cat(
        ARRAY[0,579489,579482,579453,561983,561990,562083]::integer[], 
        (select array(
            select id from question where id not in 
                (0,579489,579482,579453,561983,561990,562083)
            and status in (1, -1) 
            and created_at > 1426131436 order by id desc offset 0 limit 10 )
        )::integer[]
    )
)
```

这里用了个字查询来查找问题，然后和array做一个链接。但是发现这个 = any 返回的结构并不是按照我想要的排序。有没有办法让 = any 按照指定顺序返回呢？后来经过别人帮忙，将sql语句改成：

```
select * from question q join ( 
    select * from unnest( 
        array_cat( ARRAY[0,579489,579482,579453,561983,561990,562083]::integer[], (
            select array( 
                select id from question where id not in (0,579489,579482,579453,561983,561990,562083) and status in (1, -1) and created_at > 1426131436 order by id desc offset 0 limit 10 
            ) 
        )::integer[] ) 
    ) WITH ORDINALITY as ids(id, rn) 
) as tmp on q.id = tmp.id order by tmp.rn
```

这里主要有几个函数：
一个是unnest函数，是将一个array变成一个多行的子查询结果。
一个是WITH ORDINALITY，这个函数是只在pg9.4中才增加的函数，和unnest一起使用能返回对应的数组和在数组中的排序。

# 需求二

现在有个表，有个字段是content，content 里面存储的是双层json，即

{"title": "testtest", "content": "{\"id\":23,\"qid\":580585,\
"content\":\"\\u8fd9\\u4e2a\\u662f\\u8ffd\\u95ee\"}"}

现在我要获取按照解析后的qid进行排序分页的结构。

使用了[json ->> 符号](http://www.postgresql.org/docs/9.4/static/functions-json.html)

语句实现如下：

```
select a.question_id, max(is_read) as is_read from (
    select id, is_read, (content::json->>'content')::json->>'qid' as question_id
    from inbox where receiver=1
) a group by a.question_id order by a.question_id desc offset 0 limit 10
```

这里的json->>直接使用了两层解析结构。

# 总结

pgsql中的查询函数非常神奇，只有你想不到的，没有写不了的。