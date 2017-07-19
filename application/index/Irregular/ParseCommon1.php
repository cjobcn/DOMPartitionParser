<?php
/**
 * Created by PhpStorm.
 * User: hanchong
 * Date: 2017/5/23
 * Time: 10:09
 */
namespace app\index\Irregular;
use think\Db;
class ParseCommon1 extends AbstractParser {
    protected $project = "";
    //区块标题
    protected $titles = array(
        array('career', '^工作经历|主要经历和职位|^工作经验$'),
        array('education', '教育经历|教育水平|教育$|学历背景|教育背景|^教育培训：$'),
        array('cultivation', '培训$|培训经历'),
        array('languages', '语言能力'),
        array('evaluation', '自我评价|个人特点|个人特长'),
        array('addition', '附加信息'),
        array('skills', '其它技能|其他$|其他：$'),
        array('summary', '个人总结：'),
    );

    //关键字解析规则
//    protected $rules = array(
//        array('name', '姓名：'),
//        array('sex', '性别：'),
//        array('email', '电子邮件：'),
//        array('birth_year', '出生年份：'),
//        array('phone', '手机号码：'),
//        array('city', '所在地区：|籍贯：'),
//        array('marriage', '婚姻状况：'),
//        array('work_begin', '参加工作年份：'),
//        array('industry', '目前所在行业：|目前岗位'),
//        array('last_company', '公司名称：'),
//        array('last_position', '目前职位名称：'),
//        array('degree', '最高学历：'),
//        array('current_salary', '目前年薪：'),
//        array('target_salary', '期望月薪：'),
//        array('target_industry', '期望行业：'),
//        array('target_position', '期望职位：'),
//        array('target_city', '期望地点：|意向地区'),
//    );

    //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $patterns = array(
            '/<(td|h3|h2|h5|h1|span)/i',
            '/<\/(td|h3|h2|h5|h1|span)>/i',
            '/\||<br.*?>/i',
        );
        $replacements = array(
            '<div',
            '</div>',
            '</div><div>',
        );
        $content = preg_replace($patterns, $replacements, $content);
        //$content = $this->removeJsCss($content);
        return $content;
    }
    /**去除JSCSS标签
     * @param $str
     * @return mixed
     */
    public function removeJsCss($document){
        $search = array ("'<script[^>]*?>.*?</script>'si", // 去掉 javascript
            "'<style[^>]*?>.*?</style>'si", // 去掉 css
            "'<[/!]*?[^<>]*?>'si", // 去掉 HTML 标记
            "'<!--[/!]*?[^<>]*?>'si", // 去掉 注释标记
            "'([rn])[s]+'", // 去掉空白字符
            "'&(quot|#34);'i", // 替换 HTML 实体
            "'&(amp|#38);'i",
            "'&(lt|#60);'i",
            "'&(gt|#62);'i",
            "'&(nbsp|#160);'i",
            "'&(iexcl|#161);'i",
            "'&(cent|#162);'i",
            "'&(pound|#163);'i",
            "'&(copy|#169);'i",
            "'&#(d+);'e"); // 作为 PHP 代码运行

        $replace = array (" ",
            " ",
            " ",
            " ",
            "\1",
            "\"",
            "&",
            "<",
            ">",
            " ",
            chr(161),
            chr(162),
            chr(163),
            chr(169),
            "chr(\1)");
        $conent = preg_replace($search, $replace, $document);
        //将全角空格(E38080)和UTF8空格(C2A0)替换成半角空方
        //$conent = str_replace(array(chr(194).chr(160),'　'),' ',$conent);
        $conent = preg_replace('/\s+/',' ',$conent);
        $conent = trim($conent);
        return $conent;
    }

    //根据模板解析简历
    public function parse($originContent) {
        $record = array();
        //预处理
        $content = $this->preprocess($originContent);
        list($data, $blocks) = $this->domParse($content, 'div');
        if(!$blocks){
            list($data, $blocks) = $this->pregParse($content);
        }
        $career=false;
        foreach($blocks as $key=>$value){
            if($value[0]=='career'){
                $career = true;
                break;
            }
        }
        if($career==false){
            return false;
        }
        $end = $blocks[0][1]-2?:count($data)-1;
        //其他解析

        $partitionParse = new PartitionParse();
        $content2 = $this->removeJsCss($originContent);
        $record = $partitionParse->parse($content2);
        //各模块解析
        foreach($blocks as $block){
            $this->$block[0]($data, $block[1], $block[2],$record);
        }
        $project = $this->project;
        if($project){
            $this->project($project,$record);
        }
        //return $record;
        $Pased = false;
        if(($record['phone']||$record['email'])&&$record['career']){
            $Pased = true;
        }
        if($Pased==true)
            return $record;
        else
            return null;
    }

    //获取DOM数组
    public function getDomArray($content) {
        $content = $this->preprocess($content);
        return $this->domParse($content, 'div', true, false);
    }

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $career = "";
        while($i <= $length){
            $career = $career.'<br/>'.$data[$i++];
        }
        $record['career'] = $this->careerDetail($career);
        if($record['career']){
            $record['last_company'] = $record['career'][0]['company'];
            $record['last_position'] = $record['career'][0]['position'];
        }
        //return $record;
    }
    public function careerDetail($career){
        //提取工作经历列表
        $partitionParse = new PartitionParse();
        //$experiencesList = $partitionParse->getExperiencesForW($career);
        $experiencesList = $partitionParse->getWorkExperiences($career);
        foreach($experiencesList as $key=>$value){
            if($value['projectExperiences']){
                foreach($value['projectExperiences'] as $key1=>$value1) {
                    $projectExperiences[] = $partitionParse->getWorkExperiences($value1);;
                }
            }
            unset($experiencesList[$key]['projectExperiences']);
        }
        if($projectExperiences){
            $this->project = $projectExperiences;
        }
        return $experiencesList;
    }
    //项目经历
