<?php

namespace Fbeen\UniqueSlugBundle\Annotation;

use Doctrine\Common\Annotations\AnnotationException;
use Fbeen\UniqueSlugBundle\Validator\Constraints;


/**
 * @Annotation
 * 
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class Slug
{
    private $values;
    
    private $format = NULL;

    public function __construct($options)
    {        
        if(!isset($options['value']))
        {
            throw new AnnotationException("The @Slug annotation must have at least one parameter. e.g. @Slug(\"city\")");
        }

        $this->values = $options['value'];
        
        if(!is_array($this->values))
        {
            $this->values = array($this->values);
        }
        
        if(isset($options['format']))
        {
            $this->format = $options['format'];
        }
    }

    /*
     * returns an array with property or method names
     */
    public function getValues() : array
    {
        return $this->values;
    }

    /*
     * returns a \DateTime format string. e.g. 'Y-m-d' or NULL
     */
    public function getFormat() : ?string
    {
        return $this->format;
    }
}