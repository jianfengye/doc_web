# postgres中的视图和物化视图

## 视图和物化视图区别

postgres中的视图和mysql中的视图是一样的，在查询的时候进行扫描子表的操作，而物化视图则是实实在在地将数据存成一张表。说说版本，物化视图是在9.3 之后才有的逻辑。

## 比较下视图和物化视图的性能

创建两个表

    CREATE TABLE teacher (
        id int NOT NULL,
        sname varchar(100)
    );

    CREATE TABLE student (
        sid int NOT NULL,
        teacher_id int NOT NULL DEFAULT 0,
        tname varchar(100)
    );

创建一个视图

    CREATE OR REPLACE VIEW student_view AS
    SELECT  *
       FROM student
       LEFT JOIN teacher 
       ON student.teacher_id = teacher.id;

创建一个物化视图

    CREATE MATERIALIZED VIEW student_view_m AS
    SELECT  *
       FROM student
       LEFT JOIN teacher 
       ON student.teacher_id = teacher.id;

进行查询explain:

    master=> explain select * from student_view;
                                   QUERY PLAN
    ------------------------------------------------------------------------
     Hash Right Join  (cost=16.98..48.34 rows=496 width=448)
       Hash Cond: (teacher.id = student.teacher_id)
       ->  Seq Scan on teacher  (cost=0.00..13.20 rows=320 width=222)
       ->  Hash  (cost=13.10..13.10 rows=310 width=226)
             ->  Seq Scan on student  (cost=0.00..13.10 rows=310 width=226)
    (5 rows)

    master=> explain select * from student_view_m;
                                QUERY PLAN
    -------------------------------------------------------------------
     Seq Scan on student_view_m  (cost=0.00..11.70 rows=170 width=448)
    (1 row)

可以看出，student_view去每个表中进行查询，而student_view_m 直接去视图表查询，而物化视图的查询效率确确实实高于视图不少。

## 物化视图的数据填充

物化视图既然是一个实实在在存在的表，它就需要有数据填充过程，数据填充的命令是REFRESH MATERIALIZED VIEW

    master=> \h REFRESH
    Command:     REFRESH MATERIALIZED VIEW
    Description: replace the contents of a materialized view
    Syntax:
    REFRESH MATERIALIZED VIEW [ CONCURRENTLY ] name
        [ WITH [ NO ] DATA ]

这里有个注意的，如果你的psql是9.3的，那么你查看帮助文档就只会看到：

    master=> \h REFRESH
    Command:     REFRESH MATERIALIZED VIEW
    Description: replace the contents of a materialized view
    Syntax:
    REFRESH MATERIALIZED VIEW name
        [ WITH [ NO ] DATA ]

这里就引入说postgres的更新数据库有两种方式，一种是全量更新，一种是增量更新，增量更新是在REFRESH的时候增加一个CONCURRENTLY参数。而增量更新是9.4才加入的操作。

那么哪种更新快呢？答案是全量更新，增量更新做的操作是将当前视图表中的数据和query中的数据做一个join操作，然后才将差量做填充。

但是全量更新会阻塞select操作，就是说，你全量更新的过程中，所有对视图的select操作都会被阻塞，而增量更新却不会这样。

## 物化视图适合什么

物化视图适合的场景应该是对数据的实时性要求不高的场景。

我的项目中遇到的情况是提出问题，立刻就要在问题表中看到我提出的问题，虽然可以做触发器来当insert的时候触发增量更新，但是，当数据量大的时候，增量更新的速度确实不能承受。所以，在这种情况下，还是放弃物化视图，从索引方面多考虑考虑。