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