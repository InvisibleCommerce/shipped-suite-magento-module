<?php
declare(strict_types=1);

namespace InvisibleCommerce\ShippedSuite\Test;

use \PHPUnit\Framework\TestCase;

class SanityTest extends TestCase
{
    public function testTestEnvironmentIsSetupCorrectly()
    {
        $condition = true;
        $this->assertTrue($condition);
    }
}