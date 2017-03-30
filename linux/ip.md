# 查询运营商的ip段

所有的IP地址都是通过国际组织NIC(Network Information Center)统一分配的，目前世界上有三个这样的网络信息中心：

InterNic: 负责美国及其他地区
ENic: 负责欧洲地区
APNIC: 负责亚太地区

像我们中国的所有运营商，比如六大运营商（中国移动，中国联通，中国电信，中国网通，中国铁通和中国卫通）都是从APNIC中获取IP段的。

APNIC的IP段列表可以通过这个[地址](http://ftp.apnic.net/apnic/stats/apnic/delegated-apnic-latest)来获取到。

过滤其中的"CN|ipv4"就可以获取到分配到中国的IP地址段有哪些。

具体ip段是由哪个运营商拥有的。这个就需要使用到whois的命令，比如：

```
whois 1.0.32.0
```


```
inetnum:        1.0.32.0 - 1.0.63.255
netname:        CHINANET-GD
descr:          CHINANET Guangdong province network
descr:          Data Communication Division
descr:          China Telecom
country:        CN
admin-c:        CH93-AP
tech-c:         IC83-AP
status:         ALLOCATED PORTABLE
notify:         abuse_gdnoc@189.cn
remarks:        service provider
changed:        hm-changed@apnic.net 20110412
mnt-by:         APNIC-HM
mnt-lower:      MAINT-CHINANET-GD
mnt-irt:        IRT-CHINANET-CN
source:         APNIC
```

这段信息中的mnt-lower中的CHINANET就代表了这个网段是由中国电信网络拥有的。

其他运营商的代表名称为：

* CNCGroup | UNICOM 中国联通
* China Telecom | CHINANET 中国电信
* CMCC 中国移动
* CRTC 中国铁通
* CNNIC 中国互联网络信息中心
* CERNET 中国教育和科研计算机网

# 参考
[教你怎样获取各大网络运营商IP段](http://www.xp74.com/article/news/1078.htm)
[通过whois查询APNIC获取三大运营商公网IP段](http://380281.blog.51cto.com/370281/1588238)
[移动运营商是怎么分配ip？](https://www.zhihu.com/question/25284982)
