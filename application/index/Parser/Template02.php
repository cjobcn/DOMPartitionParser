<?php
namespace app\index\Parser;


class Template02 extends AbstractParser {
     //区块标题
    protected $titles = array(
        array('basic', '个人信息'),
        array('career', '工作经历'),
        array('education', '教育经历'),
        array('languages', '语言能力'),
        array('projects', '项目经历'),
        array('evaluation', '自我评价'),
        array('addition', '附加信息'),
    );

    //关键字解析规则
    protected $rules = array(
        array('name', '姓名：'),
        array('sex', '性别：'),
        array('email', '电子邮件：'),
        array('birth_year', '出生年份：'),
        array('phone', '手机号码：'),
        array('city', '所在地区：'),
        array('marriage', '婚姻状况：'),
        array('work_begin', '参加工作年份：'),
        array('industry', '目前所在行业：'),
        array('last_company', '公司名称：'),
        array('last_position', '目前职位名称：'),
        array('degree', '最高学历：'),
        array('current_salary', '目前年薪：'),
        array('target_salary', '期望月薪：'),
        array('target_industry', '期望行业：'),
        array('target_position', '期望职位：'),
        array('target_city', '期望地点：'),
    );

     //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $patterns = array(
            '/<(dt|dd|h2)/i',
            '/<\/(dt|dd|h2)>/i',
            '/<(a.*?|em.*?|span)>.*?<\/(a|em|span)>/is',
            '/(&nbsp;)+/'       
        );
        $replacements = array(
            '<div',
            '</div>',
            '',
            '</div><div>'
        );
        $content = preg_replace($patterns, $replacements, $content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        //预处理
        $content = $this->preprocess($content);

        list($data, $blocks) = $this->domParse($content, 'div');
        // dump($blocks);
        // dump($data);
        $end = $blocks[0][1]-2?:count($data)-1;
        //其他解析
        
        //各模块解析
        foreach($blocks as $block){
            $this->$block[0]($data, $block[1], $block[2],$record);
        }
        if(!$record['name'] || !$record['city'] || !$record['last_company']){
            sendMail(2,$content);
        }
        //dump($record);
        return $record;
    }

    //获取DOM数组
    public function getDomArray($content) {
        $content = $this->preprocess($content);
        return $this->domParse($content, 'div', true, false);
    }

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules1 = array(
            array('description', '公司描述：'), 
            array('nature', '公司性质：'), 
            array('size', '公司规模：'), 
            array('industry', '公司行业：'),         
        );
        $rules2 = array(
            array('salary', '薪酬状况：'), 
            array('city', '工作地点：'), 
            array('department', '所在部门：'), 
            array('report_to', '汇报对象：'),
            array('underlings', '下属人数：'), 
            array('duty', '工作职责：'), 
            array('performance', '工作业绩：'),
        );
        $sequence = array('company');
        $i = 0;
        $j = 0;
        $k = 0;
        $jobs = array();
        while($i < $length) {
            //正则匹配
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)$/', $data[$i], $match)) {
                $job = array();
                $k = 1;
            //关键字匹配
            }elseif($KV = $this->parseElement($data, $i, $rules1)) {
                $job[$KV[0]] = $KV[1];
                $i = $i + $KV[2]; 
            }elseif(preg_match('/^(.+?)\((\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)\)$/', $data[$i], $match)){
                $job['position'] = $match[1];
                $job['start_time'] = Utility::str2time($match[2]);
                $job['end_time'] = Utility::str2time($match[3]);
                $jobs[$j++] = $job;
            }elseif($KV = $this->parseElement($data, $i, $rules2)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }elseif($k > 0){
                if($key = $sequence[$k-1]){
                    $job[$key] = $data[$i];
                    $k++;
                }else{
                    $k = 0;
                }
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
        $education = array();
        $rules = array(
            array('major', '专业：'), 
            array('degree', '学历：'), 
        );
        $sequence = array('school');
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)$/', $data[$i], $match)){
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
        $rules = array(
            array('position', '项目职务：'), 
            array('description', '项目描述：'), 
            array('duty', '项目职责：'), 
            array('performance', '项目业绩：'),
        );
        $sequence = array('name');
        $i = 0;
        $j = 0;
        $k = 0;
        $projects = array();
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)/', $data[$i], $match)){
                $project = array();
                $project['start_time'] = Utility::str2time($match[1]);
                $project['end_time'] = Utility::str2time($match[2]);
                $projects[$j++] = $project;
                $k = 1;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }elseif($k > 0){
                if($key = $sequence[$k-1]){
                    $projects[$j-1][$key] = $data[$i];
                    $k++;
                }else{
                    $k = 0;
                }     
            }
            $i++;                                
        }
        $record['projects'] = $projects;
        return $projects;
    }

}
