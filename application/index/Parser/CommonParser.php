<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/5/1
 * Time: 17:40
 */

namespace app\index\Parser;


class CommonParser extends AbstractParser {

    //区块标题
    protected $titles = array(
        array('target', '求职意向'),
        array('evaluation', '自我评价'),
        array('career', '工作经历|工作经验'),
        array('projects', '项目经历|项目经验'),
        array('education', '教育经历'),
        array('school_situation', '在校学习情况'),
        array('practices', '在校实践经验'),
        array('training', '培训经历'),
        array('certs', '证书'),
        array('languages', '语言能力'),
        array('skills', '专业技能|IT技能'),
        array('prizes', '获得荣誉|所获奖励'),
        array('others', '附件'),
        array('interest', '兴趣爱好'),
        array('resume_content', '简历内容'),
        array('addition', '附加信息'),
    );

    //关键字解析规则
    protected $rules = array(
        array('update_time', '简历更新时间：'),
        array('true_id', 'ID:'),
        array('name', '姓名：'),
        array('city', '现居住地：'),
        array('postcode', '邮编：'),
        array('phone', '手机：'),
        array('residence', '户口：'),
        array('email', 'E-mail：'),
        array('ID' , '身份证：'),
        array('target_city', '期望工作地区：'),
        array('target_salary', '期望月薪：'),
        array('work_status', '目前状况：'),
        array('target_position', '期望从事职业：'),
        array('target_industry', '期望从事行业：'),
    );

    public function preprocess($content) {
        $redundancy = array(
            '/<head>.+?<\/head>/is',
            '/<script.*?>.+?<\/script>/is',
            '/<style.*?>.+?<\/style>/is'
        );
        $content = preg_replace($redundancy, '', $content);
        return $content;
    }

    public function parse($content) {
        $record = array();
        //预处理
        $content = $this->preprocess($content);
        //分隔网页数据
        list($data, $blocks) = $this->pregParse($content);
        dump($data);
        dump($blocks);

        //其他解析
        $length = $blocks[0][1]-1?:count($data)-1;
        $basic = array_slice($data,0, $length);
        $i = 0;
        //关键字提取
        while($i < $length) {
            $KV = $this->parseElement($data, $i);
            if($KV && !isset($record[$KV[0]])) {
                unset($basic[$i]);
                $record[$KV[0]] = $KV[1];
                if($KV[2] > 0){
                    $i = $i + $KV[2];
                    unset($basic[$i]);
                }
            }
            $i++;
        }
        $restKeywords = array_diff(array_keys($record),array_column($this->rules,0));
        //正则提取
        $Extracter = new DataExtracter();
        foreach($basic as $originValue) {
            foreach($restKeywords as $keyword) {
                if($res = $Extracter->extract($keyword, $originValue)) {
                    $record[$res[0]] = $res[1];
                }
            }
        }
        //各模块解析
        foreach($blocks as $block){
            $this->$block[0]($data, $block[1], $block[2],$record);
        }
    }

    //工作经历
    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $jobs = array();
        while($i < $length){
            $i++;
        }
        $record['career'] = $jobs;
        return $jobs;
    }

    //项目经历
    public function projects($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $projects = array();
        while($i < $length){
            $i++;
        }
        $record['projects'] = $projects;
        return $projects;
    }

    //教育经历
    public function education($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $education = array();
        while($i < $length){
            $i++;
        }
        $record['education'] = $education;
        return $education;
    }



}
