<?php
namespace app\index\Parser;

class ResumeParser {
    /**
     * 模板序号对应的匹配正则，采用顺序匹配
     * @var array
     */
    public $templateIDs = array(
        '14' => '/121\.41\.112\.72\:12885/',
        '05' => '/简历编号(:|：)\d{8}\|猎聘通/',                         //猎聘网
        '01' => '/简历编号(：|: )\d{1,8}[^\d\|]/',                       //猎聘网
        '02' => '/<title>基本信息_个人资料_会员中心_猎聘猎头网<\/title>/',  //猎聘编辑修改页面
        '03' => '/<title>我的简历<\/title>.+?<div class="index">/s',     //可能是智联招聘
        '04' => '/\(编号:J\d{7}\)的简历/i',                              //中国人才热线
        '06' => '/<title>.+?举贤网.+?<\/title>/i',                       //举贤网
        '07' => '/<span>\s*编号\s+\d{16}\D/',                            //中华英才
        '08' => '/<div class="flt_r">简历编号：\d{16}/',                  //中华英才网
        '09' => '/\(ID:\d{1,}\)|(51job\.com|^简_历|简历).+?基 本 信 息|个 人 简 历<\/b>/s',  //51job(前程无忧)
        '10' => '/<span[^>]*>智联招聘<\/span>|<div class="zpResumeS">/i',                   //智联招聘
        '11' => '/<div (id="userName" )?class="main-title-fl fc6699cc"/',                  //智联招聘
        '13' => '/<title>\s*简历ID：\d{1,}\s*<\/title>.+?51job|ID:\d{1,}\s*<\/td>|target="_blank">进入HOUR<\/a><\/div>/s',      //新版51job
        '12' => '/来源ID:[\d\w]+<br>/',                                //已被处理过的简历
        '15' => '/中文简历_\d+/',
        '18' => "/LinkedIn领英|邀请.*成为领英好友|<header class=\"pv-contact-info__header Sans-17px-black-85%-semibold-dense\">已成为好友<\/header>/",        //linkedin新简历
        '16' => '/www\.linkedin\.com/',        //linkedin老简历
        '17' => '/简历编号：<span data-nick="res_id">(\w+|<\/span>)/',//猎聘网
        '19' => '/"uinfo":/',//脉脉json
        '20' => '/div id="resume-detail-wrapper" class="resume-detail--bordered">/',//智联20180926版
    );

    /**
     * 判断是否为英文简历
     * @param $content string 简历内容
     * @return bool
     */
    public function isEnglish($content) {
        if(preg_match('/The latest work|The highest education|Career Objective|Self-Assessment|Work Experiences/i', $content) &&
            !preg_match('/最近工作|最高学历|工作经|自我评价/', $content)) {
            return true;
        }
        return false;
    }

    /**
     * 判断简历是否无效
     * @param $content
     * @return bool
     */
    public function isInvalid($content) {
        $invalidPatterns = array(
            '该简历已被求职者删除，无法查看!',  //简历已被删除
//            '^\{\"',                        //json格式
        );
        $pattern = '/'.implode('|', $invalidPatterns).'/';
        if(preg_match($pattern, $content)){
            return true;
        }
        return false;
    }

    /**
     * 读取文档(windows下需要将路径转为GBK)
     * @param $path
     * @return bool|string
     */
    public function readDocument($path) {
        return Utility::readDocument($path);
    }

    //转码为UTF-8
    public function convert2UTF8($content) {
        return Utility::convert2UTF8($content);
    }

    /**
     * 获取模板ID
     * @param $resume
     * @return int|string
     */
    public function getTemplateID($resume) {
        foreach($this->templateIDs as $id => $pattern){
            if(preg_match($pattern, $resume)){
                return strval($id);
            }
        }
        return false;
    }

    /**
     * 获取模板类
     * @param $resume
     * @param string $namespace
     * @return string
     */
    public function getTemplateClass($resume, $namespace = __NAMESPACE__) {
        foreach($this->templateIDs as $id => $pattern){
            if(preg_match($pattern, $resume)){
                $templateClass = $namespace.'\Template'.$id;
                return $templateClass;
            }
        }
        return false;
    }

    /**
     * 解析简历
     * @param $resume string 简历内容
     * @param $templateId string  简历模板编号
     * @return mixed
     */
    public function parse($resume, &$templateId = '') {
        //排除英文简历和不合法的简历
        if($this->isEnglish($resume) || $this->isInvalid($resume)) return false;
        //配对简历模板
        $namespace = __NAMESPACE__;
        if(preg_match('/^\{\"/',$resume)){//json格式走19模板
            $templateId = 19;
        }else{
            $templateId = $this->getTemplateID($resume);
        }
        if($templateId)
            $templateClass = $namespace.'\Template'.$templateId;
        //dump($templateClass);
        $record = null;
        if(isset($templateClass)){
            $template = new $templateClass();
            $record = $template->parse($resume);
            if($record){
                $Converter = new DataConverter();
                $Converter->multiConvert($record);
            }
        }
        return $record;
    }

    /**
     * 获取DOM数组(pregParse或domParse获得)
     * @param $resume
     * @return mixed
     */
    public function getDomArray($resume) {
        $templateClass = $this->getTemplateClass($resume);
        $data = null;
        if($templateClass){
            $template = new $templateClass();
            $data = $template->getDomArray($resume);
        }

        return $data;
    }

    /**
     * 获取正则分割后的数组
     * @param $resume
     * @return array
     */
    public function getPregArray($resume) {
        $Parser = new CommonParser();
        $resume = $Parser->preprocess($resume);
        //dump($resume);
        $data = $Parser->pregParse($resume, false, false);
        return $data;
    }
}
