<?php
/**
 * Created by PhpStorm.
 * User: Roy
 * Date: 2018/7/24
 * Time: 14:49
 */
namespace app\index\Parser;
class ReportTemplate07 extends AbstractParser{
    //区块标题
    protected $titles = array(
        array('baseinfo1', '候选人个人简况'),
        array('baseinfo', '基本信息'),
        array('language', '语言能力'),
        array('education', '教育背景'),
        array('recommend_reason', '推荐理由'),
        array('self_str', '能力评价'),
        array('careersummary', '工作经历简述'),
        array('career', '具体工作经验'),
        array('skill', '相关技能'),
        array('salary', '薪酬待遇'),
    );

    public function preprocess($content) {
        $redundancy = array(
            '/<head>.+?<\/head>/is',
            '/<script.*?>.+?<\/script>/is',
            '/<style.*?>.+?<\/style>/is',
        );
        $content = preg_replace($redundancy, '', $content);
        $content = str_replace(array('一、','二、','三、','四、','五、','六、','七、'),
            '', $content);
        //两个空格作为分隔符
        $content = str_replace(array('  ','&nbsp;&nbsp;'), '|', $content);
        return $content;
    }
    public function parse($content)
    {
        $record = array();
        $record['resume'] = $content;
        //预处理
        $content = $this->preprocess($content);
        //分隔网页数据
        list($data, $blocks) = $this->pregParse($content);
        //各模块解析
        foreach($blocks as $block){
            $function = $block[0];
            $this->$function($data, $block[1], $block[2],$record);
        }
        return $record;
    }
    public function baseinfo1($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('city', '目前所在地：',0),
        );
        $i = 0;
        while($i < $length) {
            if($KV = $this->parseElement($data, $i, $rules)){
                $record[$KV[0]] = $KV[1];
            }
            $i++;
        }
    }
    public function baseinfo($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('name', '姓名：|名：'),
            array('sex', '性别：|别：'),
            array('marriage', '婚姻状况：',0),
            array('birth_year', '出生日期：',0),
            array('nationality', '国籍：|籍：',0),
            array('degree', '学历：|历：',0),
            array('phone', '电话：',0),
            array('email', '邮箱：',0),
            array('left_reason', '看机会原因：',0),
        );
        $i = 0;
        while($i < $length) {
            if($KV = $this->parseElement($data, $i, $rules)){
                $record[$KV[0]] = $KV[1];
            }
            $i++;
        }
    }
    public function language($data, $start, $end, &$record){

    }
    public function recommend_reason($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $record['recommend_reason'] = $data[0];
    }
    public function self_str($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $record['self_str'] = $data[0];
    }
    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('duty', '岗位职责：|工作职责：|工作职责'),
            array('performance', '工作业绩：'),
        );
        $j = 0;
        $job = array();
        $jobs = array();
        for($i=0;$i<$length;$i++){
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)\D*$/', $data[$i], $match)){
                $start_time = Utility::str2time($match[1]);
                $end_time = Utility::str2time($match[2]);
                if($start_time>=$jobs[$j-1]['start_time'] && $end_time<=$jobs[$j-1]['end_time']){
                    continue;
                }
                $job['start_time'] = $start_time;
                $job['end_time'] = $end_time;
                $job['company'] = strlen($data[$i+1])>50?"":$data[$i+1];
                if(likeDepartment($data[$i+2])){
                    $job['department'] = $data[$i+2];
                    $job['position'] = strlen($data[$i+3])>50?"":$data[$i+3];
                }else{
                    $job['position'] = strlen($data[$i+2])>50?"":$data[$i+2];
                }
                $jobs[$j++] = $job;
            }elseif($KV = $this->parseElement($data, $i, $rules)){
                $jobs[$j-1][$KV[0]] = $KV[1];
            }else{
                if($jobs[$j-1]['performance']){
                    $jobs[$j-1]['performance'] = $jobs[$j-1]['performance'].'</br>'.$data[$i];
                }elseif($jobs[$j-1]['duty']){
                    $jobs[$j-1]['duty'] = $jobs[$j-1]['duty'].'</br>'.$data[$i];
                }
            }
        }
        if($jobs){
            $record['last_company'] = $jobs[0]['company'];
            $record['last_position'] = $jobs[0]['position'];
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
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)\D*$/', $data[$i], $match)){
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $edu['school'] = $data[$i+1];
                $edu['major'] = $data[$i+2];
                $edu['degree'] = $data[$i+3];
                $education[$j++] = $edu;
            }
            $i++;
        }
        if($education){
            $record['school'] = $education[count($education)-1]['school'];
            $record['major'] = $education[count($education)-1]['major'];
            $record['first_degree'] = $education[count($education)-1]['degree'];
        }
        //dump($education);
        $record['education'] = $education;
        return $education;
    }
    public function project($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('description', '项目描述：|项目描述:',0),
            array('duty', '项目职责：|项目职责:',0),
        );
        $j = 0;
        $project = array();
        $projects = array();
        for($i=0;$i<$length;$i++){
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)\D*$/', $data[$i], $match)){
                $start_time = Utility::str2time($match[1]);
                $end_time = Utility::str2time($match[2]);
                if($start_time>=$projects[$j-1]['start_time'] && $end_time<=$projects[$j-1]['end_time']){
                    continue;
                }
                $project['start_time'] = $start_time;
                $project['end_time'] = $end_time;
                $project['project_name'] = $data[$i+1];
                $projects[$j++] = $project;
            }elseif($KV = $this->parseElement($data, $i, $rules)){
                $projects[$j-1][$KV[0]] = $KV[1];
            }else{
                if($projects[$j-1]['description']){
                    $projects[$j-1]['description'] = $projects[$j-1]['description'].'</br>'.$data[$i];
                }elseif($projects[$j-1]['duty']){
                    $projects[$j-1]['duty'] = $projects[$j-1]['duty'].'</br>'.$data[$i];
                }
            }
        }
        //dump($jobs);
        $record['projects'] = $projects;
    }
    public function skill($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $record['skill'] = implode('<br/>',$data);
    }
    public function salary($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('current_salary', '基本工资：',0),
            array('target_salary', '期望工资：',0),
            array('jump_time', '可上岗日期：',0),
            array('bonus', '其它福利：',0),
        );
        $i = 0;
        while($i < $length) {
            if($KV = $this->parseElement($data, $i, $rules)){
                $record[$KV[0]] = $KV[1];
            }
            $i++;
        }
    }
}