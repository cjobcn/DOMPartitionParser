<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件
function vde($content){
    dump($content);die;
}
function unescape($str) {

    $str = rawurldecode ( $str );

    preg_match_all ( "/%u.{4}|&#x.{4};|&#\d+;|./U", $str, $r );

    $ar = $r [0];

    foreach ( $ar as $k => $v ) {
        if (substr ( $v, 0, 2 ) == "%u")
            $ar [$k] = iconv ( "UCS-2", "UTF-8", pack ( "H4", substr ( $v, - 4 ) ) );
        elseif (substr ( $v, 0, 3 ) == "&#x")
            $ar [$k] = iconv ( "UCS-2", "UTF-8", pack ( "H4", substr ( $v, 3, - 1 ) ) );
        elseif (substr ( $v, 0, 2 ) == "&#") {
            $ar [$k] = iconv ( "UCS-2", "UTF-8", pack ( "n", substr ( $v, 2, - 1 ) ) );
        }
    }
    return join ( "", $ar );
}
function HtmlToText($str){
    $str =str_replace('<br>',"\n",$str);
    $str =str_replace('<br/>',"\n",$str);
    $str=preg_replace('/<sty(.*)\/style>|<scr(.*)\/script>|<!--(.*)-->/isU',"",$str);//去除CSS样式、JS脚本、HTML注释
    $alltext="";//用于保存TXT文本的变量
    $start=1;//用于检测<左、>右标签的控制开关
    for($i=0;$i<strlen($str);$i++){//遍历经过处理后的字符串中的每一个字符
        if(($start==0)&&($str[$i]==">")){//如果检测到>右标签，则使用$start=1;开启截取功能
            $start=1;
        }else if($start==1){//截取功能
            if($str[$i]=="<"){//如果字符是<左标签，则使用<font color='red'>|</font>替换
                $start=0;
                //$alltext.="<font color='red'>|</font>";
            }else if(ord($str[$i])>10){//如果字符是ASCII大于31的有效字符，则将字符添加到$alltext变量中
                $alltext.=$str[$i];
            }
        }
    }
//下方是去除空格和一些特殊字符的操作
    $alltext = str_replace("　"," ",$alltext);
    //$alltext = preg_replace("/&([^;&]*)(;|&)/","",$alltext);
    $alltext = preg_replace("/[ ]+/s"," ",$alltext);
    return $alltext;
}
function likeDepartment($content){
    if(preg_match('/.+部$/',$content) || preg_match('/.+部门$/',$content) || preg_match('/.+中心$/',$content)){
        return true;
    }
    return false;
}
function str2arr($str, $glue = ','){
    return explode($glue, $str);
}
function sendMail($templateId=0,$content=''){
    $Mail = new \app\index\controller\Mail();
    $mailInfo['to'] = '';
    $mailInfo['title'] = '解析错误';
    $mailInfo['content'] = '文件解析错误，templateId: '.$templateId.',详情：'.$content;
    $Mail->sendMail($mailInfo);
}
function sort_arr_by_field(&$array, $field, $desc = false){
    $fieldArr = array();
    foreach ($array as $k => $v) {
        $fieldArr[$k] = $v[$field];
    }
    $sort = $desc == false ? SORT_ASC : SORT_DESC;
    array_multisort($fieldArr, $sort, $array);
}
function delData(&$data){
    dealEducation($data);
    dealCareer($data);
}
//学校名= 中学 || 高 || 中 结尾 + major 含有 ”高中“
function dealEducation(&$data){
    if($data['education']){
        sort_arr_by_field($data['education'],'start_time',true);
        $blackName = '/高中$|中学$|高$|中$/';
        foreach($data['education'] as $key=>$value){
            if(preg_match($blackName,$value['school']) || preg_match('/高中/',$value['major']) || preg_match('/高中/',$value['degree'])){
                unset($data['education'][$key]);
            }
        }
        $data['education'] = array_merge($data['education']);
    }
}
function dealCareer(&$data){
    if($data['career']){
        sort_arr_by_field($data['career'],'start_time',true);
    }
}
function curl_get($url,$in_cookie,$out_cookie,$headers=''){
    $ch = curl_init();
    //设置选项，包括URL
    foreach( $headers as $n => $v )
    {
        $headerArr[] = $n .':' . $v;
    }
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HEADER, 0);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 0);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_COOKIEJAR, $out_cookie);
    curl_setopt($ch, CURLOPT_COOKIEFILE, $in_cookie);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    //执行并获取HTML文档内容
    if ($headerArr)
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headerArr);
    $output = curl_exec($ch);
    //释放curl句柄
    curl_close($ch);
    return $output;
}