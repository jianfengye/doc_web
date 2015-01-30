mysql的collation

mysql的collation大致的意思就是字符序。首先字符本来是不分大小的，那么对字符的>, = , < 操作就需要有个字符序的规则。collation做的就是这个事情，你可以对表进行字符序的设置，也可以单独对某个字段进行字符序的设置。一个字符类型，它的字符序有多个，比如：

下面是UTF8对应的字符序。

    utf8_general_ci utf8    33  Yes Yes 1
    utf8_bin    utf8    83      Yes 1
    utf8_unicode_ci utf8    192     Yes 8
    utf8_icelandic_ci   utf8    193     Yes 8
    utf8_latvian_ci utf8    194     Yes 8
    utf8_romanian_ci    utf8    195     Yes 8
    utf8_slovenian_ci   utf8    196     Yes 8
    utf8_polish_ci  utf8    197     Yes 8
    utf8_estonian_ci    utf8    198     Yes 8
    utf8_spanish_ci utf8    199     Yes 8
    utf8_swedish_ci utf8    200     Yes 8
    utf8_turkish_ci utf8    201     Yes 8
    utf8_czech_ci   utf8    202     Yes 8
    utf8_danish_ci  utf8    203     Yes 8
    utf8_lithuanian_ci  utf8    204     Yes 8
    utf8_slovak_ci  utf8    205     Yes 8
    utf8_spanish2_ci    utf8    206     Yes 8
    utf8_roman_ci   utf8    207     Yes 8
    utf8_persian_ci utf8    208     Yes 8
    utf8_esperanto_ci   utf8    209     Yes 8
    utf8_hungarian_ci   utf8    210     Yes 8
    utf8_sinhala_ci utf8    211     Yes 8
    utf8_german2_ci utf8    212     Yes 8
    utf8_croatian_ci    utf8    213     Yes 8
    utf8_unicode_520_ci utf8    214     Yes 8
    utf8_vietnamese_ci  utf8    215     Yes 8
    utf8_general_mysql500_ci    utf8    223     Yes 1

mysql的字符序遵从命名惯例。以_ci(表示大小写不敏感)，以_cs(表示大小写敏感)，以_bin(表示用编码值进行比较)。比如：

    CREATE TABLE `issue_message` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `content` varchar(255) NOT NULL,
      PRIMARY KEY (`id`),
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

这个表下面的两个sql会出现同样的结果

    select * from issue_message where content = 'Yes'
    select * from issue_message where content = 'yes'

如果改成下面的定义：

    CREATE TABLE `issue_message` (
      `id` int(11) NOT NULL AUTO_INCREMENT,
      `content` varchar(255) NOT NULL COLLATE utf8_bin,
      PRIMARY KEY (`id`),
    ) ENGINE=MyISAM DEFAULT CHARSET=utf8;

那么两个sql结果就会不一样了

所以，如果对字符大小敏感的话，最好将数据库中默认的utf8_general_ci设置为utf8_bin。
