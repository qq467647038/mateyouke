# dictionary

#### 项目介绍
MySQL数据字典生成工具

#### 软件架构
没有啥子架构，纯手写，文件操作数据库操作都是自己简单封装的。


#### 安装教程

1. 入口文件index.php,可以单独配置一个域名，或者是放到某个项目里面，访问 dictionary目录即可。
2. 配置config.php的数据库账号和密码。
3. 在mac或者linux环境下面，注意需要设置runtime目录权限为777, chmod -R 777 runtime


#### 功能说明

1. 选择数据库进行生成数据字典，要记录生成的日期，还可以更新数据字典，并形成历史记录，查看各个时间生成的数据库字典。
2. 生成的数据能够缓存。
3. 能下载生成的数据字典。
		
![输入图片说明](https://images.gitee.com/uploads/images/2018/0709/084838_b344f66e_361161.png "index.png")
![输入图片说明](https://images.gitee.com/uploads/images/2018/0709/084851_33a14502_361161.png "show.png")
