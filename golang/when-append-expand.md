# slice是什么时候决定要扩张？

网上说slice的文章已经很多了，大都已经把slice的内存扩张原理都说清楚了。但是是如何判断slice是否需要扩张这个点却没有说的很清楚。想当然的我会觉得这个append是否扩张的逻辑应该隐藏在runtime中的某个函数，根据append的数组的长度进行判断。但是是否是如此呢？

本着这个疑问，我做了如下的实验。

我写了两个方法，一个需要扩张，一个不需要扩张。

# 无需扩张

不需要扩张的代码如下：
```
package main

func main() {
        a := make([]int, 1, 3)
        a = append(a, 4)
        println(a)
}
```

使用 go tool objdump 来打印出编译后的main汇编码：
```
TEXT main.main(SB) /Users/yejianfeng/Documents/gopath/src/demo/append.go
  append.go:3		0x104e140		65488b0c2530000000	MOVQ GS:0x30, CX
  append.go:3		0x104e149		483b6110		CMPQ 0x10(CX), SP
  append.go:3		0x104e14d		7661			JBE 0x104e1b0
  append.go:3		0x104e14f		4883ec38		SUBQ $0x38, SP
  append.go:3		0x104e153		48896c2430		MOVQ BP, 0x30(SP)
  append.go:3		0x104e158		488d6c2430		LEAQ 0x30(SP), BP
  append.go:4		0x104e15d		48c744241800000000	MOVQ $0x0, 0x18(SP)
  append.go:4		0x104e166		0f57c0			XORPS X0, X0
  append.go:4		0x104e169		0f11442420		MOVUPS X0, 0x20(SP)
  append.go:5		0x104e16e		48c744242004000000	MOVQ $0x4, 0x20(SP)
  append.go:6		0x104e177		e86445fdff		CALL runtime.printlock(SB)
  append.go:6		0x104e17c		488d442418		LEAQ 0x18(SP), AX
  append.go:6		0x104e181		48890424		MOVQ AX, 0(SP)
  append.go:6		0x104e185		48c744240802000000	MOVQ $0x2, 0x8(SP)
  append.go:6		0x104e18e		48c744241003000000	MOVQ $0x3, 0x10(SP)
  append.go:6		0x104e197		e8f44efdff		CALL runtime.printslice(SB)
  append.go:6		0x104e19c		e8bf47fdff		CALL runtime.printnl(SB)
  append.go:6		0x104e1a1		e8ba45fdff		CALL runtime.printunlock(SB)
  append.go:7		0x104e1a6		488b6c2430		MOVQ 0x30(SP), BP
  append.go:7		0x104e1ab		4883c438		ADDQ $0x38, SP
  append.go:7		0x104e1af		c3			RET
  append.go:3		0x104e1b0		e82b89ffff		CALL runtime.morestack_noctxt(SB)
  append.go:3		0x104e1b5		eb89			JMP main.main(SB)
```
这个汇编码的逻辑在append.go第5行就只有一个MOV指令，将4直接放到指定的内存地址。

# 需要扩张

