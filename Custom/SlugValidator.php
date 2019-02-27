<?php

namespace Fbeen\UniqueSlugBundle\Custom;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\MappingException;
use Doctrine\Common\Annotations\AnnotationException;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Fbeen\UniqueSlugBundle\Custom\Helper;

/**
 * This class will validate slug data
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class SlugValidator
{
    private $entityManager;
    private $params;
    private $supportedTypes;

    /**
     * Constructor
     * 
     * @param Doctrine\ORM\EntityManagerInterface  $entityManager      The Doctrine entitymanager to use
     * @param int                                  $minimumSlugLength  The minimum length of the slug column in the database
     */
    public function __construct(EntityManagerInterface $entityManager, ParameterBagInterface $params)
    {
        $this->entityManager = $entityManager;
        $this->params = $params;
        $this->supportedTypes = array('string', 'integer', 'smallint', 'bigint', 'float', 'decimal', 'date', 'time', 'datetime');
    }

    /**
     * Validator function. Test if given property names exist in the given entity and if the database-column type is supported to generate slugs from
     * Uses a \Doctrine\Common\Annotations\AnnotationException if validation failes.
     * 
     * @param string  $entityClassname   The fully qualified classname of the entity that has the @slug annotation.
     * @param array   $slugValues        An array with the values from a @Slug annotation
     * @param string  $slugPropertyName  The name of the property that has the @Slug annotation.
     * 
     */
    public function validate(string $entityClassname, array $slugValues, string $slugPropertyName = 'slug') : void
    {
        $metadata = $this->entityManager->getClassMetadata($entityClassname);
        
        if(!count($slugValues))
        {
            throw new AnnotationException("The @Slug annotation must have at least one parameter. e.g. @Slug(\"city\")");
        }

        
        foreach($slugValues as $value)
        {
            if(!Helper::publicMethodExists($entityClassname, $value)) 
            {
                if(!$metadata->hasField($value))
                {
                    throw new AnnotationException("The entity '" . $entityClassname . "' has no property '" . $value . "'. Check the parameters in the @Slug annotation.");
                }
                $mapping = $metadata->getFieldMapping($value);
                if(!in_array($mapping['type'], $this->supportedTypes))
                {
                    throw new AnnotationException("Column '" . $mapping['columnName'] . "' has type '" . $mapping['type'] . "' while only '" . implode("', '", $this->supportedTypes) . "' are supported for the @Slug annotation in the " . $entityClassname . " entity.");
                }
            }
        }
        
        // in case we want to generate the slug property it is not yet in the entity and we will skip this last part of the validate function
        try {
            $slugMapping = $metadata->getFieldMapping($slugPropertyName);
        } catch (MappingException $ex) {
            return;
        }
        
        if($slugMapping['length'] < $this->params->get('fbeen_unique_slug.minimum_slug_length') || $slugMapping['type'] != 'string')
        {
            throw new AnnotationException("The '" . $slugMapping['columnName'] . "' field must be type string and have a minimum length of " . $this->params->get('fbeen_unique_slug.minimum_slug_length') . " in the " . $entityClassname . " entity.");
        }
    }
}