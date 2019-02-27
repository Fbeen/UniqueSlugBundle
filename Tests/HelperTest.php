<?php

namespace Fbeen\UniqueSlugBundle\Tests;

use PHPUnit\Framework\TestCase;
use Fbeen\UniqueSlugBundle\Custom\Helper;

/**
 * Test the Helper class
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class HelperTest extends TestCase
{
    public function testPublicMethodExists()
    {
        $this->assertTrue(Helper::publicMethodExists($this, 'testPublicMethodExists'));
        $this->assertFalse(Helper::publicMethodExists($this, 'protectedDummy'));
        $this->assertFalse(Helper::publicMethodExists($this, 'privateDummy'));
        $this->assertFalse(Helper::publicMethodExists($this, 'notExistingFunction'));
    }
    
    public function testGetPathOfClass()
    {
        $this->assertNotEmpty(Helper::getPathOfClass('PHPUnit\Framework\TestCase'));
    }
    
    protected function protectedDummy()
    {
        // just to test above conditions
    }
    
    private function privateDummy()
    {
        // just to test above conditions
    }
}
