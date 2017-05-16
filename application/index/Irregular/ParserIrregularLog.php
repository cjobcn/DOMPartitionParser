<?php
/**
 * Created by PhpStorm.
 * User: Albert Jang
 * Date: 2017/5/2
 * Time: 10:20
 */

namespace app\index\Irregular;

class ParserIrregularLog {

    const  LOG_DIR = 'G:/to_support_irregular/';
    const  SAME_NAME_MAX = 100;

    //存储没有解析出来的简历文档（由于简历名字提取原因暂时不用）
    static function toSupportIrregular($content) {
        if(!file_exists(self::LOG_DIR)){
            mkdir(self::LOG_DIR);
        }
        $filename = time();        //时间戳作为文件名
        $ext = '.html';
        $i = 0;
        $filePath = self::LOG_DIR.$filename.$ext;
        //dump($filePath);
        while($i < self::SAME_NAME_MAX){
            if(file_exists($filePath)){
                $i++;
                $filename = $filename.'_'.$i;
                $filePath = self::LOG_DIR.$filename.$ext;
            }else{
                file_put_contents($filePath, $content);
                break;
            }
        }
        return $filePath;
    }
}
