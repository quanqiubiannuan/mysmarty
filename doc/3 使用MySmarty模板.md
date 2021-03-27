要使用MySmarty模板技术，需要控制器继承Controller，就可以使用MySmarty模板提供的所有方法

`\application\home\controller\Index.php`

```php
<?php

namespace application\home\controller;


use library\mysmarty\Controller;

class Index extends Controller
{
    public function test()
    {
        $this->display();
    }
}
```

模板文件

`\application\home\view\index\test.html`

```php+HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
您好，中国
</body>
</html>
```

默认模板文件目录，`\application\模块名\view\控制器名`

模板文件名为方法名，文件后缀为html

您也可以使用其它文件名，MySmarty默认模板目录指向了 `\application\模块名\view` 目录

```php
<?php
namespace application\home\controller;
use library\mysmarty\Controller;

class Index extends Controller{
    public function test(){
        $this->assign('words','您好，中国');
        $this->display('index/filetest.html');
    }
}
```

使用 index 控制器下的 filetest.html 模板文件

**渲染模板方法说明**

假如模板文件 `\application\home\view\index\test.html`

与模板文件命令规则一致

```php
$this->display();
```

```php
$this->view();
```

指定自定义模板文件

```php
$this->display('index/home.html');
```

使用view方法，不需要指定模板文件后缀名

```php
$this->view('index.test');
```

**输出变量**

控制器 application/home/controller/Index.php

```php
<?php

namespace application\home\controller;

use library\mysmarty\Controller;

class Index extends Controller
{
    public function test()
    {
        $this->assign('name', '张三');
        $this->display();
    }
}
```

模板 application/home/view/index/test.html

```html
{$name}
```

**变量修饰符**

控制器

```php
<?php

namespace application\home\controller;

use library\mysmarty\Controller;

class Index extends Controller
{
    public function test()
    {
        $this->assign('name','Lisi');
        $this->display();
    }
}
```

模板

```html
{$name|strtolower}
```

修饰符用|分隔，多个依次调用，默认调用的修饰符方法第一个参数为当前变量的值

```html
{$time|formatToTime:'Y-m-d'}
```

修饰符的函数，第一个参数必须为变量的值，否则无法调用。

修饰符的函数可以是自己编写的，但第一个参数必须是当前变量的值。

多个参数用:分隔，第一个冒号后面的参数为 修饰符函数的 第二个参数，依次类推。

**输出数组**

控制器

```php
<?php

namespace application\home\controller;

use library\mysmarty\Controller;

class Index extends Controller
{
    public function test()
    {
        $this->assign('data', [1, 2, 3, 4, 5, 6]);
        $this->display();
    }
}
```

模板

```html
{foreach $data as $item}
    数组值：{$item}<br>
{/foreach}
```

**输出二维数组**

控制器

```php
<?php

namespace application\home\controller;

use library\mysmarty\Controller;

class Index extends Controller
{
    public function test()
    {
        $this->assign('data', [
            [
                'id' => 1,
                'name' => '李四'
            ],
            [
                'id' => 2,
                'name' => '张三'
            ],
            [
                'id' => 3,
                'name' => '王五'
            ],
        ]);
        $this->display();
    }
}
```

模板

```html
{foreach $data as $item}
    id：{$item['id']}，name：{$item['name']}<br>
{/foreach}
```

另外一种循环方式

```html
{foreach from=$data item=$item key=$k}
    id：{$item['id']}，name：{$item['name']},key：{$k}<br>
{/foreach}
```

from 指定循环的数组

item 指定循环的变量

key 指定循环的索引，默认为 $index

**模板包含**

```html
{include file='index/test2.html'}
test
```

**条件判断**

控制器

```php
<?php

namespace application\home\controller;

use library\mysmarty\Controller;

class Index extends Controller
{
    public function test()
    {
        $this->assign('type', 1);
        $this->display();
    }
}
```

模板

```html
{if $type == 1}
    type = 1
{/if}

{if $type == 1}
type = 1
{elseif $type == 2}
type = 2
{/if}


{if $type == 1}
type = 1
{else}
type = 2
{/if}

{if $type == 1}
type = 1
{elseif $type == 2}
type = 2
{else}
type = 3
{/if}
```

**万能语法**

解决不了的模板语法，试试php标签

```html
{php}
function test(){
    echo 'test';
}
test();
{/php}
```

**读取模板配置文件**

模板配置文件：application/home/config/templet.conf

```
# global variables
pageTitle = "Main Menu"
bodyBgColor = #000000
tableBgColor = #000000
rowBgColor = #00ff00

[Customer]
pageTitle = "Customer Info"

[Login]
pageTitle = "Login"
focus = "username"
Intro = """This is a value that spans more
           than one line. you must enclose
           it in triple quotes."""
[Database]
host=library.example.com
db=ADDRESSBOOK
user=php-user
pass=foobar
```

模板

```html
{config_load file="templet.conf"}
<html>
<head>
</head>

<body>
{#pageTitle#}

{#Customer.pageTitle#}

{#Login.Intro#}
</body>
</html>
```

需要用 config_load 先加载配置文件

**模板继承**

父模板 application/home/view/index/p.html

```html
<html>
<head>
    {block name=title}{/block}
</head>
<body>
    {block name=content}{/block}
    {block name=content2}没有继承，就显示我{/block}
</body>
</html>
```

子模板 application/home/view/index/test.html

```html
{extends file='index/p.html'}
{block name=title}标题{/block}
{block name=content}内容{/block}
```

支持多级继承，但不支持 block 标签嵌套！

**原样输出**

```html
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Title</title>
</head>
<body>
内容
{literal}
    不会解析的地方
{$name}

{url}

<script>
    alert('xxx');
</script>

{/literal}
</body>
</html>
```

**格式化css/js文件**

将多个css文件或多个js文件合并成一个文件

```html
{css href='a.css,b.css' format='1'}
```

href 多个public文件夹下的css文件，逗号分隔

format 是否格式化，0 不合并，1 合并为一个文件

js同理

```html
{js href='a.js,b.js' format='1'}
```

**其它模板语法**

```html
域名主页：{url}<br>
当前url:{href}
```