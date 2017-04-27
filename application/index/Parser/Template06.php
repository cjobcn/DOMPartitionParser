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

    //判断模板是否匹配
    protected function isMatched($content) {
        return preg_match('/<title>.+?举贤网.+?<\/title>/', substr($content,0,1000));
    }

     //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $content = str_replace(array('td'),'div',$content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        //判断内容与模板是否匹配
        if(!$this->isMatched($content)) return false;
        //预处理
        $content = $this->preprocess($content);

        list($data, $blocks) = $this->domParse($content, 'div');
        //dump($blocks);
        //dump($data);
        if(!$blocks) return false;
        //其他解析
        
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
        return $this->domParse($content, 'td', true, false);
    }

    public function evaluation($data, $start, $end, &$record) {
        $this->basic($data, $start, $end, $record);
    }

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('nature', '公司性质：'),
            array('size', '公司规模：'),
            array('position', '担任职位：', 2),
            array('city', '工作地点：'),
            array('duty', '职位职责：'),
            array('department', '所在部门：')
        );
        $keys = array('company');
        $i = 0;
        $j = 0;
        $jobs = array();
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)/', $data[$i], $match)){
                $job = array();
                $job['start_time'] = $match[1];
                $job['end_time'] = $match[2];
                $jobs[$j++] = $job;
                $k = 1;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }elseif($k > 0){
                if($keys[$k-1]){
                    $jobs[$j-1][$keys[$k-1]] = $data[$i];
                    $k++;
                }else{
                    $k = 0;
                }     
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
            array('desciption', '项目描述：'),
        );
        $keys = array('name');
        $i = 0;
        $j = 0;
        $projects = array();
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)/', $data[$i], $match)){
                $project = array();
                $project['start_time'] = Utility::str2time($match[1]);
                $project['end_time'] = Utility::str2time($match[2]);
                $projects[$j++] = $project;
                $k = 1;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }elseif($k > 0){
                if($keys[$k-1]){
                    $projects[$j-1][$keys[$k-1]] = $data[$i];
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

    // public function career($data, $start, $end, &$record) {
    //      $rules = array(
    //         array('nature', '公司性质：'),
    //         array('size', '公司规模：'),
    //         array('position', '担任职位：', 2),
    //         array('city', '工作地点：'),
    //         array('duty', '职位职责：'),
    //         array('department', '所在部门：')
    //     );
    //     $pattern = '/^(?P<start_time>\d{4}\D+\d{1,2})\D+(?P<end_time>\d{4}\D+\d{1,2}|至今)/';
    //     $sequence = array('pattern',array('company'));
    //     $conditions = array(
    //         'rules' => $rules,
    //         'pattern' => $pattern,
    //         'sequence' => $sequence
    //     );
    //     $record['career'] = $this->blockParse($data, $start, $end, $conditions);
    //     return $record['career'];
    // }
}
