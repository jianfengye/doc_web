# golang的cms[未发布]

# 说说cms

cms（内容管理系统）是建站利器。它的本质是为了快速建站。cms本质是一个后台服务站，使用这个后台，能很快搭建一个前台web站。在PHP的世界里面，CMS框架简直不要太多：著名的wordpress，漏洞很多的dedecms，以搭建论坛为主的discuz，优雅的Octorber。详细来说，cms是业务相关的。因为业务不同，具体的cms也会有不同的偏向类型，电商类cms，博客类cms等。以建立所有类型站为目标的cms往往可能就并不是那么好用。最简单的cms就是直接在页面上编写模版和数据，然后数据和模版进行生成静态html。

弱类型的语言可能天生适合做cms，但是golang这种强类型语言，做cms好像并不是那么容易。目前我看到有几个golang的cms项目，各有特色。

# ponzu

[ponzu](https://github.com/ponzu-cms/ponzu) ponzu感觉过去是一个很有想法的项目，首先现在流行前后端分离，基本上，在前后端分离的web前台，后端直接蜕化成为API是一个不错的选择。所以之前所谓的网站生成cms在这个视角也可以变成为api生成器。ponzu是这个逻辑，创建一个后台页面，页面对数据库进行管理，在里面可以创建，修改对象。并且根据对象，使用ponzu一个命令行生成前端api代码并运行。

具体操作可以看：https://www.jianshu.com/p/fc8552e9f9ff

# qor

[qor](https://getqor.com/cn)好像是gorm的作者团队开发的项目，它是一个电商cms，应该是从公司业务抽象开源出来的。它依赖于gorm这个orm。

[研究一个golang 写的cms系统qor，功能特别全](https://blog.csdn.net/freewebsys/article/details/80575900) 这里有一系列关于qor最全的分析。

qor是具体到电商行业的cms，它是直接生成了电商的html网站，并且提供了很好的qor-admin后台管理。使用这个后台管理项目能很有效创建后台系统。
