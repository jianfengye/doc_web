# colly源码学习

[colly](http://go-colly.org/docs/)是一个golang写的网络爬虫。它使用起来非常顺手。看了一下它的源码，质量也是非常好的。本文就阅读一下它的源码。

# 使用示例

```
func main() {
	c := colly.NewCollector()

	// Find and visit all links
	c.OnHTML("a[href]", func(e *colly.HTMLElement) {
		e.Request.Visit(e.Attr("href"))
	})

	c.OnRequest(func(r *colly.Request) {
		fmt.Println("Visiting", r.URL)
	})

	c.Visit("http://go-colly.org/")
}
```

# 从Visit开始说起

首先，要做一个爬虫，我们就需要有一个结构体 Collector, 所有的逻辑都是围绕这个Collector来进行的。

这个Collector在“爬取”一个URL的时候，我们使用的是Collector.Visit方法。这个Visit方法具体有几个步骤：

* 组装Request
* 获取Response
* Response解析HTML/XML
* 结束页面抓取
* 在任何一个步骤都有可能出现错误

colly能让你在每个步骤制定你需要执行的逻辑，而且这个逻辑不一定要是单个，可以是多个。比如你可以在Response获取完成，解析为HTML之后使用OnHtml增加逻辑。这个也是我们最常使用的函数。它的实现原理如下：

```
type HTMLCallback func(*HTMLElement)

type htmlCallbackContainer struct {
	Selector string
	Function HTMLCallback
}

type Collector struct {
  ...
	htmlCallbacks     []*htmlCallbackContainer  // 这个htmlCallbacks就是用户注册的HTML回调逻辑地址
  ...
}

// 用户使用的注册函数，注册的是一个htmlCallbackContainer，里面包含了DOM选择器，和选择后的回调方法
func (c *Collector) OnHTML(goquerySelector string, f HTMLCallback) {
	...
	if c.htmlCallbacks == nil {
		c.htmlCallbacks = make([]*htmlCallbackContainer, 0, 4)
	}
	c.htmlCallbacks = append(c.htmlCallbacks, &htmlCallbackContainer{
		Selector: goquerySelector,
		Function: f,
	})
  ...
}

// 系统在获取HTML的DOM之后做的操作，将htmlCallbacks拆解出来一个个调用函数
func (c *Collector) handleOnHTML(resp *Response) error {
	...
	doc, err := goquery.NewDocumentFromReader(bytes.NewBuffer(resp.Body))
	...
	for _, cc := range c.htmlCallbacks {
		i := 0
		doc.Find(cc.Selector).Each(func(_ int, s *goquery.Selection) {
			for _, n := range s.Nodes {
				e := NewHTMLElementFromSelectionNode(resp, s, n, i)
				...
				cc.Function(e)
			}
		})
	}
	return nil
}

// 这个是Visit的主流程，在合适的地方增加handleOnHTML的逻辑。
func (c *Collector) fetch(u, method string, depth int, requestData io.Reader, ctx *Context, hdr http.Header, req *http.Request) error {
	...

	err = c.handleOnHTML(response)

  ...
	return err
}
```

整体这个代码的模式我觉得是很巧妙的，简要来说就是在结构体中存储回调函数，回调函数的注册用OnXXX开放出去，内部在合适的地方进行回调函数的嵌套执行。

这个代码模式可以完全记住，适合的场景是有注入逻辑的需求，可以增加类库的扩展性。

比如我们设计一个ORM，想在Save或者Update的时候可以注入一些逻辑，使用这个代码模式大致就是这样逻辑：
```

// 这种模型适合流式，然后每个步骤进行设计
type SaveCallback func(*Resource)
type UpdateCallback func(string, *Resource)

type UpdateCallbackContainer struct {
	Id string
	Function UpdateCallback
}

type Resource struct {
	Id string
	saveCallbacks []SaveCallback
	updateCallbacks []*UpdateCallbackContainer
}

func (r *Resource) OnSave(f SaveCallback) {
	if r.saveCallbacks == nil {
		r.saveCallbacks = make([]SaveCallback, 0, 4)
	}
	r.saveCallbacks = append(r.saveCallbacks, f)
}

func (r *Resource) Save() {
	// Do Something

	if r.saveCallbacks != nil {
		for _, f := range r.saveCallbacks {
			f(r)
		}
	}
}

func (r *Resource) OnUpdate(id string, f UpdateCallback) {
	if r.updateCallbacks == nil {
		r.updateCallbacks = make([]*UpdateCallbackContainer, 0, 4)
	}
	r.updateCallbacks = append(r.updateCallbacks, &UpdateCallbackContainer{ id, f})
}

func (r *Resource) Update() {
	// Do something

	id := r.Id
	if r.updateCallbacks != nil {
		for _, c := range r.updateCallbacks {
			c.Function(id, r)
		}
	}
}
```

# Collector的组件模型

colly的Collector的创建也是很有意思的，我们可以看看它的New方法

```
func NewCollector(options ...func(*Collector)) *Collector {
	c := &Collector{}
	c.Init()

	for _, f := range options {
		f(c)
	}

  ...
	return c
}

func UserAgent(ua string) func(*Collector) {
	return func(c *Collector) {
		c.UserAgent = ua
	}
}

func main() {
  c := NewCollector(
      colly.UserAgent("Chrome")
  )
}

```

参数是一个返回函数func(\*Collector)的可变数组。然后它的组件就可以以参数的形式在New函数中进行定义了。

这个设计模式很适合的是组件化的需求场景，如果一个后台有不同组件，我按需加载这些组件，基本上可以参照这种逻辑：

```
type Admin struct {
	SideBar string
}

func NewAdmin(options ...func(*Admin)) *Admin {
	ad := &Admin{}

	for _, f := range options {
		f(ad)
	}

	return ad
}

func SideBar(sidebar string) func(*Admin) {
	return func(admin *Admin) {
		admin.SideBar = sidebar
	}
}
```

# Collector的Debugger逻辑

创建完成Collector，但是在各种地方是需要进行“调试”的，这里的调试colly设计为可以是日志记录，也可以是开启一个web进行实时显示。

这个是怎么做到的呢？也是非常巧妙的使用了事件模型。

基本上核心代码如下：

```
package admin

import (
	"io"
	"log"
)

type Event struct {
	Type string
	RequestID int
	Message string
}

type Debugger interface {
	Init() error
	Event(*Event)
}

type LogDebugger struct {
	Output io.Writer
	logger *log.Logger
}

func (l *LogDebugger) Init() error {
	l.logger = log.New(l.Output, "", 1)
	return nil
}

func (l *LogDebugger) Event(e *Event) {
	l.logger.Printf("[%6d - %s] %q\n", e.RequestID, e.Type, e.Message)
}

func createEvent( requestID, collectorID uint32) *debug.Event {
	return &debug.Event{
		RequestID:   requestID,
		Type:        eventType,
	}
}

c.debugger.Event(createEvent("request", r.ID, c.ID, map[string]string{
	"url": r.URL.String(),
}))
```

设计了一个Debugger的接口，里面的Init其实可以根据需要是否存在，最核心的是一个Event函数，它接收一个Event结构指针，所有调试信息相关的调试类型，调试请求ID，调试信息等都可以存在这个Event里面。

在需要记录的地方，创建一个Event事件，并且通过debugger进行输出到调试器中。

colly的debugger还有个惊喜，它支持web方式的查看，我们查看里面的debug/webdebugger.go
```

type WebDebugger struct {
	Address         string
	initialized     bool
	CurrentRequests map[uint32]requestInfo
	RequestLog      []requestInfo
}

type requestInfo struct {
	URL            string
	Started        time.Time
	Duration       time.Duration
	ResponseStatus string
	ID             uint32
	CollectorID    uint32
}


func (w *WebDebugger) Init() error {
	...
	if w.Address == "" {
		w.Address = "127.0.0.1:7676"
	}
	w.RequestLog = make([]requestInfo, 0)
	w.CurrentRequests = make(map[uint32]requestInfo)
	http.HandleFunc("/", w.indexHandler)
	http.HandleFunc("/status", w.statusHandler)
	log.Println("Starting debug webserver on", w.Address)
	go http.ListenAndServe(w.Address, nil)
	return nil
}

func (w *WebDebugger) Event(e *Event) {
	switch e.Type {
	case "request":
		w.CurrentRequests[e.RequestID] = requestInfo{
			URL:         e.Values["url"],
			Started:     time.Now(),
			ID:          e.RequestID,
			CollectorID: e.CollectorID,
		}
	case "response", "error":
		r := w.CurrentRequests[e.RequestID]
		r.Duration = time.Since(r.Started)
		r.ResponseStatus = e.Values["status"]
		w.RequestLog = append(w.RequestLog, r)
		delete(w.CurrentRequests, e.RequestID)
	}
}
```

看到没，重点是通过Init函数把http server启动起来，然后通过Event收集当前信息，然后通过某个路由handler再展示在web上。

这个设计比其他的各种Logger的设计感觉又优秀了一点。

# 总结

看下来colly代码，基本上代码还是非常清晰，不复杂的。我觉得上面三个地方看明白了，基本上这个爬虫框架的架构设计就很清晰了，剩下的是具体的代码实现的部分，可以慢慢看。

colly的整个框架给我的感觉是很干练，没有什么废话和过度设计，该定义为结构的地方就定义为结构了，比如Colletor，这里它并没有设计为很复杂的Collector接口啥的。但是在该定义为接口的地方，比如Debugger，就定义为了接口。而且colly也充分考虑了使用者的扩展性。几个OnXXX流程和回调函数的设计也非常合理。
