<?php

namespace Fbeen\UniqueSlugBundle\Custom;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationException;
use Fbeen\UniqueSlugBundle\Annotation\Slug as SlugAnnotation;

/**
 * This class will determine the @Slug annotations inside the entity and then validate the annotations.
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class SlugAnnotationReader
{
    private $entityManager;
    private $additionalDigits;
    private $minimumSlugLength;
    private $supportedTypes;
    private $reader;

    /**
     * Constructor
     * 
     * @param Doctrine\ORM\EntityManagerInterface  $entityManager      The Doctrine entitymanager to use
     * @param int                                  $additionalDigits   The maximum additional digits to make a slug unique
     * @param int                                  $minimumSlugLength  The minimum length of the slug column in the database
     */
    public function __construct(EntityManagerInterface $entityManager, int $additionalDigits, int $minimumSlugLength)
    {
        $this->entityManager = $entityManager;
        $this->additionalDigits = $additionalDigits;
        $this->minimumSlugLength = $minimumSlugLength;
        $this->supportedTypes = array('string', 'integer', 'smallint', 'bigint', 'float', 'decimal', 'date', 'time', 'datetime');
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

        foreach ($reflectionObject->getProperties() as $property)
        {
            $slugAnnotation = $this->reader->getPropertyAnnotation($property, SlugAnnotation::class);

            if(null !== $slugAnnotation)
            {
                $this->validateAnnotation($entity, $property, $slugAnnotation);

                $slugProperties[] = $property;
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
    

    /**
     * tests of a given method exists and have public access for a given class
     * 
     * @param object|string  $class   An object instance or a class name
     * @param string         $method  A method name
     * 
     * @return bool TRUE if the method exists and have public access, otherwise FALSE
     */
    public function publicMethodExists($class, string $method): bool
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

    /**
     * Validator function. Test if given property names exist in the given entity and if the database-column type is supported to generate slugs from
     * Uses a \Doctrine\Common\Annotations\AnnotationException if validation failes.
     * 
     * @param object                                  $entity          Some Entity
     * @param \ReflectionProperty                     $property        The Reflection-property object of the slug property
     * @param Fbeen\UniqueSlugBundle\Annotation\Slug  $slugAnnotation  The annotation object with the @Slug annotation data
     * 
     */
    private function validateAnnotation($entity, \ReflectionProperty $property, SlugAnnotation $slugAnnotation) : void
    {
        $entityClassname = get_class($entity);
        
        $metadata = $this->entityManager->getClassMetadata($entityClassname);
        $slugMapping = $metadata->getFieldMapping($property->getName());
        foreach($slugAnnotation->getValues() as $value)
        {
            if(!$this->publicMethodExists($entity, $value)) 
            {
                if(!$metadata->hasField($value))
                {
                    throw new AnnotationException("The entity '" . $entityClassname . "' has no property '" . $value . "'. Check the parameters in the @Slug annotation.");
                }
                $mapping = $metadata->getFieldMapping($value);
                if(!in_array($mapping['type'], $this->supportedTypes))
                {
                    throw new AnnotationException("Column '" . $mapping['columnName'] . "' has type '" . $mapping['type'] . "' while only '" . implode("', '", $this->supportedTypes) . "' are supported for the @Slug annotation in the " . get_class($entity) . " entity.");
                }
            }
        }
        
        if($slugMapping['length'] < $this->minimumSlugLength || $slugMapping['type'] != 'string')
        {
            throw new AnnotationException("The '" . $slugMapping['columnName'] . "' field must be type string and have a minimum length of " . $this->minimumSlugLength . " in the " . $entityClassname . " entity.");
        }
    }
}