# PHP 的 uniqid 函数产生的 id 真的是唯一的么？

最近使用到了 uniqid，就产生了疑问？uniqid 生成的 id 由什么组成？真的是唯一的么？什么情况下会产生冲突？

从文档中看到 uniqid 函数有两个参数

![](http://tuchuang.funaio.cn/18-5-18/55310651.jpg)

# uniqid 的结构

看源码：
```
PHP_FUNCTION(uniqid)
{
    ...
	gettimeofday((struct timeval *) &tv, (struct timezone *) NULL);
	sec = (int) tv.tv_sec;
	usec = (int) (tv.tv_usec % 0x100000);

    ...
	if (more_entropy) {
		uniqid = strpprintf(0, "%s%08x%05x%.8F", prefix, sec, usec, php_combined_lcg() * 10);
	} else {
		uniqid = strpprintf(0, "%s%08x%05x", prefix, sec, usec);
	}

	RETURN_STR(uniqid);
}
```

基本就了解清楚了。uniqid 是由四个部分组成：
```
prefix + sec + usec + “.” + php_combined_lcg
```
其中 prefix 就是 uniqid 函数的第一个参数。它是一个字符串，传递进来什么，就直接返回什么。

sec 是当前时钟的秒，usec 是毫秒，这两个值都是从 gettimeofday 获取的。换句话说，只要在一台机器上，两个 php 程序在同一个毫秒内获取的 sec 和 usec 是一样的。

php_combined_lcg 是 uniqid 的第二个参数决定的，它是一个墒值，它是使用线性同余生成一个 0 ～ 1 之间的随机数。如果第二个参数为 true，就有这个值，如果第二个参数为 false，就没有这个值。

比如：
```
➜  ~ php -r 'echo uniqid("my_", true);'
my_5afe9b414c2141.76621929
```

# 结论

所以说，如果我们单纯使用 uniqid() 这个方法，不带任何参数的话，这个方法只能保证单个进程，在同一个毫秒内是唯一的。如果使用uniqid("", true)。 带了一个墒值，自身已经有一个随机的方式能保证生成的id的随机性了。但是由于线性同余是比较简单的生成随机数的算法，随机性有可能还不够，所以，网上流传的一种更随机数值的方式是：
```
uniqid(mt_rand(), true)
```

其中 mt_rand() 生成随机数就不是使用线性同余生成随机数的方式了，而是使用 Mersenne Twister Random Number Generator （梅森旋转算法）。换句话说，上面这个 id 由两种随机算法 ＋ 时间戳生成。基本上，这个算法在很大程度上能保证唯一性了（如果要问冲突率的话，估计只有数学系学生能研究出来了...）。

上面的这个给出的id会有一个点号，而且长度并不是128bit。如果希望生成uuid，就需要一个hash，不管是md5,sha1 都是可以选择的。所以网上又有一种生成唯一码的方式。
```
md5(uniqid(mt_rand(), true))
```

但是，本质上，这两种方式的随机性是相等的。
