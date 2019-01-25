<?php
/**
 * Created by PhpStorm.
 * User: Roy
 * Date: 2018/9/26
 * Time: 14:00
 */
namespace app\index\Parser;

class Template20 extends AbstractParser {
    //模块标题
    protected $titles = array(
        array('evaluation', '自我评价'),
        array('education', '教育经历'),
        array('projects', '项目经历'),
        array('career', '工作经历'),
        array('languages', '语言能力'),
        array('training', '培训经历'),
        array('skill', '专业技能'),
    );

    //关键字解析规则
    protected $rules = array(
        array('update_time', '更新时间：'),
        array('residence', '户口'),
        array('update_time', '更新时间：'),
        array('true_id', 'ID：'),
        array('city', '现居住地'),
        array('current_salary', '目前薪资：'),
        array('target_industry', '期望从事行业'),
        array('target_position', '期望从事职业'),
        array('target_city', '期望工作地点'),
        array('target_salary', '期望月薪'),
    );

    protected $separators = array(
        '<\/.+?>',      //html结束标签
        '\|',           // |
        '<br.*?>',      // 换行标签
        '(&nbsp;)+',
        '\n'
    );

    //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $redundancy = array(
            '/<head>.+?<\/head>/is',
            '/<script.*?>.+?<\/script>/is',
            '/<style.*?>.+?<\/style>/is',
            '/<\/font>/'
        );
        $content = preg_replace($redundancy, '', $content);
        return $content;
    }
    //基础信息根据关键字提取
    public function basic($data, $start, $end, &$record,$content) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        while($i < $length) {
            $KV = $this->parseElement($data, $i);
            if($KV && !$record[$KV[0]]){
                $record[$KV[0]] = $KV[1];
            }
            $i++;
        }
        preg_match_all('/(?:class="resume-content__candidate-name resume-tomb">)(.+)?(?:<\/span>)/',$content,$name);
        preg_match_all('/(?:genderDesc">)(.+)?(?:<\/span>)/isU',$content,$sex);
        preg_match_all('/(?:<span data-bind="text: age">)(.+)?(?:<\/span>)/isU',$content,$birth);
        preg_match_all('/(?:<span data-bind="text: maritalStatus\(\)">)(.+)?(?:<)/isU',$content,$marriage);
        preg_match_all('/(?:<span data-bind="text: eduLevel\(\)">)(.+)?(?:<)/isU',$content,$education);
        $record['name'] = $name[1][0];
        $record['sex'] = $sex[1][0];
        $record['marriage'] = $marriage[1][0];
        $record['degree'] = $education[1][0];
        preg_match('/(\d{1,4}年)?(\d{1,2}月\d{1,2}日)/',$record['update_time'],$update_time);
        if($update_time[1]){
            $record['update_time'] = $update_time[0];
        }else{
            $record['update_time'] = date('Y').'年'.$update_time[0];
        }
    }
//<span data-bind="textQ: candidateName" class="resume-content__candidate-name resume-tomb">金先生</span>
    //根据模板解析简历
    public function parse($content) {
        $record = array();
        $content = $this->preprocess($content);
        list($data, $blocks) = $this->pregParse($content, false, true, $this->separators, $hData);
        //dump($hData);
        //dump($blocks);
        $end = $blocks?$blocks[0][1]-2:count($data)-1;
        $this->basic($data, 0, $end, $record,$content);
        foreach($blocks as $block){
            $function = $block[0];
            $this->$function($data, $block[1], $block[2],$record, $hData,$content);
        }
        if(!$record){
            sendMail(20,$content);
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
        $i = $start;
        $evaluation = '';
        while($i <= $end){
            $evaluation .= $data[$i++];
        }
        $evaluation = preg_replace('/.*自我评价：/s','',$evaluation);
        $record['self_str'] = $evaluation;

        return $evaluation;
    }
    public function training($data, $start, $end, &$record) {
        $i = $start;
        $training = '';
        while($i <= $end){
            $training .= $data[$i++];
        }
        $training = preg_replace('/.*培训经历/s','',$training);
        $record['training'] = $training;

        return $training;
    }

    public function career($data, $start, $end, &$record, $hData) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $jobs = array();
        while($i < $length) {
            if(preg_match('/^(\d{4})\D+(\d{1,4}|至今|现在)$/', $data[$i], $match)) {
                if(preg_match('/^(\d{4})\D+(\d{1,4}|至今|现在)$/', $data[$i+2],$match1)){
                    $job['start_time'] = Utility::str2time($match[0]);
                    $job['end_time'] = Utility::str2time($match1[0]);
                    $job['company'] = $data[$i+3];
                    $job['position'] = $data[$i+5];
                    $job['industry'] = $data[$i+7];
                    $jobs[$j++] = $job;
                }
            }
            $i++;
        }
        $record['career'] = $jobs;
        if($jobs){
            $record['last_company'] = $jobs[0]['company'];
            $record['last_position'] = $jobs[0]['position'];
        }
        return $jobs;
    }
    public function education($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $education = array();
        while($i < $length) {
            if(preg_match('/^(\d{4})\D+(\d{1,4}|至今|现在)$/', $data[$i], $match)) {
                if(preg_match('/^(\d{4})\D+(\d{1,4}|至今|现在)$/', $data[$i+2],$match1)){
                    $education[$j]['start_time'] = Utility::str2time($match[0]);
                    $education[$j]['end_time'] = Utility::str2time($match1[0]);
                    $education[$j]['school'] = $data[$i+3];
                    $education[$j]['major'] = $data[$i+4];
                    $education[$j]['degree'] = $data[$i+5];
                    $j++;
                }
            }
            $i++;
        }
        $record['education'] = $education;
        if($education){
            dealEducation($record);
            $record['school'] = $record['education'][0]['school'];
            $record['major'] = $record['education'][0]['major'];
            $record['degree'] = $record['education'][0]['degree'];
            $record['first_degree'] = $record['education'][count($record['education'])-1]['degree'];
        }
        return $education;
    }


    public function projects($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $projects = array();
        $rules = array(
            array('duty', '责任描述'),
        );
        while($i < $length) {
            if(preg_match('/^(\d{4})\D+(\d{1,4}|至今|现在)$/', $data[$i], $match)) {
                if(preg_match('/^(\d{4})\D+(\d{1,4}|至今|现在)$/', $data[$i+2],$match1)) {
                    $project = array();
                    $project['start_time'] = Utility::str2time($match[0]);
                    $project['end_time'] = Utility::str2time($match1[0]);
                    $project['name'] = $data[$i + 3];
                    $projects[$j++] = $project;
                }
            }elseif($KV = $this->parseElement($data, $i, $rules)){
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }else{
                if($projects[$j-1]['duty']){
                    $projects[$j-1]['duty'] = $projects[$j-1]['duty'].'</br>'.$data[$i];
                }
            }
            $i++;
        }
        $record['projects'] = $projects;
        return $projects;

    }
}