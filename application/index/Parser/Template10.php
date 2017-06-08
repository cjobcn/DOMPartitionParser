<?php
namespace app\index\Parser;

class Template10 extends AbstractParser {
     //区块标题
    protected $titles = array(
        array('evaluation', '自我评价'),
        array('career', '工作经历'),
        array('projects', '项目经验'),
        array('education', '教育经历'), 
        array('practices', '在校实践经验|在校实践经历'),
        array('trainings', '培训经历'), 
        array('certs', '证书'), 
        array('languages', '语言能力'), 
        array('skills', '专业技能'), 
        array('prizes', '获得荣誉'),
        array('others', '附件'),
        array('resume_content', '简历内容')
    );

    //关键字解析规则
    protected $rules = array(
        array('update_time', '简历更新时间：'),
        array('ID', '身份证：'),
        array('residence', '户口：'),
        array('city', '现居住于|现居住地：'),
        array('address', '地址：'),
        array('email', 'E-mail:|E-mail：'),
        array('postcode', '邮编：'),
        array('phone', '手机：'),
        array('target_position', '期望从事职业：'),
        array('target_industry', '期望从事行业：'),
        array('target_city', '期望工作地区：'),
        array('target_salary', '期望月薪：'),
        array('work_status', '目前状况：'),
    );

