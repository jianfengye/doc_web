# flexbox简介

# 什么是flexbox

flexbox是一种新的布局方式，这种布局方式是2009年W3C提出的方案。它可以简便，完整，完成页面的布局。目前，它已经得到所有浏览器的支持。

但是flexbox从2009年以来，有各种版本的变化，比如2009年版本和2011年版本差别比较大。

# 为什么要有flexbox

每个新事物出现都有其历史使命的。flexbox也是如此，传统的布局依赖于屏幕的宽度和高度，或者依赖于计算的百分比，但是flex则是直接按照比例关系进行布局展示。

这样做的好处就是当屏幕进行拉升等情况的时候，flex布局的页面仍然符合我们的预期。

比如一个横排布局

```
|-----|-----|----------|
|  1  |  1  |     2    |
|-----|-----|----------|
```

平时的布局情况我们就会为1设置宽度25%，2设置宽度50%。但是这个时候，如果我们要变成下面这个布局呢？

```
|-----|-----|-----|----------|
|  1  |  1  |  1  |     2    |
|-----|-----|-----|----------|
```

那么我们就需要重新设置比例了，1比例为20%，2比例为40%。

在flexbox中，我们就可以直接设置一个横排为一个flex容器，然后子结构1的比例为1（flex:1）,自结构2的比例为(flex:2)。

# flexbox都有哪些属性可以设置

强烈推荐(《A Complete Guide to Flexbox》)[https://css-tricks.com/snippets/css/a-guide-to-flexbox/]。文章图文并茂地说了各个属性的各种含义。

## 父容器的属性

* display:flex。 表明这个容器是flex布局。
* flex-direction: row | row-reverse | column | column-reverse; 表明容器里面的子元素的排列方向。
* flex-wrap: nowrap | wrap | wrap-reverse; 如果子元素溢出父容器的时候是否进行换行。
* justify-content: flex-start | flex-end | center | space-between | space-around; 这一个容器子元素横向排版在容器的哪个位置
* align-items: flex-start | flex-end | center | baseline | stretch; 这个容器子元素纵向排版在容器的哪个位置
* align-content: flex-start | flex-end | center | space-between | space-around | stretch; 当容器内有多行项目的时候，项目的布局

## 子元素的属性

* order: <integer>; 子元素的排序
* flex-grow: <number>; 分配剩余空间的比例
* flex-shrink: <number>; 分配溢出空间的比例
* flex-basis: <length> | auto;
* flex: none | [ <'flex-grow'> <'flex-shrink'>? || <'flex-basis'> ] 在容器中占比
* align-self: auto | flex-start | flex-end | center | baseline | stretch; 特定某个子元素的排布情况

# 参考文章

(A Complete Guide to Flexbox)[https://css-tricks.com/snippets/css/a-guide-to-flexbox/]
(终极Flexbox属性查询列表)[http://www.w3cplus.com/css3/css3-flexbox-cheat-sheet.html]
(一个完整的Flexbox指南)[http://www.w3cplus.com/css3/a-guide-to-flexbox.html]
(Flex 布局教程：语法篇)[http://www.ruanyifeng.com/blog/2015/07/flex-grammar.html]
(利用flexbox构建可伸缩布局)[http://yanni4night.com/blog/flexbox-layout.html]
