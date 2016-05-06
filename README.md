##PHP多进程实现图书馆图书爬虫翻译

###文件

library.php

####运行方式

$php -f library.php

####运行结果

爬取成功：图书信息存入数据库library_books表  
抓取超时或数据库出错：存入timeout_no表  
运行信息：存入library.log日志文件  
top：表示图书信息解析出错  
under：表示因为图书状态就判断没有该图书  
title is null：表示图书以存入数据库，但是没有名字  
auther is null：表示图书以存入数据库，但是没有著作  
press is null：表示图书以存入数据库，但是没有出版社  
time is null：表示图书以存入数据库，但是没有出版时间  
对于数据不全的图书，如果是个别的话建议手改，没办法，图书信息录入太乱额。。。

####后期处理

对于timeout_no表数据，运行timeout.php处理  
对于top、title、auther、press、time数据，尽可能排查并修改正则以兼容此类问题  
对于under数据，认定为图书信息有误或不存在，直接略过。

###文件

timeout.php

####运行方式

$php -f timeout.php

####运行输出

控制台监控输出

####运行结果

对数据库timeout_no表中timeout数据，再进行爬取处理，如果成功，就删除该条记录。并将图书信息存入数据库library_books表，反之存入timeout_no表，并且备注原因（timeout，top，under）。

####后期处理

尽可能排除所有timeout数据。

###文件

reptile.php

###类型

图书详情页爬虫处理

###文件

library_books.sql

####类型

数据库library_books表结构

###文件

timeout_no.sql

####类型

数据库timeout_no表结构

###文件

library.log

####类型

library.php程序运行日志文件
