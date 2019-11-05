<?php

namespace Fbeen\UniqueSlugBundle\Custom;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Fbeen\UniqueSlugBundle\Slugifier\SlugifierInterface;
use Fbeen\UniqueSlugBundle\Custom\SlugValidator;
use Fbeen\UniqueSlugBundle\Custom\Helper;

/**
 * The values of the entity properties or method will be used to generate a slug. 
 * Once we have the data we will use DoctrineSlugifier to slugify the data and make it unique if it not already was.
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class SlugUpdater
{
    private $slugifier;
    private $entityManager;
    private $params;
    private $supportedTypes;
    private $reader;

    /**
     * Constructor
     * 
     * @param Fbeen\UniqueSlugBundle\Slugifier\SlugifierInterface                       $slugifier      The slugifier to use
     * @param Doctrine\ORM\EntityManagerInterface                                       $entityManager  The Doctrine entitymanager to use
     * @param Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface  $params         Parameterinterface to get parameters from the container
     */
    public function __construct(SlugifierInterface $slugifier, EntityManagerInterface $entityManager, ParameterBagInterface $params, SlugValidator $slugValidator) 
    {
        $this->slugifier = $slugifier;
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->supportedTypes = array('string', 'integer', 'smallint', 'bigint', 'float', 'decimal', 'date', 'time', 'datetime');
        $this->reader = new SlugAnnotationReader($this->entityManager, $slugValidator);

    }

    /**
     * Start updating the slug(s).
     * 
     * @param object  $entity  Some Entity
     */
    public function updateSlugs(object $entity) :void
    {
        foreach($this->reader->getSlugProperties($entity) as $slugProperty)
        {
            $this->updateSlug($entity, $slugProperty);
        }
    }
    
    /**
     * updates one slug for a given entity and a given property with a slug annotation
     * 
     * @param object               $entity        Some Entity
     * @param \ReflectionProperty  $slugProperty  Reflection object of a property that has a slug annotation
     */
    private function updateSlug(object $entity, \ReflectionProperty $slugProperty) : void
    {
        $slugAnnotation = $this->reader->getSlugData($slugProperty);
        $metadata = $this->entityManager->getClassMetadata(get_class($entity));
        $slugMapping = $metadata->getFieldMapping($slugProperty->getName());

        // retrieve the values of the properties entered in the slug annotation
        foreach($slugAnnotation->getValues() as $propertyName)
        {
            $text[] = $this->retrievePropertyValue($entity, $propertyName, $slugAnnotation->getFormat());
        }
        
        // Let's make a unique slug!
        $doctrineSlugifier = new DoctrineSlugifier(
                $this->slugifier, $metadata->getTableName(), 
                $slugMapping['columnName'], 
                $slugMapping['length'], 
                $this->entityManager, 
                $this->params->get('fbeen_unique_slug.maximum_digits'), 
                $this->params->get('fbeen_unique_slug.minimum_slug_length'));
        
        $slugProperty->setAccessible(TRUE);
        $slugProperty->setValue($entity, $doctrineSlugifier->generateSlug(implode('-', $text), $slugProperty->getValue($entity)));
    }

    /**
     * retrieves the value of one property or the return value of one method from a given entity
     * 
     * @param object       $entity        Some Entity
     * @param string       $propertyName  The name of the property or method to get a value from
     * @param string|NULL  $format        The format-string for the \DateTime property or NULL
     * 
     * @return string The (formatted) value of the property OR the result of the method.
     */
    private function retrievePropertyValue(object $entity, string $propertyName, ?string $format) : string
    {
        $reflectionObject = new \ReflectionObject($entity);

        $type = $this->entityManager->getClassMetadata(get_class($entity))->getTypeOfField($propertyName);
        
        if(Helper::publicMethodExists($entity, $propertyName))
        {
            return $entity->$propertyName();
        }

        $prop = $reflectionObject->getProperty($propertyName);
        $prop->setAccessible(TRUE);

        if(in_array($type, array('date', 'time', 'datetime')))
        {
            if($format)
            {
                return $prop->getValue($entity)->format($format);
            }

            switch($type)
            {
                case 'date':
                    return $prop->getValue($entity)->format('d-m-Y');
                case 'time':
                    return $prop->getValue($entity)->format('H:i:s');
                case 'datetime':
                    return $prop->getValue($entity)->format('d-m-Y-H:i:s');
            }
        }

        return $prop->getValue($entity);
    }
}