//    public function project($project,&$record){
//        $partitionParse = new PartitionParse();
//        $record['project'] = $partitionParse->getProjectExperiences($project);
//    }
    public function project($projectExperiences,&$record){
        $partitionParse = new PartitionParse();
        foreach($projectExperiences as $key=>$value){
            $projectExp[$key]['description'] = $value;
            $projectExp[$key]['content'] = $value;
            $projectExp[$key]['position'] = $partitionParse->getPosition($value);
        }
        $partitionParse->fundTime($projectExp);
        $record['projects'] = $projectExp;
    }
    //将教育经历拼接成一段
    public function education($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $educations = "";
        while($i <= $length){
            //$educations .= $data[$i++];
            $educations = $educations.'<br/>'.$data[$i++];
        }
        $record['education'] = $this->edducationDetail($educations);
        if($record['education']){
            $record['school'] = $record['education'][0]['school'];
            $record['major'] = $record['education'][0]['major'];
            $record['degree'] = $record['education'][0]['firstDegree'];
        }
        //return $record;
    }
    //提取教育信息
    public function edducationDetail($educations){
        $partitionParse = new PartitionParse();
        $experiencesList = $partitionParse->getExperiencesForEdu($educations,$educations);
        return $experiencesList;
    }
//    public function projects($data, $start, $end, &$record) {
//        $length = $end - $start + 1;
//        $data = array_slice($data,$start, $length);
//        $rules = array(
//            array('position', '项目职务：'),
//            array('description', '项目描述：'),
//            array('duty', '项目职责：'),
//            array('performance', '项目业绩：'),
//        );
//        $sequence = array('name');
//        $i = 0;
//        $j = 0;
//        $k = 0;
//        $projects = array();
//        while($i < $length) {
//            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)/', $data[$i], $match)){
//                $project = array();
//                $project['start_time'] = Utility::str2time($match[1]);
//                $project['end_time'] = Utility::str2time($match[2]);
//                $projects[$j++] = $project;
//                $k = 1;
//            }elseif($KV = $this->parseElement($data, $i, $rules)) {
//                $projects[$j-1][$KV[0]] = $KV[1];
//                $i = $i + $KV[2];
//            }elseif($k > 0){
//                if($key = $sequence[$k-1]){
//                    $projects[$j-1][$key] = $data[$i];
//                    $k++;
//                }else{
//                    $k = 0;
//                }
//            }
//            $i++;
//        }
//        $record['projects'] = $projects;
//        return $projects;
//    }
}