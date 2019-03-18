# 测试用例是开发人员最后一块遮羞布

最近一周写一个比较复杂的业务模块，越写到后面真心越心虚。操作越来越复杂了，代码也逐渐凌乱了起来。比如一个接口，传入的是一个比较复杂的大json，我需要解析这个大json，然后根据json中字段进行增删改查，调用第三方服务等操作。告诉前端接口已经完成的时候，总是有点没有底气。说实话，在写PHP的时候，我确实很少写单元测试，大都是对着页面进行一波一波的测试，现在想想，一个是懒，还有一个是确实PHP是不需要编译的语言，没有编译时间，测试-修正，整个流程非常短。但是这次是一个比较大的GoLang项目，如果还是按照“编译-起服务-调用-调整代码-编译-起服务-调用-...” 这种循环来做调试，真是会疯了的。所以我能静下心好好研究研究如何写Golang的单元测试了。

# 数据库怎么办？

这个是第一个需要思考的问题。这个问题和语言无关。一旦有数据库操作，就需要考虑如何在测试用例中如何处理数据库操作。我想了想，无外乎两种做法，一种是直接mock数据库的返回对象。另外一种，是搭建一个测试DB，然后灌入假数据，进行测试。这两种方式我选择了后一种。有几个理由：首先，mock数据库返回数据是一个比灌入DB数据更为复杂的逻辑，数据库返回的数据根据sql各种各样，要想在每个环节都写好数据库操作返回，倒不如我直接伪造一些数据来的方便。其次，mock数据库返回会丢失model层的测试逻辑，当然如果你是轻model层，整个model就只有一个orm，这个可能就不是理由了。

所以，我操作的第一步，从线上把数据库表结构copy一份到我本地vagrant的mysql中。

这里必须要注意，你的测试数据库和测试代码最好是同一个机器上，否则每跑一个测试用例，消耗的时间非常大，你的测试体验也不会太好。

# 第三方请求怎么办？

我的代码逻辑中也有一些第三方调用，调用其他服务。当然这里也有同样的两种办法，一种是直接在本地测试环境搭建第三方服务，另外一种是mock第三方服务的返回数据。这里我选择了mock数据的方式。基本想法是因为我这个测试毕竟不是一种全链路测试，测试的主体还是我的服务，我的服务基本上只包含服务+DB，如果要搭建第三方服务，这就有点舍本逐末的感觉了。

好了，如何mock第三方服务呢？

查了下golang中mock的包有两个比较出名，一个是golang官网出品的golang/mock，另外一个是monkey（https://github.com/bouk/monkey）。两个相比之下，我感觉golang/mock是师出有名，但是不如monkey好用，monkey属于黑科技，使用修改函数指针的方式进行mock函数。我想了想，实用第一位，投入了monkey的怀抱。

基本使用代码如下：
```
// mock路网接口
guard := monkey.Patch(lib.Curl, func(trace *lib.TraceContext, CurlType, urlString string, data url.Values, addToken bool) ([]byte, error) {
  return []byte("{[\"10010\":\"后厂村路\"}"]), nil
})
defer guard.Unpatch()

```

将lib.Curl整个函数给mock了，并且在函数结束后修改mock的函数，保证不影响其他测试用例。

# 配置文件怎么办？

web服务一般都会有读取配置的代码，我的服务是读取一个参数config=base.json来进行配置的读取的。go test中是没有办法给test的代码传递参数的，（我看网上的一些文章说有个-args的参数，但是我在go1.11版本中确实没有看到这个参数）。于是我只能选择使用环境变量的方式。在运行go test的时候，在最开头的部分设置下当前这个go test的环境变量CONFIG_PATH，然后修改下我的初始化配置文件的代码，允许传入参数进行配置文件的读取。

大概代码如下：

在运行go test的时候设置环境变量：
```
CONFIG_PATH=/home/vagrant/foo/conf/yejianfeng/base.json go test foo/signaledit/... -v -test.run TestGetGroups
```

