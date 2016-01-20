# 内存分配（未发布）

new一个对象的时候，入口函数是malloc.go中的newobject函数

```
func newobject(typ *_type) unsafe.Pointer {
	flags := uint32(0)
	if typ.kind&kindNoPointers != 0 {
		flags |= flagNoScan
	}
	return mallocgc(uintptr(typ.size), typ, flags)
}
```

这个函数先计算出传入参数的大小，然后调用mallocgc函数，这个函数三个参数，第一个参数是对象类型大小，第二个参数是对象类型，第三个参数是malloc的标志位，这个标志位有两位，一个标志位代表GC不需要扫描这个对象，另一个标志位说明这个对象并不是空内存

```
const (
	// flags to malloc
	_FlagNoScan = 1 << 0 // GC doesn't have to scan object
	_FlagNoZero = 1 << 1 // don't zero memory
)
```

mallocgc函数定义如下：

```
func mallocgc(size uintptr, typ *_type, flags uint32) unsafe.Pointer
```

它返回的是指向这个结构的指针。
进入看里面的方法

先是会进行下面的操作

```
// 基本的条件符合判断 ...

// 获取当前goroutine的m结构
mp := acquirem()
// 如果当前的m正在执行分配任务，则抛出错误
if mp.mallocing != 0 {
	throw("malloc deadlock")
}
if mp.gsignal == getg() {
	throw("malloc during signal")
}
// 锁住当前的m进行分配
mp.mallocing = 1

shouldhelpgc := false
dataSize := size
// 获取当前goroutine的m的mcache
c := gomcache()
var s *mspan
var x unsafe.Pointer
```
其中的m，p，g的信息需要对下面这个图有印象

