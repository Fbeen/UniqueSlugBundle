<?php
 
namespace Fbeen\UniqueSlugBundle\Listener;
 
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\Common\Annotations\AnnotationReader;
use Doctrine\Common\Annotations\AnnotationException;
use Fbeen\UniqueSlugBundle\Custom\Slugifier;
use Fbeen\UniqueSlugBundle\Annotation\Slug as SlugAnnotation;
 
class SlugUpdater
{
    private $supportedTypes;
    
    public function __construct() {
        $this->supportedTypes = array('string', 'integer', 'smallint', 'bigint', 'float', 'decimal', 'date', 'time', 'datetime');
    }
    
    public function prePersist(LifecycleEventArgs $args)
    {
        $this->preUpdate($args);
    }
    
    public function preUpdate(LifecycleEventArgs $args)
    {
        foreach($this->getSlugProperties($args) as $property)
        {
            $this->updateSlug($args, $property);
        }
    }
    
    private function getSlugProperties(LifecycleEventArgs $args)
    {
        $slugProperties = array();
        
        $reader = new AnnotationReader();
        $reflectionObject = new \ReflectionObject($args->getEntity());
      
        foreach ($reflectionObject->getProperties() as $property)
        {
            $slugAnnotation = $reader->getPropertyAnnotation($property, 'Fbeen\UniqueSlugBundle\Annotation\Slug');

            if(null !== $slugAnnotation)
            {
                $this->validateAnnotation($args, $property, $slugAnnotation);

                $slugProperties[] = $property;
            }
        }
        
        return $slugProperties;
    }
    
    private function validateAnnotation(LifecycleEventArgs $args, \ReflectionProperty $property, SlugAnnotation $slugAnnotation)
    {
        $metadata = $args->getEntityManager()->getClassMetadata(get_class($args->getEntity()));
        
        $slugMapping = $metadata->getFieldMapping($property->getName());
        
        foreach($slugAnnotation->getValues() as $value)
        {
            if(!$metadata->hasField($value))
            {
                throw new AnnotationException("The entity '" . get_class($args->getEntity()) . "' has no property '" . $value . "'. Check the parameters in the @Slug annotation.");
            }
            
            $mapping = $metadata->getFieldMapping($value);

            if(!in_array($mapping['type'], $this->supportedTypes))
            {
                throw new AnnotationException("Column '" . $mapping['columnName'] . "' has type '" . $mapping['type'] . "' while only '" . implode("', '", $this->supportedTypes) . "' are supported for the @Slug annotation in the " . get_class($args->getEntity()) . " entity.");
            }

        }

        if($slugMapping['length'] < 20 || $slugMapping['type'] != 'string')
        {
            throw new AnnotationException("The '" . $slugMapping['columnName'] . "' field must be type string and have a minimum length of 20 in the " . get_class($args->getEntity()) . " entity.");
        }
    }
    
    private function updateSlug(LifecycleEventArgs $args, \ReflectionProperty $property)
    {
        $reader = new AnnotationReader();
        $reflectionObject = new \ReflectionObject($args->getEntity());
        
        $slugAnnotation = $reader->getPropertyAnnotation($property, 'Fbeen\UniqueSlugBundle\Annotation\Slug');
        $metadata = $args->getEntityManager()->getClassMetadata(get_class($args->getEntity()));
        $slugMapping = $metadata->getFieldMapping($property->getName());

        // retrieve the values of the properties entered in the slug annotation
        foreach($slugAnnotation->getValues() as $propertyName)
        {
            $text[] = $this->retrievePropertyValue($args, $propertyName, $reflectionObject, $slugAnnotation->getFormat());
        }

        $slugifier = new Slugifier($metadata->getTableName(), $slugMapping['columnName'], $slugMapping['length'], $args->getEntityManager());

        $property->setAccessible(TRUE);
        $property->setValue($args->getEntity(), $slugifier->generateSlug(implode('-', $text), $property->getValue($args->getEntity())));

    }
    
    private function retrievePropertyValue(LifecycleEventArgs $args, $propertyName, $reflectionObject, $format)
    {
        $type = $args->getEntityManager()->getClassMetadata(get_class($args->getEntity()))->getTypeOfField($propertyName);
        
        $prop = $reflectionObject->getProperty($propertyName);
        $prop->setAccessible(TRUE);

        if(in_array($type, array('date', 'time', 'datetime')))
        {
            if($format)
            {
                return $prop->getValue($args->getEntity())->format($format);
            }
            
            switch($type)
            {
                case 'date':
                    return $prop->getValue($args->getEntity())->format('d-m-Y');
                case 'time':
                    return $prop->getValue($args->getEntity())->format('H:i:s');
                case 'datetime':
                    return $prop->getValue($args->getEntity())->format('d-m-Y-H:i:s');
            }
        }
        
        return $prop->getValue($args->getEntity());
    }
}