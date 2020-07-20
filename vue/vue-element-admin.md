# vue-elemnt-admin源码学习

vue-element-admin是一个基于vue，element-ui的集成的管理后台。它的安装部分就不说了，按照官网的步骤一步步就可以执行了。
https://panjiachen.github.io/vue-element-admin-site/zh/



# 单元测试

我们可以看到vue-element-admin的单元测试是使用jtest.config.js来进行测试

vue和jtest的结合就是和官网的一样：https://cn.vuejs.org/v2/guide/unit-testing.html

jtest的官网：https://github.com/facebook/jest#getting-started

我们可以看到，tests文件夹中

jtest对应的文件有jtest.config.js，这里面的配置信息在这个页面可以找到：https://jestjs.io/docs/zh-Hans/configuration

可以稍微修改下jest.config.js，将collectCoverage: true, 可以再设置一下coverageReporter

![20200624163741](http://tuchuang.funaio.cn/md/20200624163741.png)

于是可以运行 npm run test:unit 

![20200624163830](http://tuchuang.funaio.cn/md/20200624163830.png)

在控制台可以打出覆盖率报告，也可以在tests/unit/converage下面打出这些覆盖率报告。

# jsconfig.json

这个是给IDE vscode使用的配置文件。

# plop机制

plop机制是自动生成vue文件的一种机制。

plop相关的有几个地方，一个是package.json里面的npm run new。一个是plop.js中定义了3个生成器：view，store，component。

这三个生成器在文件夹plop-template中都有定义，定义了一个prompt.js和一个index.hbs。hbs是模版文件，prompt是交互文件，所以我们可以在命令行中使用npm run new 来初始化一个vue文件。

![20200624170500](http://tuchuang.funaio.cn/md/20200624170500.png)

plopjs的文档地址：https://plopjs.com/documentation/

# postcss.config.js

postcss.config.js说明可以使用postcss插件来进行配置。

关于为什么要使用postcss，这两篇文章写的很好： 
https://segmentfault.com/a/1190000003909268
https://juejin.im/post/59e5dc1d6fb9a0450a666d62

简单来说，使用postcss会让css可以按照标准写法，生成不同前缀的写法文件

这个是官方说明地址：https://github.com/postcss/postcss/blob/master/README-cn.md

# svgo

我们在package.json中可以看到有个script是svgo。

这个命令是将svg图片文件进行压缩的。https://panjiachen.github.io/vue-element-admin-site/zh/feature/script/svgo.html

svg是矢量图，svg放大不失真。svg和canvas都是h5推荐使用的图形技术，canvas基于像素，svg为矢量，还有完整的动画，事件机制。

# 目录结构

其实vue-elemnt-admin的目录结构在官网这边也描述很清楚了：
https://panjiachen.github.io/vue-element-admin-site/zh/guide/#%E7%9B%AE%E5%BD%95%E7%BB%93%E6%9E%84

![20200624175550](http://tuchuang.funaio.cn/md/20200624175550.png)

这个目录结构还是很适合做前端工程学习的。



