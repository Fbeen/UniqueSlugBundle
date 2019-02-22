<?php

namespace Fbeen\UniqueSlugBundle\Annotation;

use Doctrine\Common\Annotations\AnnotationException;
/**
 * @Annotation
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

    public function getValues()
    {
        return $this->values;
    }

    public function getFormat()
    {
        return $this->format;
    }
}