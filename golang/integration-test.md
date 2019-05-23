# Golang项目的测试实践


最近有一个项目，链路涉及了4个服务。最核心的是一个配时服务。要如何对这个项目进行测试，保证输出质量，是最近思考和实践的重点。这篇就说下最近这个实践的过程总结。


# 测试金字塔

按照Mike Cohn提出的“测试金字塔”概念，测试分为4个层次

![test](https://i.loli.net/2019/05/21/5ce34ea15584577479.png)

最下面是单元测试，单元测试对代码进行测试。再而上是集成测试，它对一个服务的接口进行测试。继而是端到端的测试，我也称他为链路测试，它负责从一个链路的入口输入测试用例，验证输出的系统的结果。再上一层是我们最常用的UI测试，就是测试人员在UI界面上根据功能进行点击测试。

# 单元测试

对于一个Golang写的服务，单元测试已经是很方便了。我们在写一个文件，函数的时候，可以直接在需要单元测试的文件旁边增加一个_test.go的文件。而后直接使用 `go test` 直接跑测试用例就可以了。

一般单元测试，最直接的衡量标准就是代码覆盖率。

单元测试一般测试的对象是一个函数，一个类。

这个部分已经有很多实践例子了，就没什么好聊的。

# 集成测试

## 思考和需求

对于一个服务，会提供多个接口，那么，测试这些接口的表现就是集成测试最重要的目标了。只有通过了集成测试，我们的这个服务才算是有保障。

手头这个配时项目，对外提供的是一系列HTTP服务，基本上代码是以MVC的形式架构的。在思考对它的集成测试过程中，我希望最终能做到下面几点：

首先，我希望我手上这个配时服务的集成测试是自动化的。最理想的情况下，我能调用一个命令，直接将所有case都跑一遍。

其次，衡量集成测试的达标指标。这个纠结过一段时间，是否需要有衡量指标呢？还是直接所有case通过就行？我们的服务，输入比较复杂，并不是简单的1-2个参数，是一个比较复杂的json。那么这个json的构造有各种各样的。需要实现写一些case，但是怎么保证我的这些case是不是有漏的呢？这里还是需要有个衡量指标的，最终我还是选择用代码覆盖率来衡量我的测试达标情况，但是这个代码覆盖率在MVC中，我并不强制要求所有层的所有代码都要覆盖住，主要是针对Controller层的代码。controller层主要是负责流程控制的，需要保证所有流程分支都能走到。

然后，我希望集成测试中有完善的测试概念，主要是TestCase， TestSuite，这里参考了JUnit的一些概念。TestCase是一个测试用例，它提供测试用例启动和关闭时候的注入函数，TestSuite是一个测试套件，代表的是一系列类似的测试用例集合，它也带测试套件启动和关闭时候的注入函数。

最后，可视化需求。我希望这个测试结果很友好，能有一个可视化的测试界面，我能很方便知道哪个测试套件，哪个测试用例中的哪个断言失败了。

## 集成测试实践

Golang 只有_test.go的测试，其中的每个Test_XXX相当于是TestCase的概念，也没有提供测试case启动，关闭执行的注入函数，也没有TestSuite的概念。首先我需要使用 Golang 的test搭建一个测试架子。

集成测试和单元测试不一样，它不属于某个文件，集成测试可能涉及到多个文件中多个接口的测试，所以它需要有一个单独的文件夹。它的目录结构我是这么设计的：

![tester_folder](https://i.loli.net/2019/05/21/5ce3657dcc3c146792.png)

### suites
存放测试套件
### suites/xxx 
这里存放测试套件，测试套件文件夹需要包含下列文件：

before.go存放有

* SetUp() 函数，这个函数在Suite运行之前会运行
* Before() 函数，这个函数在所有Case运行之前运行

after.go存放有

* TearDown() 函数，这个函数在Suite运行之后会运行
* After() 函数，这个函数在Suite运行之后运行

run_test.go文件

这个文件是testsuite的入口，代码如下：
```
package adapt

import "testing"
import . "github.com/smartystreets/goconvey/convey"

func TestRunSuite(t *testing.T) {
	SetUp()
	defer TearDown()
	Convey("初始化", t, nil)

	runCase(t, NormalCasePEE001)
	runCase(t, PENormalCase01)
	runCase(t, PENormalCase04)
	runCase(t, PENormalCase11)
	runCase(t, PENormalCase13)
	runCase(t, PENormalCase14)
	runCase(t, NormalCasePIE001)
	runCase(t, NormalCasePIE002)
	runCase(t, NormalCase01)
	runCase(t, NormalCase02)
	runCase(t, NormalCase07)
	runCase(t, NormalCase08)
	runCase(t, NormalCasePIN003)
	runCase(t, NormalCasePIN005)
	runCase(t, NormalCasePIN006)
	runCase(t, NormalCasePIN015)

}

func runCase(t *testing.T, testCase func(*testing.T)) {
	Before()
	defer After()

	testCase(t)
}

```

### envionment

初始化测试环境的工具

当前我这里面存放了初始化环境的配置文件和db的建表文件。

### report

存放报告的地址

代码覆盖率需要额外跑脚本

在tester目录下运行：

`sh coverage.sh` 会在report下生成coverage.out和coverage.html，并自动打开浏览器


## 引入Convey

关于可视化的需求。

我引入了Convey这个项目，http://goconvey.co/ 。第一次看到这个项目，觉得这个项目的脑洞真大。

下面可了劲的夸一夸这个项目的优点：

### 断言
首先它提供了基于原装go test的断言框架；提供了Convey和So两个重要的关键字，还提供了 Shouldxxx等一系列很好用的方法。它的测试用例写下来像是这个样子：

```
package package_name

import (
	"testing"
	. "github.com/smartystreets/goconvey/convey"
)

func TestIntegerStuff(t *testing.T) {
	Convey("Given some integer with a starting value", t, func() {
		x := 1

		Convey("When the integer is incremented", func() {
			x++

			Convey("The value should be greater by one", func() {
				So(x, ShouldEqual, 2)
			})
		})
	})
}
```
很清晰明了，并且超赞的是很多参数都使用函数封装起来了，go中的 := 和 = 的问题能很好避免了。并且不要再绞尽脑汁思考tmp1,tmp2这种参数命名了。（因为都已经分散到Convey语句的func中了）

### Web界面

其次，它提供了一个很赞的Web平台，这个web平台有几个点我非常喜欢。首先它有一个case编辑器

![convy-edit](https://i.loli.net/2019/05/21/5ce36937c732851354.png)

什么叫好的测试用例实践? 我认为这个编辑器完全体现出来了。写一个完整的case先考虑流程和断言，生成代码框架，然后我们再去代码框架中填写具体的逻辑。这种实践步骤很好解决了之前写测试用例思想偷懒的问题，特别是断言，基本不会由于偷懒而少写。

其次它提供很赞的测试用例结果显示页面：
![goconvey-img](https://i.loli.net/2019/05/21/5ce36aecaff8497245.png)
很赞吧，哪个case错误，哪个断言问题，都很清楚显示出来。

还有，goconvey能监控你运行测试用例的目录，当目录中有任何文件改动的时候，都会重新跑测试用例，并且提供提醒

![goconve-notice](https://i.loli.net/2019/05/21/5ce36bbc83d0036844.jpg)

这个真是太方便了，可以在每次保存的时候，都知道当前写的case是否有问题，能直接提高测试用例编写的效率。

## TestSuite初始化

Web服务测试的环境是个很大问题。特别是DB依赖，这里不同的人有不同的做法。有使用model mock的，有使用db的。这里我的经验是：集成测试尽量使用真是DB，但是这个DB应该是私有的，不应该是多个人共用一个DB。

所以我的做法，把需要初始化的DB结构使用sql文件导出，放在目录中。这样，每个人想要跑这一套测试用例，只需要搭建一个mysql数据库，倒入sql文件，就可以搭建好数据库环境了。其他的初始化数据等都在TestSuite初始化的SetUp函数中调用。

关于保存测试数据环境，我这里有个小贴士，在SetUp函数中实现 清空数据库+初始化数据库 ，在TearDown函数中不做任何事情。这样如果你要单独运行某个TestSuite，能保持最后的测试数据环境，有助于我们进行测试数据环境测试。

## TestCase编写

在集成测试环境中，TestCase编写调用HTTP请求就是使用正常的 httptest包，其使用方式没有什么特别的。

## 代码覆盖率

goconvey有个小问题，测试覆盖率是根据运行goconvey的目录计算的，不能额外设置，但是go test是提供的。所以代码覆盖率我还额外写了一个shell脚本
```
#!/bin/bash

go test  -coverpkg xxx/controllers/... -coverprofile=report/coverage.out ./...
go tool cover -html=report/coverage.out -o report/coverage.html
open report/coverage.html

```

主要就是使用converpkg参数，把代码覆盖率限制在controller层。

![tester-coverage](https://i.loli.net/2019/05/21/5ce36eb964db211138.png)

## 集成测试总结

这套搭建实践下来，对接口的代码测试有底很多了，也测试出不少controller层面的bug。

# 端到端测试

这个是测试金字塔的第二层了。

关于端到端的测试，我的理解就是全链路测试。从整个项目角度来看，它属于一个架构的层次了，需要对每个服务有一定的改造和设计。这个测试需要保证的是整个链路流转是按照预期的。

比如我的项目的链路通过了4个服务，一个请求可能在多个服务之间进行链路调用。但是这个项目特别的是，这些服务并不都是一个语言的。如何进行测试呢？

理想的端到端测试我的设想是这样的，测试人员通过postman调用最上游的服务，构造不同的请求参数和case，有的case其实可能无法通到最下游，那么就需要有一个全链路日志监控系统，在这个系统可以看到这个请求在各个服务中的流转情况。全链路日志监控系统定义了一套tag和一个traceid，要求所有服务在打日志的时候带上这个traceid，和当前步骤的tag，日志监控系统根据这些日志，在页面上能很好反馈出这个链路。

然后测试人员每个case，就根据返回的traceid，去日志中查找，并且确认链路中的tag是否都全齐。

关于如何在各个服务中传递traceid，这个很多微服务监控的项目中都已经说过了，我也是一样的做法，在http的header头中增加这个traceId。

关于打日志的地方，其实有很多地方都可以打日志，但是我只建议在失败的地方+请求的地方打上tag日志，并且是由调用方进行tag日志记录，这样主要是能把请求和返回都记录，方便调试，查错等问题。

# UI测试

这个目前还是让测试人员手动进行点击。这种方式看起来确实比较low，但是貌似也是目前大部分互联网公司的测试方法了。

# 总结

这几周主要是在集成测试方面做了一些实践，有一些想法和思路，所以拿出来进行了分享，肯定还有很多不成熟的地方没有考虑到，欢迎评论留言讨论。

测试是一个费时费力的工作，大多数情况下，业务的迭代速度估计都不允许做很详细的测试。但是对于复杂，重要的业务，强烈建议这四层的测试都能做到，这样代码上线才能有所底气。