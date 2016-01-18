# Linux中的默认进程

用了linux那么久了，不一定对 linux的默认进程都能说的上来，

```
[yejianfeng@test ~]$ ps  xao pid,ppid,pgid,sid,comm
  PID  PPID  PGID   SID COMMAND
    1     0     1     1 init
    2     0     0     0 kthreadd
    3     2     0     0 migration/0
    4     2     0     0 ksoftirqd/0
    5     2     0     0 stopper/0
    6     2     0     0 watchdog/0
    7     2     0     0 migration/1
    8     2     0     0 stopper/1
    9     2     0     0 ksoftirqd/1
   10     2     0     0 watchdog/1
   11     2     0     0 migration/2
   12     2     0     0 stopper/2
   13     2     0     0 ksoftirqd/2
   14     2     0     0 watchdog/2
   15     2     0     0 migration/3
   16     2     0     0 stopper/3
   17     2     0     0 ksoftirqd/3
   18     2     0     0 watchdog/3
   19     2     0     0 migration/4
   20     2     0     0 stopper/4
   21     2     0     0 ksoftirqd/4
   22     2     0     0 watchdog/4
   23     2     0     0 migration/5
   24     2     0     0 stopper/5
   25     2     0     0 ksoftirqd/5
   26     2     0     0 watchdog/5
   27     2     0     0 migration/6
   28     2     0     0 stopper/6
   29     2     0     0 ksoftirqd/6
   30     2     0     0 watchdog/6
   31     2     0     0 migration/7
   32     2     0     0 stopper/7
   33     2     0     0 ksoftirqd/7
   34     2     0     0 watchdog/7
   35     2     0     0 events/0
   36     2     0     0 events/1
   37     2     0     0 events/2
   38     2     0     0 events/3
   39     2     0     0 events/4
   40     2     0     0 events/5
   41     2     0     0 events/6
   42     2     0     0 events/7
   43     2     0     0 cgroup
   44     2     0     0 khelper
   45     2     0     0 netns
   46     2     0     0 async/mgr
   47     2     0     0 pm
   48     2     0     0 xenwatch
   49     2     0     0 xenbus
   50     2     0     0 sync_supers
   51     2     0     0 bdi-default
   52     2     0     0 kintegrityd/0
   53     2     0     0 kintegrityd/1
   54     2     0     0 kintegrityd/2
   55     2     0     0 kintegrityd/3
   56     2     0     0 kintegrityd/4
   57     2     0     0 kintegrityd/5
   58     2     0     0 kintegrityd/6
   59     2     0     0 kintegrityd/7
   60     2     0     0 kblockd/0
   61     2     0     0 kblockd/1
   62     2     0     0 kblockd/2
   63     2     0     0 kblockd/3
   64     2     0     0 kblockd/4
   65     2     0     0 kblockd/5
   66     2     0     0 kblockd/6
   67     2     0     0 kblockd/7
   68     2     0     0 kacpid
   69     2     0     0 kacpi_notify
   70     2     0     0 kacpi_hotplug
   71     2     0     0 ata_aux
   72     2     0     0 ata_sff/0
   73     2     0     0 ata_sff/1
   74     2     0     0 ata_sff/2
   75     2     0     0 ata_sff/3
   76     2     0     0 ata_sff/4
   77     2     0     0 ata_sff/5
   78     2     0     0 ata_sff/6
   79     2     0     0 ata_sff/7
   80     2     0     0 ksuspend_usbd
   81     2     0     0 khubd
   82     2     0     0 kseriod
   83     2     0     0 md/0
   84     2     0     0 md/1
   85     2     0     0 md/2
   86     2     0     0 md/3
   87     2     0     0 md/4
   88     2     0     0 md/5
   89     2     0     0 md/6
   90     2     0     0 md/7
   91     2     0     0 md_misc/0
   92     2     0     0 md_misc/1
   93     2     0     0 md_misc/2
   94     2     0     0 md_misc/3
   95     2     0     0 md_misc/4
   96     2     0     0 md_misc/5
   97     2     0     0 md_misc/6
   98     2     0     0 md_misc/7
   99     2     0     0 linkwatch
  100     2     0     0 khungtaskd
  101     2     0     0 kswapd0
  102     2     0     0 ksmd
  103     2     0     0 khugepaged
  104     2     0     0 aio/0
  105     2     0     0 aio/1
  106     2     0     0 aio/2
  107     2     0     0 aio/3
  108     2     0     0 aio/4
  109     2     0     0 aio/5
  110     2     0     0 aio/6
  111     2     0     0 aio/7
  112     2     0     0 crypto/0
  113     2     0     0 crypto/1
  114     2     0     0 crypto/2
  115     2     0     0 crypto/3
  116     2     0     0 crypto/4
  117     2     0     0 crypto/5
  118     2     0     0 crypto/6
  119     2     0     0 crypto/7
  127     2     0     0 kthrotld/0
  128     2     0     0 kthrotld/1
  129     2     0     0 kthrotld/2
  130     2     0     0 kthrotld/3
  131     2     0     0 kthrotld/4
  132     2     0     0 kthrotld/5
  133     2     0     0 kthrotld/6
  134     2     0     0 kthrotld/7
  136     2     0     0 kpsmoused
  137     2     0     0 usbhid_resumer
  138     2     0     0 deferwq
  169     2     0     0 kdmremove
  170     2     0     0 kstriped
  281     2     0     0 scsi_eh_0
  282     2     0     0 scsi_eh_1
  350     2     0     0 jbd2/xvda1-8
  351     2     0     0 ext4-dio-unwrit
  385     2     0     0 flush-202:0
  442     1   442   442 udevd
  850     2     0     0 kauditd
```

## init

所有进程的父进程，unix的进程是树形结构，这个进程是进程树的根进程。

## kthreadd

这个进程是调度其他的内核线程，可以说，其他的内核线程都是从这个线程派生出来的。

## 
