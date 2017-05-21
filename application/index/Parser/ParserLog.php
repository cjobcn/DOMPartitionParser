<?php
/**
 * Created by PhpStorm.
 * User: Albert Jang
 * Date: 2017/5/2
 * Time: 10:20
 */

namespace app\index\Parser;


class ParserLog {

    const  LOG_DIR = 'G:/to_support/';
    const  SAME_NAME_MAX = 100;

    static function toSupport($content) {
        if(!file_exists(self::LOG_DIR)){
            mkdir(self::LOG_DIR);
        }
        $current_dir = self::LOG_DIR.date("Y-m-d").'/';
        if(!file_exists($current_dir)){
            mkdir($current_dir);
        }
        $filename = time();         //时间戳作为文件名
        $ext = '.html';
        $i = 0;
        $filePath = $current_dir.$filename.$ext;
        //dump($filePath);
        while($i < self::SAME_NAME_MAX){
            if(file_exists($filePath)){
                $i++;
                $file = $filename.'_'.$i;
                $filePath = $current_dir.$file.$ext;
            }else{
                file_put_contents($filePath, $content);
                break;
            }
        }
        return $filePath;
    }
}
