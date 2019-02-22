<?php

namespace Fbeen\UniqueSlugBundle\Slugifier;

/**
 * Slugifier Interface
 * 
 * Write your own slugifier if you need to e.g. add transliterate functionality.
 * The only prerequist is that you implement this interface and that you define your slugifier as a service
 * Set the 'fbeen_unique_slug.slugifier_class' container parameter to your service id. (See Configuration)
 * 
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
interface SlugifierInterface
{
    /*
     * The slugify function must return a string with valid URL characters parsed from the $text parameter.
     * The string can only contain the following characters:
     * 
     * ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-._~:/?#[]@!$&'()*+,;=
     * 
     * Any other character should be avoided or encoded with the percent-encoding (%hh)
     */
    public function slugify($text) : string;
}
