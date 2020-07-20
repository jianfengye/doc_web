# chromedp入门

# chromedp是什么？

chromedp是go写的，支持Chrome DevTools Protocol 的一个驱动浏览器的库。并且它不需要依赖其他的外界服务（比如 Selenium 和 PhantomJs）。

## Chrome DevTools Protocol (CDP) 

Chrome DevTools Protocol (CDP) 的主页在：https://chromedevtools.github.io/devtools-protocol/。 它提供一系列的接口来查看，检查，调整并且检查 Chromium 的性能。Chrome 的开发者工具就是使用这一系列的接口，并且 Chrome 开发者工具来维护这些接口。

所谓 CDP 的协议，本质上是什么呢？本质上是基于 websocket 的一种协议。比如
![20200622105228](http://tuchuang.funaio.cn/md/20200622105228.png)
在我们打开 webtool 调试工具的时候，其实调试工具也是一个web页面，两个web页面通过websocket建立了一个联系。
所以我们如果写了一个客户端程序，也和目标页面创建一个基于 CDP 的 websocket连接，我们也可以通过这个协议来对页面进行操作。

### 如何打开 Protocol Monitor

在chrome的开发者工具中
![20200622105732](http://tuchuang.funaio.cn/md/20200622105732.png)
打开实验选项 Protocol Monitor
![20200622110118](http://tuchuang.funaio.cn/md/20200622110118.png)
重启chrome，在console的更多里面就可以打开对应的 Monitor
![20200622110240](http://tuchuang.funaio.cn/md/20200622110240.png)

## CDP 协议内容

我们从 Protocol Monitor 面板中可以看到，其中有几个字样，Method，Request，Response。
这里的 Method 就是对应官网 https://chromedevtools.github.io/devtools-protocol/ 左侧每个Domain的 Event。

![20200622151635](http://tuchuang.funaio.cn/md/20200622151635.png)

这里的每个Method方法可能是调试页面给目标页面发送的，但是更多是目标页面给调试页面发送的消息。所以我们需要读懂每个Method的内容。不过很可惜，我个人感觉官网的每个Method文档的描述写的实在是太简单了，也没有看到更详细的描述，只能通过名字和事件来猜测每个Method意思了。

# chromedp 使用

chromedp的使用最快的方法就是看 https://github.com/chromedp/examples 这个项目

基本我们可以熟悉最常用的几个方法了：
* chromedp.NewContext() 初始化chromedp的上下文，后续这个页面都使用这个上下文进行操作
* chromedp.Run() 运行一个chrome的一系列操作
* chromedp.Navigate() 将浏览器导航到某个页面
* chromedp.WaitVisible() 等候某个元素可见，再继续执行。
* chromedp.Click() 模拟鼠标点击某个元素
* chromedp.Value() 获取某个元素的value值
* chromedp.ActionFunc() 再当前页面执行某些自定义函数
* chromedp.Text() 读取某个元素的text值
* chromedp.Evaluate() 执行某个js，相当于控制台输入js
* network.SetExtraHTTPHeaders() 截取请求，额外增加header头
* chromedp.SendKeys() 模拟键盘操作，输入字符
* chromedp.Nodes() 根据xpath获取某些元素，并存储进入数组
* chromedp.NewRemoteAllocator
* chromedp.OuterHTML() 获取元素的outer html
* chromedp.Screenshot() 根据某个元素截图
* page.CaptureScreenshot() 截取整个页面的元素
* chromedp.Submit() 提交某个表单
* chromedp.WaitNotPresent() 等候某个元素不存在，比如“正在搜索。。。”
* chromedp.Tasks{} 一系列Action组成的任务

# 实践

我们尝试打开 https://www.cnblogs.com/ 的首页，然后获取所有文章的标题和链接：

```go
package main

import (
	"context"
	"fmt"
	"log"

	"github.com/chromedp/cdproto/cdp"
	"github.com/chromedp/chromedp"
)

func main() {

	ctx, cancel := chromedp.NewContext(
		context.Background(),
		chromedp.WithLogf(log.Printf),
	)
	defer cancel()

	var nodes []*cdp.Node
	err := chromedp.Run(ctx,
		chromedp.Navigate("https://www.cnblogs.com/"),
		chromedp.WaitVisible(`#footer`, chromedp.ByID),
		chromedp.Nodes(`.//a[@class="titlelnk"]`, &nodes),
	)
	if err != nil {
		log.Fatal(err)
	}

	fmt.Println("get nodes:", len(nodes))
	// print titles
	for _, node := range nodes {
		fmt.Println(node.Children[0].NodeValue, ":", node.AttributeValue("href"))
	}
}

```