# Composer 结合 Git 创建 “服务类库”

我一直认为，现在的 PHP 已经进展到了工程化的领域。以前的 PHP 开发者，以快为美，速度和规模永远都是矛盾体。现在的 PHP 项目，特别是稍微大型一点的项目中，已经在逐渐演化成为需要兼顾工程化和规模化的层次了。一个代码工程化，就意味着演化为逐渐复杂的架构。复杂的架构，微服务往往就是一个很好的选择。

我在最近的一个项目中，就需要这个问题。我需要开发一个地图服务，这个服务当然不是简单的类库形式，而是有自己的数据库，自己的服务接口。这种情况其实最优的选择就是服务化。服务化的方式当然有很多了，Thrift，Http 等。但是我评估了下当前的部门环境，PHP 是主流的语言，加上自己这个项目的进度也比较紧，在我眼中，Thrift，Http 等方式都是使用网络协议实现服务的解耦合，这在我看来已经是重度解决方案了。我觉得在项目没有明确清晰病入膏肓的情况下是没有必要这种方式的。使用网络协议服务化的劣势在于引入了强大的复杂度。这个复杂度往往意味着人力，物力，时间上的投入。所以我希望，能够提供一个 PHP 语言的 “服务类库” 的形式进行开发。

我想到的就是 PHP 的 Composer。

# Composer 的修改

## 创建服务类库

首先，我需要把我的 “服务类库” 从我的应用程序（起名为 xxx/main1）中独立出来，这个独立，我不是选择在应用程序中创建一个目录（事实我想过创建一个诸如 Services 的目录）。但是，如果和业务程序在代码上耦合起来，我觉得以人的惰性，很难从始至终都控制住自己能坚持不使用应用程序中方便的各种函数。所以我的选择是在 Git 库中新创建一个项目，起名为 xxx/mapService 。

## composer.json

现在两个 Git 项目(xxx/main1 和 xxx/mapService)，我在 main1 中的 `composer.json` 文件中增加下面的语句
```
"require": {
    "xxx/mapService" : "dev-master"
},
"repositories" : [
    {
        "type": "vcs",
        "url" : "git@git.xxxx.com:cloud/mapService.git"
    }
],
```

而在 mapService 的 `composer.json` 如下：
```
{
  "description": "xxxxxx",
  "name": "xxx/mapService",
  "type": "library",
  "authors": [
      {
          "name": "Yejianfeng",
          "email": "yejianfeng@xxxx.com"
      }
  ],
  "require": {
      "php": ">=5.2.4",
      "illuminate/database" : "*"
  },
  "autoload": {
      "psr-4": {
          "xxxx\\xxxx\\MapService\\": "src"
      }
  }
}

```
这个配置告诉 main1 项目，mapService 的 Git地址，需要使用的版本。
当然需要注意下面几点：
- dev-master 意思是直接使用 mapService 的master分支。如果 mapService 有其他的 tag，这里完全可以使用 tag 信息。
- repositories 是说明项目的地址。
- 我这里的这个服务是放在我们公司自己搭建的 GitLab 上的
- mapService 下面的 src 文件夹的命名空间为 `xxxx\\xxxx\\MapService\\` 并且支持 PSR-4
- mapService 使用了 illuminate/database

最后使用 `composer update -vvv` 可以把我们需要的 mapService 下载下来放在 vendor 目录下。

## 更新修改

我们现在编辑器在 main1 项目中，如果我们有对 mapService 这个项目有进行编辑修改，并且希望合并到 mapService 的 master 分支的化，就直接进入 vender/xxx/mapService 目录，进行 Git 对应的操作。这样就可以进行直接的代码修改了。

## 独立配置

这种结构的组合方式只是完成了万里长征的第一步。后续更为重要的是在编写这个服务的时候，我需要时刻记住不使用 main1 的所有东西，这样才能保持 mapService 的独立性（独立性是服务化的必要条件之一）。比如我第一个遇到的问题就是配置文件需要独立。

我的实现方式是直接在 mapService 中创建一个 Config 类，这个类中直接写死配置。

这里一直觉得这个配置文件的实现方式有点挫，因为这样，这个配置文件就进入到了 Git库。但是确实没有想到更好的方案了。Laravel 中有通过实现 ServiceProvider 将 Config 创建在 Laravel 的config 文件夹下的方式，但是这种方式仅仅只适用于 Laravel。没有通用性。在另外一个方向，我想服务使用哪个数据库这个本身也是服务的一部分，放在服务的 Git 库中貌似也没有什么。

## 目录结构

![](http://tuchuang.funaio.cn/18-2-22/3995902.jpg)

目录结构如上

- Configs 提供配置文件
- Contracts 提供接口协议
- Exceptions 提供异常
- Supports 提供第三方方法或者类库
- Models 提供对数据库的交互
- Node.php 实现具体的接口

服务最重要的事情是接口协议。所以创建一个Contracts文件夹，将提供的服务接口化。

```
interface NodeInterface
{
    /*
     * 获取某个城市某个坐标点某个范围内的nodes
     * @params int $cityId 城市id
     * @params int $lat 纬度
     * @params int $lng 经度
     * @params int $distance 坐标点范围,单位：米
     *
     * @return array(Models/Node)
     *
     * @throws
     */
    public function gets($cityId, $lat, $lng, $distance);
}
```

接口的异常处理尽量使用异常，而不是错误码的方式进行交互。而且这些异常尽量要自定义。这样，在上层就有了统一处理的可能性。

# 思考

这个架构模式我定位为 PHP 代码层面服务化的模式。适用的场景应该是
- 后期计划服务化
- 前期人力和思维都希望维持快速开发
的场景。

## 和 Git 的 SubTree 还有 SubModule 的区别

其实这三种方式说到底都是将一个项目作为另外一个项目的类库来使用的。SubTree 和 SubModule 是 Git 的解决方案。而 Composer 是 PHP 语言的解决方案，它除了将某个项目加入到另外一个项目的功能之外，还提供了加入版本，依赖解决等方案。如果你的项目是 PHP 的，那么无疑，使用 Composer 是更优的选择。

## 后期协议服务化

如果后期我的这个 mapService 想要协议服务化，那么这个 mapService 项目就可以简化成为一个SDK，对于上层业务逻辑，只需要使用 `composer update` 进行更新就行。

## 服务注册和发现

我这里所谓的 “服务类库” 确实没有解决服务注册的问题，我无法知道到底有几个项目使用了我的服务。这个可能需要额外的流程的工作了。
