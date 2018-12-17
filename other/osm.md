# 聊聊OSM

做路网的同学一定对OSM并不陌生，OSM是一个由英国人Steve Coast创立的一个项目，这个项目的目标是创建一个内容自由，且能让所有人编辑的世界地图。类似于维基百科的概念。和它对标的是谷歌的google map。google map是谷歌提出的电子地图服务。它是收费的。且并不提供友善的可以提供给所有人编辑的地图服务。

OSM最值得称道的是它的语义结构，设计的非常简单，但是却非常通用，我们可以很方便的使用这个语义结构来定义我们需要的路网。

# 语义结构

OSM 仅仅定义了四个语义结构，node, way, relation, tag。 基本思想是一个路网是可以使用点和线来进行划分的。如果有更为宏观的信息需要展示，就使用 relation 来进行展示。而每种元素的属性都可以使用tag进行无限延生。

node表示的最重要的属性是地理位置坐标。它表示的是一个点。

way是由一系列有序的node组成的，它可以表示三种结构（非闭合线，闭合线，区域）。非闭合线，通常用来表示道路，河流，铁路等。闭合线，通常可以用来表示环形的东西，比如地铁等。区域，表示一个真实闭合的区域。

relation 是由一系列的node，way，和其他的relation组合而成。它的作用其实比较大，比如，在路网里面，我用node和way画了一个路网，现在我想要画公交车121的路线，那么就可以使用relation来进行绘画。当然，如果你要表示的地图并不想有这样的信息，这个relation是可以不画的。

node, way, relation就相当于是地图世界中的三原色，使用这三个元素理论上能绘制出所有你需要的地图。当然，这三个元素也有对应的属性，比如你这个way叫做“后厂村路”。那么这个名字就是一个属性。这里OSM抽象出了tag 概念，tag是key, value的组合。所以它可以无限延展。

三原色也有一些公用的属性。

* user 最后修改/创建这个对象的用户
* uid  最后修改/创建这个对象的用户id
* timestamp 最后修改/创建这个对象的时间
* visible 这个对象是否要在地图中显示出来
* version 最后修改/创建这个对象的版本号
* changeset 这个节点的最后修改/创建这个对象的所在的changeList ID，这个和version不一样，version是每个对象自带的，changeset是全局的。

如果你使用过git，那么这个和git就很像了。当地图在开放的时间，一些人补充上了一些变动，如果平台每天收集，那么这些变动就会生成一个changeset。https://www.openstreetmap.org/history 这个地址告知了你指定的区域有哪些changeset。

# 例子

```
<?xml version="1.0" encoding="UTF-8"?>
<osm version="0.6" generator="CGImap 0.0.2">
 <bounds minlat="54.0889580" minlon="12.2487570" maxlat="54.0913900" maxlon="12.2524800"/>
 <node id="298884269" lat="54.0901746" lon="12.2482632" user="SvenHRO" uid="46882" visible="true" version="1" changeset="676636" timestamp="2008-09-21T21:37:45Z"/>
 <node id="261728686" lat="54.0906309" lon="12.2441924" user="PikoWinter" uid="36744" visible="true" version="1" changeset="323878" timestamp="2008-05-03T13:39:23Z"/>
 <node id="1831881213" version="1" changeset="12370172" lat="54.0900666" lon="12.2539381" user="lafkor" uid="75625" visible="true" timestamp="2012-07-20T09:43:19Z">
  <tag k="name" v="Neu Broderstorf"/>
  <tag k="traffic_sign" v="city_limit"/>
 </node>
 ...
 <node id="298884272" lat="54.0901447" lon="12.2516513" user="SvenHRO" uid="46882" visible="true" version="1" changeset="676636" timestamp="2008-09-21T21:37:45Z"/>
 <way id="26659127" user="Masch" uid="55988" visible="true" version="5" changeset="4142606" timestamp="2010-03-16T11:47:08Z">
  <nd ref="292403538"/>
  <nd ref="298884289"/>
  ...
  <nd ref="261728686"/>
  <tag k="highway" v="unclassified"/>
  <tag k="name" v="Pastower Straße"/>
 </way>
 <relation id="56688" user="kmvar" uid="56190" visible="true" version="28" changeset="6947637" timestamp="2011-01-12T14:23:49Z">
  <member type="node" ref="294942404" role=""/>
  ...
  <member type="node" ref="364933006" role=""/>
  <member type="way" ref="4579143" role=""/>
  ...
  <member type="node" ref="249673494" role=""/>
  <tag k="name" v="Küstenbus Linie 123"/>
  <tag k="network" v="VVW"/>
  <tag k="operator" v="Regionalverkehr Küste"/>
  <tag k="ref" v="123"/>
  <tag k="route" v="bus"/>
  <tag k="type" v="route"/>
 </relation>
 ...
</osm>
```

这个是[osm wiki](https://wiki.openstreetmap.org/wiki/OSM_XML)上摘抄出来的例子。我们可以看到，这个例子已经包含了所有的定义。它表达了在
```
<bounds minlat="54.0889580" minlon="12.2487570" maxlat="54.0913900" maxlon="12.2524800"/>
```
这个矩形框范围内的4个node，一个way, 还有一个relation。这个relation表达的就是`Küstenbus Linie 123`公交交通工具的线路。

我们从上面例子也看出，OSM描述自己的语言是选择XML，XML是一种清晰且易于人类解读的结构。OSM输出的XML文件通常以.osm后缀。这个文件现在已经被多种工具所适配。OSM的schema如下：https://wiki.openstreetmap.org/wiki/API_v0.6/XSD

比如最常用的qgis 和 arcgis 都支持直接使用osm文件导入进行编辑的功能。

[OSM（openstreetmap）矢量数据下载方法（路网，水系，铁路，建筑物）](https://blog.csdn.net/qq_912917507/article/details/81736041)这篇文章展示了各种用osm绘制的地图。

当然，OSM只是描述了地图的路网，而在绘制地图的时候，地图的底图需要使用地图底图提供商的服务，比如mapbox。

OSM的生态也非常好，比如[不用百度API也能计算驾驶距离——OSMnx计算多点驾车距离](https://zhuanlan.zhihu.com/p/37370059) 使用的是OSM的数据，以及python的osmnx包，直接提供了计算最短路径的服务。

OSM存储在数据库中一般使用POSTGIS（http://blog.geoserver.org/2009/01/30/geoserver-and-openstreetmap/）它会创建几张表：
* planet_osm_line
* planet_osm_point
* planet_osm_polygon
* planet_osm_roads
