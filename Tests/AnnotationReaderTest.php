<?php

namespace Fbeen\UniqueSlugBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Fbeen\UniqueSlugBundle\Custom\SlugAnnotationReader;
use Fbeen\UniqueSlugBundle\Tests\Entity\Book;
use Fbeen\UniqueSlugBundle\Annotation\Slug;
use Fbeen\UniqueSlugBundle\Custom\SlugValidator;

/**
 * Test the annotation reader
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class AnnotationReaderTest extends KernelTestCase
{
    private $entityManager;
    private $bookEntity;

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();
        
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        
        $this->bookEntity = new Book();
        $this->bookEntity->setTitle('This is a awesome book!');
    }

    public function testSlugProperties()
    {
        $container = self::$container;
        $slugValidater = new SlugValidator($this->entityManager, $container->get('parameter_bag'));
        $reader = new SlugAnnotationReader($this->entityManager, $slugValidater);
        
        $properties = $reader->getSlugProperties($this->bookEntity);
        
        foreach($properties as $property) {
            
            // Is $property an instance of \ReflectionProperty ?
            $this->assertInstanceOf(\ReflectionProperty::class, $property);
            
            $slugData = $reader->getSlugData($property);
            
            // is $slugData an instance of Fbeen\UniqueSlugBundle\Annotation\Slug ?
            $this->assertInstanceOf(Slug::class, $slugData);
            
            // is $slugData->getValues() an array ?
            $this->assertInternalType('array', $slugData->getValues());
            
            // is $slugData->getFormat() null OR an array ?
            if(null === $slugData->getFormat()) {
                $this->assertNull($slugData->getFormat());                
            } else {
                $this->assertInternalType('string', $slugData->getFormat());
            }
        }
    }
}
