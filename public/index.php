<?php
define('ROOT_DIR', dirname(__DIR__));
require_once ROOT_DIR . '/library/mysmarty/Start.php';
//定义默认模块
const MODULE = 'home';
//定义默认控制器
const CONTROLLER = 'Index';
//定义默认方法
const ACTION = 'home';

use library\mysmarty\Start;

Start::forward();