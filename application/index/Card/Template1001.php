<?php
namespace app\index\Card;

use app\index\Parser\AbstractParser;
use app\index\Parser\Utility;

class Template1001 extends AbstractParser {
    public function preprocess($content) {
        $redundancy = array(
            '/<head>.+?<\/head>/is',
            '/<script.*?>.+?<\/script>/is',
            '/<style.*?>.+?<\/style>/is',
        );
        $content = preg_replace($redundancy, '', $content);
//        $content = str_replace(array('\\'),
//            array(''), $content);
        //两个空格作为分隔符
        $content = str_replace(array('  ','&nbsp;&nbsp;'), '|', $content);
        return $content;
    }
    //解析名片
    public function parse($content) {
        $record = array();
        preg_match('/(?<=<strong>).+?(?=<)/', $content, $name);
        preg_match_all('/(?<=<td title=").+?(?=<|"|\s|\(|（)/', $content, $titleInfo);
        $tdcontent = preg_replace('/\n/','',$content);
        preg_match_all('/(?<=<td>).+?(?=<\/td>)/', $tdcontent, $tdInfo);
        if($name){
            $record['name'] = $name[0];
        }
        if($titleInfo){
            $record['degree'] = $titleInfo[0][0];
            $record['city'] = $titleInfo[0][1];
            $record['last_position'] = $titleInfo[0][2];
            $record['last_company'] = $titleInfo[0][3];
        }
        if($tdInfo){
            preg_match('/(\d{1,2})(?=.*)/',trim($tdInfo[0][2]),$work_year);
            preg_match('/\d{4}(?=.*)/',trim($tdInfo[0][3]),$update_year);
            $work_time= $update_year[0]-$work_year[0];
            $record['sex'] = trim($tdInfo[0][0]);
            $record['age'] = trim($tdInfo[0][1]);
            $record['work_begin'] = $work_time;
            $record['update_time'] = trim($tdInfo[0][3]);
        }
        $this->caree($content,$record);
        $this->education($content,$record);
        if(!$record){
            sendMail(1001,$content);
        }
        return $record;
    }
    public function caree($content,&$record){
        $record['career'] = array();
        $record['resume'] = $content;
        preg_match('/(?<=<div class="table-list-info-r">)[\s\S]+?(?=<\/div>)/', $content, $exper);
        if($exper){
            $careeStr = preg_replace('/\s/','',$exper[0]);
            $careeStr = trim($careeStr);
            $careeArr = explode('<br/>',$careeStr);
            foreach ($careeArr as $key => $value) {
                $arr = explode('|',$value);
                foreach($arr as $k=>$v){
                    $arr[$k] =HtmlToText($v);
                }
                preg_match('/(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)\D*/', $arr[0], $match);
                $record['career'][$key]['start_time'] = Utility::str2time($match[1]);
                $record['career'][$key]['end_time'] = Utility::str2time($match[2]);
                $record['career'][$key]['company'] = $arr[1];
                $record['career'][$key]['position'] = $arr[2];
            }
        }
    }
    public function education($content,&$record){
        $record['education'] = array();
        preg_match('/(?<=<div class="table-list-info-l">)[\s\S]+?(?=<\/div>)/', $content, $exper);
        if($exper){
            $careeStr = preg_replace('/\s/','',$exper[0]);
            $careeStr = preg_replace('/：/','|',$careeStr);
            $careeStr = trim($careeStr);
            $careeArr = explode('<br/>',$careeStr);
            foreach ($careeArr as $key => $value) {
                $arr = explode('|',$value);
                foreach($arr as $k=>$v){
                    $arr[$k] =HtmlToText($v);
                }
                preg_match('/(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)\D*/', $arr[0], $match);
                $record['education'][$key]['start_time'] = Utility::str2time($match[1]);
                $record['education'][$key]['end_time'] = Utility::str2time($match[2]);
                $record['education'][$key]['school'] = $arr[1];
                $record['education'][$key]['major'] = $arr[2];
                $record['education'][$key]['degree'] = $arr[3];
            }
        }
    }

}
