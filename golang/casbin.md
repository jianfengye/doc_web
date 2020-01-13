# casbin源码分析

Casbin是一个强大的、高效的开源访问控制框架，其权限管理机制支持多种访问控制模型。目前这个框架的生态已经发展的越来越好了。提供了各种语言的类库，自定义的权限模型语言，以及模型编辑器。在各种语言中，golang的支持还是最全的，所以我们就研究casbin的golang实现。

# 访问控制模型

控制访问模型有哪几种？我们需要先来了解下这个。

## UGO(User, Group, Other)
这个是Linux中对于资源进行权限管理的访问模型。Linux中一切资源都是文件，每个文件都可以设置三种角色的访问权限（文件创建者，文件创建者所在组，其他人）。这种访问模型的缺点很明显，只能为一类用户设置权限，如果这类用户中有特殊的人，那么它无能为力了。


## ACL(访问控制列表)
它的原理是，每个资源都配置有一个列表，这个列表记录哪些用户可以对这项资源进行CRUD操作。当系统试图访问这项资源的时候，会首先检查这个列表中是否有关于当前用户的访问权限，从而确定这个用户是否有权限访问当前资源。linux在UGO之外，也增加了这个功能。

```
setfacl -m user:yejianfeng:rw- ./test
```

```
[yejianfeng@ipd-itstool ~]$ getfacl test
# file: test
# owner: yejianfeng
# group: yejianfeng
user::rw-
user:yejianfeng:rw-
group::rw-
mask::rw-
other::r--
```

当我们使用getfacl和setfacl命令的时候我们就能对某个资源设置增加某个人，某个组的权限列表。操作系统会根据这个权限列表进行判断，当前用户是否有权限操作这个资源。

## RBAC(基于角色的权限访问控制)

这个是很多业务系统最通用的权限访问控制系统。它的特点是在用户和具体权限之间增加了一个角色。就是先设置一个角色，比如管理员，然后将用户关联某个角色中，再将角色设置某个权限。用户和角色是多对多关系，角色和权限是多对多关系。所以一个用户是否有某个权限，根据用户属于哪些角色，再根据角色是否拥有某个权限来判断这个用户是否有某个权限。

RBAC的逻辑有更多的变种。

变种一：角色引入继承

角色引入了继承概念，那么继承的角色有了上下级或者等级关系。

变种二：角色引入了约束

角色引入了约束概念。约束概念有两种，

一种是静态职责分离：
a、互斥角色：同一个用户在两个互斥角色中只能选择一个
b、基数约束：一个用户拥有的角色是有限的，一个角色拥有的许可也是有限的
c、先决条件约束：用户想要获得高级角色，首先必须拥有低级角色

一种是动态职责分离：
可以动态的约束用户拥有的角色，如一个用户可以拥有两个角色，但是运行时只能激活一个角色。

变种三：既有角色约束，又有角色继承

就是前面两种角色变种的集合。

## ABAC(基于属性的权限验证)

Attribute-based access control，这种权限验证模式是用属性来标记资源权限的。比如k8s中就用到这个权限验证方法。比如某个资源有pod属性，有命名空间属性，那么我设置的时候可以这样设置：

```
Bob 可以在命名空间 projectCaribou 中读取 pod：
{"apiVersion": "abac.authorization.kubernetes.io/v1beta1", "kind": "Policy", "spec": {"user": "bob", "namespace": "projectCaribou", "resource": "pods", "readonly": true}}
```

这个权限验证模型的好处就是扩展性好，一旦要增加某种权限，就可以直接增加某种属性。

## DAC（自主访问控制）

在ACL的访问控制模式下，有个问题，能给资源增加访问控制的是谁，这里就有几种办法，比如增加一个super user，这个超级管理员来做统一的操作。还有一种办法，有某个权限的用户来负责给其他用户分配权限。这个就叫做自主访问控制。

