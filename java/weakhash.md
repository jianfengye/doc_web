# WeakHashMap

今天在具体业务的时候看到HashMap和WeakHashMap的区别。因为PHP语言并没有这种概念。所以很好奇做了一下研究。

# WeakHashMap

WeakHashMap所谓的“弱”是针对GC来说的。换句话说，GC操作的时候，会不会自动去回收掉WeakHashMap中已经没有被引用的数据？它的规则是这样的：如果WeakHashMap中的key是一个变量，并且这个变量没有被引用了。那么这个时候，系统gc的时候，就会把这个没有引用的HashMap的key，value删除。

具体看下面的代码：
```
/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
package com.mycompany.demo;

import java.util.HashMap;
import java.util.Iterator;
import java.util.Map;
import java.util.WeakHashMap;

/**
 *
 * @author yejianfeng
 */
public class hashmap {

    public static void main(String[] args){
        String a = new String("a");  
        String b = new String("b");  
        Map weakmap = new WeakHashMap();  

        weakmap.put(a, "aaa");  
        weakmap.put(b, "bbb");  

        a=null;  

        System.gc();  
        Iterator j = weakmap.entrySet().iterator();  
        while (j.hasNext()) {  
            Map.Entry en = (Map.Entry)j.next();  
            System.out.println("weakmap:"+en.getKey()+":"+en.getValue());   // weakmap:b:bbb

        }  
    }  
}

```

上面的只是把a对象去掉引用，就相当于告诉gc可以进行回收了。

WeakHashMap中的具体Entry实现了WeakReference<Object>的接口，在put数据进入HashMap的时候把queue传递进去了。ReferenceQueue<Object>是WeakHashMap创建的一个Reference队列。当gc回收Entry的key的时候，就会把消息通知到这个队列中，然后这个hash就知道了这个key被删除了，同时就会把这个key对应的Entry进行删除了。

# 参考文章

http://rockybalboa.blog.51cto.com/1010693/813161/
