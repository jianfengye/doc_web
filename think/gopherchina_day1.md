# GopherChina第一天小结

今天参加了Asta举办的第五届GopherChina，第一天参加完，颇有感受，晚上回来趁着还有记忆，来做一下记录。

# 写在前面

一早从9点开始，一天下来一共八个主题，各个主题都有自己的特色，全部听下来也是挺不容易的。这些主题有大到说框架，也有小到说具体的某个包的使用的。

![参会](http://tuchuang.funaio.cn/gopherchinaWechatIMG25.jpeg)

一早上真心起的比上班还早，早早就过去占了个位子。之前也参加过一些技术大会，最担心的就是讲师讲说的主题变成某个公司的宣传分享。我们听众大都怀着学习的心态来的，都希望能在技术大会上得到的是一些干货，所谓干货，就是经验。就是说并不是你用Golang做了多牛逼的事情，而是你怎么用Golang做了多牛逼的事情，在做了多牛逼的事情的过程中有哪些经验只谈，哪怕只有一个经验分享，能让我们在实际工作中绕开这个坑，也或许就值回票价了。

# 大型微服务框架设计实践

这个是我司滴滴欢总的分享。其实之前我在内部已经听过一遍这个分享了，当时听完之后就对这个在我们外卖部门践行下来的微服务框架的思想、实现都非常感兴趣。还强烈建议其他公司的小伙伴一定要仔细听欢总这个分享。今天第二次听，PPT已经较之前内部分享的润色不少，也做了一些公司业务的脱敏处理。不过整体听完，让我对业务框架的认识又深了一层。

整个PPT大致是从框架开始说起，从框架的进化史，说到框架的风格（从配置->约定->DSL->容器化），从而引入“操作系统”的概念，表述说现在的服务框架，正越来越向操作系统的方向发展。我现在还记得今天杜欢在大会上表达的一些对框架的言论，比如业务框架应该符合原则“Rule Of Lease Power”，即刚好够用。好的框架需要屏蔽业务无关的通用技术细节，让一些不可靠的调用变得可靠起来。这些言论真是不能同意更多。框架和业务代码的关系就是一个封闭开放的原则，框架需要把不想对业务代码开放的部分给尽可能封装掉，让业务的思维负担完全聚焦在具体的业务代码中。

![参会](http://tuchuang.funaio.cn/gopherchinaWechatIMG26.jpeg)

而后欢总就开始聊起了他亲手写的微服务框架的一些实现要点。这些要点虽然没有具体的代码展示，不过基本也把如何实现都说了一下。首先框架与业务正交，通过提供一系列工具链，能自动生成最初的项目模版，并通过代码注入一些框架需要的代码。这个是说的对于框架，完善的工具链是能减轻很多业务的负担的。其次，他的框架将所有底层实现都进行了封装，包括对mysql，redis，kafka等的封装。其中还特意说了他的redis封装，基本就是按照redis的command 文档进行了一轮封装。还有，他提到的对http和rpc做劫持的工作，我觉得这个部分不是所有的业务框架设计者都能想到的，他包装了http.Handler，也对thrift的序列化流程进行了劫持，具体在劫持过程后，能有效的进行context注入，请求回放，全链路压测等工作。具体如何使用FSM实现thrift的protocol这块就没大听懂了。接着，就是提供了跨服务边界的context。这块下午在b站毛总的分享里面也提到了，基本上跨服务的context控制是golang微服务框架的标配了把。实现跨服务context之后，对TimeoutContext的使用就能做防雪崩的事情，每个服务调用都会自动计算超时时间，能有效防止雪崩效应。

最终欢总很自豪的给我们展示了这个框架服务的业务收益。

![参会](http://tuchuang.funaio.cn/gopherchinaWechatIMG28.jpeg)

从欢总这个分享中，基本能对Golang的微服务框架所必须的特性都会有了很好的了解了。

# 如何用Go打造高性能路径规划和ETA引擎

这个也是我今天很期待的一个topic之一。之前半年在路网的项目中也投入了不少精力。对路网的生产，制造，使用流程都有了一些了解。其实我很想了解下Grab中路网在整个项目工程中的生产、服务、变更方面的东西。但是整个PPT听下来，和我想听到的描述方向还是有一些不同的。

胡泊描述的这个topic主要描述的是Grab地图团队中使用Golang做了哪些事情。他们使用OSM做地图数据，通过轨迹数据和算法来补充OSM中缺失的路网数据。他们称之为路网学习。然后轨迹数据和路网数据的mapmatch的匹配也是完全由golang实现，具体实现使用的算法给我们展示了很复杂的算法PPT，完全听懵了。在司机轨迹定位处理和提供路况服务工程这块，花了好几个PPT说了下Grab在轨迹处理这块的一些优化点，比如对数据进行压缩，将数据存储分离，创建缓存层，引入大数据spark streaming处理等。后续又展示了路径规划和ETA的算法逻辑。

这个主题整个听下来只能用一脸懵逼来形容了。感觉听了一个小时的算法和机器学习知识的课程。最后给的Go在GEO领域的一些开源的项目倒是一大亮点，后续如果还有机会做路网相关的工作，可以研究一下这些项目。

![参会](http://tuchuang.funaio.cn/gopherchinaWechatIMG29.jpeg)

# TiDB的Golang实践

早就听闻TiDB的大名，也没有机会在项目中实践使用。这次的分享，整体听下来对TiDB的一些架构设计也有了一些模模糊糊的印象了。

首先姚维先介绍了下 TiDB的 SQL处理层的模型结构。感觉和mysql的SQL解析层的逻辑一样。主要是创建AST树，对这个树进行语法验证等处理。

而后画风一转，聊到了分布式系统的测试，像TiDB这种ToB的产品对测试要求更是严格了，因为一旦被企业使用，无法轻易进行升级。分布式系统的Error可以出现在任何地方，软件，硬件网络等都有可能出现问题，所以如何模拟这些各种场景下的错误是很重要的。姚维介绍了Pingcap内部的一个Schrodinger平台，薛定谔平台。不过没有很好展示一下这个平台的使用和有点。而后就花了大篇幅描述了一下FailPoint的测试注入。

咋听下，我没有能立马理解FailPoint的意思，模糊的理解到这个意思是以某种方式来模拟各种异常情况，比如panic，之类的错误Mock的方式。后来回家之后又去网上查了查，[TiKV 源码解析（五）fail-rs 介绍](https://www.jianshu.com/p/231f5e6b2af9)大致说的也是这个意思。

![参会](http://tuchuang.funaio.cn/gopherchinaWechatIMG30.jpeg)

聊完failpoint之后，姚维很干货的分享了他们是如何测试代码是否又goroutine泄漏的。原理说白了就很简单，使用runtime.Stack在测试代码运行前后计算goroutine数量，当然我理解测试代码运行完成之后是会触发gc的。如果触发gc之后，发现还有goroutine没有被回收，那么这个goroutine很有可能是被泄漏的。这招是我觉得从这个分享学到的最实用的一招之一。姚维能将一个看起来很不容易解决的问题用最直接的语言描述出来，我喜欢这样的分享方式。

再后面，就说到了TiDB是如何使用Chunk结构来存储表数据的。基本上在我理解就是使用Apache Arrow的方式，将TiDB的内容列式存储到Chunk结构中。同样的，姚维对Chunk的描述，引入，演变过程都用最直接的语言描述的非常清楚。

# Testing； how， what，why

这个Dave大神的一个分享，不过时间安排的不是很好，下午一点，这是困点时间，再加上是英文阐述。我不得不承认，我在一个小时中间，大概打盹了半个小时。。。

Dave人很nice，贴心的把PPT的关键字都粗体。基本上Dave的整个Topic是在告诉大家如何进行单元测试，如何看测试覆盖率，测试的重要性，如何才是合理的测试用例。基本上他也是建议使用现在比较流行的数组测试，一个大数组中存储不同的输入，输出，然后对这个大数组循环判断输入是否能产生期望输出。

我打盹之前听的内容是如何写单元测试，打盹之后听到的内容是测试是非常重要的。中间的部分，等PPT放出来的时候再具体看看把。

感觉Dave大神说的比较务实，完全是站在工程师编写代码的角度来说如何写测试用例，没有日常听到的架构、设计等还是稍微有点感动。可能基本功才是体现工程师素养的地方把。

# Go业务基础库之Error & Context

这个是B站毛剑的分享。毛总上台之前，台下响起了雷鸣般的掌声，因为B站由于泄漏事件正处在焦点。毛总上台第一句话希望大家提问缓解不要问一些不合适的问题。。。全场笑然。

毛总的干货还是很多的，他的主题也是很踏实的，也是从业务框架的角度来说，B站是如何处理Error和Context的。

关于Error，首先是使用WithStack保存堆栈信息，以方便查找根因。并且详细告诉我们B站的大仓库是如何处理error的规则的。基本上听下来，就是对所有调用第三方的服务的错误都需要第一时间对error进行wrap，对于go-common库之前的error，统一在go-commmon层进行wrap处理。而后，再三告诉大家他在错误处理方面的一些最佳实践。包括何时打错误日志，何时直接透传，如何集中处理并发goroutine的错误，如何规划错误代码和错误信息等。整体听下来，毛总的这些建议，恐怕需要是从架构师视角才能得出来的干货。

![参会](http://tuchuang.funaio.cn/gopherchinaWechatIMG31.jpeg)

关于Context，其实是在业界讨论非常多的了。总结下来，毛总对Context的观点有几个，首先强烈建议显示传递，即函数第一个参数为context。其次，强烈建议context覆盖全业务，包括日志，mysql，缓存等。再次，context的超时控制需要在流量入口处设置，并且越早设置越好，甚至说到了如果能提早到LSB路由分发之前设置会更好。还有，Context中存储的元数据都需要有哪些，包括调用者，调用地址，traceid等信息。在goroutine中如何传递context等都是很好的B站实践总结。

最终总结了下业务基础库的思考。

干货满满，问答环节也没人不识相的提起一些不合适的问题。这一part愉快结束。

# Go同步和并发设计模型

这场严重超时，基本上讲了一个小时多时间。主要是做了一下Golang中锁、并发处理、内存模型的梳理。虽然讲的很细，不过我个人并不喜欢这样的梳理描述。一个是比较冗长，另外一个是没有结合具体的业务场景来说，光总结还是有点虚了。

这个topic一共五个话题，基本同步原语，扩展同步原语，原子操作，channel，内存模型。基本就是把mutex，RWmutex，Cond，channel等并发相关的结构都梳理了一遍。

到最后已经没有很仔细听了，总体感觉有点尴尬。

# 百度App Go语言实战

这个topic其实更像是百度效能平台的介绍。

首先百度对内部项目都会有一个工程能力评估图，对代码规范、测试、上线等流程都有自己的评估标准。其次介绍了一下百度内部对开发规范，开发工具，代码规范的介绍。比较有干货的是介绍了一下在实现开发框架server遇到的点，比如创建了一个goroutine池来控制goroutine的数量。server端出现TIME_WAIT过多问题的处理。而后介绍了一下百度的构建体系，如何自建镜像等。具体的实现逻辑没有细想，不过感觉百度内部为了保证golang的代码交付质量，做了很多工作。最后还介绍了百度的代码检查工具，基本上也是使用AST解析代码，并和规则匹配来检查的。

# 用Golang搭建实时音视频云

这个是最后一个主题，由于前面的延迟，时间已经比较玩了，基本上会场上人数较少了一半多了。

具体的内容我也已经没有很高的注意力听了，基本上是聊的golang在WebRTC协议的服务端实现。上来先是例行解释下为何技术选型使用golang，而后对WEBRTC的协议进行了说明，接着大致说了一下他们实现的具体架构，和他们遇到的问题。

比较有印象是他们遇到的问题。他们整个团队是之前各种语系的人都有，于是出现各种错误，比如阻塞for循环select的问题，比如日期格式化的问题。比如依赖库版本问题。整体听下来感觉他们的CR还是需要加强，可能百度的那套Code的检测机制就很合适。

不过整体听下来开阔了一下视野。

# 总结

其实一天若干个话题听下来，能记住的寥寥，但是晚上写这篇文章的时候，还能在头脑中浮现的，就是已经记在心里的干货了。

明天继续早起。
