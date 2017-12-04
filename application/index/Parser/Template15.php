<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/6/20
 * Time: 10:23
 */

namespace app\index\Parser;


class Template15 extends  AbstractParser {
    //区块标题
    protected $titles = array(
        array('target', '职业意向'),
        array('career', '工作经历'),
        array('education', '教育经历'),
        array('languages', '语言能力'),
        array('projects', '项目经历'),
        array('evaluation', '自我评价'),
    );

    //关键字解析规则
    protected $rules = array(
        array('last_company', '目前公司：'),
        array('last_position', '目前职位：'),
        array('work_year', '工作年限：'),
        array('age', '年龄：'),
        array('marriage', '婚姻状况：'),
        array('phone', '手机：'),
        array('email', '邮箱：'),
        array('residence', '户籍：'),
        array('city', '所在地点：'),
        array('target_industry', '期望行业：'),
        array('target_position', '期望职位：'),
        array('target_city', '期望地点：'),
        array('target_salary', '期望年薪：'),
    );

    //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $redundancy = array(
            '/<head>.+?<\/head>/is',
            '/<script.*?>.+?<\/script>/is',
            '/<style.*?>.+?<\/style>/is',
            '/<!--.*?-->/s',
            '/<\/span><span\s*lang=EN-US style=\'font-family:"Tahoma","sans-serif"\'>/s',
            '/<\/span><span\s*style=\'font-family:宋体\'>/',
            '/<\/span><\/span>/',
            '/(<\/span>)?<\/b><b>/',
            '/<\/span><\/b><\/span><b>/',
            '/<\/span><span\s*style=\'font-family:宋体;mso-bidi-font-family:Tahoma\'>/',
            '/<\/span><span\s*lang=EN-US style=\'font-family:"Tahoma","sans-serif";mso-bidi-font-family:\s*"Times New Roman";color:#666666\'>/'
        );
        $content = preg_replace($redundancy, '', $content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        //预处理
        $content = $this->preprocess($content);
        //echo $content;
        //分隔网页数据
        list($data, $blocks) = $this->pregParse($content);
        //dump($data);
        //dump($blocks);

        //其他解析
        $length = $blocks[0][1]-1 > 0 ?$blocks[0][1]-1:count($data);
        $this->basic($data, 0, $length - 1, $record);
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
        return $this->pregParse($content, false, false);
    }

    public function education($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $BlockEdu = new BlockEdu();
        $education = $BlockEdu->parse($data, '3');
        $record['education'] = $education;
        return $education;
    }

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $BlockCareer = new BlockCareer();
        $jobs = $BlockCareer->parse($data, '2');
        //dump($jobs);
        $record['career'] = $jobs;
        return $jobs;
    }

    public function projects($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $BlockProject = new BlockProject();
        $projects = $BlockProject->parse($data, '1');
        //dump($projects);
        $record['projects'] = $projects;
        return $projects;
    }
}
