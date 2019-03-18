# flutter初体验

和flutter斗争了两个周末，基本弄清楚了这个玩意的布局和一些常用组件了。

在flutter里面，所有东西都是组件Widget。我们像拼接积木一样拼接Widget，拼接的关键词是child或者children。以我几乎为0的web布局经验，往往在使用widget的时候，头脑里面映射的都是<div><table>等语义话标签。

# StatefulWidget和StatelessWidget

Widget大类上分为两个，StatefulWidget和StatelessWidget。这两个都是抽象类，具体的里面代码我没有进去看。在我理解中，他们两个的区别就是一个是有状态，就是有数据的，在页面切换的时候，需要注意处理这些“状态”，甚至从不同的入口进来是有不同状态的。而另外一个stateless就是无状态，不管什么样子都是这样的一种静态表达。

就我使用的感觉来看，甚至不需要了解太多细节，也能很好使用他们。基本上我们做应用不管是建立应用还是组件，都是使用StatefulWidget。它的基本代码如下:
```
import 'package:flutter/material.dart';

// 我的页面
class MineView extends StatefulWidget {
  MineView({Key key});

  @override
  State<StatefulWidget> createState() {
    return new _MineViewState();
  }
}

class _MineViewState extends State<MineView> {

  @override
  void initState() {
    // TODO: implement initState
    super.initState();
  }

  @override
  Widget build(BuildContext context) {
    return Text("这是mine");
  }

}
```

每次创建一个StatefulWidget，都需要对应创建一个State。这个State里面的build才是最重要的内容。每次这个StatefulWidget打开的时候都会调用initState方法。貌似这个initState是一些页面打开速度的优化点，里面逻辑要是太多，整个页面效果就很受影响。具体怎么操作后续再学习。

当需要给一个页面传递数据的时候，就需要修改StatefulWidget的构造函数，创建一个类属性，构造函数可以给传递进去。在state里面要获取widget里面的数据的时候直接使用widget.<属性名>就行了。state和widget基本上是联通的。

# 布局即代码

在flutter中，所有的布局都是代码，用代码来绘制页面上的所有元素。没有了以前安卓的xml等文件。

这种方式让我想起了MVC模式，基本上它把View和Controller混合在一起了。和PHP以前的Smarty时代有的一拼，甚至更为古老。我觉得这种模式其实也没啥太大的问题。作为前端，其实View层远远强大于Controller层，它最好就是做一些展示的工作就好，如果有太多的业务逻辑，反而不美了。所以这种布局即代码应该很多情况下只有View层代码而已，并不会很乱。

其次这种布局即代码的方式大大增加了组件的复用性。组件本质也是开放闭合，代码的方式让一个组件开放的东西更为自由了。而且让组件简单的退化为一个包了。（而不是先复制xml，再xxx等）。闭合也更内聚了。最终结果就是复用性大大提高。我要一种很好看的样式，直接import一个包万事。

不过这个让我想到了现在各种语言都颇为混乱的包管理平台。包可以有大，有小，有正规，有垃圾，还有各种升级冲突等问题。可预见的，以后每个flutter developer 也会是每人拥有一个自己最熟悉的军火库了。

# 布局组件

照着这个项目：https://github.com/pj0579/Flutter_E_Project 撸了一个页面，进行组件学习。说实话，这个项目让我走了不少弯路，感觉作者也是刚写flutter不久。特别是布局方面，我踩了不少坑，不过也慢慢有一些感觉了。下面把遇到的一些组件和注意事项说说：

## MaterialApp

这个是风格组件，安卓的Material风格的组件，与之相对应的是Cupertino（IOS风格）。其实这里我也有点困惑，原本我以为的flutter统一Andriod和IOS，我以为是UI也会统一，就是我使用一个控件，在Android下面会是安卓风格，在IOS下面会是IOS风格。不过后来想想，UI层是不可能适配的，毕竟两边的UI风格差别这么大。

MaterialApp基本上最外面的main最外层的一个Widget了
```
import 'package:flutter/material.dart';
import 'package:first_flutter_app/pages/home_page.dart';

void main() => runApp(new MyApp());

class MyApp extends StatelessWidget {

  // 第一个widget
  @override
  Widget build(BuildContext context) {

    return new MaterialApp(

      title: "我的第一个APP",

      theme: new ThemeData(
        primaryColor: Colors.white,
      ),

      home: MyHomePage()
    );
  }
}
```

## Scaffold

如果你希望你的页面是上中下分的，上面是一些标题，下面是一些tab按钮，那么不需要你绘制了，这个组件就给你做好了。

它里面有几个属性
* appBar 头部Widget
* body 中间部分的Widget
* bottomNavigationBar 底部的导航按钮设置

基本是必须填写的。

