# 如何远端连接hdfs

# 服务端

首先要确保远端的hdfs的namenode监听的是外网ip。
```
vim etc/hadoop/core-site.xml
```

确保下面的
```
<property>
     <name>fs.defaultFS</name>
     <value>hdfs://0.0.0.0:9001</value>
</property>
```
这里的value是外网可以访问的。

# 客户端代码

```
package com.demo.yjf.demo;

import java.io.IOException;
import java.net.URI;

import org.apache.hadoop.conf.Configuration;
import org.apache.hadoop.fs.FileStatus;
import org.apache.hadoop.fs.FileSystem;
import org.apache.hadoop.fs.Path;

/**
 * Hello world!
 *
 */
public class App {

	public static void main(String[] args) throws IOException {
		try {
			Configuration conf = new Configuration();
			FileSystem fs = FileSystem.get(URI.create("hdfs://10.94.120.194:9001/"), conf);
			Path path = new Path("hdfs://10.94.120.194:9001/input/");
			FileStatus[] list = fs.listStatus(path);
			for (int i = 0; i < list.length; i++) {
				System.out.println(list[i].getPath());
			}
		} catch(Exception e) {
			System.out.println(e.getMessage());
		}

	}
}

```


## 代码说明

先获取一个fs，再操作这个fs就可以做相关的操作了。