我的另一个需要扩张的代码如下：
```
package main

func main() {
        a := make([]int, 1, 1)
        a = append(a, 4)
        println(a)
}
```
生成的汇编码如下：
```
TEXT main.main(SB) /Users/yejianfeng/Documents/gopath/src/demo/append.go
  append.go:3		0x104e140		65488b0c2530000000	MOVQ GS:0x30, CX
  append.go:3		0x104e149		483b6110		CMPQ 0x10(CX), SP
  append.go:3		0x104e14d		0f86b0000000		JBE 0x104e203
  append.go:3		0x104e153		4883ec68		SUBQ $0x68, SP
  append.go:3		0x104e157		48896c2460		MOVQ BP, 0x60(SP)
  append.go:3		0x104e15c		488d6c2460		LEAQ 0x60(SP), BP
  append.go:5		0x104e161		48c744245000000000	MOVQ $0x0, 0x50(SP)
  append.go:5		0x104e16a		488d05af9d0000		LEAQ type.*+40128(SB), AX
  append.go:5		0x104e171		48890424		MOVQ AX, 0(SP)
  append.go:5		0x104e175		488d442450		LEAQ 0x50(SP), AX
  append.go:5		0x104e17a		4889442408		MOVQ AX, 0x8(SP)
  append.go:5		0x104e17f		48c744241001000000	MOVQ $0x1, 0x10(SP)
  append.go:5		0x104e188		48c744241801000000	MOVQ $0x1, 0x18(SP)
  append.go:5		0x104e191		48c744242002000000	MOVQ $0x2, 0x20(SP)
  append.go:5		0x104e19a		e8b16bfeff		CALL runtime.growslice(SB)
  append.go:5		0x104e19f		488b442428		MOVQ 0x28(SP), AX
  append.go:5		0x104e1a4		4889442458		MOVQ AX, 0x58(SP)
  append.go:5		0x104e1a9		488b4c2430		MOVQ 0x30(SP), CX
  append.go:5		0x104e1ae		48894c2448		MOVQ CX, 0x48(SP)
  append.go:5		0x104e1b3		488b542438		MOVQ 0x38(SP), DX
  append.go:5		0x104e1b8		4889542440		MOVQ DX, 0x40(SP)
  append.go:5		0x104e1bd		48c7400804000000	MOVQ $0x4, 0x8(AX)
  append.go:6		0x104e1c5		e81645fdff		CALL runtime.printlock(SB)
  append.go:6		0x104e1ca		488b442458		MOVQ 0x58(SP), AX
  append.go:6		0x104e1cf		48890424		MOVQ AX, 0(SP)
  append.go:5		0x104e1d3		488b442448		MOVQ 0x48(SP), AX
  append.go:5		0x104e1d8		48ffc0			INCQ AX
  append.go:6		0x104e1db		4889442408		MOVQ AX, 0x8(SP)
  append.go:6		0x104e1e0		488b442440		MOVQ 0x40(SP), AX
  append.go:6		0x104e1e5		4889442410		MOVQ AX, 0x10(SP)
  append.go:6		0x104e1ea		e8a14efdff		CALL runtime.printslice(SB)
  append.go:6		0x104e1ef		e86c47fdff		CALL runtime.printnl(SB)
  append.go:6		0x104e1f4		e86745fdff		CALL runtime.printunlock(SB)
  append.go:7		0x104e1f9		488b6c2460		MOVQ 0x60(SP), BP
  append.go:7		0x104e1fe		4883c468		ADDQ $0x68, SP
```
这里的第5行就和之前的那个大不一样了。有非常多的逻辑。基本进入第五行做的事情就是开始准备调用runtime.growslice的逻辑了
```
append.go:5		0x104e161		48c744245000000000	MOVQ $0x0, 0x50(SP)
append.go:5		0x104e16a		488d05af9d0000		LEAQ type.*+40128(SB), AX
append.go:5		0x104e171		48890424		MOVQ AX, 0(SP)
append.go:5		0x104e175		488d442450		LEAQ 0x50(SP), AX
append.go:5		0x104e17a		4889442408		MOVQ AX, 0x8(SP)
append.go:5		0x104e17f		48c744241001000000	MOVQ $0x1, 0x10(SP)
append.go:5		0x104e188		48c744241801000000	MOVQ $0x1, 0x18(SP)
append.go:5		0x104e191		48c744242002000000	MOVQ $0x2, 0x20(SP)
append.go:5		0x104e19a		e8b16bfeff		CALL runtime.growslice(SB)
```

这里就很明显了，所以slice的append是否进行cap扩张是在编译器进行判断的？至少我上面的两个代码，编译器编译的时候是知道这个slice是否需要进行扩张的，根据是否进行扩张就决定是否调用growslice。

# 再复杂的case

在雨痕群里问了下这个问题，有位群友给了个更为复杂点的case:

```
package main

func main() {
        a := make([]int, 1, 5)
        b := 3
        for i := 0; i < b; i++ {
                a = append(a, 4)
        }
        println(a)
}
```

这里的append是包围在for循环里面的，编译器其实就很难判断了。我们看下汇编：

