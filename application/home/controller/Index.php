<?php

namespace application\home\controller;

use library\mysmarty\Controller;

class Index extends Controller
{
    public function home()
    {
        var_dump(lang('test'));
    }
}