测试环境的初始化配置文件逻辑：
```
package test

import (
	..
)

var HasSetup = false

// signalEdit初始化，只调用一次
func SetUpSignalEdit() {
	if HasSetup == false {
		gin.SetMode(gin.TestMode)

		confPath := os.Getenv("CONFIG_PATH") // 获取环境变量
		commonlib.Init(confPath, "")    // 初始化配置文件
		conf.ParseLocalConfig()
		db.InitDB()

		HasSetup = true
	}

	DestroyTestData(db.EditDB)
	CreateTestData(db.EditDB)
}

```

# web怎么进行单元测试？

关于这个，httptest这个包提供给我们想要的逻辑了，网上的文章也一大堆了。使用起来也是很方便，
```
router := gin.New()
jc := Controller{}
// 灯组模型表获取信息
router.GET("/group/all", jc.GroupAll)
...
//构建返回值
w := httptest.NewRecorder()
//构建请求
r, _ := http.NewRequest("GET", "/group/all?logic_junction_id=test_junction", nil)
//调用请求接口
router.ServeHTTP(w, r)

resp := w.Result()
body, _ := ioutil.ReadAll(resp.Body)
```

就没有什么好说的了。

# 关于数据初始化和销毁

既然我选择使用本地DB进行测试，那么按照逻辑，需要在测试用例开始初始化DB数据，然后在测试用例结束后销毁数据。这里我还选择在测试用例开始的时候，先销毁数据，然后初始化数据，测试用例结束的时候不要销毁数据。这样做我承认有不好的地方，就是有可能会有脏数据。比较好的地方，就是我在单个测试用例跑完的时候，我有机会去数据库看一眼现在数据库里面的测试数据是什么样子。

不管怎么洋，数据初始化和销毁的工作就变得异常重要了，它们必须是幂等，而且可以循环幂等。（销毁-初始化）=（销毁-销毁-初始化）=（初始化-销毁-初始化）。要做到这个我的感受必须借助具体的业务数据表逻辑了。比如我的所有数据表都有一个路口id的字段，那么我就很容易做到销毁的幂等，我每次销毁的时候，就只要把这个路口的所有数据删除就可以了。如果没有的话，由于我们的数据库是本地数据库，不妨采用整个数据表清空的方式操作。


数据初始化和销毁的函数我封装成两个函数，放在一个包里面
```
var (
	SignalID        = int64(999999)
	LogicJunctionId = "test_junction"
)

// 创建测试数据
func CreateTestData(db *gorm.DB) {

	// SignalInfo表创建一条数据
	signalInfo := &models.SignalInfo{}
	signalInfo.Id = SignalID
	signalInfo.Name = "测试路口id"
	signalInfo.LogicJunctionId = LogicJunctionId
	signalInfo.Status = 1
	db.Create(signalInfo)
}
```

```
// 销毁测试数据
func DestroyTestData(db *gorm.DB) {

	db.Delete(&models.SignalInfo{}, "logic_junctionid=" + LogicJunctionId)

  ...
}

```

然后把上面说的初始化操作封装成一个函数
```
var HasSetup = false

// signalEdit初始化，只调用一次
func SetUpSignalEdit() {
	if HasSetup == false {
		gin.SetMode(gin.TestMode)
		confPath := os.Getenv("CONFIG_PATH")
		commonlib.Init(confPath, "")
		conf.ParseLocalConfig()
		db.InitDB()

		HasSetup = true
	}
	DestroyTestData(db.EditDB)
	CreateTestData(db.EditDB)
}
```

所有测试用例都先调用下这个函数
```
func TestGetGroups(t *testing.T) {
	test.SetUpSignalEdit()
  ...
}
```

这里真心要吐槽下testing框架，既然做了测试框架，SetUp函数，SetDown函数这些都不考虑，和主流的测试框架的思想真的有点偏差，导致像这种“普通”的初始化的需求都要自己写方法来绕过，至少testing框架为应用思考的东西还是太少了。

