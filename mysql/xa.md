# 使用golang理解mysql的两阶段提交

文章源于一个问题：如果我们现在有两个mysql实例，在我们要尽量简单地完成分布式事务，怎么处理？


# 场景重现

比如我们现在有两个数据库，mysql3306和mysql3307。这里我们使用docker来创建这两个实例：
```bash
# mysql3306创建命令
docker run -d -p 3306:3306 -v /Users/yjf/Documents/workspace/mysql-docker/my3306.cnf:/etc/mysql/mysql.conf.d/mysqld.cnf -v /Users/yjf/Documents/workspace/mysql-docker/data3306:/var/lib/mysql -e MYSQL_ROOT_PASSWORD=123456 --name mysql-3307 mysql:5.7

# msyql3306的配置：
[mysqld]
pid-file	= /var/run/mysqld/mysqld.pid
socket		= /var/run/mysqld/mysqld.sock
datadir		= /var/lib/mysql
server-id = 1
log_bin = mysql-bin
binlog_format = ROW
expire_logs_days = 30

# mysql3307创建命令
docker run -d -p 3307:3306 -v /Users/yjf/Documents/workspace/mysql-docker/my3307.cnf:/etc/mysql/mysql.conf.d/mysqld.cnf -v /Users/yjf/Documents/workspace/mysql-docker/data3307:/var/lib/mysql -e MYSQL_ROOT_PASSWORD=123456 --name mysql-3307 mysql:5.7

# msyql3307的配置：
[mysqld]
pid-file	= /var/run/mysqld/mysqld.pid
socket		= /var/run/mysqld/mysqld.sock
datadir		= /var/lib/mysql
server-id = 2
log_bin = mysql-bin
binlog_format = ROW
expire_logs_days = 30

```

在mysql3306中
我们有一个user表
```sql
create table user (
    id int,
    name varchar(10),
    score int
);


insert into user values(1, "foo", 10)
```

在mysql3307中，我们有一个wallet表。

```sql
create table wallet (
    id int,
    money float 
);


insert into wallet values(1, 10.1)
```

我们可以看到，id为1的用户初始分数（score）为10，而它的钱，在wallet中初始钱（money）为10.1。

现在假设我们有一个操作，需要对这个用户进行操作：每次操作增加分数2，并且增加钱数1.2。

这个操作需要很强的一致性。


# 思考

## 两阶段提交

这里是一个分布式事务的概念，我们可以使用2PC的方法进行保证事务