比如我们常用的windows就是用这么一种方法。
![windows](http://tuchuang.funaio.cn/WX20191230-160514.png)

很多的wiki权限也是这样的权限管理方式。

## MAC(强制访问控制)

强制访问控制和DAC相反，它不将某些权限下放给用户，而是在更高维度（比如操作系统）上将所有的用户设置某些策略，这些策略是需要所有用户强制执行的。这种访问控制也是基于某些安全因素考虑。



# casbin的基本使用

casbin使用配置文件来设置访问控制模型。我们可以通过casbin的模型编辑器来查看。

它有两个配置文件，model.conf 和 policy.csv。其中 model.conf 存储的是我们的访问控制模型，policy.csv 存储的是我们具体的用户权限配置。

权限本质上就是最终询问这么一个问题“某个用户，对某个资源，是否可以进行某种操作”。casbin的使用非常精炼。基本上就生成一个结构，Enforcer，构造这个结构的时候加载 model.conf 和 policy.csv。使用示例如下：

```go
import "github.com/casbin/casbin/v2"

e, err := casbin.NewEnforcer("path/to/model.conf", "path/to/policy.csv")

sub := "alice" // the user that wants to access a resource.
obj := "data1" // the resource that is going to be accessed.
act := "read" // the operation that the user performs on the resource.

ok, err := e.Enforce(sub, obj, act)  // 查看alice是否对data1z这个资源有read权限

if err != nil {
    // handle err
}

if ok == true {
    // permit alice to read data1
} else {
    // deny the request, show an error
}
```

当然，casbin 可以读取具体 policy 的时候不仅仅可以通过 csv 文件进行读取，也可以通过数据库进行读取。这样我们甚至可以写一个用户管理后台来配置不同的用户权限。model.conf 也是可以从配置文件中获取，也可以从代码中获取，从代码中获取就可以扩展为先读取数据库，再代码加载。但是 model.conf 一旦修改，对应的 policy 就需要进行同步修改，所以 model 在一个系统中不要进行频繁修改。

# PML

casbin 是一种典型的“配置即一切”的软件思路，那么它的配置语法就显得格外重要。我们可以通过 casbin 的在线配置编辑器 https://casbin.org/en/editor 来进行学习。

casbin 的理论基础是这么一篇论文：[PML：一种基于Interpreter的Web服务访问控制策略语言](https://arxiv.org/abs/1903.09756) 。这篇论文是北大的三个学生一起发表的。要理解 casbin 的配置文件，就需要先看这篇论文。

论文的作者觉得现在云计算时代，权限管理系统是各种云非常重要的组成部分，但是各种权限管理模型在各个云厂商，或者各种云时代的产品又都不一样。那么是否有一种权限模型来统一描述各种权限访问方式呢？如果有的化，这种权限模型又需要独立于各种语言而存在，才能被各种语言的云产品所通用。

于是论文就创造除了这么一种语言：PML(PERM modeling language)。其中的 PERM 指的是 Policy-Effect-Request-Matcher 。 下面我们需要一一了解每一个概念。

## Request

Request 代表的是请求，它的写法是

```
request ::= r : attributes
attributes ::= {attr1, attr2, attr3, ..}
```

比如我们写一行：

r = sub, obj, act

代表一个请求有三个标准的元素，请求主体，请求对象，请求操作。其中的sub, obj, act 可以是自己定义的，只要你在一个配置文件中定义的元素标识符一致就行。

## Policy

Policy 代表策略，它表示具体的权限定义的规则是什么。

它同样是形如 p = sub, obj, act 的表示方法，比如我们定义了 policy 的规则如此，那么我们在 policy.csv 中每一行定义的 policy_rule 就必须和这个属性一一对应。


## Policy_Rule

在 policy.csv 文件中定义的策略就是 policy_rule。它和 Policy 是一一对应的。

比如 policy 为
```
p = sub, obj, act
```
我设置的一条 policy_rule 为
```
p, bob, data2, write
```

表示bob（p.sub = bob）可以对data2 (p.obj = data2)进行 write (p.act = write) 操作这个规则。

policy 默认的最后一个属性为决策结果，字段名eft，默认值为allow，即通过情况下，p.eft就设置为allow。

## Matcher

有请求，有规则，那么请求是否匹配某个规则，则是matcher进行判断的。

```
matcher ::=< boolean expr > (variables, constants, stub functions)
variables ::= {r.attr1, r.attr2, .., p.attr1, p.attr2, ..}
constants ::= {const1, const2, const3, ..}
```

比如下面这个matcher : 

```
m = r.sub == p.sub && r.obj == p.obj && r.act == p.act 
```

表示当（r.sub == p.sub && r.obj == p.obj && r.act == p.act ）的时候，返回true，否则返回false。

## Effect

Effect 用来判断如果一个请求满足了规则，是否需要同意请求。它的规则比较复杂一些。

```
effect ::=< boolean expr > (effect term1, effect term2, ..)
effect term ::= quantifier, condition
quantif ier ::= some|any|max|min
condition ::=< expr > (variables, constants, stub functions)
variables ::= {r.attr1, r.attr2, .., p.attr1, p.attr2, ..}
constants ::= {const1, const2, const3, ..}
```
这里的 quantifier一般是some（论文中支持max和min），some表示括号中的表达式个数大于等于1就行。max/min表示括号中表达式的结果取最大/小的。（这里我不是很理解，不过好像casbin也没有实现min和max）

下面这个例子：

```
e = some(where (p.eft == allow))
```

这句话的意思就是将 request 和所有 policy 比对完之后，所有 policy 的策略结果（p.eft）为allow的个数 >=1，整个请求的策略就是为 true。

## 自定义函数

自定义函数是在 matcher 中使用的。我们可以自己定义一个函数，然后注册进enforcer，在matcher中我们就可以使用了。

比如
```

func KeyMatch(key1 string, key2 string) bool {
    i := strings.Index(key2, "*")
    if i == -1 {
        return key1 == key2
    }

    if len(key1) > i {
        return key1[:i] == key2[:i]
    }
    return key1 == key2[:i]
}

func KeyMatchFunc(args ...interface{}) (interface{}, error) {
    name1 := args[0].(string)
    name2 := args[1].(string)

    return (bool)(KeyMatch(name1, name2)), nil
}

e.AddFunction("my_func", KeyMatchFunc)


// 配置文件中就可以这样写了
[matchers]
m = r.sub == p.sub && my_func(r.obj, p.obj) && r.act == p.act
```

casbin中有一些自定义的函数：

![function](http://tuchuang.funaio.cn/web_doc/function.png)

## 关系

上面几个概念关系如下：
![metadata](http://tuchuang.funaio.cn/WX20200108-153541@2x.png)

大概解释一下：

1 我们先定义属性，通用的一些属性如 subject, object, action。
2 定义的属性可以作为 Request 的属性，也可以作为 Policy的属性。
3 Policy_Rule 是 Policy 的具体规则。
4 使用定义的 Matcher 将 Request 和 Policy 进行匹配，这个匹配的过程可能使用到自定义函数。
5 所有的 Policy 匹配完成的结果，通过 Effect 规则得出最终是否可以访问的结果。

## 例子：ACL

理解上面的知识，我们应该能理解这个ACL的例子：

![](http://tuchuang.funaio.cn/acl-su.png)

这个例子中定义了两个 Policy_Rule: （alice 对 data1 有 read 权限） 和 （bob 对 data2 有 write 权限）

当request （alice, data1, read）进来的时候，它匹配了其中一条规则，所以some 之后的最终结果为true。

## 例子：RESTFUL

RESTFUL接口使用URL和HTTP请求方法表示资源的增删改查，那么我们可以使用自定义函数来判断是否可以进行某个请求

![](http://tuchuang.funaio.cn/web_doc/restful.png)

# 更多

这个论文还有一些其他的定义：

## Has_Role

其实这个就是一个自定义函数的概念，只是它的参数是请求的主体和角色。这里引入了一个角色的概念。这个也是RBAC 权限模型所定义的。Has_Role 本质就是定义了一个 g 函数，这个 g 用于判断哪个用户是否属于哪个角色。这个 g 的函数也可以用配置写规则：

```
g = _, _
```
然后在 Policy 写规则：

```
g, alice, data2_admin
```
表示 alice 属于角色 data2_admin。
matcher 就可以写成这样：

```
m = g(r.sub, p.sub) && r.obj == p.obj && r.act == p.act
```

## 例子：RBAC

我们来看下下面这个RBAC的规则：

![rbac](http://tuchuang.funaio.cn/rbac.png)

我们可以看这里的 Policy 中，其实用户和角色是分不出来的，（比如我们单看policy里面的p，是不了解data2_admin是用户，还是角色的）。但是我们有一个 g （has_role）的规则，说明了alice 是有 data2_admin的角色的。

那么最终判断请求， alice, data2, read， 由于alice 有data2_admin的角色，它满足了(p, data2_admin, data2, read) 这条规则，所以最终判定结果为 true。

其实有了这个has_role，我们也可以把一个用户属于另一个用户的关系做出来。这个也就是 RBAC1 的。

## Has Tenant Role

g 函数同时也可以有三个参数，两个参数的时候表示“谁 是 什么角色”，三个参数的时候表示“谁 在 什么域 是 什么角色”。

这个还是直接看例子：

![domain](http://tuchuang.funaio.cn/web_doc/domain.png)

在这个例子里面，有个域的概念，它就相当于可以表示“某个用户在某个域（租户）中是什么角色”。

这个是实现了一种基于RBAC的分权分域用户权限系统。


# 总结

Casbin 支持的权限模型有：

* ACL (Access Control List, 访问控制列表)
* 具有 超级用户 的 ACL
* 没有用户的 ACL: 对于没有身份验证或用户登录的系统尤其有用。
* 没有资源的 ACL: 某些场景可能只针对资源的类型, 而不是单个资源, 诸如 write-article, read-log等权限。 它不控制对特定文章或日志的访问。
* RBAC (基于角色的访问控制)
* 支持资源角色的RBAC: 用户和资源可以同时具有角色 (或组)。
* 支持域/租户的RBAC: 用户可以为不同的域/租户设置不同的角色集。
* ABAC (基于属性的访问控制): 支持利用resource.Owner这种语法糖获取元素的属性。
* RESTful: 支持路径, 如 /res/*, /res/: id 和 HTTP 方法, 如 GET, POST, PUT, DELETE。
* 拒绝优先: 支持允许和拒绝授权, 拒绝优先于允许。
* 优先级: 策略规则按照先后次序确定优先级，类似于防火墙规则。


我们可以通过这个页面上的链接看每个权限模型的配置：https://casbin.org/docs/zh-CN/supported-models
