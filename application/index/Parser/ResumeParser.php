<?php
namespace app\index\Parser;

class ResumeParser {

    protected $templateIDs = array(
        '01' => '/简历编号：\d{8}/',                //猎聘网
        '02' => '/<title>基本信息_个人资料_会员中心_猎聘猎头网<\/title>/',  
        '03' => '/<title>我的简历<\/title>/',  
        '04' => '/\(编号:J\d{7}\)的简历/i',                   //中国人才热线
        '05' => '/猎聘网logo/',                              //猎聘网
        '06' => '/<title>.+?举贤网.+?<\/title>/i',            //举贤网
        '07' => '/编号\s+\d{16}/',                           //中华英才
        '08' => '/简历编号：\d{16}/',
        '09' => '/基 本 信 息|个 人 简 历|\(ID:\d{7,}\)/',     //51job(前程无忧)
        '10' => '/<span[^>]*>智联招聘<\/span>|<div class="zpResumeS">/i',       //智联招聘
        '11' => '/resume-preview-left/i',
        '12' => '/来源ID:([A-Z]{2}\d{9}R\d{11})|<title>简历<\/title>.+?来源ID:(\w*?)(?=<br>)/s',
    );
    /**
     * 读取文档
     * @param $path
     * @return bool|string
     */
    public function readDocument($path) {
        if(!$path) return '';
        $path = iconv("UTF-8", "GBK", $path);
        if(file_exists($path))
            $content = file_get_contents($path);
        else
            $content = '';
        return $content;
    }

    //转码为UTF-8
    public function convert2UTF8($content) {
        $encodingList = array('UTF-8','GBK','GB2312');
        $encoding = mb_detect_encoding($content,$encodingList,true);

        if(!$encoding){
            $content = iconv('UCS-2', "UTF-8", $content);
            if(!$content) return false;       
        }elseif($encoding != 'UTF-8'){
            $content = mb_convert_encoding($content,'UTF-8',$encoding); 
        }
        $content = str_ireplace(array('gb2312', 'gbk'),'UTF-8',$content);
        return $content;
    }

    /**
     * 获取模板ID
     * @param $resume
     * @return int|string
     */
    public function getTemplateID($resume) {
        foreach($this->templateIDs as $id => $pattern){
            if(preg_match($pattern, $resume)){
                return $id;
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
    public function getTemplateClass($resume, $namespace = 'app\index\Parser') {
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
     * @param $resume
     * @return mixed
     */
    public function parse($resume) {
        $templateClass = $this->getTemplateClass($resume);
        $record = null;
        if($templateClass){
            $template = new $templateClass();
            $record = $template->parse($resume);
        }
        return $record;
    }

    /**
     * 获取DOM数组
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
}
