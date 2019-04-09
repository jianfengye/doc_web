# 史上最快的后台搭建框架

如果你要问我说最快的后台搭建框架是什么，我会毫不犹豫的说，laravel-admin(https://laravel-admin.org/)。这个框架的作者是z-song，应该是腾讯公司的。它的官网写着“在十分钟内构建一个功能齐全的管理后台”。没有夸张，就是这么虎。

搭建完成的例子如下：![avatar](http://tuchuang.funaio.cn/WX20190328-081700@2x.png)
具体如何使用查看官网就可以了。这里就说下它用到的几个技术。

# Pjax

这个框架左边导航选择后，右边页面刷新，使用的技术是Pjax。它解决的就是局部刷新页面的功能。这个技术说白了就是Ajax请求返回的不是json，而是html，替换页面上的局部界面。

Pjax在前端是有一个jquery的库支持的，jquery.pjax.js。这个库基本原理是使用window.history.pushState来配合ajax进行页面刷新，并且重置页面的上一步，下一步的操作。要达到的效果就是虽然我只使用ajax更新页面的一个部分，但是我也能像进入新页面一样拥有浏览器点击上一步回退到旧的页面的功能。

这个pushState是h5的特性。所以浏览器必须要支持h5。

# grid, form, tree

这三种结构，特别是前面两种结构，是经常使用到的。grid和form使用的熟练与否就代表了我们能否很好使用这个框架了。

grid就是我们查看一个模型页面的表单。它借用php的魔术方法，把所有要展示的字段都封装成了方法。大致代码如下：
```
return ReportTag::grid(function(Grid $grid) {
    $grid->id("标签id");
    $grid->name("标签名称");
    $grid->parent("父级id");

    $grid->created_at("创建时间");
});
```
而form则是我们编辑/新建时候的表单。它则是以form表单的类型做方法：
```
return ReportTag::form(function (Form $form) {
    $form->text('name', '标签名字')->rules('required');
    $form->text('parent', '父级id，如果自身就是父级，填0')->rules('required');
});
```
实际上，我在使用的过程中遇到不少额外的需求。算是碰上了一些高级用法把。当然文档里面也都有，但是写的不尽详细。这里我记录下这些不常见的用法。

## form中有两个下拉框联动下拉，即我下拉第一个select，第二个select或者multiselect会自动变化选项

```
$form = ReportTagArea::form(function (Form $form) use ($tags, $areas) {
    $form->select('area_id', '区域')->options($areas)->rules('required')->load("periods", "/tag/periods?q=[]");
    $form->multipleSelect("periods", "时段")->rules("required");
});

```

上面例子就是我选择了区域选项的时候，调用ajax接口，/tag/periods?q=[area_id] 来填充periods这个时段选择框。（官网只说了联动选择可以联动两个select，我试了下，可以联动select和multipleSelect）

## grid 在每一列增加一个行为

```
$grid->actions(function ($actions) {
    // 跳转到路口规则列表
    $actions->append('<a href="/tag/junctions?area_id='. $actions->row->area_id .'"><i class="fa fa-eye"></i></a>');
});
```

## 要在页面上增加自定义的ajax如何做？

使用 Admin::script方法
```
class FullController extends Controller
{
  private function getjs() {
      return "alert(11)";
  }

  public function index () {
      Admin::script($this->getjs());
      return Admin::content(function (Content $content) {
        ....
      }
  }
}
```
# 总结

关于管理后台，快速搭建快速相应是王道，有屎以来，我真心没有见过比laravel-admin能更快搭建管理后台的框架了。如果说laravel是个大斧子，laravel-admin就是一个镶金嵌玉的斧子，只要你熟练掌握了使用方法，就可以很方便完成你的需求。当然，如果有的bt需求是这个框架没有想到的，那么你可能花费的时间就会比平时更多。但是再强调一下，这个框架适合做的是管理后台，一般管理后台的需求，都是可以和PM进行pk的，不是么。

# 参考

https://www.fanhaobai.com/2017/07/pjax.html
https://www.renfei.org/blog/html5-introduction-3-history-api.html