```
TEXT main.main(SB) /Users/yejianfeng/Documents/gopath/src/demo/append.go
  append.go:3		0x104e140		65488b0c2530000000	MOVQ GS:0x30, CX
  append.go:3		0x104e149		488d4424f0		LEAQ -0x10(SP), AX
  append.go:3		0x104e14e		483b4110		CMPQ 0x10(CX), AX
  append.go:3		0x104e152		0f86fb000000		JBE 0x104e253
  append.go:3		0x104e158		4881ec90000000		SUBQ $0x90, SP
  append.go:3		0x104e15f		4889ac2488000000	MOVQ BP, 0x88(SP)
  append.go:3		0x104e167		488dac2488000000	LEAQ 0x88(SP), BP
  append.go:4		0x104e16f		48c744245800000000	MOVQ $0x0, 0x58(SP)
  append.go:4		0x104e178		0f57c0			XORPS X0, X0
  append.go:4		0x104e17b		0f11442460		MOVUPS X0, 0x60(SP)
  append.go:4		0x104e180		0f11442470		MOVUPS X0, 0x70(SP)
  append.go:4		0x104e185		31c0			XORL AX, AX
  append.go:4		0x104e187		488d4c2458		LEAQ 0x58(SP), CX
  append.go:4		0x104e18c		ba01000000		MOVL $0x1, DX
  append.go:4		0x104e191		bb05000000		MOVL $0x5, BX
  append.go:6		0x104e196		eb0e			JMP 0x104e1a6
  append.go:7		0x104e198		48c704d104000000	MOVQ $0x4, 0(CX)(DX*8)
  append.go:6		0x104e1a0		48ffc0			INCQ AX
  append.go:9		0x104e1a3		4889f2			MOVQ SI, DX
  append.go:9		0x104e1a6		4889542448		MOVQ DX, 0x48(SP)
  append.go:6		0x104e1ab		4883f803		CMPQ $0x3, AX
  append.go:6		0x104e1af		7d51			JGE 0x104e202
  append.go:7		0x104e1b1		488d7201		LEAQ 0x1(DX), SI
  append.go:7		0x104e1b5		4839de			CMPQ BX, SI
  append.go:7		0x104e1b8		7ede			JLE 0x104e198
  append.go:6		0x104e1ba		4889442440		MOVQ AX, 0x40(SP)
  append.go:7		0x104e1bf		488d05ba9d0000		LEAQ type.*+40128(SB), AX
  append.go:7		0x104e1c6		48890424		MOVQ AX, 0(SP)
  append.go:7		0x104e1ca		48894c2408		MOVQ CX, 0x8(SP)
  append.go:7		0x104e1cf		4889542410		MOVQ DX, 0x10(SP)
  append.go:7		0x104e1d4		48895c2418		MOVQ BX, 0x18(SP)
  append.go:7		0x104e1d9		4889742420		MOVQ SI, 0x20(SP)
  append.go:7		0x104e1de		e86d6bfeff		CALL runtime.growslice(SB)
  append.go:7		0x104e1e3		488b4c2428		MOVQ 0x28(SP), CX
  append.go:7		0x104e1e8		488b442430		MOVQ 0x30(SP), AX
  append.go:7		0x104e1ed		488b5c2438		MOVQ 0x38(SP), BX
  append.go:7		0x104e1f2		488d7001		LEAQ 0x1(AX), SI
  append.go:6		0x104e1f6		488b442440		MOVQ 0x40(SP), AX
  append.go:7		0x104e1fb		488b542448		MOVQ 0x48(SP), DX
  append.go:7		0x104e200		eb96			JMP 0x104e198
  append.go:9		0x104e202		48898c2480000000	MOVQ CX, 0x80(SP)
  append.go:9		0x104e20a		48895c2450		MOVQ BX, 0x50(SP)
  append.go:9		0x104e20f		e8cc44fdff		CALL runtime.printlock(SB)
```

重点看这一行：
```
  append.go:7		0x104e1b5		4839de			CMPQ BX, SI
  append.go:7		0x104e1b8		7ede			JLE 0x104e198
```
BX里面存的是a现在的cap值，（可以从`MOVL $0x5, BX`看出来）。而SI里面存储的是老的slice的长度（DX）加1之后的值，就是新的slice需要的len值。所以上面两句的意思就是比较下新的len和cap的大小，如果len小于cap的话，就跳到0x104e198，就是直接执行MOVE操作，否则的话，就开始准备growslice。


## 总结

上面的分析说明，slice是否需要扩张的逻辑是编译器做的，并且编译器如果能直接判断是否这个slice需要扩张，就直接将是否需要扩张的结果作为编译结果。否则的话，就将这个if else的逻辑写在编译结果里面，在runtime时候跳转判断。

到这里我有点理解编译器和运行时的边界。其实本质上，两个步骤都是为了代码更快得出结果，编译器优化的越多，运行过程执行的速度就越快，当然编译器同时也需要兼顾生成的可执行文件的大小问题等。对一个语言，编译器优化，是个很重要的工作。
