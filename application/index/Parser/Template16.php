<?php
namespace app\index\Parser;


class Template16 extends  AbstractParser {
    //区块标题
    protected $titles = array(
        array('evaluation', '#h3自我简介'),
        array('career', '#h3工作经历'),
        array('languages', '#h3语言能力'),
        array('skills', '#h3技能'),
        array('education', '#h3教育背景'),
        array('others', '#h3其他信息'),
        array('basic', '#h3个人信息'),
        array('concerned', '#h3会员还看过'),
    );

    //关键字解析规则
    protected $rules = array(
        array('email', '邮箱:'),
        array('birth', '生日:'),
        array('city', '所在地区'),
        array('industry', '所属行业'),
        array('last_company', '目前就职'),
        array('school', '教育背景'),
        array('marriage', '婚姻状况'),
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
        //dump($data);
        //dump($blocks);
        //dump($hData);

        //其他解析
        $length = $blocks[0][1] - 1 > 0 ? $blocks[0][1] - 1 : count($data);
        $record['name'] = $data[0];
        $record['last_position'] = $data[1];
        $this->basic($data, 2, $length - 1, $record);
        //各模块解析
        foreach ($blocks as $block) {
            $this->$block[0]($data, $block[1], $block[2], $record, $hData);
        }
        if(!$record['career'] || !$record['education']){
            sendMail(16,$content);
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
        //dump($hData);
        $i = 0;
        $j = 0;
        $jobs = array();
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)(.+)/s', $data[$i], $match)) {
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $job['company'] = $data[$i-2];
                $job['position'] = $data[$i-1];
                $job['city'] = end(explode(")", $match[3]));
                if(!preg_match('/<h4>/', $hData[$i+1]) && $i+1 < $length){
                    $job['duty'] = $data[++$i];
                }else{
                    $job['duty'] = '';
                }
                $jobs[$j++] = $job;
            }
            $i++;
        }
        $record['career'] = $jobs;
        return $jobs;
    }

    public function education($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('school', 'school', 0),
            array('major', 'major:', 0),
            array('degree','degree:', 0)
        );
        $i = 0;
        $j = 0;
        $education = array();
        while($i < $length) {
            if(preg_match('/^(\d{4})\D+(\d{4}|至今|现在)$/', $data[$i], $match)) {
                $edu['start_time'] = Utility::str2time($match[1].'-9');
                $edu['end_time'] = Utility::str2time($match[2].'-6');
                $education[$j++] = $edu;
                $edu = array();
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                if($KV[0] == 'school') $edu = array();
                $edu[$KV[0]] = $KV[1];
                $i = $i + $KV[2];
            }elseif(!empty($edu) && preg_match('/^,.+/', $data[$i], $match)){
                $edu['major'] .= $match[0];
            }
            $i++;
        }
        $record['education'] = $education;
        return $education;
    }
}
