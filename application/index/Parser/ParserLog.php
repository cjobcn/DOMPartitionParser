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

    static function toSupport($content, $filename = '') {
        if(!file_exists(self::LOG_DIR)){
            mkdir(self::LOG_DIR);
        }
        $current_dir = self::LOG_DIR.date("Y-m-d").'/';
        if(!file_exists($current_dir)){
            mkdir($current_dir);
        }
        $filename = $filename?:time();         //时间戳作为文件名
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

    static function classify($dirName) {
        $patterns = array(
            'json'=> '/^\{\"/',
            'English' => '/Career Objective|Self-Assessment|Work Experience|Education/',
            'wujiegou' => '/\.barp \{/',
            'deleted' => '/该简历已被求职者删除，无法查看!/',
            '14' => '/121\.41\.112\.72\:12885/',
            '01' => '/简历编号(：|: )\d{1,8}[^\d\|]/',                //猎聘网
            '02' => '/<title>基本信息_个人资料_会员中心_猎聘猎头网<\/title>/',  //猎聘编辑修改页面
            '03' => '/<title>我的简历<\/title>.+?<div class="index">/s',         //可能是智联招聘
            '04' => '/\(编号:J\d{7}\)的简历/i',                   //中国人才热线
            '05' => '/简历编号(:|：)\d{8}\|猎聘通/',                    //猎聘网
            '06' => '/<title>.+?举贤网.+?<\/title>/i',            //举贤网
            '07' => '/编号\s+\d{16}/',                           //中华英才
            '08' => '/简历编号：\d{16}/',
            '09' => '/\(ID:\d{1,}\)|(51job\.com|^简_历|简历).+?基 本 信 息|个 人 简 历<\/b>/s',     //51job(前程无忧)
            '10' => '/<span[^>]*>智联招聘<\/span>|<div class="zpResumeS">/i',       //智联招聘
            '11' => '/<div (id="userName" )?class="main-title-fl fc6699cc"/',    //智联招聘
            '12' => '/来源ID:[\d\w]+<br>/',     //已被处理过的简历
            '13' => '/<title>简历ID：\d{5,}<\/title>.+?51job/s',  //新版51job
        );
        $path = self::LOG_DIR.$dirName.'/';
        //dump($path);
        //$path = $dirName;
        $dir = dir($path);
        if(!$dir) {
            echo '目录不存在！';
            return false;
        }
        while (($file = $dir->read()) !== false){
            if(is_file($path.$file)){
                $content = Utility::readDocument($path.$file);
                foreach($patterns as $key => $pattern) {
                    if(preg_match($pattern, $content)){
                        $newPath= $path.'/'.$key.'/';
                        if(!file_exists($newPath)){
                            mkdir($newPath);
                        }
                        copy($path.$file, $newPath.$file);
                        unlink($path.$file);
                        break;
                    }
                }
            }
        }
        echo "归类结束！";
        return true;
    }
}
