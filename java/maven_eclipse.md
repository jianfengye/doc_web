# eclipse中创建maven项目

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
          <archive>
          	<manifest>
          		<mainClass>com.didichuxing.flowduration.realtime.Realtime</mainClass>
          	</manifest>
          </archive>
        </configuration>
        <executions>
          <execution>
            <id>make-assembly</id> <!-- this is used for inheritance merges -->
            <phase>package</phase> <!-- bind to the packaging phase -->
            <goals>
              <goal>single</goal>
            </goals>
          </execution>
        </executions>
		</plugin>
	</plugins>
</build>
```