![](http://i8.tietuku.com/3412b9f11e4a9ed3.png)

然后根据size判断是否是大对象，小对象，微小对象

如果是微小对象：

```
// 是微小对象

// 进行微小对象的校准操作
// ...

// 如果是微小对象，并且申请的对象微小对象能cover住
if off+size <= maxTinySize && c.tiny != nil {
    // 直接在tiny的块中进行分配就行了
	x = add(c.tiny, off)
    ...
	return x
}

// 从mcache中获取对应的span链表
s = c.alloc[tinySizeClass]
v := s.freelist
// 如果这个span链表没有微小对象的空闲span了，从MCache中获取tinySize的链表补充上这个tiny链表
if v.ptr() == nil {
	systemstack(func() {
		mCache_Refill(c, tinySizeClass)
	})
}
s.freelist = v.ptr().next
s.ref++

// 预读取指令能加快速度
prefetchnta(uintptr(v.ptr().next))
// 初始化微小结构
x = unsafe.Pointer(v)
(*[2]uint64)(x)[0] = 0
(*[2]uint64)(x)[1] = 0

// 对比新旧两个tiny块剩余空间
if size < c.tinyoffset {
    // 如果旧块的剩余空间比新块少，则使用新块替代mcache中的tiny块
	c.tiny = x
	c.tinyoffset = size
}
```

如果是小对象

```
// 是小对象
var sizeclass int8
// 计算最接近的size
if size <= 1024-8 {
	sizeclass = size_to_class8[(size+7)>>3]
} else {
	sizeclass = size_to_class128[(size-1024+127)>>7]
}
size = uintptr(class_to_size[sizeclass])

// 获取mcache中预先分配的spans链表
s = c.alloc[sizeclass]
v := s.freelist
if v.ptr() == nil {
    // 如果没有链表了，则从mcache中划出对应的spans链表
	systemstack(func() {
		mCache_Refill(c, int32(sizeclass))
	})
}
// 有链表则直接使用
s.freelist = v.ptr().next
s.ref++
```

如果是大对象，则直接从heap上拿内存
```
// 如果是大对象，直接去heap中获取数据
systemstack(func() {
	s = largeAlloc(size, uint32(flags))
})
x = unsafe.Pointer(uintptr(s.start << pageShift))
size = uintptr(s.elemsize)
```

总结一下
* 如果要申请的对象是tiny大小，看mcache中的tiny block是否足够，如果足够，直接分配。如果不足够，使用mcache中的tiny class对应的span分配
* 如果要申请的对象是小对象大小，则使用mcache中的对应span链表分配
* 如果对应span链表已经没有空span了，先补充上mcache的对应链表，再分配（mCache_Refill）
* 如果要申请的对象是大对象，直接去heap中获取（largeAlloc）

再仔细看代码，不管是tiny大小的对象还是小对象，他们去mcache中获取对象都是使用mCache_Refill方法为这个对象对应的链表申请内存。那么我们可以追到里面去看看。

```
func mCache_Refill(c *mcache, sizeclass int32) *mspan {
    // 获取当时的goroutine
	_g_ := getg()

    // 锁上m
	_g_.m.locks++
	// 获取对应sizeclass的span链表，如果对应的链表还有剩余空间，抛出错误
	s := c.alloc[sizeclass]
	if s.freelist.ptr() != nil {
		throw("refill on a nonempty span")
	}

    // 从mCentral中获取span链表，并赋值
    s = mCentral_CacheSpan(&mheap_.central[sizeclass].mcentral)

	c.alloc[sizeclass] = s

    // 打开锁
    _g_.m.locks--
	return s
}
```

这里实际是使用mCentral_CacheSpan来获取内存，这里需要看下mCentral的结构

```
type mcentral struct {
	lock      mutex
	sizeclass int32
	nonempty  mspan // list of spans with a free object
	empty     mspan // list of spans with no free objects (or cached in an mcache)
}
```
mcentral有两个链表，一个链表是有空闲的span可以使用，叫noempty，另一个链表是没有空间的span可以使用，叫empty。这个时候我们需要获取span，一定是从nonempty链表中取出span来使用。
这两个链表的机制是这样的，我new一个对象的时候，从nonempty中获取这个空间，放到empty链表中去，当我free一个对象的时候，从empty链表中还原到nonempty链表中去。
所以在下面获取空span的时候，会先去empty中查找有没有，如果没有，再去nonempty中查找有没有，nonempty中有可能有为资源回收但是却是没有使用的span。

```
func mCentral_CacheSpan(c *mcentral) *mspan {

	sg := mheap_.sweepgen
retry:
	var s *mspan
    // 遍历有空间span的链表
	for s = c.nonempty.next; s != &c.nonempty; s = s.next {
        // 如果这个span是需要回收的，那么先回收这个span，转移到empty链表中，再把这个span返回
		if s.sweepgen == sg-2 && cas(&s.sweepgen, sg-2, sg-1) {
			mSpanList_Remove(s)
			mSpanList_InsertBack(&c.empty, s)
			unlock(&c.lock)
            // 垃圾清理
            mSpan_Sweep(s, true)
			goto havespan
		}

        // 如果nonempty中有不需要swapping的空间，这个就可以直接使用了
		mSpanList_Remove(s)
		mSpanList_InsertBack(&c.empty, s)
		unlock(&c.lock)
		goto havespan
	}

    // 遍历没有空间的span链表，为什么没有空间的span链表也需要遍历呢？
	for s = c.empty.next; s != &c.empty; s = s.next {
        // 如果这个span是需要回收的，回收之
		if s.sweepgen == sg-2 && cas(&s.sweepgen, sg-2, sg-1) {
			mSpanList_Remove(s)
			mSpanList_InsertBack(&c.empty, s)
			unlock(&c.lock)
			mSpan_Sweep(s, true)
			if s.freelist.ptr() != nil {
				goto havespan
			}
			lock(&c.lock)
			goto retry
		}

		break
	}
	unlock(&c.lock)

    // 到这里就说明central中都没有可以使用的span了，那么，就增长mCentral
	s = mCentral_Grow(c)
	mSpanList_InsertBack(&c.empty, s)

havespan:   
    // 找到空span的情况
	cap := int32((s.npages << _PageShift) / s.elemsize)
	n := cap - int32(s.ref)
	if n == 0 {
		throw("empty span")
	}
	if s.freelist.ptr() == nil {
		throw("freelist empty")
	}
	s.incache = true
	return s
}
```

mCentral判断一个span是否过期是使用
```
s.sweepgen == sg-2 && cas(&s.sweepgen, sg-2, sg-1)
```
这个sweepgen是span和mheap中各有一个，根据这两个结构的sweepgen就能判断这个span是否需要进入gc回收了。
```
// sweep generation:
// if sweepgen == h->sweepgen - 2, the span needs sweeping
// if sweepgen == h->sweepgen - 1, the span is currently being swept
// if sweepgen == h->sweepgen, the span is swept and ready to use
// h->sweepgen is incremented by 2 after every GC
```

如果mCentral没有可用的span了，就需要调用mCentral_Grow(c)

```
func mCentral_Grow(c *mcentral) *mspan {
    ...
    // 从heap上进行分配
	s := mHeap_Alloc(&mheap_, npages, c.sizeclass, false, true)
	...
    // 设置span的bitmap
    heapBitsForSpan(s.base()).initSpan(s.layout())
	return s
}
```

再进入到mHeap_Alloc
```
func mHeap_Alloc(h *mheap, npage uintptr, sizeclass int32, large bool, needzero bool) *mspan {
    ...
	systemstack(func() {
		s = mHeap_Alloc_m(h, npage, sizeclass, large)
	})
    ...
}
```

再进入mHeap_Alloc_m

```
func mHeap_Alloc_m(h *mheap, npage uintptr, sizeclass int32, large bool) *mspan {
	...
	s := mHeap_AllocSpanLocked(h, npage)
	...

	return s
}
```


```
func mHeap_AllocSpanLocked(h *mheap, npage uintptr) *mspan {
    ...

	// 获取Heap中最合适的内存大小
	s = mHeap_AllocLarge(h, npage)
    // 如果mHeap满了
	if s == nil {
        // 增长mHeap大小
		if !mHeap_Grow(h, npage) {
			return nil
		}
		s = mHeap_AllocLarge(h, npage)
		if s == nil {
			return nil
		}
	}

HaveSpan:
	// mHeap中有了数据
}
```

看看如何增长mHeap大小
```
func mHeap_Grow(h *mheap, npage uintptr) bool {
    ...
    // 调用操作系统分配内存
	v := mHeap_SysAlloc(h, ask)
    ...
}
```
下面就看到mheap的扩容了，这个之前需要了解heap的结构

```
type mheap struct {
	lock      mutex
	free      [_MaxMHeapList]mspan // free lists of given length
	freelarge mspan                // free lists length >= _MaxMHeapList
	busy      [_MaxMHeapList]mspan // busy lists of large objects of given length
	busylarge mspan                // busy lists of large objects length >= _MaxMHeapList
	allspans  **mspan              // all spans out there
	gcspans   **mspan              // copy of allspans referenced by gc marker or sweeper
	nspan     uint32
	sweepgen  uint32 // sweep generation, see comment in mspan
	sweepdone uint32 // all spans are swept
	// span lookup
	spans        **mspan
	spans_mapped uintptr

	// Proportional sweep
	spanBytesAlloc    uint64  // bytes of spans allocated this cycle; updated atomically
	pagesSwept        uint64  // pages swept this cycle; updated atomically
	sweepPagesPerByte float64 // proportional sweep ratio; written with lock, read without

	// Malloc stats.
	largefree  uint64                  // bytes freed for large objects (>maxsmallsize)
	nlargefree uint64                  // number of frees for large objects (>maxsmallsize)
	nsmallfree [_NumSizeClasses]uint64 // number of frees for small objects (<=maxsmallsize)

	// range of addresses we might see in the heap
	bitmap         uintptr
	bitmap_mapped  uintptr
	arena_start    uintptr
	arena_used     uintptr // always mHeap_Map{Bits,Spans} before updating
	arena_end      uintptr
	arena_reserved bool

	// central free lists for small size classes.
	// the padding makes sure that the MCentrals are
	// spaced CacheLineSize bytes apart, so that each MCentral.lock
	// gets its own cache line.
	central [_NumSizeClasses]struct {
		mcentral mcentral
		pad      [_CacheLineSize]byte
	}

	spanalloc             fixalloc // allocator for span*
	cachealloc            fixalloc // allocator for mcache*
	specialfinalizeralloc fixalloc // allocator for specialfinalizer*
	specialprofilealloc   fixalloc // allocator for specialprofile*
	speciallock           mutex    // lock for special record allocators.
}
```
它最重要的结构有三个，spans，指向所有span指针，bitmap是spans的标志位，arena是堆生成区。
```
+---------------------+---------------+-----------------------------+
| spans 512MB .......| bitmap 32GB | arena 512GB ..................|
+---------------------+---------------+-----------------------------+ +
```


```
func mHeap_SysAlloc(h *mheap, n uintptr) unsafe.Pointer {
    // 如果超出了arean预留的区块限制了
	if n > uintptr(h.arena_end)-uintptr(h.arena_used) {
        // 使用一些系统保留的空间
        ...
	}

    // 申请的大小在arean范围内
	if n <= uintptr(h.arena_end)-uintptr(h.arena_used) {
		// 使用系统的sysMap申请内存
		sysMap((unsafe.Pointer)(p), n, h.arena_reserved, &memstats.heap_sys)
		mHeap_MapBits(h, p+n)
		mHeap_MapSpans(h, p+n)
		...
	}
    ...
}
```


```
func sysMap(v unsafe.Pointer, n uintptr, reserved bool, sysStat *uint64) {
	...
    // 最终调用mmap
    p := mmap(v, n, _PROT_READ|_PROT_WRITE, _MAP_ANON|_MAP_FIXED|_MAP_PRIVATE, -1, 0)
	...
}

```

## 参考文章
[Implemention of golang](https://tracymacding.gitbooks.io/implementation-of-golang/content/index.html)

[Go 1.5 源码剖析.pdf](https://github.com/qyuhen/book)
