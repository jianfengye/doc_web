# service worker

serviceWorker可以截获浏览器的所有请求，从而判断这个ajax请求是从本地请求还是从远程请求。

serviceworker特性：
* 不能直接访问/操作DOM
* 需要时直接唤醒，不需要时自动休眠
* 离线内容开发者可控
* 一旦被安装则永远存活，除非手动卸载
* 必须在HTTPS环境下使用（本地环境除外）
* 广泛使用了Promise

serviceWorker的启动流程

![20200505110628](http://tuchuang.funaio.cn/md/20200505110628.png)

1 注册
通知浏览器

serviceWorker不能越狱

2 安装

使用caches.open开辟一个缓存区域。caches.addAll增加资源缓存。

参考文档：
* https://www.bilibili.com/video/BV1it411U7Fz?from=search&seid=4113804772417513329