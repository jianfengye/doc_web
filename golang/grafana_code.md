​grafana 的主体架构是如何设计的？

grafana 是非常强大的可视化项目，它最早从 kibana 生成出来，渐渐也已经形成了自己的生态了。研究完 grafana 生态之后，只有一句话：可视化，grafana 就够了。

这篇就想了解下它的主体架构是如何设计的。如果你对 grafana 有兴趣，不妨让这篇成为入门读物。

# 入口代码

grafana 的最外层就是一个 build.go，它并不是真正的入口，它只是用来编译生成 grafana-server 工具的。

grafana 会生成两个工具，grafana-cli 和 grafana-server。

go run build.go build-server 其实就是运行

```goalng
go build ./pkg/cmd/grafana-server -o ./bin/xxx/grafana-server
```

这里可以划重点学习一下:

如果你的项目要生成多个命令行工具，又或者有多个参数，又或者有多个操作，使用 makefile 已经很复杂了，我们是可以这样直接写个 build.go 或者 main.go 在最外层，来负责编译的事情。

所以真实的入口在 ./pkg/cmd/grafana-server/main.go 中。可以跟着这个入口进入。

# 设计结构

这篇不说细节，从宏观角度说下 grafana 的设计结构。带着这个架构再去看 granfana 才更能理解其中一些细节。

grafana 中最重要的结构就是 Service。 grafana 设计的时候希望所有的功能都是 Service。是的，所有，包括用户认证 UserAuthTokenService，日志 LogsService， 搜索 LoginService，报警轮训 Service。 所以，这里需要设计出一套灵活的 Service 执行机制。

理解这套 Service 机制就很重要了。这套机制有下列要处理的地方：

## 注册机制

首先，需要有一个 Service 的注册机制。

grafana 提供的是一种有优先级的，服务注册机制。grafana 提供了 pkg/registry 包。

在 Service 外层包了一个结构，包含了服务的名字和服务的优先级。

```golang
type Descriptor struct {
	Name         string
	Instance     Service
	InitPriority Priority
}
```

这个包提供的三个注册方法：

```golang
RegisterServiceWithPriority
RegisetrService
Register
```

这三个注册方法都是把 Descriptior（本质也就是 Service）注册到一个全局的数组中。

取的时候也很简单，就是把这个全局数组按照优先级排列就行。

那么什么时候执行注册操作呢？答案就是在每个 Service 的 init() 函数中进行注册操作。所以我们可以看到代码中有很多诸如：

```golang
_ "github.com/grafana/grafana/pkg/services/ngalert"
_ "github.com/grafana/grafana/pkg/services/notifications"
_ "github.com/grafana/grafana/pkg/services/provisioning"
```

的 import 操作，就是为了注册服务的。

## Service 的类型

如果我们自己定义 Service，差不多定义一个 interface 就好了，但是实际这里是有问题的。我们有的服务需要的是后端启动，有的服务并不需要后端启动，而有的服务需要先创建一个数据表才能启动，而有的服务需要根据配置文件判断是否开启。要定义一个 Service 接口满足这些需求，其实也是可以的，只是比较丑陋，而 grafana 的写法就非常优雅了。

grafana 定义了基础的 Service 接口，仅仅需要实现一个 Init() 方法：

```golang
type Service interface {
	Init() error
}
```

而定义了其他不同的接口，比如需要后端启动的服务：

```golang
type BackgroundService interface {
	Run(ctx context.Context) error
}
```

需要数据库注册的服务：

```golang
type DatabaseMigrator interface {
	AddMigration(mg *migrator.Migrator)
}
```

需要根据配置决定是否启动的服务：

```golang
type CanBeDisabled interface {
	IsDisabled() bool
}
```

在具体使用的时候，根据判断这个 Service 是否符合某个接口进行判断。

```golang
service, ok := svc.Instance.(registry.BackgroundService)
if !ok {
    continue
}
```

这样做的优雅之处就在于在具体定义 Service 的时候就灵活很多了。不会定义很多无用的方法实现。

这个也是 golang 鸭子类型的好处。

## Service 的依赖

这里还有一个麻烦的地方，Service 之间是有互相依赖的。比如 sqlstore.SQLStore 这个服务，是负责数据存储的。它会在很多服务中用到，比如用户权限认证的时候，需要去数据存储中获取用户信息。那么这里如果在每个 Service 初始化的时候进行实例化，也是颇为痛苦的事情。

grafana 使用的是 facebook 的 inject.Graph 包处理这种依赖的问题的。https://github.com/facebookarchive/inject。

这个 inject 包使用的是依赖注入的解决方法，把一堆实例化的实例放进包里面，然后使用反射技术，对于一些结构中有指定 tag 标签的字段，就会把对应的实例注入进去。

比如 grafana 中的：

```golangß
type UserAuthTokenService struct {
	SQLStore          *sqlstore.SQLStore            `inject:""`
	ServerLockService *serverlock.ServerLockService `inject:""`
	Cfg               *setting.Cfg                  `inject:""`
	log               log.Logger
}
```

这里可以看到 SQLStore 中有额外的注入 tag。那么在 pkg/server/server.go 中的

```golang
services := registry.GetServices()
if err := s.buildServiceGraph(services); err != nil {
    return err
}
```

这里会把所有的 Service （包括这个 UserAuthTokenService） 中的 inject 标签标记的字段进行依赖注入。

这样就完美解决了 Service 的依赖问题。

## Service 的运行

Service 的运行在 grafana 中使用的是 errgroup, 这个包是 “golang.org/x/sync/errgroup”。

使用这个包，不仅仅可以并行 go 执行 Service，也能获取每个 Service 返回的 error，在最后 Wait 的时候返回。

大体代码如下：

```golang
s.childRoutines.Go(func() error {
		...
		err := service.Run(s.context)
		...
	})
}

defer func() {
	if waitErr := s.childRoutines.Wait(); waitErr != nil && !errors.Is(waitErr, context.Canceled) {
		s.log.Error("A service failed", "err", waitErr)
		if err == nil {
			err = waitErr
		}
	}
}()
```

# 总结

理解了 Service 机制之后，grafana 的主流程就很简单明了了。如图所示。当然，这个只是 grafana 的主体流程，它的每个 Service 的具体实现还有待研究。

![20201216121821](http://tuchuang.funaio.cn/20201216121821.png)
