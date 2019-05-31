<?php
/**
 * Created by PhpStorm.
 * User: Roy
 * Date: 2018/7/25
 * Time: 11:51
 */
//通用推荐报告模板
namespace app\index\Parser;
class ReportTemplate999 extends AbstractParser{
    //区块标题
    protected $titles = array(
        array('baseinfo', '个人信息|基本信息'),
        array('self_str', '候选人优势'),
        array('career', '工作经历|工作履历'),
        array('project', '项目经验',0),
        array('education', '教育背景|教育经历'),
        array('salary_info', '薪酬信息'),
        array('recommend_info', '推荐信息'),
    );

    public function preprocess($content) {
        $redundancy = array(
            '/<head>.+?<\/head>/is',
            '/<script.*?>.+?<\/script>/is',
            '/<style.*?>.+?<\/style>/is',
        );
        $content = preg_replace($redundancy, '', $content);
//        $content = str_replace(array('\\'),
//            array(''), $content);
        $content = str_replace(array('一、','二、','三、','四、','五、','六、','七、'),
            '', $content);
        //两个空格作为分隔符
        $content = str_replace(array('  ','&nbsp;&nbsp;','（','）'), '|', $content);
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
        if(!$record['career'] || !$record['education']){
            sendMail(999,$content);
        }
        return $record;
    }
    public function baseinfo($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('name', '姓名：|名：',0),
            array('sex', '性别：|别：',0),
            array('residence', '籍贯：|家庭住址：'),
            array('birth_year', '出生年月：|出生日期：'),
            array('height', '身高：'),
            array('first_degree', '第一学历：'),
            array('degree', '学历：'),
            array('city', '目前居住地：|目前工作地：|城市：'),
            array('target_city', '期望工作地：|期望城市：'),
            array('marriage', '婚育状况：'),
            array('current_salary', '目前薪酬：'),
            array('target_salary', '期望薪酬：'),
            array('jump_time', '最快到岗时间：|可到岗时间：'),
        );
        $i = 0;
        while($i < $length) {
            if($KV = $this->parseElement($data, $i, $rules)){
                $record[$KV[0]] = $KV[1];
            }
            $i++;
        }
    }
    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('department', '所在部门：'),
            array('report_to', '汇报上级：|汇报对象：'),
            array('underlings', '下属人数：|下属：'),
            array('duty', '工作职责：|职责：'),
            array('performance', '工作业绩：|业绩：'),
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
                $job['company'] = $data[$i+1];
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
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)\D*$/', $data[$i], $match)){
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                if($i>0 && preg_match('/(大学|学院)$/', $data[$i-1],$match)){//如果大学在时间的前面
                    $edu['school'] = $data[$i-1];
                    $edu['major'] = $data[$i+1];
                    $edu['degree'] = $data[$i+2];
                }else{
                    $edu['school'] = $data[$i+1];
                    $edu['major'] = $data[$i+2];
                    $edu['degree'] = $data[$i+3];
                }
                $education[$j++] = $edu;
            }
            $i++;
        }
        if($education){
            $record['school'] = $education[count($education)-1]['school'];
            $record['major'] = $education[count($education)-1]['major'];
            if(!$record['degree'])
                $record['degree'] = $education[0]['degree'];
            if(!$record['first_degree'])
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
            array('description', '项目描述：'),
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
                }
            }
        }
        //dump($jobs);
        $record['projects'] = $projects;
    }
    public function self_str($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $record['self_str'] = implode('</br>',$data);
    }
    public function salary_info($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('current_salary', '目前年薪及组成：|目前薪资：|目前薪酬：|目前税前年薪：',0),
            array('target_salary', '期望年薪及组成：|期望薪资：|期望薪酬：|期望税前年薪：',0),
            array('jump_time', '最快到岗时间：|可到岗时间：|最快入职时间：',0),
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