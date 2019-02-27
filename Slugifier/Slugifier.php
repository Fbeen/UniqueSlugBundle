<?php

namespace Fbeen\UniqueSlugBundle\Slugifier;

/**
 * This is the slugifier that this bundle will use by default but you can also implement your own slugifier.
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class Slugifier implements SlugifierInterface
{
    /**
     * Returns a URL valid slug from any string
     * 
     * @param string $text The string to slugify.
     * 
     * @return string
     */
    public function slugify(?string $text) : string
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        //NO LATIN CONVERSION: $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
        $text = \transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove', $text);

        // lowercase
        $text = strtolower($text);

        // remove unwanted characters
        $text = preg_replace('~[^-\w]+~', '', $text);

        if (empty($text))
        {
          return 'n-a';
        }

        return $text;
    }
}
