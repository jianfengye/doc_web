# 似然函数[未发布]

# 概率

从概率说起，概率需要有几个概念要明确：

* 条件概率。 事件A在另外一个事件B已经发生条件下的发生概率，表示为<img src="http://chart.googleapis.com/chart?cht=tx&chl=\[P(A|B)\]" style="border:none;">
* 联合概率。两个事件共同发生的概率。A与B的联合概率标示为<img src="http://chart.googleapis.com/chart?cht=tx&chl=\[P(A\cap B)\]" style="border:none;">
* 边缘概率。某个事件发生的概率。在联合概率中，把最终结果中，不需要的那些时间合并成其事件的全概率而消失。这个也称为边缘化。A的边缘概率表示为P(A)，B的边缘概率表示为P(B)

贝叶斯定理

<img src="http://chart.googleapis.com/chart?cht=tx&chl=\[P(A|B)=\frac{P(B|A)P(A)}{P(B)}\]" style="border:none;">

其中P(A)时A的边缘概率，P(A|B)是B发生后A的条件概率，P(B|A)是A发生后B的条件概率，P(B)时B的边缘概率。

# 似然函数

似然函数是统计模型中关于参数的函数，表示模型参数中的似然性。似然这个概念和概率这个概念有不同，概率指的是已知一些参数的情况下，预测接下来的观测所得到的结果。而似然则是用于已知某些观测所得到的结果时，对参数进行估计。例如，对于“一枚正反对称的硬币上抛十次”这种事件，我们可以问硬币落地时十次都是正面向上的“概率”是多少；而对于“一枚硬币上抛十次，落地都是正面向上”这种事件，我们则可以问，这枚硬币正反面对称的“似然”程度是多少。

<img src="http://chart.googleapis.com/chart?cht=tx&chl=\[L(\theta|X )\]" style="border:none;">表示<img src="http://chart.googleapis.com/chart?cht=tx&chl=\[\theta\]" style="border:none;">这个参数对于X的似然率的函数。

我们可以得出似然函数和概率函数的关系。

<img src="http://chart.googleapis.com/chart?cht=tx&chl=\[L(\theta|X) = \alpha P(X|\theta=b)\]" style="border:none;">

基本我们可以得出，如果给出一堆已知的结果X，似然率是参数和标准参数的相像程度，似然率越高，则这个时候的参数和标准参数就越符合。

所以“最大似然估计”表示的就是求让似然函数最大的参数值。当然似然函数的最大值不一定唯一，也不一定存在。但是这个求最大似然值的过程就叫做最大似然估计。

# 参考

[似然函数](https://zh.wikipedia.org/wiki/%E4%BC%BC%E7%84%B6%E5%87%BD%E6%95%B0)
[最大似然估计](https://zh.wikipedia.org/wiki/%E6%9C%80%E5%A4%A7%E4%BC%BC%E7%84%B6%E4%BC%B0%E8%AE%A1)
[贝叶斯定理](https://zh.wikipedia.org/wiki/%E8%B4%9D%E5%8F%B6%E6%96%AF%E5%AE%9A%E7%90%86)
[条件概率](https://zh.wikipedia.org/wiki/%E6%9D%A1%E4%BB%B6%E6%A6%82%E7%8E%87)
[概率论与数理统计](https://books.google.com/books?id=XB0EXQht6bUC&pg=PA11&lpg=PA11&dq=%E6%A6%82%E7%8E%87%E5%AD%A6+P&source=bl&ots=zp_z669fRH&sig=2hBaVbxUXlwH7cbtqLXuZGnBrmE&hl=en&sa=X&ved=0ahUKEwiYi-el4YLLAhVM0WMKHWMxA0gQ6AEIYTAJ#v=onepage&q=%E6%A6%82%E7%8E%87%E5%AD%A6%20P&f=false)
