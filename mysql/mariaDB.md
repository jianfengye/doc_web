# mariaDB vs mysql（未发布）

今天遇到一个库使用的是mariaDB的数据库版本
```
Server version: 10.1.20-MariaDB MariaDB Server
```
理了一下mariaDB和mysql的关系。

# 分支

简要来说，mariaDB是mysql上的分支。首先为什么要有这个分支呢？mysql被oracle收购之后，原本的那些mysql的开发者（MySQL 的联合创始人兼CEO Michael Widenius）觉得mysql后续的发展之路会受到oracle公司的影响。所以单独出来，创建了一家公司Monty Program Ab。这个公司从mysql上拉一个分支出来进行开发和维护，命名为mariaDB。

mariaDB的github上的项目地址为：https://github.com/MariaDB

mariaDB的主页为：https://mariadb.org/

mysql现在最新版本是5.7。mariaDB在5.5之前都兼容了mysql所有的新特性。也就是说，mariaDB是mysql的超集。但是当然mariaDB每次需要同步mysql的新的特性到自己的分支，这个是非常痛苦的事情。但是mariaDB的目标是另外创建一个独立产品和社区。所以mariaDB创建了10的版本号。从这个版本号开始，mariaDB的特性和功能就越来越走向独立了。

# 功能

mariaDB的可以看作是mysql的超集。mysql有的特性mariaDB都有，但是mariaDB有的功能不一定mysql有。比如Dynamic columns。

## Dynamic columns

这个功能有点像json，表中有一个字段，可以存储key,value格式的数据，并且这个value的数据类型可以动态定义。这样，就好像mysql的表扩展了多个动态列。

比如：

```
EATE TABLE bird_sightings
(
    sighting_id INT AUTO_INCREMENT KEY,
    human_id INT,
    time_seen DATETIME,
    observations BLOB
);
```

这里的observations是BLOB类型，可以存储key-value的格式。
```
INSERT INTO bird_sightings
(human_id, time_seen, observations)
VALUES
  (36, NOW(),
   COLUMN_CREATE(
       'wing-shape','rounded',
       'wingspan','60',
       'bill-shape','all-purpose',
       'main-color','orange'
      ));
```

看这里的observations就存储了四个key-value。
当select的时候
```
ECT name_first AS 'Birder',
DATE_FORMAT(time_seen, '%b %d') AS 'Date',
COLUMN_GET(observations, 'wing-shape' AS CHAR) AS 'Wings',
COLUMN_GET(observations, 'wingspan' AS INT) AS 'Span (cm)',
COLUMN_GET(observations, 'bill-shape' AS CHAR) AS 'Beak'
FROM bird_sightings
JOIN humans USING(human_id);

+---------+--------+---------+-----------+-------------+
| Birder  | Date   | Wings   | Span (cm) | Beak        |
+---------+--------+---------+-----------+-------------+
| Anahit  | Apr 14 | pointed |      NULL | all-purpose |
| Michael | Apr 14 | rounded |        60 | all-purpose |
+---------+--------+---------+-----------+-------------+

```
这里的COLUMN_GET有个输出类型的设置。这一个功能就是mariaDB特有的。感觉和postgres里面的jsonb结构很相似。

## 存储引擎

mariaDB提供的XtraDB存储引擎替换InnoDB。XtraDB 是 Percona 开发维护的 InnoDB 威力加强版，整合 Google、Facebook 等公司和 MySQL 社区的补丁。

# 性能

在性能上，mariaDB的性能有一定程度优于mysql是不争的事实。比如维基百科就从mysql迁移到mariaDB了。反馈，总的来说，mariaDB会比mysql在qps上有2－10%的提升。当然我相信不同的场景，不同的语言可能会有不同的性能提升程度。

# 参考

http://radar.oreilly.com/2015/04/dynamic-columns-in-mariadb.html
https://seravo.fi/2015/10-reasons-to-migrate-to-mariadb-if-still-using-mysql
https://softwareengineering.stackexchange.com/questions/120178/whats-the-difference-between-mariadb-and-mysql
