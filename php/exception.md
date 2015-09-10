# PHP的异常设计原则[未发布]

在任何项目中，异常都是非常重要的一环设计。一个PHP框架中的异常设计也是非常重要的。异常是一个双刃剑，我们可以在任何地方抛出异常，也可以在任何地方cover异常。

我想，基本异常的设计有几个要思考的地方：

1 什么时候抛出异常

异常是代表这个层级处理不了的问题，一个函数中抛出异常，代表这个异常是这个函数中无法处理，期待外层捕获异常再进行处理。

2 为什么不用错误码？

比如你写一个函数

function f($a)
{
    if ($a < 10) {
        return ['error' =>  100, 'ret' => ''];
    }

    return ['error' => 0, 'ret' => 'function success'];
}

这个就是使用错误码的方式进行函数返回。如果用异常的方式：

function f($a)
{
    if ($a < 10) {
        throw new DomainException("function params domain error");
    }

    return 'function success';
}

使用错误码是不是很丑陋？在外层，每次使用到这个函数的时候，必须进行错误码的验证。
