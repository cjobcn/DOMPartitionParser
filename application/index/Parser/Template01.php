<?php
namespace app\index\Parser;

class Template01 extends AbstractParser {
    //模块标题
    protected $titles = array(
        array('target', '职业发展意向：|求职意向'),
        array('evaluation', '自我评价(:|：)?'),
        array('education', '教育经历(:|：)?'),
        array('projects', '项目经历(:|：)?'),
        array('career', '工作经历(:|：)?'),
        array('languages', '语言能力(:|：)?'),
        array('addition', '附加信息(:|：)?'),
        array('history', '历史版本(:|：)?'),
    );

    //关键字解析规则
    protected $rules = array(
        array('true_id', '简历编号：|简历编号:'),
        array('update_time', '最后更新：|最后更新:'),
        array('name', '姓名：'), 
        array('sex', '性别：'), 
        array('phone', '手机号码：|联系电话：'),
        array('age', '年龄：'), 
        array('email', '电子邮件：'), 
        array('degree', '教育程度：'), 
        array('marriage', '婚姻状况：'), 
        array('city', '所在地：|工作地点：'),
        array('work_year', '工作年限：'),
        array('work_status', '目前职业概况：|目前状态：'),
        array('industry', '所在行业：'), 
        array('last_company', '公司名称：'),
        array('last_position', '所任职位：'), 
        array('current_salary', '目前薪金：|目前年薪：'),
        array('bonus', '绩效奖金：'),
        array('target_industry', '期望行业：|期望从事行业：'),
        array('target_position', '期望职位：'), 
        array('target_city', '期望地点：|期望从事职业：'),
        array('target_salary', '期望月薪：'),
    );

    protected $separators = array(
        '<\/.+?>',      //html结束标签
        '\|',           // |
        '<br.*?>',      // 换行标签
        '(&nbsp;)+'
        // '\r\n'
    );

    //判断模板是否匹配
    protected function isMatched($content) {

    }

    //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $redundancy = array(
            '/<head>.+?<\/head>/is',
            '/<script.*?>.+?<\/script>/is',
            '/<style.*?>.+?<\/style>/is'
        );
        $content = preg_replace($redundancy, '', $content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        $content = $this->preprocess($content);
        list($data, $blocks) = $this->pregParse($content);
        //dump($data);
        //dump($blocks);
        $end = $blocks?$blocks[0][1]-2:count($data)-1;
        $this->basic($data,0,$end, $record);
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

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('city', '-所在地区：', 0),
            array('report_to', '-汇报对象：', 0),
            array('underlings', '-下属人数：', 0),
            array('duty', '-工作职责：|主要工作:'),
            array('performance', '-工作业绩：'),
        );
        $i = 0;
        $j = 0;
        $currentKey = '';
        $jobs = array();
        $job = array();
        while($i < $length) {
            //正则匹配
            if(preg_match('/(.+) (?P<start_time>\d{4}\D+\d{1,2})\D+(?P<end_time>\d{4}\D+\d{1,2}|至今|现在)$/',
                $data[$i], $match)) {

                $job = array();
                $job['company'] = $match[1];
                $job['start_time'] = Utility::str2time($match["start_time"]);
                $job['end_time'] = Utility::str2time($match['end_time']);
            }elseif(preg_match('/^(?P<start_time>\d{4}\D+\d{1,2})\D+(?P<end_time>\d{4}\D+\d{1,2}|至今|现在)$/',
                $data[$i], $match)) {

                $jobs[$j++] = $job;
                $jobs[$j-1]['position'] = $data[$i-1];
                $jobs[$j-1]['start_time'] = Utility::str2time($match["start_time"]);
                $jobs[$j-1]['end_time'] = Utility::str2time($match['end_time']);
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($currentKey){
                $jobs[$j-1][$currentKey] .=  $data[$i];
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
        $k = 0;
        $sequence = array('school');
        $rules = array(
            array('major', '-?专业：', 0),
            array('degree', '-?学历：', 0),
        );
        $education = array();
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)$/', $data[$i], $match)) {
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $education[$j++] = $edu;
                $k = 1;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $education[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }elseif($k > 0){
                if($key = $sequence[$k-1]){
                    $education[$j-1][$key] = $data[$i];
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
        //需要去除空格符
        $rules = array(
            array('position', '-项目职务：', 0),
            array('company', '-所在公司：', 0),
            array('description', '-项目简介：', 0),
            array('duty', '-项目职责：'),
            array('performance', '-项目业绩：'),
        );
        $i = 0;
        $j = 0;
        $projects = array();
        while($i < $length) {
            if(preg_match('/(.+?)(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)：/', $data[$i], $match)){
                $project = array();
                $project['name'] = $match[1];
                $project['start_time'] = Utility::str2time($match[2]);
                $project['end_time'] = Utility::str2time($match[3]);
                $projects[$j++] = $project;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }
            $i++;
        }
        $record['projects'] = $projects;
        return $projects;
    }

}
