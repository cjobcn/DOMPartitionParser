<?php
/**
 * Created by PhpStorm.
 * User: Roy
 * Date: 2018/8/22
 * Time: 17:07
 */
namespace app\index\Parser;
class Template19 extends AbstractParser{
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
            $uinfo = $json['data']['uinfo'];
            $data['name'] = $uinfo['realname'];
            $data['target_city'] = $uinfo['city'];
            $data['email'] = $uinfo['email'];
            $data['birth_year'] = $uinfo['birth_year'];
            $data['sex'] = $uinfo['sex'];
            $data['phone'] = $uinfo['phone'];
            $data['current_salary'] = $uinfo['current_salary'];
            $data['target_salary'] = $uinfo['target_salary'];
            if($uinfo['work_exp']){
                foreach($uinfo['work_exp'] as $key=>$value){
                    $data['career'][$key]['company'] = $value['company'];
                    $data['company'][] = $value['company'];
                    $data['position'][] = $value['position'];
                    $data['career'][$key]['start_time'] = Utility::str2time($value['start_date']);
                    $data['career'][$key]['end_time'] = Utility::str2time($value['end_date']?:'至今');
                    $data['career'][$key]['description'] = $value['description'];
                    $data['career'][$key]['position'] = $value['position'];
                    $data['update_time'] = $value['uptime'];
                }
            }
            if($uinfo['education']){
                foreach($uinfo['education'] as $key=>$value){
                    $data['education'][$key]['school'] = $value['school'];
                    $data['education'][$key]['start_time'] = Utility::str2time($value['start_date']);
                    $data['education'][$key]['end_time'] = Utility::str2time($value['end_date']);
                    $data['education'][$key]['degree'] = $this->getDegree($value['degree']);
                    $data['education'][$key]['major'] = $value['department'];
                }
            }
            if($data['career']){//倒序排
                sort_arr_by_field($data['career'],'start_time',true);
            }
            if($data['education']){//倒序排
                sort_arr_by_field($data['education'],'start_time',true);
            }
            $data['projects'] = $uinfo['projects'];
            $data['last_company'] = $data['career'][0]['company'];
            $data['last_position'] = $data['career'][0]['position'];
            $data['major'] = $data['education'][0]['major'];
            $data['degree'] = $data['education'][0]['degree'];
            $data['school'] = $data['education'][0]['school'];
        }
        if(!$data){
            sendMail(19,$content);
        }
        return $data;
    }
    public function getDegree($degree){
        switch($degree){
            case 0:
                $degreeStr = '专科';
                break;
            case 1:
                $degreeStr = '本科';
                break;
            case 2:
                $degreeStr = '硕士';
                break;
            case 3:
                $degreeStr = '博士';
                break;
            case 4:
                $degreeStr = '博士后';
                break;
            case 5:
                $degreeStr = '其他';
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