# microCMS
MicroCMS提供一个轻量级企业网站解决方案，基于 PHP + [MySQL/Sqlite] 的技术开发，整个系统压缩为单个PHP文件，全部源码开放，数据库结构完全兼容ECMS，适用于普通企业官网、企业商城类型网站搭建。

<br>

# 演示地址
官网地址： https://esite.likun.work/
<br>
后台演示： https://demo.esite.likun.work/admin/
<br>
默认帐号： `admin`	 默认密码： `123456`

<br>

# 安装方法
克隆此项目到站点根目录，重命名 [index.released.php] 为 [index.php] ，然后直接访问即可；
若站点未初始化，首次访问会自动进入数据初始化界面；

<br>

# 目录结构
```
-- template		# 模板目录
	|-- admin	# 后台模板目录，用户若是不接受后期升级服务，可以按需进行二次开发
	|-- default	# 默认模板目录，用户自行开发站点前端界面
	|-- ...		# 用户可以自行增加其它模板目录，在后台设定界面填写对应模板目录名称即可使用模板
-- index.released.php	# 默认主页，用户应该自行改名为 index.php
-- nginx.conf	# Nginx配置文件
-- .htaccess	# apache重写文件
-- mysqli.sql	# mysql数据库结构文件，若删除此文件表示不接受后期升级服务
-- sqlite.sql	# sqlite数据库结构文件，若删除此文件表示不接受后期升级服务
-- .filelist	# cms系统文件索引，若删除此文件表示不接受后期升级服务
-- LICENSE		# MicroCMS开源授权许可协议，必须保留
-- README.md	# 程序说明文件
```

<br>

# 近期待完成工作
#### 模板仓库对接  
获取从ecms云系统获取模板列表，对接到microCMS，供用户快速安装更多模板；

#### 程序文档编写
基于MicroCMS建站的模板手册，以及自定义扩展接口。
