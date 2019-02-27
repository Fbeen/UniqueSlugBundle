<?php

namespace Fbeen\UniqueSlugBundle\Custom;

use Symfony\Bundle\MakerBundle\Util\ClassDetails;

/**
 * Helper functions
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class Helper {
    /**
     * tests of a given method exists and have public access for a given class
     * 
     * @param object|string  $class   An object instance or a class name
     * @param string         $method  A method name
     * 
     * @return bool TRUE if the method exists and have public access, otherwise FALSE
     */
    public static function publicMethodExists($class, string $method): bool
    {
        if(method_exists($class, $method))
        {
            $reflection = new \ReflectionMethod($class, $method);
            if($reflection->isPublic()) {
                return true;
            }
        }

        return false;
    }
    
    public static function getPathOfClass(string $class): string
    {
        $classDetails = new ClassDetails($class);

        return $classDetails->getPath();
    }
}
