<?php
/**
 * Created by PhpStorm.
 * User: Albert Jang
 * Date: 2017/4/27
 * Time: 13:36
 */
namespace app\index\controller;

use app\index\Parser\ParserLog;
use app\index\Parser\ResumeParser;
use think\Controller;

class Parser extends Controller {

    //简历解析
    public function resume() {
        header('Access-Control-Allow-Origin:*');
        $request = request();
        if($request->isPost()) {
            $content = $request->post('content');

            //$type = $request->post('type');
            $Parser = new ResumeParser();
            $content = $Parser->convert2UTF8($content);
            $data = $Parser->parse($content);

            if($data){
                $info = array(
                    'data' => $data,
                    'status' => 1,
                );
            }else{
                $file = ParserLog::toSupport($content);
                $info = array(
                    'data' => $file,
                    'status' => 0,
                );
            }
            return json($info);
        }else{
            $info = array(
                'status' => -1,
            );
            return json($info);
        }
    }
}
