**MySmarty技术手册**

​	MySmarty是基于PHP开发的mvc框架

**一、环境要求**

​	`php >= 8`

**二、代码上传**

​	将代码上传至您的服务器，如`/usr/share/nginx/html/`文件夹下

**三、权限设置**

​	查看nginx运行的用户，`cat /etc/nginx/nginx.conf`

​	`user  nginx;`

​	一般在第一行，即用户为nginx，这个用户可修改

​	也可以使用`ps aux | grep "nginx: worker process"`查看nginx启动用户

​	进入到mysmarty目录

​	更改文件夹所属用户

```
chown -R nginx:nginx mysmarty
```

​	更改文件夹权限（所有者，读取、执行，组，读取，其它，啥都没有）

```
chmod -R 540 mysmarty
```

​	确保`mysmarty/runtime`,`mysmarty/public/upload`具有可写权限

```
chmod -R 740 mysmarty/runtime
chmod -R 740 mysmarty/public/upload
```

​	确保`mysmarty/public/runtime`具有可读可写权限

```
chmod -R 740 mysmarty/public/runtime
```

**四、服务器配置**

​	添加转发，将所有请求转发至`public/index.php`

```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

以nginx为例

参考示例

```nginx
server {
    #端口
	listen 80;
    #域名
	server_name localhost;
    #代码位置
	root /usr/share/nginx/html/mysmarty/public;
    #首页默认文件
	index index.html index.htm index.php;
    #文件编码
	charset utf-8;
    #错误页面
	error_page 500 502 503 504 /50x.html;
    #所有请求转发至index.php
	location / {
		try_files $uri $uri/ /index.php?$query_string;
	}
    #配置favicon.ico请求
	location = /favicon.ico {
		access_log off;
		log_not_found off;
	}
    #配置robots.txt请求
	location = /robots.txt {
		access_log off;
		log_not_found off;
	}
    #处理PHP文件
	location ~ \.php$ {
		fastcgi_pass 127.0.0.1:9000;
		fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
		include fastcgi_params;
	}
}
```

**五、出现问题？**

​	页面出现403/500？

​    `（一般为文件没有可写权限）`

​	`确保启动用户和nginx工作用户一致`

​	`确保nginx配置了默认主页文件`

​	`确保文件具有写文件权限`

​	SELinux设置为开启状态（enabled）的原因：

​		查看当前selinux的状态： `/usr/sbin/sestatus`

​		关闭selinux：`vi /etc/selinux/config`，将`SELINUX=enforcing` 修改为 `SELINUX=disabled` 状态

​		重启系统，`reboot`

**六、目录说明**

```
application - 应用目录

----home - 模块，可以建多个模块，一个网站对应一个模块

--------config - 模板配置，网站独立配置（可覆盖全局配置文件）

--------controller - 控制器

--------model - 模型

--------view - 视图

----command.php - 命令配置文件

----common.php - 网站公共函数	

doc - 文档目录，实际部署时可删除

vender - 第三方php库，使用composer安装时，可自动加载此目录下的文件

config - 网站全局配置文件目录

----app.php 网站配置

----cookie.php Cookie配置

----cors.php 跨域配置

----database.php 数据库配置

----mail.php 邮件发送配置

----session.php Session配置

----mysmarty.php MySmarty模板配置、缓存配置

extend - 扩展目录

----fonts - 字体文件目录，用户水印等

library - 框架核心目录

public - 网站根目录

----static - 静态文件目录

----upload - 文件/图片上传目录

----index.php - 网站入口文件

----runtime - 合并后css,js目录

mysmarty - 命令行运行文件

runtime - mysmarty编译文件、缓存文件等目录

README.md - 本文件，可删除
```

**七、其它说明**

​	框架使用utf-8编码

​	目录使用小写+下划线

​	类文件采用驼峰法命名（首字母大写），其它文件采用小写+下划线命名

​	类名和类文件名保持一致，统一采用驼峰法命名（首字母大写）

​	方法的命名使用驼峰法（首字母小写）

​	属性的命名使用驼峰法（首字母小写）

​	常量以大写字母和下划线命名

​	配置参数以小写字母和下划线命名

​	数据表和字段采用小写加下划线方式命名，并注意字段名不要以下划线开头

​	**详细使用文档请查看 doc 目录下的文档**

### 感谢

​	https://www.jetbrains.com/?from=MySmarty
