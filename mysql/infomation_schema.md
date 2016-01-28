# mysql的information_schema数据库

mysql在创建的时候都会创建一个information_schema数据库用来存放数据库整体信息。

```
mysql> show tables;
+---------------------------------------+
| Tables_in_information_schema          |
+---------------------------------------+
| CHARACTER_SETS                        |
| COLLATIONS                            |
| COLLATION_CHARACTER_SET_APPLICABILITY |
| COLUMNS                               |
| COLUMN_PRIVILEGES                     |
| ENGINES                               |
| EVENTS                                |
| FILES                                 |
| GLOBAL_STATUS                         |
| GLOBAL_VARIABLES                      |
| KEY_COLUMN_USAGE                      |
| PARTITIONS                            |
| PLUGINS                               |
| PROCESSLIST                           |
| PROFILING                             |
| REFERENTIAL_CONSTRAINTS               |
| ROUTINES                              |
| SCHEMATA                              |
| SCHEMA_PRIVILEGES                     |
| SESSION_STATUS                        |
| SESSION_VARIABLES                     |
| STATISTICS                            |
| TABLES                                |
| TABLE_CONSTRAINTS                     |
| TABLE_PRIVILEGES                      |
| TRIGGERS                              |
| USER_PRIVILEGES                       |
| VIEWS                                 |
+---------------------------------------+
28 rows in set (0.00 sec)
```

通过show tables可以看到其中的数据库有这些...一个个说各代表什么意思。

## CHARACTER_SETS

mysql所有可用的字符集，包括建表的时候的charset，或者建字段时候的也可以设置charset。
我们平时使用的SHOW CHARACTER SET命令实际上就是去这个表中获取数据。

表中数据例子：

```
mysql> SHOW CHARACTER SET;
+----------+-----------------------------+---------------------+--------+
| Charset  | Description                 | Default collation   | Maxlen |
+----------+-----------------------------+---------------------+--------+
| big5     | Big5 Traditional Chinese    | big5_chinese_ci     |      2 |
| dec8     | DEC West European           | dec8_swedish_ci     |      1 |
| cp850    | DOS West European           | cp850_general_ci    |      1 |
```

## COLLATIONS 和 COLLATION_CHARACTER_SET_APPLICABILITY

字符序相关的信息，对于字符序概念不清楚的可以看这篇[mysql的collation](http://www.cnblogs.com/yjf512/p/4233601.html)。

为什么这里有两个表呢，其实COLLATIONS的前两行就是COLLATION_CHARACTER_SET_APPLICABILITY。
COLLATION_CHARACTER_SET_APPLICABILITY表明了可用于校对的字符集。

```
mysql> select * from COLLATIONS;
+--------------------------+--------------------+-----+------------+-------------+---------+
| COLLATION_NAME           | CHARACTER_SET_NAME | ID  | IS_DEFAULT | IS_COMPILED | SORTLEN |
+--------------------------+--------------------+-----+------------+-------------+---------+
| big5_chinese_ci          | big5               |   1 | Yes        | Yes         |       1 |
| big5_bin                 | big5               |  84 |            | Yes         |       1 |
```

```
mysql> select * from COLLATION_CHARACTER_SET_APPLICABILITY;
+--------------------------+--------------------+
| COLLATION_NAME           | CHARACTER_SET_NAME |
+--------------------------+--------------------+
| big5_chinese_ci          | big5               |
| big5_bin                 | big5               |
```

## COLUMNS 和 COLUMN_PRIVILEGES

COLLUMNS表中存储了所有表的所有列及列的属性。

mysql> select * from COLUMNS limit 1\G;
*************************** 1. row ***************************
           TABLE_CATALOG: NULL
            TABLE_SCHEMA: information_schema
              TABLE_NAME: CHARACTER_SETS
             COLUMN_NAME: CHARACTER_SET_NAME
        ORDINAL_POSITION: 1
          COLUMN_DEFAULT:
             IS_NULLABLE: NO
               DATA_TYPE: varchar
CHARACTER_MAXIMUM_LENGTH: 32
  CHARACTER_OCTET_LENGTH: 96
       NUMERIC_PRECISION: NULL
           NUMERIC_SCALE: NULL
      CHARACTER_SET_NAME: utf8
          COLLATION_NAME: utf8_general_ci
             COLUMN_TYPE: varchar(32)
              COLUMN_KEY:
                   EXTRA:
              PRIVILEGES: select
          COLUMN_COMMENT:
1 row in set (0.01 sec)

COLUMN_PRIVILEGES存储了列权限的信息。

# ENGINES

```
mysql> select * from ENGINES;
+------------+---------+------------------------------------------------------------+--------------+------+------------+
| ENGINE     | SUPPORT | COMMENT                                                    | TRANSACTIONS | XA   | SAVEPOINTS |
+------------+---------+------------------------------------------------------------+--------------+------+------------+
| MRG_MYISAM | YES     | Collection of identical MyISAM tables                      | NO           | NO   | NO         |
| CSV        | YES     | CSV storage engine                                         | NO           | NO   | NO         |
| MyISAM     | DEFAULT | Default engine as of MySQL 3.23 with great performance     | NO           | NO   | NO         |
| InnoDB     | YES     | Supports transactions, row-level locking, and foreign keys | YES          | YES  | YES        |
| MEMORY     | YES     | Hash based, stored in memory, useful for temporary tables  | NO           | NO   | NO         |
+------------+---------+------------------------------------------------------------+--------------+------+------------+
```

mysql数据库的数据引擎。后面的三个列，也说明了每个数据引擎是否支持事务，是否支持分布式事务，是否支持快照节点。

## EVENTS

mysql的事件表，触发器之类的。

## FILES

存储mysql NDB集群的数据表信息。

## GLOBAL_STATUS 和 GLOBAL_VARIABLES

存储全局状态信息。

```
mysql> select * from GLOBAL_STATUS;
+-----------------------------------+----------------+
| VARIABLE_NAME                     | VARIABLE_VALUE |
+-----------------------------------+----------------+
| ABORTED_CLIENTS                   | 0              |
| ABORTED_CONNECTS                  | 0              |
| BINLOG_CACHE_DISK_USE             | 0              |
| BINLOG_CACHE_USE                  | 0              |
| BYTES_RECEIVED                    | 3888           |
| BYTES_SENT                        | 261617         |
| COM_ADMIN_COMMANDS                | 1              |
| COM_ASSIGN_TO_KEYCACHE            | 0              |
```

存储全局配置信息。

```
mysql> select * from GLOBAL_VARIABLES;
+-----------------------------------------+---------------------------------------------------------+
| VARIABLE_NAME                           | VARIABLE_VALUE                                                                            |
+-----------------------------------------+-----------------------------------------------------------+
| MAX_PREPARED_STMT_COUNT                 | 16382                                                                                     |
| CHARACTER_SETS_DIR                      | /usr/share/mysql/charsets/                                                                |
| HAVE_CRYPT                              | YES                                                                                       |
| CONNECT_TIMEOUT                         | 10                                                                                        |
| MYISAM_REPAIR_THREADS                   | 1                                                                                         |
| AUTOMATIC_SP_PRIVILEGES                 | ON                                                                                        |
| MAX_CONNECT_ERRORS                      | 10                                                                                        |
```

## KEY_COLUMN_USAGE

描述了具有约束的健。


# 参考文章

[MySQL Study之--MySQL schema_information数据库](http://tiany.blog.51cto.com/513694/1677634)
