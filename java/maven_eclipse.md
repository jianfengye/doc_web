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

# 打包

maven的打包需要使用到一个maven插件，maven-assembly-plugin。

第一步需要引入assembly-plugin

```
<build>
	<plugins>
		<plugin>
			<artifactId>maven-assembly-plugin</artifactId>
			<version>3.1.0</version>
			<configuration>
	          <descriptorRefs>
	            <descriptorRef>jar-with-dependencies</descriptorRef>
	          </descriptorRefs>
        </configuration>
		</plugin>
	</plugins>
</build>
```

上面是最简单的引入assembly-plugin的方法，引入了maven-assembly-plugin的3.1.0版本，其对应的配置我们配置了一个descriptorRefs, 定义了生成的assembly的名字。就是我们最后生成的jar包会命名为[artifactId]-[version]-[descriptorRef].jar