![20200331161038](http://tuchuang.funaio.cn/md/20200331161038.png)

2PC的概念如图所示，引入一个资源协调者的概念，由这个资源协调者进行事务协调。

第一阶段，由这个资源协调者对每个mysql实例调用prepare命令，让所有的mysql实例准备好，如果其中由mysql实例没有准备好，协调者就让所有实例调用rollback命令进行回滚。如果所有mysql都prepare完成，那么就进入第二阶段。

第二阶段，资源协调者让每个mysql实例都调用commit方法，进行提交。

mysql里面也提供了分布式事务的语句XA。

## 用单个实例的事务行不行

等等，这个两阶段提交和我们的事务感觉也差不多，都是进行一次开始，然后执行，最后commit，mysql为什么还要专门定义一个xa的命令呢？于是我陷入了思考...

思考不如实操，于是我用golang写了一个使用mysql的事务实现的“两阶段提交”:

```go
package main

import (
	"database/sql"
	"fmt"

	_ "github.com/go-sql-driver/mysql"
	"github.com/pkg/errors"
)

func main() {
	var err error

	// db1的连接
	db1, err := sql.Open("mysql", "root:123456@tcp(127.0.0.1:3306)/hade1")
	if err != nil {
		panic(err.Error())
	}
	defer db1.Close()

	// db2的连接
	db2, err := sql.Open("mysql", "root:123456@tcp(127.0.0.1:3307)/hade2")
	if err != nil {
		panic(err.Error())
	}
	defer db2.Close()

	// 开始前显示
	var score int
	db1.QueryRow("select score from user where id = 1").Scan(&score)
	fmt.Println("user1 score:", score)
	var money float64
	db2.QueryRow("select money from wallet where id = 1").Scan(&money)
	fmt.Println("wallet1 money:", money)

	tx1, err := db1.Begin()
	if err != nil {
		panic(errors.WithStack(err))
	}
	tx2, err := db2.Begin()
	if err != nil {
		panic(errors.WithStack(err))
	}

	defer func() {
		if err := recover(); err != nil {
			fmt.Printf("%+v\n", err)
			fmt.Println("=== call rollback ====")
			tx1.Rollback()
			tx2.Rollback()
		}

		db1.QueryRow("select score from user where id = 1").Scan(&score)
		fmt.Println("user1 score:", score)
		db2.QueryRow("select money from wallet where id = 1").Scan(&money)
		fmt.Println("wallet1 money:", money)
	}()

	// DML操作
	if _, err = tx1.Exec("update user set score=score+2 where id =1"); err != nil {
		panic(errors.WithStack(err))
	}
	if _, err = tx2.Exec("update wallet set money=money+1.2 where id=1"); err != nil {
		panic(errors.WithStack(err))
	}

    // panic(errors.New("commit before error"))

	// commit
	fmt.Println("=== call commit ====")
	err = tx1.Commit()
	if err != nil {
		panic(errors.WithStack(err))
	}

    // panic(errors.New("commit db2 before error"))

	err = tx2.Commit()
	if err != nil {
		panic(errors.WithStack(err))
	}

	db1.QueryRow("select score from user where id = 1").Scan(&score)
	fmt.Println("user1 score:", score)
	db2.QueryRow("select money from wallet where id = 1").Scan(&money)
	fmt.Println("wallet1 money:", money)
}

```

我这里已经非常小心地在defer中recover错误信息，并且执行了rollback命令。

如果我在commit命令之前的任意一个地方调用了`panic(errors.New("commit before error"))` 那么命令就会进入到了rollback这里，就会把两个实例的事务都进行回滚。

![20200331162451](http://tuchuang.funaio.cn/md/20200331162451.png)

通过结果我们可以看到，分数和钱数都没有改变。这个是ok的。

但是如果我在db2的commit之前触发了panic，那么这个命令进入到了rollback中，但是db1已经commit了，db2还没有commit，这个时候会出现什么情况？

![20200331162723](http://tuchuang.funaio.cn/md/20200331162723.png)

非常可惜，我们看到了这里的score增长了，但是money没有增长，这个就说明无法做到事务一致性了。

## 回到mysql的xa

那么还要回归到2PC，mysql为2PC的实现增加了xa命令，那么使用这个命令我们能不能避免这个问题呢？

同样，我用golang写了一个使用xa命令的代码
```go
package main

import (
	"database/sql"
	"fmt"
	"strconv"
	"time"

	_ "github.com/go-sql-driver/mysql"
	"github.com/pkg/errors"
)

func main() {
	var err error

	// db1的连接
	db1, err := sql.Open("mysql", "root:123456@tcp(127.0.0.1:3306)/hade1")
	if err != nil {
		panic(err.Error())
	}
	defer db1.Close()

	// db2的连接
	db2, err := sql.Open("mysql", "root:123456@tcp(127.0.0.1:3307)/hade2")
	if err != nil {
		panic(err.Error())
	}
	defer db2.Close()

	// 开始前显示
	var score int
	db1.QueryRow("select score from user where id = 1").Scan(&score)
	fmt.Println("user1 score:", score)
	var money float64
	db2.QueryRow("select money from wallet where id = 1").Scan(&money)
	fmt.Println("wallet1 money:", money)

	// 生成xid
	xid := strconv.FormatInt(time.Now().Unix(), 10)
	fmt.Println("=== xid:" + xid + " ====")
	defer func() {
		if err := recover(); err != nil {
			fmt.Printf("%+v\n", err)
			fmt.Println("=== call rollback ====")
			db1.Exec(fmt.Sprintf("XA ROLLBACK '%s'", xid))
			db2.Exec(fmt.Sprintf("XA ROLLBACK '%s'", xid))
		}

		db1.QueryRow("select score from user where id = 1").Scan(&score)
		fmt.Println("user1 score:", score)
		db2.QueryRow("select money from wallet where id = 1").Scan(&money)
		fmt.Println("wallet1 money:", money)
	}()

	// XA 启动
	fmt.Println("=== call start ====")
	if _, err = db1.Exec(fmt.Sprintf("XA START '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}
	if _, err = db2.Exec(fmt.Sprintf("XA START '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}

	// DML操作
	if _, err = db1.Exec("update user set score=score+2 where id =1"); err != nil {
		panic(errors.WithStack(err))
	}
	if _, err = db2.Exec("update wallet set money=money+1.2 where id=1"); err != nil {
		panic(errors.WithStack(err))
	}

	// XA end
	fmt.Println("=== call end ====")
	if _, err = db1.Exec(fmt.Sprintf("XA END '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}
	if _, err = db2.Exec(fmt.Sprintf("XA END '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}

	// prepare
	fmt.Println("=== call prepare ====")
	if _, err = db1.Exec(fmt.Sprintf("XA PREPARE '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}
	// panic(errors.New("db2 prepare error"))
	if _, err = db2.Exec(fmt.Sprintf("XA PREPARE '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}

	// commit
	fmt.Println("=== call commit ====")
	if _, err = db1.Exec(fmt.Sprintf("XA COMMIT '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}
	// panic(errors.New("db2 commit error"))
	if _, err = db2.Exec(fmt.Sprintf("XA COMMIT '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}

	db1.QueryRow("select score from user where id = 1").Scan(&score)
	fmt.Println("user1 score:", score)
	db2.QueryRow("select money from wallet where id = 1").Scan(&money)
	fmt.Println("wallet1 money:", money)
}

```

首先看成功的情况：
![20200331164057](http://tuchuang.funaio.cn/md/20200331164057.png)

一切完美。

如果我们在prepare阶段抛出panic，那么结果如下:

![20200331164425](http://tuchuang.funaio.cn/md/20200331164425.png)

证明在第一阶段出现异常是可以回滚的。

但是如果我们在commit阶段抛出panic:

![20200331164533](http://tuchuang.funaio.cn/md/20200331164533.png)

我们发现，这里的分数增加了，但是money却没有增加。

那么这个xa和单个事务有什么区别呢？我又陷入了深深的沉思...

## xa的用法不对

经过在技术群（全栈神盾局）请教，讨论之后，发现这里对2pc的两个阶段理解还没到位，这里之所以分为两个阶段，是强调的是每个阶段都会持久化，就是第一个阶段完成了之后，每个mysql实例就把第一个阶段的请求实例化了，这个时候不管是mysql实例停止了还是其他问题，每次重启的时候都会重新回复这个commit。

我们把这个代码的rollback去掉，假设commit必须成功。

```go
package main

import (
	"database/sql"
	"fmt"
	"strconv"
	"time"

	_ "github.com/go-sql-driver/mysql"
	"github.com/pkg/errors"
)

func main() {
	var err error

	// db1的连接
	db1, err := sql.Open("mysql", "root:123456@tcp(127.0.0.1:3306)/hade1")
	if err != nil {
		panic(err.Error())
	}
	defer db1.Close()

	// db2的连接
	db2, err := sql.Open("mysql", "root:123456@tcp(127.0.0.1:3307)/hade2")
	if err != nil {
		panic(err.Error())
	}
	defer db2.Close()

	// 开始前显示
	var score int
	db1.QueryRow("select score from user where id = 1").Scan(&score)
	fmt.Println("user1 score:", score)
	var money float64
	db2.QueryRow("select money from wallet where id = 1").Scan(&money)
	fmt.Println("wallet1 money:", money)

	// 生成xid
	xid := strconv.FormatInt(time.Now().Unix(), 10)
	fmt.Println("=== xid:" + xid + " ====")
	defer func() {
		if err := recover(); err != nil {
			fmt.Printf("%+v\n", err)
			fmt.Println("=== call rollback ====")
			// db1.Exec(fmt.Sprintf("XA ROLLBACK '%s'", xid))
			// db2.Exec(fmt.Sprintf("XA ROLLBACK '%s'", xid))
		}

		db1.QueryRow("select score from user where id = 1").Scan(&score)
		fmt.Println("user1 score:", score)
		db2.QueryRow("select money from wallet where id = 1").Scan(&money)
		fmt.Println("wallet1 money:", money)
	}()

	// XA 启动
	fmt.Println("=== call start ====")
	if _, err = db1.Exec(fmt.Sprintf("XA START '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}
	if _, err = db2.Exec(fmt.Sprintf("XA START '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}

	// DML操作
	if _, err = db1.Exec("update user set score=score+2 where id =1"); err != nil {
		panic(errors.WithStack(err))
	}
	if _, err = db2.Exec("update wallet set money=money+1.2 where id=1"); err != nil {
		panic(errors.WithStack(err))
	}

	// XA end
	fmt.Println("=== call end ====")
	if _, err = db1.Exec(fmt.Sprintf("XA END '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}
	if _, err = db2.Exec(fmt.Sprintf("XA END '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}

	// prepare
	fmt.Println("=== call prepare ====")
	if _, err = db1.Exec(fmt.Sprintf("XA PREPARE '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}
	// panic(errors.New("db2 prepare error"))
	if _, err = db2.Exec(fmt.Sprintf("XA PREPARE '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}

	// commit
	fmt.Println("=== call commit ====")
	if _, err = db1.Exec(fmt.Sprintf("XA COMMIT '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}
	panic(errors.New("db2 commit error"))
	if _, err = db2.Exec(fmt.Sprintf("XA COMMIT '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}

	db1.QueryRow("select score from user where id = 1").Scan(&score)
	fmt.Println("user1 score:", score)
	db2.QueryRow("select money from wallet where id = 1").Scan(&money)
	fmt.Println("wallet1 money:", money)
}

```

![20200331165500](http://tuchuang.funaio.cn/md/20200331165500.png)

这个时候，我们停掉程序（停掉mysql的链接），使用`xa recover`可以发现，db2的xa事务还留在db2中了。
![20200331165622](http://tuchuang.funaio.cn/md/20200331165622.png)

我们在控制台直接调用`xa commit '1585644880'` 还能继续把这个xa事务进行提交。

![20200331165742](http://tuchuang.funaio.cn/md/20200331165742.png)

这下money就进行了提交，又恢复了一致性。

所以呢，我琢磨了一下，我们写xa的代码应该如下：

```go
package main

import (
	"database/sql"
	"fmt"
	"log"
	"strconv"
	"time"

	_ "github.com/go-sql-driver/mysql"
	"github.com/pkg/errors"
)

func main() {
	var err error

	// db1的连接
	db1, err := sql.Open("mysql", "root:123456@tcp(127.0.0.1:3306)/hade1")
	if err != nil {
		panic(err.Error())
	}
	defer db1.Close()

	// db2的连接
	db2, err := sql.Open("mysql", "root:123456@tcp(127.0.0.1:3307)/hade2")
	if err != nil {
		panic(err.Error())
	}
	defer db2.Close()

	// 开始前显示
	var score int
	db1.QueryRow("select score from user where id = 1").Scan(&score)
	fmt.Println("user1 score:", score)
	var money float64
	db2.QueryRow("select money from wallet where id = 1").Scan(&money)
	fmt.Println("wallet1 money:", money)

	// 生成xid
	xid := strconv.FormatInt(time.Now().Unix(), 10)
	fmt.Println("=== xid:" + xid + " ====")
	defer func() {
		if err := recover(); err != nil {
			fmt.Printf("%+v\n", err)
			fmt.Println("=== call rollback ====")
			db1.Exec(fmt.Sprintf("XA ROLLBACK '%s'", xid))
			db2.Exec(fmt.Sprintf("XA ROLLBACK '%s'", xid))
		}

		db1.QueryRow("select score from user where id = 1").Scan(&score)
		fmt.Println("user1 score:", score)
		db2.QueryRow("select money from wallet where id = 1").Scan(&money)
		fmt.Println("wallet1 money:", money)
	}()

	// XA 启动
	fmt.Println("=== call start ====")
	if _, err = db1.Exec(fmt.Sprintf("XA START '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}
	if _, err = db2.Exec(fmt.Sprintf("XA START '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}

	// DML操作
	if _, err = db1.Exec("update user set score=score+2 where id =1"); err != nil {
		panic(errors.WithStack(err))
	}
	if _, err = db2.Exec("update wallet set money=money+1.2 where id=1"); err != nil {
		panic(errors.WithStack(err))
	}

	// XA end
	fmt.Println("=== call end ====")
	if _, err = db1.Exec(fmt.Sprintf("XA END '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}
	if _, err = db2.Exec(fmt.Sprintf("XA END '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}

	// prepare
	fmt.Println("=== call prepare ====")
	if _, err = db1.Exec(fmt.Sprintf("XA PREPARE '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}
	// panic(errors.New("db2 prepare error"))
	if _, err = db2.Exec(fmt.Sprintf("XA PREPARE '%s'", xid)); err != nil {
		panic(errors.WithStack(err))
	}

	// commit
	fmt.Println("=== call commit ====")
	if _, err = db1.Exec(fmt.Sprintf("XA COMMIT '%s'", xid)); err != nil {
		// TODO: 尝试重新提交COMMIT
		// TODO: 如果还失败，记录xid，进入数据恢复逻辑，等待数据库恢复重新提交
		log.Println("xid:" + xid)
	}
	// panic(errors.New("db2 commit error"))
	if _, err = db2.Exec(fmt.Sprintf("XA COMMIT '%s'", xid)); err != nil {
		log.Println("xid:" + xid)
	}

	db1.QueryRow("select score from user where id = 1").Scan(&score)
	fmt.Println("user1 score:", score)
	db2.QueryRow("select money from wallet where id = 1").Scan(&money)
	fmt.Println("wallet1 money:", money)
}

```

就是第二阶段的commit，我们必须设定它一定会“成功”，如果有不成功的情况，那么就需要记录下不成功的xid，有一个数据恢复逻辑，重新commit这个xid。来保证最终一致性。

## binlog

其实我们使用binlog也能看出一些端倪

```
# 这里的mysql-bin.0003替换成为你当前的log
SHOW BINLOG EVENTS in 'mysql-bin.000003';
```

```
## XA的binlog
| mysql-bin.000003 | 1967 | Anonymous_Gtid |         1 |        2032 | SET @@SESSION.GTID_NEXT= 'ANONYMOUS'                                               |
| mysql-bin.000003 | 2032 | Query          |         1 |        2138 | XA START X'31353835363338363233',X'',1                                             |
| mysql-bin.000003 | 2138 | Table_map      |         1 |        2190 | table_id: 108 (hade1.user)                                                         |
| mysql-bin.000003 | 2190 | Update_rows    |         1 |        2252 | table_id: 108 flags: STMT_END_F                                                    |
| mysql-bin.000003 | 2252 | Query          |         1 |        2356 | XA END X'31353835363338363233',X'',1                                               |
| mysql-bin.000003 | 2356 | XA_prepare     |         1 |        2402 | XA PREPARE X'31353835363338363233',X'',1                                           |
| mysql-bin.000003 | 2402 | Anonymous_Gtid |         1 |        2467 | SET @@SESSION.GTID_NEXT= 'ANONYMOUS'                                               |
| mysql-bin.000003 | 2467 | Query          |         1 |        2574 | XA COMMIT X'31353835363338363233',X'',1



## 非xa的事务
| mysql-bin.000003 | 2574 | Anonymous_Gtid |         1 |        2639 | SET @@SESSION.GTID_NEXT= 'ANONYMOUS'                                               |
| mysql-bin.000003 | 2639 | Query          |         1 |        2712 | BEGIN                                                                              |
| mysql-bin.000003 | 2712 | Table_map      |         1 |        2764 | table_id: 108 (hade1.user)                                                         |
| mysql-bin.000003 | 2764 | Update_rows    |         1 |        2826 | table_id: 108 flags: STMT_END_F                                                    |
| mysql-bin.000003 | 2826 | Xid            |         1 |        2857 | COMMIT /* xid=67 */
```

我们很明显可以看到两阶段提交中是有两个GTID的，生成一个GTID就代表内部生成一个事务，所以第一个阶段prepare结束之后，第二个阶段commit的时候就持久化了第一个阶段的内容，并且生成了第二个事务。当commit失败的时候，最多就是第二个事务丢失，第一个事务实际上已经保存起来了了（只是还没commit）。

而非xa的事务，只有一个GTID，在commit之前任意一个阶段出现问题，整个事务就全部丢失，无法找回了。所以这就是mysql xa命令的机制。

# 总结

看了一些资料，原来mysql从5.7之后才真正实现了两阶段的xa。当然这个两阶段方式在真实的工程中的使用其实很少的，xa的第一定律是避免使用xa。工程中会有很多方式来避免这种分库的事务情况。

不过，不妨碍掌握了mysql的xa，在一些特定的场合，我们也能完美解决问题。
