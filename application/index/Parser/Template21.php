<?php
/**
 * Created by PhpStorm.
 * User: Roy
 * Date: 2018/9/27
 * Time: 17:06
 */
namespace app\index\Parser;
class Template19 extends AbstractParser{
    protected $city = array("北京","上海","广州","深圳","天津","武汉","西安","成都","大连","长春","沈阳","南京","济南","青岛","杭州","苏州","无锡","宁波","重庆","郑州","长沙","福州","厦门","哈尔滨","石家庄","合肥","惠州","太原","昆明","烟台","佛山","南昌","贵阳","广东","湖北","陕西","四川","辽宁","吉林","江苏","山东","浙江","广西","安徽","河北","山西","内蒙古","黑龙江","福建","江西","河南","湖南","海南","贵州","云南","西藏","甘肃","青海","宁夏","新疆","澳门","香港","台湾省","国外");
    protected $cityId = array("530","538","763","765","531","736","854","801","600","613","599","635","702","703","653","639","636","654","551","719","749","681","682","622","565","664","773","576","831","707","768","691","822","548","546","556","552","535","536","539","544","540","549","541","532","533","534","537","542","543","545","547","550","553","554","555","557","558","559","560","562","561","563","480");
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