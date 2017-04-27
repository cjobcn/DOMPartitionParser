<?php
/**
 * Created by PhpStorm.
 * User: Albert Jang
 * Date: 2017/4/27
 * Time: 13:36
 */
namespace app\index\controller;

use app\index\Parser\ResumeParser;
use think\Controller;

class Parser extends Controller {

    //简历解析
    public function resume() {
        $request = request();
        if($request->isPost()) {
            $content = $request->post('name');
            //$type = $request->post('type');
            $Parser = new ResumeParser();
            $record = $Parser->parse($content);
            return json($record);
        }
    }
}
