<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/5/23
 * Time: 22:23
 */

namespace app\index\Parser;

// 项目经历模块解析方法
class BlockProject extends AbstractParser {

    protected $patterns = array(
        1=> '/^(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今|现在)：(.+)/',
        2=> '/(?:时间： )?(\d{4}\D+\d{1,2})\D+(\d{4}\D+\d{1,2}|至今)(月)?$/'
    );

    /**
     * @param array $data      区块dom数组
     * @param string $methods  提取方案序号
     * @return array
     */
    public function parse($data, $methods = '') {
        $projects = array();
        if($methods && is_string($methods)){
            $methods = explode(',', $methods);
        }
        foreach($methods as $method) {
            if(preg_match($this->patterns[$method], $data[0])) {
                $method = 'extract'.$method;
                //dump($method);
                $projects = $this->$method($data);
                break;
            }
        }
        return $projects;
    }

    public function extract1($data) {

    }
}
