<?php

namespace application\home\controller;

use library\mysmarty\App;
use library\mysmarty\Controller;

class Index extends Controller
{
    public function home()
    {
        var_dump('------------------------------');
//        var_dump(env('APP_DEBUG'));
        var_dump(env('APP_INIT'));
        var_dump(config('database'));
        var_dump(config('app'));

    }
}