<?php

use \PHPUnit\Framework\TestCase;
class SanityTest extends TestCase
{
    public function testTestEnvironmentIsSetupCorrectly()
    {
        $condition = true;
        $this->assertTrue($condition);
    }
}
?>