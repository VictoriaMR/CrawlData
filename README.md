# 数据爬取工具包

### 如何使用
注 一个 网站 应使用一个独立的 项目包 这里以 demo 为例

1.  进入resource/composer.json目录安装依赖。
2.  在项目下引入依赖的 autoload.php 如 demo/index.php。
3.  配置项目的数据库信息 demo/comfig.php
4.  依次执行脚本。


### 步骤解读：

1.  自动创建数据库以及相关的有层级关系的表,index > list > detail。
2.  解析首页获取列表页链接，下载列表页并解析获取详情页链接，下载详情页并解析获取需要的数据。

### 项目优点：

1.  使用简单,php + MySQL即可运行项目。
2.  自动创建MySQL数据库表，状态值记录方便排错并保证数据完整和唯一，支持断点下载。
3.  guzzle异步并发爬取保证一定爬取速度，DOM解析简便，加入代理IP池防止被反爬取，代结构简单清晰易读。

### 运行示图：

![gif](https://github.com/weiqiangxu/php_doc/blob/master/static/show.gif?raw=true)
