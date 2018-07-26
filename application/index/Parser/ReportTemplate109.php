<?php
/**
 * Created by PhpStorm.
 * User: Roy
 * Date: 2018/7/24
 * Time: 16:39
 */
namespace app\index\Parser;
class ReportTemplate109 extends AbstractParser{
    //区块标题
    protected $titles = array(
        array('baseinfo', '^\d*个人信息$'),
        array('education', '教育背景'),
        array('skill', '职业资格及技能'),
        array('career', '工作经历'),
        array('other_info', '辅助报告信息：'),
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
    public function baseinfo($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('name', '姓名：|名：',0),
            array('sex', '性别：|别：',0),
            array('residence', '籍贯：|贯：',0),
            array('birth_year', '出生日期：'),
            array('city', '现所在地：'),
            array('residence', '户口所在地：'),
            array('address', '家庭所在地：'),
            array('marriage', '婚育状况：'),
            array('phone', '联系方式：'),
            array('email', '电子邮箱：'),
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
            array('description', '公司简介：'),
            array('address', '工作地点：'),
            array('report_to', '汇报对象：'),
            array('underlings', '下属人数：'),
            array('duty', '工作职责：'),
            array('performance', '工作业绩：'),
            array('project', '项目经验：|经典项目：'),
            array('left_reason', '离职原因：'),
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
                if($jobs[$j-1]['left_reason']){
                    $jobs[$j-1]['left_reason'] = $jobs[$j-1]['left_reason'].'</br>'.$data[$i];
                }elseif($jobs[$j-1]['project']){
                    $jobs[$j-1]['project'] = $jobs[$j-1]['project'].'</br>'.$data[$i];
                }elseif($jobs[$j-1]['performance']){
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
            $record['degree'] = $education[count($education)-1]['degree'];
            $record['first_degree'] = $education[count($education)-1]['degree'];
        }
        //dump($education);
        $record['education'] = $education;
        return $education;
    }
    public function project($data, $start, $end, &$record){

    }
    public function other_info($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('current_salary', '目前税前年薪：',0),
            array('target_salary', '期望税前年薪：',0),
            array('pay_project', '是否有赔偿项目：',0),
            array('jump_time', '最快入职时间：',0),
        );
        $i = 0;
        while($i < $length) {
            if($KV = $this->parseElement($data, $i, $rules)){
                $record[$KV[0]] = $KV[1];
            }else{
                if($record['jump_time']){
                    $record['jump_time'] = $record['jump_time'].'<br/>'.$data[$i];
                }elseif($record['pay_project']){
                    $record['pay_project'] = $record['pay_project'].'<br/>'.$data[$i];
                }elseif($record['target_salary']){
                    $record['target_salary'] = $record['target_salary'].'<br/>'.$data[$i];
                }elseif($record['current_salary']){
                    $record['current_salary'] = $record['current_salary'].'<br/>'.$data[$i];
                }
            }
            $i++;
        }
    }
}