<?php

namespace Fbeen\UniqueSlugBundle\Custom;

use Fbeen\UniqueSlugBundle\Slugifier\SlugifierInterface;

/**
 * This class generates unique slugs in the scope of a database table using Doctrine
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class DoctrineSlugifier
{
    private $tableName;
    private $columnName;
    private $fieldlength;
    private $entityManager;
    private $additionalChars;
    private $slugifier;


    public function __construct(SlugifierInterface $slugifier, $tableName, $columnName, $fieldlength, $entityManager, $additionalChars = 10)
    {
        $this->tableName = $tableName;
        $this->columnName = $columnName;
        $this->fieldlength = $fieldlength;
        $this->entityManager = $entityManager;
        $this->additionalChars = $additionalChars;
        $this->slugifier = $slugifier;
    }

    public function generateSlug($text, $oldSlug = NULL)
    {
        if(!$this->slugifier instanceof SlugifierInterface) {
            throw new \Symfony\Component\Validator\Exception\RuntimeException($slugifyClass . ' does not implement Fbeen\UniqueSlugBundle\Slugifier\SlugifierInterface');
        }

        return $this->makeSlugUnique( $this->truncateSlug( $this->slugifier->slugify($text) ), $oldSlug );;
    }

    private function truncateSlug($slug)
    {
        // truncate slug to fieldlength - $additionalChars positions for additional number.
        if(strlen($slug) > $this->fieldlength - $this->additionalChars)
            return substr($slug, 0, $this->fieldlength - $this->additionalChars);

        return $slug;
    }

    private function makeSlugUnique($slug, $oldSlug)
    {
        $i = 0;
        $baseSlug = $slug;
        // SELECT * FROM `article` WHERE slug REGEXP '^nice-slug-[0-9]' OR slug='nice-slug'
        $query = "SELECT " . $this->columnName . " FROM " . $this->tableName .
                 " WHERE " . $this->columnName . "='" . $slug . "'" .
                 " OR " . $this->columnName . " REGEXP '^" . $slug . "-[0-9]'";

        $statement = $this->entityManager->getConnection()->prepare($query);
        $statement->execute();
        $results = $statement->fetchAll();

        // if the old slug (before the update) is in the results then we keep the same slug
        if($this->existSlug($oldSlug, $results))
        {
            return $oldSlug;
        }

        // now find a new unique slug
        while($this->existSlug($slug, $results))
        {
            $i++;
            $slug = $baseSlug . '-' . $i;
        }

        return $slug;
    }

    private function existSlug($slug, $results)
    {
        foreach($results as $row)
        {
            if($row[$this->columnName] == $slug)
                return TRUE;
        }

        return FALSE;
    }
}
