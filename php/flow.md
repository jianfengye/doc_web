# php流程控制[未发布]

php中函数的规则：

控制php流程的关键词有：

## if, else, else if

## while，do-while

## for

### for中有计算数组长度语句怎么办？

如果将count放在for的结束判断语句中，可能会很慢，因为每次循环时都要计算一遍数组长度。可以用一个中间变量来存储数组长度来进行优化。

	<?php
	$people = Array(
	        Array('name' => 'Kalle', 'salt' => 856412),
	        Array('name' => 'Pierre', 'salt' => 215863)
	        );

	for($i = 0, $size = sizeof($people); $i < $size; ++$i)
	{
	    $people[$i]['salt'] = rand(000000, 999999);
	}
	?>

## foreach

### 用 list() 给嵌套的数组解包

php大于等于5.5.

PHP 5.5 增添了遍历一个数组的数组的功能并且把嵌套的数组解包到循环变量中，只需将 list() 作为值提供。

	<?php
	$array = [
	    [1, 2],
	    [3, 4],
	];

	foreach ($array as list($a, $b)) {
	    // $a contains the first element of the nested array,
	    // and $b contains the second element.
	    echo "A: $a; B: $b\n";
	}
	?>

## break

break可以接受一个可选的数字来决定跳出几重循环，默认为1。其中从5.4后定义break 0 为不合法。
```
	<?php
	$arr = array('one', 'two', 'three', 'four', 'stop', 'five');
	while (list (, $val) = each($arr)) {
	    if ($val == 'stop') {
	        break;    /* You could also write 'break 1;' here. */
	    }
	    echo "$val<br />\n";
	}

	/* 使用可选参数 */

	$i = 0;
	while (++$i) {
	    switch ($i) {
	    case 5:
	        echo "At 5<br />\n";
	        break 1;  /* 只退出 switch. */
	    case 10:
	        echo "At 10; quitting<br />\n";
	        break 2;  /* 退出 switch 和 while 循环 */
	    default:
	        break;
	    }
	}
	?>
```
## continue



## 流程控制的替代语法

php提供的流程控制的替代语法包括if, while, for, foreach, switch。替代语法的基本形式是把左边括号（{）换成冒号（:），把右花括号（}）替换成 endif，endwhile，endfor，endforeach。以及 endswitch。
```
	<?php if ($a == 5): ?>
	A ist gleich 5
	<?php endif; ?>
```
