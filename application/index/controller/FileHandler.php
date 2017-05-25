<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/5/25
 * Time: 10:22
 */

namespace app\index\controller;
use app\index\Parser\ParserLog;
use think\Controller;

class FileHandler extends Controller {

    public function classify($dir) {
        //$dir = "E:/PHP/wamp64/www/parser/resumes/14/";
        ParserLog::classify($dir);
    }
}
