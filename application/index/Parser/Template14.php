<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/5/24
 * Time: 9:44
 */

namespace app\index\Parser;


class Template14 extends AbstractParser {
    //区块标题
    protected $titles = array(
        array('target', '职业意向'),
        array('evaluation', '自我评价'),
        array('career', '工作经历'),
        array('education', '教育经历'),
        array('languages', '语言能力'),
        array('projects', '项目经历'),
    );

    //关键字解析规则
    protected $rules = array(
        array('name', '姓名：'),
        array('true_id', '编号：'),
        array('sex', '性别：'),
        array('creator', '创建者：'),
        array('degree', '学历：'),
        array('birth_year', '出生年：'),
        array('target_city', '意向地区：'),
        array('industry', '所属行业：'),
        array('phone', '联系方式：|手机：'),
        array('email', '邮箱：'),
        array('update_time', '创建时间：'),
        array('last_company', '当前所在公司：|目前公司：'),
        array('last_position', '目前职位：|最近职位：'),
        array('work_year', '工作年限：'),
        array('age', '年龄：'),
        array('marriage', '婚姻状况：'),
        array('nationality', '国籍：'),
        array('residence', '户籍：'),
        array('work_status', '目前状态：'),
        array('city', '所在地点：'),
        array('target_industry', '期望行业：'),
        array('target_position', '期望职位：'),
        array('target_city', '期望地点：'),
        array('target_salary', '期望年薪：'),
        array('current_salary', '目前年薪：'),
    );


    public function preprocess($content) {
        $redundancy = array(
            '/<head>.+?<\/head>/is',
            '/<script.*?>.+?<\/script>/is',
            '/<style.*?>.+?<\/style>/is',
        );
        $content = preg_replace($redundancy, '', $content);
        $content = str_replace(array('年  龄：', '手  机：', '国  籍：', '邮  箱：', '户  籍：','<h1>', '<h3>'),
            array('年龄：', '手机：', '国籍：', '邮箱：', '户籍：','姓名：', '最近职位：'), $content);
        //两个空格作为分隔符
        $content = str_replace(array('  ','&nbsp;&nbsp;'), '|', $content);
        return $content;
    }

    public function parse($content) {
        $record = array();
        //预处理
        $content = $this->preprocess($content);
        //分隔网页数据
        list($data, $blocks) = $this->pregParse($content);
        //dump($data);
        //dump($blocks);

        //其他解析
        $length = $blocks[0][1]-1 > 0 ?$blocks[0][1]-1:count($data)-1;
        $basic = array_slice($data,0, $length);
        $this->basic($basic, 0, $length - 1, $record);
        //各模块解析
        foreach($blocks as $block){
            $this->$block[0]($data, $block[1], $block[2],$record);
        }
        if(!$record['name'] || !$record['city'] || !$record['last_company']){
            sendMail(14,$content);
        }
        //dump($record);
        return $record;
    }

    //获取DOM数组
    public function getDomArray($content) {
        $content = $this->preprocess($content);
        return $this->pregParse($content, false, false);
    }

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules1 = array(
            array('industry', '公司行业：'),
            array('description', '公司描述：'),
            array('nature', '公司性质：'),
            array('size', '公司规模：'),
        );
        $rules2 = array(
            array('city', '工作地点：'),
            array('underlings', '下属人数：'),
            array('duty', '工作职责：'),
            array('performance', '工作业绩：'),
            array('salary', '薪酬状况：'),
            array('department', '所在部门：'),
            array('report_to', '汇报对象：'),
        );
        $i = 0;
        $j = 0;
        $currentKey = '';
        $timeSpan = '';
        $job = array();
        $jobs = array();
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)$/', $data[$i], $match)){
                if($timeSpan != $data[$i]){
                    $job = array();
                    $timeSpan = $data[$i];
                    $job['company'] = $data[++$i];
                }else{
                    $job['start_time'] = Utility::str2time($match[1]);
                    $job['end_time'] = Utility::str2time($match[2]);
                    $jobs[$j++] = $job;
                    $jobs[$j-1]['position'] = $data[$i-1];
                }
            }elseif($KV = $this->parseElement($data, $i, $rules1)){
                $job[$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($KV = $this->parseElement($data, $i, $rules2)){
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($j > 0 && $currentKey == 'description' || $currentKey == 'duty' || $currentKey == 'performance'){
                $jobs[$j-1][$currentKey] .=  '#br#'.$data[$i];
            }

            $i++;
        }
        //dump($jobs);
        $record['career'] = $jobs;
        return $jobs;
    }

    public function education($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $education = array();
        $rules = array(
            array('major', '专业名称：'),
            array('degree','学历：')
        );
        while($i < $length) {
            if(preg_match('/(.+)（\s(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)\s）/', $data[$i], $match)){
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[2]);
                $edu['end_time'] = Utility::str2time($match[3]);
                $edu['school'] = $match[1];
                $education[$j++] = $edu;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $education[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }
            $i++;
        }
        //dump($education);
        $record['education'] = $education;
        return $education;
    }

    public function projects($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('position', '项目职务：'),
            array('description', '项目描述：'),
            array('duty', '项目职责：'),
            array('performance', '项目业绩：'),
        );
        $i = 0;
        $j = 0;
        $currentKey = '';
        $projects = array();
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)/', $data[$i], $match)){
                $project = array();
                $project['start_time'] = Utility::str2time($match[1]);
                $project['end_time'] = Utility::str2time($match[2]);
                $project['name'] = $data[$i-1];
                $projects[$j++] = $project;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }if($j > 0 && $currentKey){
                $projects[$j-1][$currentKey] .=  '#br#'.$data[$i];
            }
            $i++;
        }
        $record['projects'] = $projects;
        return $projects;
    }
}
