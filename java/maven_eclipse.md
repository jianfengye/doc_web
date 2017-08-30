# eclipse中创建maven项目(未发布)

# 创建项目

在eclipse中创建maven项目之后，会生成.classpath / .setting / .project 三个文件或者文件夹

## .classpath

这个文件是用来说明这个工程的项目环境的

比如
* kind=src: 用来表示源文件地址
* kind=con: 表示运行的系统环境
* kind=lib: 表示工程的library具体位置
* kind=output: 表示工程的输出目录

## .project

这个文件表示说明这个工程的描述信息
比如：
* name: 表示工程名字
* comment: 表示工程描述

## .settings

描述各种插件的配置文件

## pom.xml

这个就是maven的配置文件了

# eclipse使用maven

## 创建

当使用eclipse创建一个maven项目的时候，pom如下：

```
<project xmlns="http://maven.apache.org/POM/4.0.0" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
  xsi:schemaLocation="http://maven.apache.org/POM/4.0.0 http://maven.apache.org/xsd/maven-4.0.0.xsd">
  <modelVersion>4.0.0</modelVersion>

  <groupId>com.yejianfeng</groupId>
  <artifactId>maventest</artifactId>
  <version>0.0.1-SNAPSHOT</version>
  <packaging>jar</packaging>

  <name>maventest</name>
  <url>http://maven.apache.org</url>

  <properties>
    <project.build.sourceEncoding>UTF-8</project.build.sourceEncoding>
  </properties>

  <dependencies>
    <dependency>
      <groupId>junit</groupId>
      <artifactId>junit</artifactId>
      <version>3.8.1</version>
      <scope>test</scope>
    </dependency>
  </dependencies>
</project>

```
看文件目录，其实只有两个文件夹src和target