     //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $redundancy = array(
            '<head>.+?<\/head>',
            '<script.*?>.+?<\/script>',
            '<style.*?>.+?<\/style>'
        );
        $pattern = '/'.implode('|', $redundancy).'/is';
        $content = preg_replace($pattern, '', $content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        //预处理
        $content = $this->preprocess($content);

        //list($data, $blocks) = $this->domParse($content,'td', true);
        list($data, $blocks) = $this->pregParse($content);
        //dump($blocks);
        //dump($data);
        //if(!$blocks) return false;
        //其他解析
        $length = $blocks[0][1]?$blocks[0][1] - 1:count($data);
        $basic = array_slice($data, 0 , $length);
        $i = 0;
        while($i < $length) {
            $KV = $this->parseElement($basic, $i);
            if($KV){
                $record[$KV[0]] = $KV[1];
                unset($basic[$i]);
                $i = $i + $KV[2];
                unset($basic[$i]);
            }
            $i++;
        }
        $patterns = array(
            array('sex', '/男|女/'),
            array('marriage', '/未婚|已婚/'),
            array('birth_year', '/(\d{4})\s*年/', 1),
            array('phone', '/(\d{11})\s*\(手机\)/', 1),
            array('work_year', '/(\S+年)工作经验/', 1),
        );
        $i = 0;
        $extracted = array();
        $restBasic = array_values($basic);
        //dump($basic);
        while($i < count($restBasic)){
            foreach($patterns as $key=>$pattern){
                if(preg_match($pattern[1], $restBasic[$i], $match)) {
                    $index = $pattern[2]?:0;
                    $record[$pattern[0]] = $match[$index];
                    $extracted[] = $i;
                }
            }
            $i++;
        }
        if(!isset($record['name'])) {
            $k = $extracted[0] - 1;
            if(preg_match('/简历名称/', $restBasic[$k])){
                $k = $extracted[1] - 1;
            }
            while($k >= 0){
                if (!preg_match('/当前状态：|待处理/', $restBasic[$k]) &&
                    !$this->isKeyword($restBasic[$k])) {
                    $record['name'] = $restBasic[$k];
                    break;
                }
                $k --;
            }
        }
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
        //return $this->domParse($content, 'td', true, false);
        return $this->pregParse($content,false, false);
    }

    public function evaluation($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $currentKey = 'self_str';
        while($i < $length){
            if($KV = $this->parseElement($data, $i)) {
                $record[$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($currentKey){
                $record[$currentKey] .= $data[$i];
            }
            $i++;
        }
    }

    public function career($data, $start, $end, &$record) {
        $jobs = array();
        if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)：/', $data[$start])){
            $jobs = $this->career1($data, $start, $end, $record);
        }elseif(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (.+?) （\d.+?）/', $data[$start])){
            $jobs = $this->career2($data, $start, $end, $record);
        }
        return $jobs;
    }

    public function career1($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $jobs = array();
        $rules = array(
            array('description', '公司描述：|企业简介：'),
            array('duty', '工作职责：|工作職責：'),
            array('performance', '工作业绩：'),
            array('position', '职务：'),
            array('size', '规模:'),
            array('salary', '月薪：'),
        );
        $currentKey = '';
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)：/', $data[$i], $match)) {
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $job['company'] = $data[++$i];
                $k = $i;
                $jobs[$j++] = $job;
            }elseif(preg_match('/元\/月/', $data[$i])){
                $jobs[$j-1]['salary'] = $data[$i];
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                if($KV[0] == 'size'){
                    $jobs[$j-1]['nature'] = $data[$i-1];
                    $jobs[$j-1]['industry'] = $data[$i-2];
                    if(isset($k) && $k < $i-3){
                        $jobs[$j-1]['position'] = $data[$i-3];
                        if($k < $i-4)
                            $jobs[$j-1]['department'] = $data[$i-4];
                        unset($k);
                    }
                    $currentKey = 'duty';
                }else{
                    $currentKey = $KV[0];
                }
            }elseif($currentKey){

                $jobs[$j-1][$currentKey] .= $data[$i];             
            }
            $i++;
        }
        //dump($jobs);
        $record['career'] = $jobs;
        return $jobs;
    }

    public function career2($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('nature', '企业性质：'),
            array('size', '规模：'),
            array('duty', '工作描述：'),
            array('report_to', '汇报对象：', 0),
            array('underlings', '下属人数：', 0),
            array('salary', '年收入：', 0),
            array('performance', '业绩描述：', 0),
        );
        $i = 0;
        $j = 0;
        $k = 0;
        $currentKey = '';
        $jobs = array();
        while($i < $length) {
            //正则匹配
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (.+?) （\d.+?）/', $data[$i], $match)) {
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $job['company'] = $match[3];
                $jobs[$j++] = $job;
                $k = $i;
                //关键字匹配
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
                if($currentKey == 'nature') {
                    $jobs[$j-1]['industry'] = $data[$i-1];
                    if(!isset($jobs[$j-1]['salary']) && $i-1 > $k+1){
                        $jobs[$j-1]['position'] = $data[$k+1];
                    }
                }
            }elseif(preg_match('/元\/月/', $data[$i])){
                $jobs[$j-1]['salary'] = $data[$i];
                $m = $i - 1;
                while($m > $k) {
                    if($jobs[$j-1]['position']){
                        $jobs[$j-1]['department'] = $data[$m];
                    }else{
                        $jobs[$j-1]['position'] = $data[$m];
                    }
                    $m--;
                }
            }else{
                if($currentKey == 'duty') {
                    $jobs[$j-1][$currentKey] .= $data[$i];
                }
            }
            $i++;
        }
        //dump($jobs);
        $record['career'] = $jobs;
        return $jobs;
    }

    public function projects($data, $start, $end, &$record) {
        $projects = array();
        if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)：(.+)/', $data[$start])){
            $projects = $this->projects1($data, $start, $end, $record);
        }elseif(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (.+)/', $data[$start])){
            $projects = $this->projects2($data, $start, $end, $record);
        }
        return $projects;
    }

    public function projects1($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $projects = array();
        $rules = array(
            array('soft', '软件环境：', 0),
            array('hard', '硬件环境：', 0),
            array('dev', '开发工具：', 0), 
            array('duty', '责任描述：' , 0),
            array('department', '涉及部门：', 0),
            array('performance', '专案业绩：|项目业绩：'),
            array('description', '项目描述：')
        );
        $currentKey = '';
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)：(.+)/', $data[$i], $match)) {
                $project = array();
                $project['start_time'] = Utility::str2time($match[1]);
                $project['end_time'] = Utility::str2time($match[2]);
                $project['name'] = $match[3];
                $projects[$j++] = $project;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }else{
                $projects[$j-1][$currentKey] .= $data[$i];             
            }
            $i++;
        }
        $record['projects'] = $projects;
        return $projects;
    }

    public function projects2($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $projects = array();
        $rules = array(
            array('soft', '软件环境：'),
            array('hard', '硬件环境：'),
            array('dev', '开发工具：'),
            array('duty', '责任描述：' ),
            array('description', '项目描述：'),
        );
        $currentKey = '';
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (.+)/', $data[$i], $match)) {
                $project = array();
                $project['start_time'] = Utility::str2time($match[1]);
                $project['end_time'] = Utility::str2time($match[2]);
                $project['name'] = $match[3];
                $projects[$j++] = $project;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif($currentKey){
                $projects[$j-1][$currentKey] .= $data[$i];
            }
            $i++;
        }
        $record['projects'] = $projects;
        return $projects;
    }

    public function education($data, $start, $end, &$record) {
        $education = array();
        if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)：(.+)/', $data[$start])){
            $education = $this->education1($data, $start, $end, $record);
        }elseif(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (.+)/', $data[$start])){
            $education = $this->education2($data, $start, $end, $record);
        }
        return $education;
    }

    public function education1($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $k = 0;
        $sequence = array('major', 'degree');
        $education = array();
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)：(.+)/', $data[$i], $match)) {
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $edu['school'] = $match[3];
                $education[$j++] = $edu;
                $k = 1;
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

    public function education2($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $education = array();
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (.+)/', $data[$i], $match)) {
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $info = preg_split('/\s+/', $match[3]);
                if(($info_length = count($info)) > 1){
                    $edu['school'] = trim(implode(' ', array_slice($info, 0 ,$info_length-2)));
                    $edu['major'] = $info[$info_length - 2];
                    $edu['degree'] = $info[$info_length - 1];
                    $education[$j++] = $edu;
                }

            }
            //dump($data[$i]);
            $i++;
        }
        //dump($education);
        $record['education'] = $education;
        return $education;
    }

}
