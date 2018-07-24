<?php
namespace app\index\Card;

use app\index\Parser\AbstractParser;
use app\index\Parser\Utility;

class Template01 extends AbstractParser {
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
        $record['resume'] = $content;
        preg_match('/(?<=<strong>).+?(?=<)/', $content, $name);
        preg_match_all('/(?<=<td title=").+?(?=<|"|\s|\(|（)/', $content, $titleInfo);
//        preg_match('/(?<=\\\\)[^\\\\]+?(?=\+)/', $content, $record['company']);
//        preg_match('/(?<=\+)[^\\\\]+?(?=_)/', $content, $record['city']);
//        preg_match('/(?<=pv-top-card-section__headline mt1 Sans-19px-black-85%">)[\s\S]+?(?=<)/', $content, $record['title']);
//        preg_match_all('/(?<=<h3 class="Sans-17px-black-85%-semibold">)[\s\S]+?(?=<)/', $content, $record['positions']);
//        preg_match('/(?<=<div class="table-list-info-r">)
//                         [\s\S]+?
//                    (?=<\/div>)/', $content, $record['exper']);
        if($name){
            $record['name'] = $name[0];
        }
        if($titleInfo){
            $record['degree'] = $titleInfo[0][0];
            $record['city'] = $titleInfo[0][1];
            $record['last_position'] = $titleInfo[0][2];
            $record['last_company'] = $titleInfo[0][3];
        }
        $this->caree($content,$record);
        $this->education($content,$record);
        return $record;
    }
    public function caree($content,&$record){
        $record['caree'] = array();
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
                $record['caree'][$key]['start_time'] = Utility::str2time($match[1]);
                $record['caree'][$key]['end_time'] = Utility::str2time($match[2]);
                $record['caree'][$key]['company'] = $arr[1];
                $record['caree'][$key]['position'] = $arr[2];
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
