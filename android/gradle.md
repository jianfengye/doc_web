# Gradle目录解析(未发布)

Gradle 是以 Groovy 语言为基础，面向Java应用为主。基于DSL（领域特定语言）语法的自动化构建工具。
Gradle这个工具集成了构建，测试，发布和其他，比如软件打包，生成注释文档等功能。
之前eclipse使用ant进行软件的构建功能，需要配置一大堆的xml，但是在gradle中就不需要了。

目前主流的打包方式有ant,maven,gradle。gradle是近几年发展起来的自动化构建工具，解决ant构建上的繁琐代码。
比如在ant上发布多渠道的包，你需要自己写脚本替换渠道名称，而在gradle中就不需要了。已经内建支持多渠道打包。

# Gradle的文件结构

* ./build.gradle
* ./gradle.properties
* ./gradlew
* ./gradlew.bat
* ./local.properties
* ./setting.gradle
* ./XXX.iml
* ./app/build.gradle
* ./app/app.iml
* ./app/proguard-rules.pro

### ./builld.gradle 和 ./app/build.grade

gradle项目自动编译的时候要读取的配置文件。比如指定项目的依赖包等。
build.grade有两个，一个是全局的，一个是在模块里面。
全局的build.grade主要设置的是声明仓库源，gradle的版本号说明等。

./build.gradle

```
buildscript {
    repositories {
        // 声明仓库源，比如我们构建了一个安卓的库，现在想要把库上传到jcenter中供别人一起使用，则可以上传到jcenter中
        // 具体上传步骤见：http://www.jcodecraeer.com/a/anzhuokaifa/Android_Studio/2015/0227/2502.html
        jcenter()
    }
    dependencies {
        // 说明gradle的版本号
        classpath 'com.android.tools.build:gradle:1.3.0'

        // NOTE: Do not place your application dependencies here; they belong
        // in the individual module build.gradle files
    }
}

// 所有项目都继承这个配置
allprojects {
    repositories {
        mavenLocal()
        jcenter()
    }
}

```

./app/build.grade 设置了模块的gradle构建配置

```
// 说明这个模块是安卓项目，如果是多模块开发，有可能有的值为java/war
apply plugin: 'com.android.application'

// 配置了所有android构建的参数
android {
    // 编译使用SDK版本
    compileSdkVersion 23
    // 编译工具的版本
    buildToolsVersion "23.0.1"

    defaultConfig {
        // 包名
        applicationId "com.awesomeproject"
        // sdk最低支持版本
        minSdkVersion 16
        // 目标SDK版本，如果目标设备的API版本正好等于此数值，就不会为此程序开启兼容性检查判断的工作
        targetSdkVersion 22
        // 版本号
        versionCode 1
        versionName "1.0"
        // 原生
        ndk {
            abiFilters "armeabi-v7a", "x86"
        }
    }
    buildTypes {
        // 发布时候的设置
        release {
            // 是否进行混淆
            minifyEnabled false
            // 混淆使用文件
            proguardFiles getDefaultProguardFile('proguard-android.txt'), 'proguard-rules.pro'
        }
    }
}

// 依赖的工具包
dependencies {
    compile fileTree(dir: 'libs', include: ['*.jar'])
    compile 'com.android.support:appcompat-v7:23.0.0'
    compile 'com.facebook.react:react-native:0.11.+'
}

```

## ./app/proguard-rules.pro

这个和上面说的一样混淆文件

## ./gradle.properties

grade的运行环境配置，比如使用多少内存之类的。

## ./gradlew 和 ./gradlew.bat

自动完成 gradle 环境的脚本，在linux和mac下直接运行gradlew会自动完成gradle环境的搭建。

## ./local.properties

配置SDK或者NDK的环境路径，各个机器上这个变量可能都是不一样的，所以不应该进入版本库

## ./setting.gradle

整个项目的管理，比如这个项目包含哪些模块等。

## ./XXX.iml 和 ./app/app.iml

iml是Intellij模块文件。Intellij是一款JAVA的IDE。Android Studio是基于开源的Intellij IDEA开发出来的IDE。
所以Android Studio有的IDE功能是需要有.iml才能使用的。比如我们删除了iml文件，可能就在Android Studio中看不到一些目录了。

# 参考

[IDEA 及 Gradle 使用总结](http://www.jiechic.com/archives/the-idea-and-gradle-use-summary)
[使用 Gradle 管理你的 Android Studio 工程](http://www.open-open.com/lib/view/open1437144995334.html)
[史上最详细的Android Studio系列教程四--Gradle基础](http://segmentfault.com/a/1190000002439306)
[用Gradle 构建你的android程序](http://www.cnblogs.com/youxilua/archive/2013/05/20/3087935.html)
