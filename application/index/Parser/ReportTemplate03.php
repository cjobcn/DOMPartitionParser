<?php
/**
 * Created by PhpStorm.
 * User: Roy
 * Date: 2018/7/20
 * Time: 11:39
 */
//中骏推荐报告模板解析
namespace app\index\Parser;
class ReportTemplate03 extends AbstractParser {
    //区块标题
    protected $titles = array(
        array('baseinfo', '个人信息'),
        array('education', '教育背景'),
        array('career', '工作经历'),
        array('otherinfo', '辅助报告信息：'),
    );

    public function preprocess($content) {
        $redundancy = array(
            '/<head>.+?<\/head>/is',
            '/<script.*?>.+?<\/script>/is',
            '/<style.*?>.+?<\/style>/is',
        );
        $content = preg_replace($redundancy, '', $content);
        $content = str_replace(array('性    别', '国    籍', '籍    贯','&','一、','二、','三、','四、','五、','六、','七、'),
            array('性别', '国籍', '籍贯','</br>','','','','','','',''), $content);
        //两个空格作为分隔符
        $content = str_replace(array('  ','&nbsp;&nbsp;'), '|', $content);
        return $content;
    }

    public function parse($content) {

        $record = array();
        $record['resume'] = $content;
        //预处理
        $content = $this->preprocess($content);
        //分隔网页数据
        list($data, $blocks) = $this->pregParse($content);
        //其他解析
        $length = $blocks[0][1]-1 > 0 ?$blocks[0][1]-1:count($data)-1;
        $basic = array_slice($data,0, $length);
        $this->basicinfo($basic, 0, $length - 1, $record);
        //各模块解析
        foreach($blocks as $block){
            $function = $block[0];
            $this->$function($data, $block[1], $block[2],$record);
        }
        return $record;
    }
    //获取DOM数组
    public function getDomArray($content) {
        $content = $this->preprocess($content);
        return $this->pregParse($content, false, false);
    }
    //前置信息
    public function basicinfo($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        while($i < $length) {
            if(preg_match('/^应聘公司$/',$data[$i],$match)){
                $record['apply_company'] = $data[$i+1];
            }elseif(preg_match('/^应聘职位$/',$data[$i],$match)){
                $record['apply_position'] = $data[$i+1];
            }elseif(preg_match('/^推荐日期$/',$data[$i],$match)){
                $record['recommend_time'] = Utility::str2time($data[$i+1]);
            }elseif(preg_match('/^面试记录$/',$data[$i],$match)){
                $record['interview_list'] = "面试记录</br>";
            }elseif(preg_match('/^推荐理由$/',$data[$i],$match)){
                $record['recommend_reason'] = "推荐理由$</br>";
            }elseif($record['interview_list'] && !$record['recommend_reason']){
                $record['interview_list'] = $record['interview_list'].'</br>'.$data[$i];
            }elseif($record['recommend_reason']){
                $record['recommend_reason'] = $record['recommend_reason'].'</br>'.$data[$i];
            }
            $i++;
        }
    }
    public function baseinfo($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('name', '中文姓名'),
            array('birth_year', '出生年月'),
            array('sex', '性别'),
            array('height', '身高'),
            array('marriage', '婚姻状况'),
            array('nationality', '国籍'),
            array('residence', '籍贯'),
            array('city', '工作所在'),
            array('residence', '家庭所在'),
            array('phone', '联系方式：'),

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
            array('description', '公司背景：'),
            array('department', '所在部门：'),
            array('position', '担任职位：'),
            array('report_to', '汇报对象：'),
            array('underlings', '管辖下属：'),
            array('duty', '工作职责：'),
            array('performance', '工作业绩：'),
            array('left_reason', '离职原因：'),
        );
        $i = 0;
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
                }elseif($jobs[$j-1]['performance']){
                    $jobs[$j-1]['performance'] = $jobs[$j-1]['performance'].'</br>'.$data[$i];
                }elseif($jobs[$j-1]['duty']){
                    $jobs[$j-1]['duty'] = $jobs[$j-1]['duty'].'</br>'.$data[$i];
                }elseif($jobs[$j-1]['underlings']){
                    $jobs[$j-1]['underlings'] = $jobs[$j-1]['underlings'].'</br>'.$data[$i];
                }elseif($jobs[$j-1]['report_to']){
                    $jobs[$j-1]['report_to'] = $jobs[$j-1]['report_to'].'</br>'.$data[$i];
                }elseif($jobs[$j-1]['department']){
                    $jobs[$j-1]['department'] = $jobs[$j-1]['department'].'</br>'.$data[$i];
                }elseif($jobs[$j-1]['description']){
                    $jobs[$j-1]['description'] = $jobs[$j-1]['description'].'</br>'.$data[$i];
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
        $rules = array(
            array('major', '专业名称：'),
            array('degree','学历：')
        );
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
            $record['school'] = $education[0]['school'];
            $record['major'] = $education[0]['major'];
            $record['degree'] = $education[count($education)-1]['degree'];
            $record['first_degree'] = $education[0]['degree'];
        }
        //dump($education);
        $record['education'] = $education;
        return $education;
    }

    public function careersummary($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $record['careersummary'] = implode('</br>',$data);
    }
    public function otherinfo($data, $start, $end, &$record){
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $record['otherinfo'] = implode('</br>',$data);
    }
}