# 测试用例的粒度

我一直知道写好测试用例是一个难度不亚于开发的工作。测试用例有粒度问题，我觉得，测试用例的粒度宜大不宜小。我这个项目是controller-service-mmodels架构，controller一个函数就是一个接口，service一个函数是一个通用性比较高的服务，model是比较瘦的model，基本只做增删改查。在我这个架构中，我写的测试用例粒度大多数是controller级别的，有少数是service级别的，model级别的测试用例基本没有。

测试用例粒度大一些，有个明显的好处，就是对需求的容忍度高了很多。一般测试用例最痛的就是需求一旦修改了，我的业务逻辑就修改了，我的测试用例也要跟着修改。修改测试用例是很痛苦的事情。所以如果测试用例足够大，比如和接口一样大，那么基本上，由于业务接口的兼容性要求，我们的测试用例的输入输出一般不会进行大的变动（虽然里面的service或者model会进行比较大的变动）。这样有一些需求变化了之后，我甚至不需要修改任何测试用例的代码就可以。

当然有的测试用例粒度太大，一些小的分支可能就测试不到，或者很难构建测试数据，所以有的时候，还是需要一写稍微小一点的粒度的测试用例。

另外对于不需要依赖测试数据的类库函数，如果你对这个类库函数的输入输出的需求变更有把握控制的话，（你需要对自己的这个判断负责）这种类库函数的测试用例则是越细越好。

# 其他原则性的东西

说说写测试用例的一些原则性的东西。

## 检验逻辑抗需求变更能力越强越好

首先，测试用例的检验逻辑不是越全越好，而且有很多技巧。比如一个插入的接口，你测试是否插入成功，有很多时候，你根据判断插入条数是否多一条会比你判断这个插入条数的所有字段是否是你要求的更好。原则还是那个，测试用例的抗需求变更能力会更高，首先基本上如果我的插入逻辑很简单，那么插入成功就约等于插入的每个字段都满足，当然这里是约等于，但是因为业务代码也是我自己写的，心里这个B数还是有的。然后，如果一旦需求变更我这个数据多了一个字段，那么我这个测试用例基本不需要做任何修改就还可以继续跑起来。

再次强调下，这里的约等于的判断就是看你对你业务代码的感觉了。

## 并不是所有的错误都需要完美处理

测试代码毕竟不像业务代码那么需要完美的严谨，所有的panic都是欢迎的。换句话说，我们业务代码基本上对所有error都需要有所处理，但是测试用例并不一定了。如果我在上一行代码中没有处理这个error，那么我传递给下一行的参数很可能就是nil，很有可能在下一行代码中直接panic了一个错误出来。这个也能让我发现我的错误。

所以，测试用例并不需要写那么严谨，有的地方直接panic错误也是一个很好的选择。

## Fatal和Error的选择

基本上我觉得Error没啥用，我目前的测试用例都要求所有的判断节点都跑成功，任何一个地方失败了，直接就报错进行调试。我的精力也不允许我一次性能处理多个错误case，基本上调试失败的测试用例是一个个调试的，所以error并没有什么用。

这点纯粹我个人观点，估计会有很多人不同意。

## 检验逻辑多用变量

检验逻辑尽量少用 response.Name == "测试路口" 这种代码，能尽量找到替换"测试路口" 这个的变量尽量使用变量，同样的理由，测试用例的抗需求变更能力会更高。

# 总结

测试用例是开发人员最后一块遮羞布，写Golang的代码和写PHP的代码确实体验完全不一样，在Golang代码中，首先写测试用例异常方便了。其次，Golang的调试成本远远高于PHP，写测试用例看起来是浪费时间，实际上是节省你的调试时间。最后，golang代码的每次重构（增加一个字段，少一个字段）影响的文件数远远高于PHP，如果没有这块遮羞布，你怎么确保你的代码修改后还能正常运行呢？

Just Testing！
