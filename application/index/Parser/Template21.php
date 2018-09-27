<?php
/**
 * Created by PhpStorm.
 * User: Roy
 * Date: 2018/9/27
 * Time: 17:06
 */
namespace app\index\Parser;
class Template19 extends AbstractParser{
    protected $city = array("530"=>"北京","538"=>"上海","763"=>"广州","765"=>"深圳","531"=>"天津","736"=>"武汉",
        "854"=>"西安","801"=>"成都","600"=>"大连","613"=>"长春","599"=>"沈阳","635"=>"南京","702"=>"济南","703"=>"青岛",
        "653"=>"杭州","639"=>"苏州","636"=>"无锡","654"=>"宁波","551"=>"重庆","719"=>"郑州","749"=>"长沙","681"=>"福州",
        "682"=>"厦门","622"=>"哈尔滨","565"=>"石家庄","664"=>"合肥","773"=>"惠州","576"=>"太原","831"=>"昆明","707"=>"烟台",
        "768"=>"佛山","691"=>"南昌","822"=>"贵阳","548"=>"广东","546"=>"湖北","556"=>"陕西","552"=>"四川","535"=>"辽宁",
        "536"=>"吉林","539"=>"江苏","544"=>"山东","540"=>"浙江","549"=>"广西","541"=>"安徽","532"=>"河北","533"=>"山西",
        "534"=>"内蒙古","537"=>"黑龙江","542"=>"福建","543"=>"江西","545"=>"河南","547"=>"湖南","550"=>"海南","553"=>"贵州",
        "554"=>"云南","555"=>"西藏","557"=>"甘肃","558"=>"青海","559"=>"宁夏","560"=>"新疆","562"=>"澳门","561"=>"香港","563"=>"台湾省","480"=>"国外");
    public function parse($content) {
        $jsonStr = preg_replace('/((?<=:)""(?!(,|}|，))|(?<!:)""(?=(,|})))/','"',$content);
        $jsonStr = preg_replace('/\r|\n/','',$jsonStr);
        $json = json_decode($jsonStr,true);
        if(!$json){
            $jsonStr1 = preg_replace('/\s/','',$content);
            $jsonStr1 = preg_replace('/\r|\n/','',$jsonStr1);
            $json = json_decode($jsonStr1,true);
        }
        if($json){
            $this->testFunc($json,$resume);
            $data['resume'] = $resume;
            $uinfo = $json['data'];
            $candidate = $uinfo['candidate'];
            $detail = $uinfo['detail'];
            $data['name'] = $candidate['username'];
            $cityStr = $detail['DesiredCity'];
            $cityArr = explode(',',$cityStr);
            if($cityArr){
                foreach($cityArr as $key=>$value){
                    $cityNameArr[] = $this->city[$value];
                }
                $cityNameStr = implode(',',$cityNameArr);
                $data['target_city'] = $cityNameStr;
            }
            $data['birth_year'] = Utility::str2time($candidate['birthYear'].'/'.$candidate['birthMonth'].'/'.$candidate['birthDay']);
            if($detail['Gender']==1){
                $data['sex'] = '男';
            }elseif($detail['Gender']==2){
                $data['sex'] = '女';
            }
            $data['target_salary'] = $uinfo['target_salary'];
            //$data['target_salary'] = $uinfo['target_salary'];
            if($detail['WorkExperience']){
                foreach($detail['WorkExperience'] as $key=>$value){
                    $data['career'][$key]['company'] = $value['CompanyName'];
                    $data['company'][] = $value['CompanyName'];
                    $data['position'][] = $value['JobTitle'];
                    $data['career'][$key]['start_time'] = Utility::str2time($value['DateStart']);
                    $data['career'][$key]['end_time'] = Utility::str2time($value['DateEnd']?:'至今');
                    $data['career'][$key]['description'] = $value['WorkDescription'];
                    $data['career'][$key]['position'] = $value['JobTitle'];
                }
            }
            if($detail['EducationExperience']){
                foreach($detail['EducationExperience'] as $key=>$value){
                    $data['education'][$key]['school'] = $value['SchoolName'];
                    $data['education'][$key]['start_time'] = Utility::str2time($value['DateStart']);
                    $data['education'][$key]['end_time'] = Utility::str2time($value['DateEnd']);
                    $data['education'][$key]['degree'] = $this->getDegree($value['EducationLevel']);
                    $data['education'][$key]['major'] = $value['MajorName'];
                }
            }
            if($detail['ProjectExperience']){
                foreach($detail['ProjectExperience'] as $key=>$value){
                    $data['projects'][$key]['name'] = $value['ProjectName'];
                    $data['projects'][$key]['start_time'] = Utility::str2time($value['DateStart']);
                    $data['projects'][$key]['end_time'] = Utility::str2time($value['DateEnd']);
                    $data['projects'][$key]['duty'] = $value['ProjectDescription'];
                    $data['projects'][$key]['description'] = $value['ProjectDescription'];
                }
            }
            if($data['career']){//倒序排
                sort_arr_by_field($data['career'],'start_time',true);
            }
            if($data['education']){//倒序排
                sort_arr_by_field($data['education'],'start_time',true);
            }
            $data['last_company'] = $data['career'][0]['company'];
            $data['last_position'] = $detail['CurrentJobTitle'];
            $data['major'] = $data['education'][0]['major'];
            $data['degree'] = $data['education'][count( $data['education'])-1]['degree'];
            $data['school'] = $detail['GraduatedFrom'];
        }
        if(!$data['education'] || !$data['career'] || !$data['name']){
            sendMail(20,$content);
        }
        return $data;
    }
    public function getDegree($degree){
        switch($degree){
            case 0:
                $degreeStr = '未知';
                break;
            case 1:
                $degreeStr = '博士';
                break;
            case 3:
                $degreeStr = '硕士';
                break;
            case 4:
                $degreeStr = '本科';
                break;
            case 5:
                $degreeStr = '大专';
                break;
            case 10:
                $degreeStr = 'MBA';
                break;
            case 11:
                $degreeStr = 'EMBA';
                break;
            case 12:
                $degreeStr = '中专';
                break;
            default:
                $degreeStr = '未知';
                break;
        }
        return $degreeStr;
    }
    public function testFunc($array,&$str){
        foreach ($array as $value){
            if (is_array($value)) {
                $this->testFunc($value,$str);
            } else {
                $str = $str."<br>".$value;
            }
        }
    }
}