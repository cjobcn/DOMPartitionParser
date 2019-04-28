<?php
namespace app\index\Parser;

class Template23 extends AbstractParser {
    //模块标题
    protected $titles = array(
        array('career', '工作经历'),
        array('education', '教育经历'),
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
            '/搜索同事/',
            '/<\/font>/'
        );
        $content = preg_replace($redundancy, '', $content);
        return $content;
    }

    //根据模板解析简历
    public function parse($content) {
        $record = array();
        //姓名
        preg_match('/(?<=<h1 class="pv-top-card-section__name inline t-24 t-black t-normal">)[\s\S]*?(?=<\/h1>)/',$content,$name);
        $record['name'] = preg_replace('/\s/','',$name[0]);
        //地址
        preg_match('/(?<=<h3 class="pv-top-card-section__location t-16 t-black--light t-normal mt1 inline-block">)[\s\S]*?(?=<\/h3>)/',$content,$location);
        $record['city'] = preg_replace('/\s/','',$location[0]);
        //电话
        preg_match('/(?<=<section class="pv-contact-info__contact-type ci-phone">)[\s\S]*?(?=<\/section>)/',$content,$phone);
        preg_match('/1[3|4|5|6|7|8][0-9]{9}/',$phone[0],$phone_match);
        $record['phone'] = preg_replace('/\s/','',$phone_match[0]);
        //邮箱
        preg_match('/(?<=<section class="pv-contact-info__contact-type ci-email">)[\s\S]*?(?=<\/section>)/',$content,$email);
        preg_match('/\w+(?:[-+.]\w*)*@\w+(?:[-.]\w+)*\.\w+(?:[-.]\w+)*/',$email[0],$mail_match);
        $record['email'] = $mail_match[0];
        //头像
        preg_match('/(?<=style="background-image:url\(&quot;)[\s\S]*?(?=&quot;)/',$content,$faceImgPath);
        if($faceImgPath[0]){
            $faceImgPath[0] = (htmlspecialchars_decode($faceImgPath[0]));
            $faceImg = curl_get(urldecode($faceImgPath[0]));
            $base64_image = 'data:img;base64,' . (base64_encode($faceImg));
            $record['photo'] = $base64_image;
        }
        //工作经历
        //教育经历
        list($data, $blocks) = $this->pregParse($content, false, true, $this->separators, $hData);
        foreach($blocks as $block){
            $function = $block[0];
            $this->$function($data, $block[1], $block[2],$record, $hData,$content);
        }
        if($record['career']){
            dealCareer($record['career']);
            $record['last_position'] = $record['career'][0]['position'];
            $record['last_company'] = $record['career'][0]['company'];
        }
        //最近职位
//        preg_match('/(?<=<h2 class="pv-top-card-section__headline mt1 t-18 t-black t-normal">)[\s\S]*?(?=<\/h2>)/',$content,$last_position);
//        $record['last_position'] = preg_replace('/\s/','',$last_position[0]);
        if(!$record){
            sendMail(23,$content);
        }
        return $record;
    }
    public function career($data, $start, $end, &$record, $hData,$html) {
        $timePattern = '/(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)/';
        preg_match_all('/<div class="pv-entity__logo company-logo">[\s\S]+?(?=<div class="pv-entity__logo company-logo">|<\/ul>)/',$html,$projects);
        $project_num = 0;
        foreach ($projects[0] as $key=>$value){
            list($project_arr, $project_block) = $this->pregParse($value, false, true, $this->separators, $hData);
            if($project_arr){
                if(preg_match('/职业头衔/',$value)){
                    $company_name = '';
                    foreach ($project_arr as $k1=>$v1){
                        if(preg_match('/公司名称/',$v1)){
                            $company_name = $project_arr[$k1+1];
                            break;
                        }
                    }
                    foreach ($project_arr as $k1=>$v1){
                        if(preg_match('/职业头衔/',$v1)){
                            $project_num++;
                            $record['career'][$project_num]['company'] = $company_name;
                            $record['career'][$project_num]['position'] = $project_arr[$k1+1];
                        }
                        elseif(preg_match('/入职日期/',$v1)){
                            preg_match($timePattern,$project_arr[$k1+1],$times);
                            if($times){
                                $record['career'][$project_num]['start_time'] = Utility::str2time($times[1]);
                                $record['career'][$project_num]['end_time'] = Utility::str2time($times[2]);
                            }
                        }
                        elseif(preg_match('/所在地区/',$v1)){
                            $record['career'][$project_num]['city'] = $project_arr[$k1+1];
                        }
                        if($record['career'][$project_num]['company']){
                            $record['career'][$project_num]['description'] .=  $v1;
                            $record['career'][$project_num]['duty'] .=  $v1;
                        }
                    }
                }else{
                    foreach ($project_arr as $k1=>$v1){
                        if(preg_match('/公司名称/',$v1)){
                            $project_num++;
                            $record['career'][$project_num]['position'] = $project_arr[$k1-1];
                            $record['career'][$project_num]['company'] = $project_arr[$k1+1];
                        }
                        elseif(preg_match('/入职日期/',$v1)){
                            preg_match($timePattern,$project_arr[$k1+1],$times);
                            if($times){
                                $record['career'][$project_num]['start_time'] = Utility::str2time($times[1]);
                                $record['career'][$project_num]['end_time'] = Utility::str2time($times[2]);
                            }
                        }
                        elseif(preg_match('/所在地区/',$v1)){
                            $record['career'][$project_num]['city'] = $project_arr[$k1+1];
                        }
                        if($record['career'][$project_num]['company']){
                            $record['career'][$project_num]['description'] .=  $v1;
                            $record['career'][$project_num]['duty'] .=  $v1;
                        }
                    }
                }
            }
        }
    }
    public function education($data, $start, $end, &$record,$hData,$html){
        preg_match_all('/<div class="pv-entity__summary-info pv-entity__summary-info--background-section">[\s\S]+?<\/li>/',$html,$educations);
        if($educations[0]){
            foreach($educations[0] as $key=>$value){
                list($education_arr, $education_blocks) = $this->pregParse($value, false, true, $this->separators, $hData);
                $record['education'][$key]['school'] = $education_arr[0];
                foreach ($education_arr as $k=>$v){
                    if(preg_match('/学位/',$v) && !preg_match('/专业/',$education_arr[$k+1])){
                        $record['education'][$key]['degree'] = $education_arr[$k+1];
                    }
                    if(preg_match('/专业/',$v) && !preg_match('/时间/',$education_arr[$k+1])){
                        $record['education'][$key]['major'] = $education_arr[$k+1];
                    }
                    if(preg_match('/时间/',$v)){
                        preg_match('/\d+/',$education_arr[$k+1],$start_time);
                        preg_match('/\d+/',$education_arr[$k+2],$end_time);
                        if($start_time[0]){
                            if(strlen($start_time[0])==4){
                                $start_time[0] = $start_time[0].'/09';
                            }
                            //vde($start_time);
                            $record['education'][$key]['start_time'] = Utility::str2time($start_time[0]);
                        }
                        if($end_time[0]){
                            if(strlen($end_time[0])==4){
                                $end_time[0] = $end_time[0].'/06';
                            }
                            $record['education'][$key]['end_time'] = Utility::str2time($end_time[0]);
                        }
                        break;
                    }
                }
            }
        }
    }

}
