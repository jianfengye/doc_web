# maven使用实战

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

## 增加依赖库

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

        String json = "{'name':'yejianfeng','age':20}";
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

我们着重看两个goal，一个是prepare-agent，一个是report

prepare-agent的说明文档在：http://www.jacoco.org/jacoco/trunk/doc/prepare-agent-mojo.html，它的目标就是为测试的JVM设置属性和参数，做一些遇上线之前的操作。jacoco的原理大概就是在JVM前面启动一个agent，这个agent负责传递参数给JVM，和收集JVM的运行结果到本地。这个goal就是启动agent的过程。即使你没有需要传递的额外的参数，也需要在execute里面设置这个goal。

report的说明文档在：http://www.jacoco.org/jacoco/trunk/doc/report-mojo.html，它的目标是为了使用agent的运行结果生成一个测试报告。也是必要的。当然，我们可以配置各个参数来设置这个结果的覆盖文件，编码，报告生成地址等。

我们把pom里面的plugin改为下面的形式：
```
<plugin>
    <groupId>org.jacoco</groupId>
    <artifactId>jacoco-maven-plugin</artifactId>
    <version>0.7.9</version>
    <executions>
        <execution>  
            <id>pre-test</id>  
            <goals>  
              <goal>prepare-agent</goal>  
            </goals>  
          </execution>
        <execution>  
          <id>post-test</id>  
          <phase>test</phase>  
          <goals>  
                <goal>report</goal>  
          </goals>  
        </execution>  
   </executions>
</plugin>
```
好，下面运行maven install, 就看到了生成的测试覆盖率报告了。

![](http://tuchuang.funaio.cn/17-8-31/54457620.jpg)

# 主清单属性

当我们在命令行要运行这个jar的时候
```
java -jar target/maventest-0.0.1-SNAPSHOT.jar
```
发现提示错误:
```
target/maventest-0.0.1-SNAPSHOT.jar中没有主清单属性
```
这里提示我们没有设置主清单属性，就是没有设置主函数。
（当然我们可以使用
```
java -cp target/maventest-0.0.1-SNAPSHOT.jar com.yejianfeng.maventest.App
```
来指定运行哪个类）

我们需要一个插件来设置主清单，让我们的这个jar包变成可执行jar包（也叫uber-jar 或者 fat-jar）。这个插件的主页在：https://maven.apache.org/plugins/maven-shade-plugin/

通过看文档，我们知道这个插件有两个goal，除了help目标之外，最有用的是shade目标。它默认绑定在package阶段的。这个goal有一个trasformer的配置，可以设置manifestEntity。这个实体可以告知这个库使用的主类是什么。

```
  <plugin>
    <groupId>org.apache.maven.plugins</groupId>
    <artifactId>maven-shade-plugin</artifactId>
    <version>3.1.0</version>
    <configuration>
      <!-- put your configurations here -->
    </configuration>
    <executions>
      <execution>
        <phase>package</phase>
        <goals>
          <goal>shade</goal>
        </goals>
        <configuration>
        	<transformers>
        		<transformer implementation="org.apache.maven.plugins.shade.resource.ManifestResourceTransformer">
        			<manifestEntries>
        				<Main-Class>com.yejianfeng.maventest.App</Main-Class>
        			</manifestEntries>
        		</transformer>
        	</transformers>
        </configuration>
      </execution>
    </executions>
  </plugin>
```

好了，现在可以生成可执行jar包了

```
➜  maventest java -jar target/maventest-0.0.1-SNAPSHOT.jar
Hello World!
name is yejianfeng
```

而且这个jar包也包含了所有的依赖包。
