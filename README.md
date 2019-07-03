大转盘 / Turntable
===============
###项目简介
* 项目总共有2个版本，一个是PHP，另一个是swoole
* PHP对应的目录在/application/normal
* swoole对应的目录在/application/swoole
* 两个版本相互独立，互不干扰。有需要的可以分别拷源码到自己的项目中使用。


###项目所用到的技术
* Thinkphp 5.0
* mysql
* PHP
* swoole

###基本配置
    tp框架，nginx指向/public/
    sql文件 根目录/turntable.sql

###实现的功能
1. 商家可配置若干个大转盘活动
2. 每个大转盘相互独立
3. 大转盘可配置： 
    1. 当天抽奖次数/活动期内的抽奖次数
    2. 活动有效期
    3. 是否开启
    4. 奖品是否有奖。可分出有奖/谢谢惠顾

###接口列表
    根目录/接口.md