# gorm的日志模块源码解析

# 如何让gorm的日志按照我的格式进行输出

这个问题是《如何为gorm日志加traceId》之后，一个群里的朋友问我的。如何让gorm的sql日志不打印到控制台，而打印到自己的日志文件中去。正好我实现了这个功能，就记录一下，并且再把gorm的logger这个线捋一下。

首先我写了一个demo来实现设置我自己的Logger。其实非常简单，只要实现print方法就行了。

```
package main

import (
	"fmt"
	"github.com/jinzhu/gorm"
	_ "github.com/jinzhu/gorm/dialects/mysql"
	"log"
)

type T struct {
	Id int `gorm:"id"`
	A int `gorm:"a"`
	B int `gorm:"b"`
}

func (T) TableName() string {
	return "t"
}

type MyLogger struct {
}

func (logger *MyLogger) Print(values ...interface{}) {
	var (
		level           = values[0]
		source          = values[1]
	)

	if level == "sql" {
		sql := values[3].(string)
		log.Println(sql, level, source)
	} else {
		log.Println(values)
	}
}



func main() {
	db, _ := gorm.Open("mysql", "root:123456@(192.168.33.10:3306)/mysqldemo?charset=utf8&parseTime=True&loc=Local")
	defer db.Close()

	logger := &MyLogger{}

	db.LogMode(true)

	db.SetLogger(logger)

	first := T{}
	err := db.Find(&first, "id=1").Error
	if err != nil {
		panic(err)
	}

	fmt.Println(first)
}


```

这里的mylogger就是实现了gorm.logger接口。

输出就是按照我logger的输出打印出来了
```
2019/04/02 09:11:16 SELECT * FROM `t`  WHERE (id=1) sql /Users/yejianfeng/Documents/gopath/src/gorm-log/main.go:50
{1 1 1}
```

但是这里有个有点奇怪地方，就是这个Print方法里面的values貌似是有隐含内容的，里面的隐含内容有哪些呢？需要追着看下去。

# sql的请求怎么进入到Print中的？

我们在db.Find之前只调用过gorm.Open，db.LogMode，db.SetLogger。后面两个函数的逻辑又是极其简单，我们看到Open里面。

重点在这里：

```
db = &DB{
	db:        dbSQL,
	logger:    defaultLogger,
	callbacks: DefaultCallback,
	dialect:   newDialect(dialect, dbSQL),
}
```

这里的 callbacks 默认是 DefaultCallback。
```
var DefaultCallback = &Callback{}

type Callback struct {
	creates    []*func(scope *Scope)
	updates    []*func(scope *Scope)
	deletes    []*func(scope *Scope)
	queries    []*func(scope *Scope)
	rowQueries []*func(scope *Scope)
	processors []*CallbackProcessor
}
```
我们这里看到的DefaultCallback是空的，但是实际上，它并不是空的，在callback_query.go这个文件中有个隐藏的init()函数
```
func init() {
	DefaultCallback.Query().Register("gorm:query", queryCallback)
	DefaultCallback.Query().Register("gorm:preload", preloadCallback)
	DefaultCallback.Query().Register("gorm:after_query", afterQueryCallback)
}
```

这个init的函数往DefaultCallback.queries里面注册了三个毁掉函数，queryCallback，preloadCallback，afterQueryCallback

然后再结合回db.Find的方法
```
func (s *DB) Find(out interface{}, where ...interface{}) *DB {
	return s.NewScope(out).inlineCondition(where...).callCallbacks(s.parent.callbacks.queries).db
}
```
我们看到最终执行的 callCallbacks(s.parent.callbacks.queries) 就是将这三个方法 queryCallback，preloadCallback，afterQueryCallback 逐一调用。

很明显，这三个方法中，和我们有关系的就是queryCallback方法。

```
func queryCallback(scope *Scope) {
	...

	defer scope.trace(NowFunc())
	...
}
```

这里有个赤裸裸的scope.trace方法

```
func (scope *Scope) trace(t time.Time) {
	if len(scope.SQL) > 0 {
		scope.db.slog(scope.SQL, t, scope.SQLVars...)
	}
}

func (s *DB) slog(sql string, t time.Time, vars ...interface{}) {
	if s.logMode == detailedLogMode {
		s.print("sql", fileWithLineNum(), NowFunc().Sub(t), sql, vars, s.RowsAffected)
	}
}

func (s *DB) print(v ...interface{}) {
	s.logger.Print(v...)
}
```
找到了，这里是使用scope.db.slog->db.print->db.logger.Print

这个db.logger就是前面使用SetLogger设置为MyLogger的地方了。

欣赏下这里的print这行：
```
s.print("sql", fileWithLineNum(), NowFunc().Sub(t), sql, vars, s.RowsAffected)
```

第一个参数为 level，表示这个是个什么请求，第二个参数为打印sql的代码行号，如`/Users/yejianfeng/Documents/gopath/src/gorm-log/main.go:50`, 第三个参数是执行时间戳，第四个参数是sql语句，第五个参数是如果有预处理，请求参数，第六个参数是这个sql影响的行数。

好了，这个逻辑圈画完了。对照我们前面的MyLogger的Print，我们要取出什么就记录什么就行了。

```
type MyLogger struct {
}

func (logger *MyLogger) Print(values ...interface{}) {
	var (
		level           = values[0]
		source          = values[1]
	)

	if level == "sql" {
		sql := values[3].(string)
		log.Println(sql, level, source)
	} else {
		log.Println(values)
	}
}
```

# 总结

从gorm的log也能大概窥探出gorm的代码架构设计了。它的几个结构是核心，DB, Scope, 在Scope中，会注册各种回调方法，creates，updates, querys等，在诸如Find等函数触发了回调调用的时候，才去和真是的DB进行交互。至于日志，就埋藏在这些回调函数之中。

所以《如何为gorm日志加traceId》中如果需要在gorm中增加一个traceId，做不到的原因就是这个gorm.logger没有实现SetContext方法，并且在打印的时候没有将Context输出到Print的参数中。所以除非修改源码中调用db.slog的地方，否则无能为力。
