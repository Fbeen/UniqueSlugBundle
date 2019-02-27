<?php

namespace Fbeen\UniqueSlugBundle\Custom;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Fbeen\UniqueSlugBundle\Annotation\Slug as SlugAnnotation;
use Fbeen\UniqueSlugBundle\Custom\SlugValidator;

/**
 * This class will determine the @Slug annotations inside the entity and then validate the annotations.
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class SlugAnnotationReader
{
    private $entityManager;
    private $slugValidator;
    private $reader;

    /**
     * Constructor
     * 
     * @param Doctrine\ORM\EntityManagerInterface  $entityManager      The Doctrine entitymanager to use
     */
    public function __construct(EntityManagerInterface $entityManager, SlugValidator $slugValidator)
    {
        $this->entityManager = $entityManager;
        $this->slugValidator = $slugValidator;
        $this->reader = new AnnotationReader();
    }

    /**
     * get an array of \ReflectionProperty objects that have a @Slug annotation
     * 
     * @param object  $entity  Some Entity
     * 
     * @return array an array with \ReflectionProperty objects that have a @Slug annotation
     */
    public function getSlugProperties($entity) : array
    {
        $slugProperties = array();

        $reflectionObject = new \ReflectionObject($entity);

        foreach ($reflectionObject->getProperties() as $slugProperty)
        {
            $slugAnnotation = $this->reader->getPropertyAnnotation($slugProperty, SlugAnnotation::class);

            if(null !== $slugAnnotation)
            {
                $this->slugValidator->validate($reflectionObject->getName(), $slugAnnotation->getValues(), $slugProperty->getName());

                $slugProperties[] = $slugProperty;
            }
        }

        return $slugProperties;
    }

    /**
     * get the data out of the @Slug annotation
     * 
     * @return Fbeen\UniqueSlugBundle\Annotation\Slug Object
     */
    public function getSlugData(\ReflectionProperty $slugProperty) : SlugAnnotation
    {
        return $this->reader->getPropertyAnnotation($slugProperty, SlugAnnotation::class);
    }
}