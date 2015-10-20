# 全网访问速度优化

# 网页主要性能指标

* DNS解析时间
* TCP连接时间
* HTTP重定向时间
* 首包时间
* DOM加载时间
* 首次绘制时间
* 首屏时间
* 加载完成时间

# 服务端优化

## 尽早刷新缓存区

示例：facebook的bigpipe

## 后端服务异步API请求

# 前段优化

## ySlow优化 和 Google PageSpeed

## 降低请求数

* 静态资源永不过期
* CSS sprite/ Inline Image
* 让页面/favicon/Ajax均可以缓存
* 合并JS/CSS文件

## 降低传输量

* 开启gzip
* CSS/JS尽量放在页面外
* 通过流程工具或内容管理工具优化图片

## 提高并发性能

* Domain Hash
* Cookie free domain
* 少用iframe
* 减少cookie的大小

## 合理利用带宽

* 节约成本，提高并发能力
* 屏幕适配，图片自动剪裁
* LazyLoad/PostPone
* 削峰杨谷和策略降级

# 客户端优化

## 问题

* DNS由于线路不稳定线路而具备优化价值
* IP直连＋指定HOST头
* 配置动态下发＋自动异步更新
* 内容预取，资源预取

## 数据访问资源优化

* 区分优先级，先访问必备信息，再访问其他信息
* 必备请求包控制在14KB内，减少慢启动
* 连接复用或合并请求
* 务必做好重试策略

## 突破WebView的局限性

* 无法区分网路场景适用不同加载，预取策略
* 数据更新自由度低，难以实现查分更新
* 页面跳转或刷新导致正在进行的网络请求被重置
* 默认的脚本加载机制会导致堵塞
* 并行的CSS更可能由于网络慢导致DOM重绘
* 连续多页跳转后的内存占用不可控

# 参考文章

[全网访问速度优化](http://www.infoq.com/cn/presentations/speed-optimization-of-whole-network-access)
