<?php
namespace app\index\Parser;

class Template11 extends AbstractParser {
     //区块标题
    protected $titles = array(
        array('target', '求职意向'), 
        array('evaluation', '自我评价'), 
        array('career', '工作经历'),
        array('projects', '项目经历'),
        array('education', '教育经历'), 
        array('practices', '在校实践经验'),
        array('training', '培训经历'), 
        array('certs', '证书'), 
        array('languages', '语言能力'), 
        array('skills', '专业技能'), 
        array('prizes', '获得荣誉'),
        array('others', '附件'),
        array('resume_content', '简历内容')
    );

    //关键字解析规则
    protected $rules = array(
        array('name', '姓名：'),
        array('city', '现居住地：'), 
        array('phone', '手机：'), 
        array('email', 'E-mail：'), 

        array('targte_city', '期望工作地区：'), 
        array('target_salary', '期望月薪：'), 
        array('work_status', '目前状况：'), 
        array('work_properity', '期望工作性质：'), 
        array('target_position', '期望从事职业：'), 
        array('target_industry', '期望从事行业：'),
    );

    //判断模板是否匹配
    protected function isMatched($content) {
        
    }

    //对简历内容预处理,使其可以被解析
    public function preprocess($content) {
        $patterns = array(
            '/<(td|h3|h2|h5)/i',
            '/<\/td|h3|h2|h5>/i',
            '/\||<br.*?>/i',
            '/<div.+?id=\"userName\".+?>/is',
        );
        $replacements = array(
            '<div',
            '</div>',
            '</div><div>',
            '<div>姓名：',
        );
        $content = preg_replace($patterns, $replacements, $content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        //判断内容与模板是否匹配
        //if(!$this->isMatched($content)) return false;
        //预处理
        $content = $this->preprocess($content);

        list($data, $blocks) = $this->domParse($content, 'div');
        //dump($blocks);
        //dump($data);
        if(!$blocks) return false;
        //其他解析
        $i = 0;
        while($i < $blocks[0][1] - 2) {
            if(preg_match('/男|女/', $data[$i], $match)){
                $record['sex'] = $match[0];
                if(preg_match('/(\d{4})\s*年/', $data[$i], $match)) {
                    $record['birth_year'] = $match[1];
                }
                break;
            }
            $i++;
        }
        $this->basic($data, 0 , $blocks[0][1] - 2, $record);
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
        return $this->domParse($content, 'div', true, false);
    }

    public function career($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $rules = array(
            array('nature', '企业性质：'), 
            array('size', '规模：'), 
            array('duty', '工作描述：'), 
        );
        $sequence = array('industry');
        $i = 0;
        $j = 0;
        $jobs = array();
        while($i < $length) {
            //正则匹配
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (.+?) （\d.+?）/', $data[$i], $match)) {
                $job = array();
                $job['start_time'] = Utility::str2time($match[1]);
                $job['end_time'] = Utility::str2time($match[2]);
                $job['company'] = $match[3];
                $jobs[$j++] = $job;
                $k = $i;
            //关键字匹配
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $jobs[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }elseif(preg_match('/元\/月/', $data[$i])){
                $jobs[$j-1]['salary'] = $data[$i];
                $m = $i - 1;
                while($m > $k) {
                    if($jobs[$j-1]['position']){
                        $jobs[$j-1]['department'] = $data[$m];
                    }else{
                        $jobs[$j-1]['position'] = $data[$m];
                    } 
                    $m--;
                }
            }else{
                switch($currentKey) {
                    case 'duty':
                    $jobs[$j-1][$currentKey] .= $data[$i];
                    break;
                    case 'nature':
                    $jobs[$j-1][$currentKey] = $data[$i-1];
                    break;
                }                       
            }
            $i++;
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
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (.+)/', $data[$i], $match)) {
                $edu = array();
                $edu['start_time'] = Utility::str2time($match[1]);
                $edu['end_time'] = Utility::str2time($match[2]);
                $info = explode(' ', $match[3]);
                $edu['school'] = $info[0];
                $edu['major'] = $info[1];
                $edu['degree'] = $info[2];
                $education[$j++] = $edu;
            }
            $i++;
        }
        //dump($education);
        $record['education'] = $education;
        return $education;
    }

    public function projects($data, $start, $end, &$record) {
        $length = $end - $start + 1;
        $data = array_slice($data,$start, $length);
        $i = 0;
        $j = 0;
        $projects = array();
        $rules = array(
            array('duty', '责任描述：' ),
            array('description', '项目描述：'),
        );
        while($i < $length) {
            if(preg_match('/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今) (.+)/', $data[$i], $match)) {
                $project = array();
                $project['start_time'] = Utility::str2time($match[1]);
                $project['end_time'] = Utility::str2time($match[2]);
                $project['name'] = $match[3];
                $projects[$j++] = $project;
            }elseif($KV = $this->parseElement($data, $i, $rules)) {
                $projects[$j-1][$KV[0]] = $KV[1];
                $i = $i + $KV[2];
                $currentKey = $KV[0];
            }else{
                $projects[$j-1][$currentKey] .= $data[$i];             
            }
            $i++;
        }
        $record['projects'] = $projects;
        return $projects;
    }
}
