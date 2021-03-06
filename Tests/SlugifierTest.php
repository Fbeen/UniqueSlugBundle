<?php

namespace Fbeen\UniqueSlugBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Fbeen\UniqueSlugBundle\Slugifier\Slugifier;

/**
 * Test the slugifier
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class SlugifierTest extends KernelTestCase
{
    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();
    }

    public function testSlugify()
    {
        $container = self::$container;
        $params = $container->get('parameter_bag');
        $slugifierClass = $params->get('fbeen_unique_slug.slugifier_class');

        $slugifier = $container->get($slugifierClass);
        
        $this->assertTrue($slugifier instanceof \Fbeen\UniqueSlugBundle\Slugifier\SlugifierInterface, 'Slugifier class does not implement Fbeen\UniqueSlugBundle\Slugifier\SlugifierInterface');

        echo "\n\nUsing: " . get_class($slugifier) . "\n\n";
        
        foreach($this->generateTestStrings() as $msg => $text)
        {
            $result = $slugifier->slugify($text);

            echo $msg . ': ' . $result . "\n";

            $this->assertTrue($this->slugValidator($result), 'The slug contains invalid characters!');

            $this->assertNotEmpty($result, 'The slug is an empty string!');
        }
    }
    
    private function slugValidator($slug) : bool
    {
        $validChars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~:/?#[]@!$&'()*+,;=";
        
        for($i = 0 ; $i < strlen($slug) ; $i++)
        {
            if(false === strpos($validChars, $slug[$i]))
            {
                return false;
            }
        }
        
        return true;
    }

    private function generateTestStrings() : array
    {
        $text = '';

        for($i = 0 ; $i < 256 ; $i++)
        {
            $text .= chr($i);
        }
        
        return [
            'All asscii chars   ' => $text,
            'Normal sentence    ' => "just a normal sentence.",
            'All valid url chars' => "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~:/?#[]@!$&'()*+,;=",
            'Letters and digits ' => "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789",
            'Special valid chars' => "-._~:/?#[]@!$&'()*+,;=",
            'Empty string       ' => "",
            'NULL               ' => null,
            'Spaces only        ' => "   ",
            'Escapes            ' => " first\nsecond\tthird\rfourth ",
            'Invalid chars      ' => "éôãìë",
        ];
    }
}
