##PHP多进程实现图书馆图书爬虫翻译

###文件

library.php

####运行方式

$php -f library.php

####运行输出

控制台监控输出

日志文件library.log

####运行结果

如果爬取成功，图书信息存入数据库library_books表，反之存入timeout_no表，并且备注原因（timeout，top，under）。

####后期处理

对于timeout数据，运行timeout.php处理。对于top数据，尽可能排查并修改正则以兼容此类问题。对于under数据，认定为图书信息有误或不存在，直接略过。

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