## AppBar

这个就是用来填写 Scaffold 的appBar部分。

## BottomNavigationBar

这个用来填写 Scaffold 的bottomNavigationBar部分。我基本上用了它的
* items 有哪些按钮
* currentIndex 当前触发的按钮
* onTap 在选择某个按钮的时候触发的动作
这三个属性也就差不多了

## Center

这个是偏向布局的属性，表示内部包含的结构在整个父结构的中间

## ListView

这个是非常非常重要的组件！！！所有的上下类型的列表结构都可以使用它填充。它有个特点，左右和屏幕一样宽，上下是无限的。

ListView的children属性是保存它展示哪些Widget的。比如：

```
@override
Widget build(BuildContext context) {
  return ListView(
      children: <Widget>[
        new BannerWidget(
          200.0,
          this.bannerList,
          bannerPress: (pos, item){
            print("第 $pos 点击了");
          },
        ),
        new LimitWidget(),
        new CommonWidget(),
      ],
  );
}

```
ListView还有一个需要特别注意的地方，就是ListView嵌套的情形，基本上，我们不可能没有ListView嵌套的情形。（当然你的布局分析够牛逼，也是可能的）。当嵌套的时候，需要注意两个属性。

一个是shrinkWrap。被嵌套的必须设置为true。代表我当前的ListView是嵌套在另外一个ListView中的。可能对于当前ListView测绘长宽都是有好处的。

另外一个是physics: new NeverScrollableScrollPhysics(); 这个是为了取消当前ListView的下滑功能。否则你会发现，你触摸在子集ListView的时候滑动的是子的ListView。

当你的ListView的数据比较多，不希望一次性加载完的时候，下拉再渲染的时候，你就要使用ListView.builder来做下拉渲染。

## GridView

这个基本上和ListView是齐名的组件，它的表现形式就是网格，在一些商品列表或者卡片列表的时候非常好用。

它也有shrinkWrap和physics属性，也是一样，一旦它被ListView或者GridView嵌套，shrinkWrap需要设置true，physics设置为不允许下拉。

## Column 和 Row

这两个也是很经常用，但是我的建议是这两个在最后绘制到具体Widget的时候再用，在大的布局上面还是尽量使用ListView。原因是我碰到了几次使用他们嵌套导致主方向或者从方向长度冲突的问题。（这个真是时间的教训，最开始我使用的都是这两种进行整个页面的布局，后续碰到问题，在这个坑里面绕了一阵，后来使用ListView把外层的全部替换，就过了。）

Column表示它的子元素是一层一层叠加的。Row表示它的子元素是从左到右罗列的。他们都有主方向和次方向，Column的主方向就是上下，Row主方向是左右。主方向和次方向的布局，比如是中间间隔，还是靠左，靠右，还是两边分开。主方向主要看MainAxisAlignment，次方向看CrossAxisAlignment。

## Image.network

当从网络获取图片进行展示的时候使用这个组件

## Text

文字展示用这个组件

这里就是有个注意点，flutter没有链接的组件，需要去组件库查找，或者自己写。依赖于url_launcher/url_launcher包，大概写法如下：
```
import 'package:flutter/material.dart';
import 'package:flutter/gestures.dart';
import 'package:url_launcher/url_launcher.dart';

class Link extends TextSpan {

  Link({ TextStyle style, String url, String text }) : super(
      style: style,
      text: text ?? url,
      recognizer: TapGestureRecognizer()
        ..onTap = () {
          launch(url, forceSafariVC: false);
      }
  );
}
```

使用
```
Text.rich(
    Link(
        style: new TextStyle(fontSize: 12.0,
            fontWeight: FontWeight.normal,
            color: Colors.blue),
        text: "查看全部",
        url: "http://baidu.com"
    )
)
```
## Card

这个也是很常用的组件。就是带阴影的卡片结构。基本上我现在也就使用到child的属性。

## Container

这个就相当于DOM中的<div>，将内部的结构用Container包裹，统一做一个margin的操作等。

# 总结

flutter毕竟才1.2，我是很看好它的前景的。（鉴于3年前React刚出来的时候我也做过类似判断，这句话听听就算了。）我的观点就是市场决定语言的普世程度。后续开发APP会变得异常简单（当然基于对UI要求不是那么严格的情况下）。毕竟能一份代码同时开发IOS和Android，甚至以后的桌面应用和Web应用，简直把天堂描述的太美好了。

然后这个语言的大公司支持程度也很牛，Google，国内的阿里（闲鱼）。加了闲鱼的一个群，一天之内成员就达到上线。。。目测一大波程序员已经涌入了，可是这个是在革自己的命啊。。。感叹下，但是对大局来说，把开发成本降最低绝对是一个好事。
