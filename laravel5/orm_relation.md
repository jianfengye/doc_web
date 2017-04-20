# laravel的ORM的模型关联

laravel中的模型关联是使用laravel好与否的关键。一旦模型关联建立的好，那么代码往往就非常优雅。官方文档：http://d.laravel-china.org/docs/5.4/eloquent-relationships 描述的非常清楚了。这篇就想梳理下具体的laravel的模型关联。

# 关键方法

HasOne : 一个用户有一个手机号
HasMany : 一个用户有多篇文章(通过用户查文章)
HasManyThrough : 一个地区有多少文章（一个地区有多个用户，一个用户有多个文章）

BelongsTo : 一篇文章属于一个用户（通过文章查用户）
BelongsToMany: 一个用户属于多个角色

MorphTo: 一个评论属于文章还是视频
MorphToMany: 一个评论属于多个文章
MorphMany: 一个文章的评论（评论可以针对文章，也可以针对视频）
MorphOne: 一个视频的评论（评论可以针对文章，也可以针对视频，且视频的文章只有一个）
MorphPivot:

Pivot: 中间表
