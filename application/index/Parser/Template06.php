<?php

namespace app\index\Parser;

class Template06 extends AbstractParser {
    protected $website = '举贤网';
     //区块标题
    protected $titles = array(
        array('basic', '个人信息'),
        array('target', '求职意向'),
        array('evaluation', '自我评价'),
        array('career', '工作经历'),
        array('education', '教育经历'),
        array('projects', '项目经验'),
        array('languages','语言能力'),
    );

    //关键字解析规则
    protected $rules = array(
        array('name', '姓名：'), 
        array('sex', '性别：'), 
        array('email', '电子邮件：'), 
        array('birth_year', '出生年月：'), 
        array('phone', '手机号码：'), 
        array('city', '所在地区：'), 
        array('marriage', '婚姻状况：'), 
        array('work_begin', '参加工作时间：'),
        array('work_status', '目前状态：'),
        array('degree', '最高学历：'), 
        array('target_salary', '期望月薪：'), 
        array('target_industry', '期望行业：'),
        array('target_city', '期望地点：'),
        array('target_position', '期望职位：'),
        array('current_salary', '目前年薪'), 
        array('self_str', '自我评价：'),
    );

     //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $redundancy = array(
            chr(239).chr(187).chr(191), //去除UTF-的BOM头
            '<head>.+?<\/head>',
            '<script.*?>.+?<\/script>',
            '<style.*?>.+?<\/style>',
            '\r\n'
        );
        $pattern = '/'.implode('|', $redundancy).'/is';
        $content = preg_replace($pattern, '', $content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        //判断内容与模板是否匹配
        //if(!$this->isMatched($content)) return false;
        //预处理
        $content = $this->preprocess($content);

        list($data, $blocks) = $this->pregParse($content);
        //dump($blocks);
        //dump($data);
        if(!$blocks) return false;
        dump($data[0][0].$data[0][1].$data[0][2]);
        //其他解析
        //$this->basic($data, 0 , $blocks[0][1]-1, $record);
        //各模块解析
        foreach($blocks as $block){
            $this->$block[0]($data, $block[1], $block[2],$record);
        }

        //dump($record);
        return $record;
    }

    //获取DOM数组
    public function getDomArray($content) {
        $content = $this->preprocess($content);
        return $this->pregParse($content, false, false);
    }

    public function evaluation($data, $start, $end, &$record) {
        $this->basic($data, $start, $end, $record);
    }

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules1 = array(
            array('nature', '公司性质：'),
            array('size', '公司规模：'),
        );
        $rules2 = array(
            array('position', '担任职位：', 2),
            array('city', '工作地点：'),
            array('duty', '职位职责：'),
            array('department', '所在部门：')
        );
        $sequence = array('company');
        $i = 0;
        $j = 0;
        $k = 0;
        $jobs = array();
        $job = array();
        $currentKey = '';
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)/', $data[$i], $match)){
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $k = 1;
            }elseif($KV = $this->parseElement($data, $i, $rules1)) {
                $job[$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }elseif($KV = $this->parseElement($data, $i, $rules2)) {
                if($KV[0] == 'position') {
                    $jobs[$j++] = $job;
                    if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)/', $data[$i+1], $match)){
                        $jobs[$j-1]['start_time'] = Utility::str2time($match[1]);
                        $jobs[$j-1]['end_time'] = Utility::str2time($match[2]);
                    }
                }elseif($KV[0] == 'duty'){
                    $KV[1] = $this->clean($KV[1]);
                }

                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($k > 0){
                if($key = $sequence[$k-1]){
                    $job[$key] = $data[$i];
                    $k = 0;
                }
            }elseif($currentKey){
                $jobs[$j-1][$currentKey] .= '|'.$data[$i];
            }
            $i++;                                
        }
        $record['career'] = $jobs;
        return $jobs;
    }

    public function education($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $k = 0;
        $education = array();
        $keys = array('school', 'major', 'degree', 'class');
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)/', $data[$i], $match)){
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $education[$j++] = $edu;
                $k = 1;
            }elseif($k > 0){
                if($keys[$k-1]){
                    $education[$j-1][$keys[$k-1]] = $data[$i];
                    $k++;
                }else{
                    $k = 0;
                } 
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
            array('company', '所在公司：'),
            array('description', '项目描述：'),
        );
        $sequence = array('name');
        $i = 0;
        $j = 0;
        $k = 0;
        $projects = array();
        $currentKey = '';
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)$/', $data[$i], $match)){
                $project = array();
                $project['start_time'] = Utility::str2time($match[1]);
                $project['end_time'] = Utility::str2time($match[2]);
                $projects[$j++] = $project;
                $k = 1;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($k > 0){
                if($key = $sequence[$k-1]){
                    $projects[$j-1][$key] = $data[$i];
                    $k = 0;
                }
            }elseif($currentKey){
                $projects[$j-1][$currentKey] .= '|'.$data[$i];
            }
            $i++;                                
        }
        $record['projects'] = $projects;
        return $projects;
    }
}
