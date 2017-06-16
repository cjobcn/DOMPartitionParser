<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/5/25
 * Time: 10:22
 */

namespace app\index\controller;
use app\index\Parser\ParserLog;
use app\index\Parser\ResumeParser;
use think\Controller;

class FileHandler extends Controller {


    /**对文件分类
     * @param $dir
     */
    public function classify($dir) {
        //$dir = "E:/PHP/wamp64/www/parser/resumes/14/";
        ParserLog::classify($dir);
    }

    public function resume($dir, $id, $template = '') {
        $content = $this->getResume($dir, $id, $template);
        echo $content;
    }

    public function getResume($dir, $id, $template = '') {
        $Parser = new ResumeParser();
        $path = ParserLog::LOG_DIR.$dir.'/'.$template.'/'.$id.'.html';
        //dump($path);
        $content = $Parser->readDocument($path);
        //dump($content);
        $content = $Parser->convert2UTF8($content);
        return $content;
    }
}