![](http://tuchuang.funaio.cn/17-8-30/90635676.jpg)

## maven install

当我们调用
```
mvn install
```
的时候，我们看到下面的信息
```
➜  maventest mvn install
[INFO] Scanning for projects...
[INFO]
[INFO] ------------------------------------------------------------------------
[INFO] Building maventest 0.0.1-SNAPSHOT
[INFO] ------------------------------------------------------------------------
[INFO]
[INFO] --- maven-resources-plugin:2.6:resources (default-resources) @ maventest ---
[INFO] Using 'UTF-8' encoding to copy filtered resources.
[INFO] skip non existing resourceDirectory /Users/yejianfeng/Documents/didi/workspace/maventest/src/main/resources
[INFO]
[INFO] --- maven-compiler-plugin:3.1:compile (default-compile) @ maventest ---
[INFO] Changes detected - recompiling the module!
[INFO] Compiling 1 source file to /Users/yejianfeng/Documents/didi/workspace/maventest/target/classes
[INFO]
[INFO] --- maven-resources-plugin:2.6:testResources (default-testResources) @ maventest ---
[INFO] Using 'UTF-8' encoding to copy filtered resources.
[INFO] skip non existing resourceDirectory /Users/yejianfeng/Documents/didi/workspace/maventest/src/test/resources
[INFO]
[INFO] --- maven-compiler-plugin:3.1:testCompile (default-testCompile) @ maventest ---
[INFO] Changes detected - recompiling the module!
[INFO] Compiling 1 source file to /Users/yejianfeng/Documents/didi/workspace/maventest/target/test-classes
[INFO]
[INFO] --- maven-surefire-plugin:2.12.4:test (default-test) @ maventest ---
[INFO] Surefire report directory: /Users/yejianfeng/Documents/didi/workspace/maventest/target/surefire-reports
Downloading: http://maven.aliyun.com/nexus/content/groups/public/org/apache/maven/surefire/surefire-junit3/2.12.4/surefire-junit3-2.12.4.pom
Downloaded: http://maven.aliyun.com/nexus/content/groups/public/org/apache/maven/surefire/surefire-junit3/2.12.4/surefire-junit3-2.12.4.pom (1.7 kB at 3.4 kB/s)
Downloading: http://maven.aliyun.com/nexus/content/groups/public/org/apache/maven/surefire/surefire-providers/2.12.4/surefire-providers-2.12.4.pom
Downloaded: http://maven.aliyun.com/nexus/content/groups/public/org/apache/maven/surefire/surefire-providers/2.12.4/surefire-providers-2.12.4.pom (2.3 kB at 9.1 kB/s)
Downloading: http://maven.aliyun.com/nexus/content/groups/public/org/apache/maven/surefire/surefire-junit3/2.12.4/surefire-junit3-2.12.4.jar
Downloaded: http://maven.aliyun.com/nexus/content/groups/public/org/apache/maven/surefire/surefire-junit3/2.12.4/surefire-junit3-2.12.4.jar (26 kB at 95 kB/s)

-------------------------------------------------------
 T E S T S
-------------------------------------------------------
Running com.yejianfeng.maventest.AppTest
Tests run: 1, Failures: 0, Errors: 0, Skipped: 0, Time elapsed: 0.012 sec

Results :

Tests run: 1, Failures: 0, Errors: 0, Skipped: 0

[INFO]
[INFO] --- maven-jar-plugin:2.4:jar (default-jar) @ maventest ---
[INFO] Building jar: /Users/yejianfeng/Documents/didi/workspace/maventest/target/maventest-0.0.1-SNAPSHOT.jar
[INFO]
[INFO] --- maven-install-plugin:2.4:install (default-install) @ maventest ---
[INFO] Installing /Users/yejianfeng/Documents/didi/workspace/maventest/target/maventest-0.0.1-SNAPSHOT.jar to /Users/yejianfeng/.m2/repository/com/yejianfeng/maventest/0.0.1-SNAPSHOT/maventest-0.0.1-SNAPSHOT.jar
[INFO] Installing /Users/yejianfeng/Documents/didi/workspace/maventest/pom.xml to /Users/yejianfeng/.m2/repository/com/yejianfeng/maventest/0.0.1-SNAPSHOT/maventest-0.0.1-SNAPSHOT.pom
[INFO] ------------------------------------------------------------------------
[INFO] BUILD SUCCESS
[INFO] ------------------------------------------------------------------------
[INFO] Total time: 4.993 s
[INFO] Finished at: 2017-08-30T11:06:34+08:00
[INFO] Final Memory: 21M/208M
[INFO] ------------------------------------------------------------------------
```

可以清晰看到，这个install经过了：
* resources
* compile
* testResources
* testCompile
* test
* package(jar)
* install

环节，这个是packaging为jar的默认构建阶段，我们使用mvn install, 就执行到install为止

## 依赖包打包

我们引用了一个json依赖包，修改pom增加json库：
```
<dependency>
    <groupId>org.json</groupId>
    <artifactId>json</artifactId>
    <version>20160810</version>
</dependency>
```

增加了对应的代码：

```
public class App
{
    public static void main( String[] args )
    {
        System.out.println( "Hello World!" );

        String json = "{'name':'xiazdong','age':20}";
        String name = getName(json);
        System.out.println("name is " + name);

    }

    public static String getName(String json)
    {
    	JSONObject jsonobj = new JSONObject(json);
    	String name = jsonobj.getString("name");
    	return name;
    }
}
```

和测试代码：
```
public class AppTest extends TestCase
{
    ...

    public void testGetName()
    {
    	 String json = "{'name':'yejianfeng','age':20}";
         String name = App.getName(json);
         assertEquals("yejianfeng", name);
    }
}
```
我们再运行mvn install，看到执行了2个测试用例。

# 测试覆盖率报告

我们想知道这个测试覆盖了哪些代码，这个时候就需要有测试覆盖率了。我们使用jacoco来生成测试覆盖率报告。JaCoCo的官网在：http://www.eclemma.org/jacoco/

我们可以通过maven-help-plugin来查看这个jacoco插件的goal和具体的参数
```
mvn help:describe -Dplugin=org.jacoco:jacoco-maven-plugin -Ddetail
```
