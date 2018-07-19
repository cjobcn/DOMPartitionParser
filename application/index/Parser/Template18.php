<?php
/**
 * Created by PhpStorm.
 * User: Roy
 * Date: 2018/7/18
 * Time: 10:00
 */
namespace app\index\Parser;


class Template18 extends  AbstractParser {
    //区块标题
//    protected $titles = array(
//        array('evaluation', '#h3自我简介'),
//        array('career', '#h3工作经历'),
//        array('languages', '#h3语言能力'),
//        array('skills', '#h3技能'),
//        array('education', '#h3教育经历'),
//        array('others', '#h3其他信息'),
//        array('basic', '#h3个人信息'),
//        array('concerned', '#h3会员还看过'),
//    );
    protected $titles = array(
        array('career', '工作经历'),
        array('education', '教育经历'),
        array('volunteer', '志愿者经历'),
        array('skills', '技能认可'),
        array('education', '教育经历'),
//        array('others', '#h3其他信息'),
//        array('basic', '#h3个人信息'),
        array('concerned', '关注'),
    );

    //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $redundancy = array(
            '/<head>.+?<\/head>/is',
            '/<script.*?>.+?<\/script>/is',
            '/<style.*?>.+?<\/style>/is',
            '/<!--.*?-->/s',
            '/<\/time>/',
            '/<br>/',
        );
        $search = array('<h3>', '<h4 class="summary fn org" dir="auto">',
            '<span class="major">', '<span class="degree">');
        $replace = array('#h3', 'school:',
            'major:', 'degree:');
        $content = preg_replace($redundancy, '', $content);
        $content = str_replace($search, $replace, $content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        //预处理
        $content = $this->preprocess($content);
        //echo $content;
        //分隔网页数据
        list($data, $blocks) = $this->pregParse($content, false, true, array(), $hData);
        //dump($content);
        //dump($data);
        //dump($blocks);
        //dump($hData);

        //其他解析
        $length = $blocks[0][1] - 1 > 0 ? $blocks[0][1] - 1 : count($data);
        $this->getBaseInfo($content,$data,$record);
        $this->basic($data, 2, $length - 1, $record);
        //dump($blocks);
        //各模块解析
        foreach ($blocks as $block) {
            $function = $block[0];
            $this->$function($data, $block[1], $block[2], $record, $hData);
        }

        //dump($record);
        return $record;
    }

    //获取DOM数组
    public function getDomArray($content) {
        $content = $this->preprocess($content);
        return $this->pregParse($content, false, false);
    }

    public function career($data, $start, $end, &$record, $hData) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $hData = array_slice($hData,$start, $length);
        $i = 0;
        $j = 0;
        $jobs = array();
        while($i < $length) {
            //if(preg_match('/^(\d{4}\D+\d{1,2})\D+((\d{4}\D+\d{1,2})?|至今|现在)(.+)/s', $data[$i], $match)) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)/s', $data[$i], $match)) {
                $job = array();
                $hadCompany = false;
                $hadCity = false;
                for($k = $i-5;$k<$i+5;$k++){
                    if(preg_match('/公司名称/s', $data[$k])) {
                        if($hadCompany==true){//公司名只取第一个再次匹配到则是下一个工作经历
                            break;
                        }
                        $job['company'] = $data[$k+1];
                        $job['position'] = $data[$k-1];
                        $hadCompany = true;
                    }
                    if(preg_match('/所在地区/', $data[$k])){
                        $job['city'] = $data[$k+1];
                        $hadCity = true;
                    }
                }
                if($hadCompany == false){
                    $job['company'] = '';
                    $job['position'] = '';
                }
                if($hadCity == false){
                    $job['city'] = '';
                }
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $jobs[$j++] = $job;
            }

            $i++;
        }
        if($jobs){
            $record['work_begin'] = $jobs[count($jobs)-1]['start_time'];
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
        //vde($data);
        while($i < $length) {
            $hadmajor = false;
            $hadmaschool = false;
            $haddegree = false;
            if(preg_match('/^(\d{4})\D+((\d{4})?|至今|现在)/', $data[$i], $match)) {
                $edu['start_time'] = Utility::str2time($match[1].'-9');
                $edu['end_time'] = Utility::str2time($match[2].'-6');
                for($k = $i-5;$k<$i;$k++){
                    if(preg_match('/学位/s', $data[$k])) {
                        if($haddegree==true){//公司名只取第一个再次匹配到则是下一个工作经历
                            break;
                        }
                        $edu['degree'] = $data[$k+1];
                        $edu['school'] = $data[$k-1];
                        $haddegree = true;
                        $hadmaschool = true;
                    }
                    if(preg_match('/专业/s', $data[$k])) {
                        if($hadmajor==true){//公司名只取第一个再次匹配到则是下一个工作经历
                            break;
                        }
                        $edu['major'] = $data[$k+1];
                        if($hadmaschool==false){
                            $edu['school'] = $data[$k-1];
                        }
                        $hadmajor = true;
                    }
                }
                $education[$j++] = $edu;
                $edu = array();
            }
            $i++;
        }
        if($education){
            $record['major'] = $education[0]['major'];
            $record['school'] = $education[0]['school'];
            $record['degree'] = $education[0]['degree'];
        }
        $record['education'] = $education;
        return $education;
    }
    public function getBaseInfo($content,$data,&$record){
        $arr = array();
        $patten_name = '/<h1 class="pv-top-card-section__name Sans-26px-black-85%">\s*(.*?)\s*<\/h1>/';
        preg_match_all($patten_name, $content, $arr);
        $record['name'] = $arr[1][0];
        $arr = array();
        $patten_last_position = '/<h2 class="pv-top-card-section__headline mt1 Sans-19px-black-85%">\s*(.*?)\s*<\/h2>/';
        preg_match_all($patten_last_position, $content, $arr);
        if($arr[1][0]){
            $arr[1][0] = str_replace(' ','',$arr[1][0]);
            $company_position = explode('-',$arr[1][0]);
            $record['last_company'] = $company_position[0];
            $record['last_position'] = $company_position[1];
        }
        $arr = array();
        $patten_last_city = '/<h3 class="pv-top-card-section__location Sans-17px-black-55%-dense mt1 inline-block">\s*(.*?)\s*<\/h3>/';
        preg_match_all($patten_last_city, $content, $arr);
        $record['city'] = $arr[1][0];
//        $arr = array();
//        $patten_last_evaluation = '/<p id="ember.*\s*(.*?)\s*<\/p>';
//        preg_match_all($patten_last_evaluation, $content, $arr);
//        $record['evaluation'] = $arr[1][0];
    }
}
