<?php

namespace Fbeen\UniqueSlugBundle\Tests;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Fbeen\UniqueSlugBundle\Custom\SlugAnnotationReader;
use Fbeen\UniqueSlugBundle\Tests\Entity;
use Fbeen\UniqueSlugBundle\Custom\SlugValidator;

/**
 * Test the annotation reader
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class SlugValidatorTest extends KernelTestCase
{
    private $entityManager;
    private $failureEntity;  // not existing property or method
    private $failure2Entity; // unsupported type boolean
    private $failure3Entity; // empty @Slug annotation

    /**
     * {@inheritDoc}
     */
    protected function setUp()
    {
        $kernel = self::bootKernel();
        
        $this->entityManager = $kernel->getContainer()
            ->get('doctrine')
            ->getManager();
        
        $this->failureEntity = new Entity\Failure();
        $this->failureEntity->setTitle('This is a awesome book!');
        
        $this->failure2Entity = new Entity\Failure2();
        $this->failure2Entity->setTitle('This is a awesome book!');
        
        $this->failure3Entity = new Entity\Failure3();
        $this->failure3Entity->setTitle('This is a awesome book!');
    }

    
    public function testValidateAnnotation()
    {
        $container = self::$container;
        $slugValidater = new SlugValidator($this->entityManager, $container->get('parameter_bag'));
        $reader = new SlugAnnotationReader($this->entityManager, $slugValidater);
        
        $this->expectException(\Doctrine\Common\Annotations\AnnotationException::class);
        $reader->getSlugProperties($this->failureEntity);
        
        $this->expectException(\Doctrine\Common\Annotations\AnnotationException::class);
        $reader->getSlugProperties($this->failure2Entity);
        
        $this->expectException(\Doctrine\Common\Annotations\AnnotationException::class);
        $reader->getSlugProperties($this->failure3Entity);
    }
}
