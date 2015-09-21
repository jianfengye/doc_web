# lucene 的评分机制

elasticsearch是基于lucene的，所以他的评分机制也是基于lucene的。评分就是我们搜索的短语和索引中每篇文档的相关度打分。
如果没有干预评分算法的时候，每次查询，lucene会基于一个评分算法来计算所有文档和搜索语句的相关评分。
使用lucene的评分机制基本能够把最符合用户需要的搜索放在最前面。
当然有的时候，我们可能想要自定义评分算法，这个就和lucene的评分算法没有什么关系了。当然，我们大多数应该还是会根据自己的需求，来调整lucene本身的算法。


## lucene的评分公式

\begin{equation}
score(q,d)   =   coord(q,d) ·  queryNorm(q) ·  \sum_{t\space in\space q}(tf(t\space in\space d) ·  idf(t)^2·  t.getBoost() ·  norm(t,d) )
\end{equation}
