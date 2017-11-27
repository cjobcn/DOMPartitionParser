<?php
/**
 * Created by PhpStorm.
 * User: DFFuture
 * Date: 2017/11/14
 * Time: 20:06
 */

namespace tests;


class ParserTest extends TestCase {

    private $templateId = 1;

    public function testFailure() {
        $this->assertJsonStringEqualsJsonString(
            json_encode(array("Mascot" => "Tux", "a" => "c")), json_encode(array("Mascot" => "ux", "a" => "b"))
        );
    }
}