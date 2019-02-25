<?php

namespace Fbeen\UniqueSlugBundle\Custom;

use Doctrine\ORM\EntityManagerInterface;
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
    private $additionalDigits;
    private $minimumSlugLength;
    private $slugifier;


    /**
     * Constructor
     * 
     * @param Fbeen\UniqueSlugBundle\Slugifier\SlugifierInterface  $slugifier         The slugifier to use
     * @param string                                               $tableName         The database tablename from the entity
     * @param string                                               $columnName        The database columnname from the slug property
     * @param int                                                  $fieldlength       The length of the column from the slug property
     * @param Doctrine\ORM\EntityManagerInterface                  $entityManager     The Doctrine entitymanager to use
     * @param int                                                  $additionalDigits  The maximum additional digits to make a slug unique
     * @param int                                                  $minimumSlugLength The minimum length of the slug column in the database
     */
    public function __construct(SlugifierInterface $slugifier, string $tableName, string $columnName, int $fieldlength, EntityManagerInterface $entityManager, int $additionalDigits, int $minimumSlugLength)
    {
        $this->tableName = $tableName;
        $this->columnName = $columnName;
        $this->fieldlength = $fieldlength;
        $this->entityManager = $entityManager;
        $this->additionalDigits = $additionalDigits;
        $this->minimumSlugLength = $minimumSlugLength;
        $this->slugifier = $slugifier;
    }

    /**
     * Let's the slugifier generate a slug, truncate the slug to it maximum possible length and make the slug unique
     * 
     * @param string $text    The text to slugify
     * @param string $oldSlug The current slug in case of an update
     * 
     * @return string The unique slug
     */
    public function generateSlug(string $text, ?string $oldSlug = NULL) : string
    {
        return $this->makeSlugUnique( $this->truncateSlug( $this->slugifier->slugify($text) ), $oldSlug );
    }

    /**
     * Truncates a slug to its maximal possible length regarding the maximal characters that are reserved in the database column and the maximal additional digits
     * 
     * @param string $slug The slug to truncate
     * 
     * @return string The truncated slug
     */
    private function truncateSlug(string $slug) : string
    {
        // truncate slug to fieldlength - $additionalDigits positions for additional number.
        if(strlen($slug) > $this->fieldlength - $this->additionalDigits)
            return substr($slug, 0, $this->fieldlength - $this->additionalDigits);

        return $slug;
    }

    /**
     * Now let's make the given slug unique for this database table
     * 
     * @param string $slug The new slug
     * @param string $oldSlug The Old slug if any
     * 
     * @return string The unique slug
     */
    private function makeSlugUnique(string $slug, ?string $oldSlug) : string
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
        if(null !== $oldSlug && $this->existSlug($oldSlug, $results))
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

    /**
     * Tries to find the same slug in a associative array.
     * 
     * @param string  $slug     The slug to search for
     * @param array   $results  An associative array with the results of query.
     * 
     * @return bool Returns TRUE if the slug is found and otherwise FALSE
     */
    private function existSlug(string $slug, array $results): bool
    {
        foreach($results as $row)
        {
            if($row[$this->columnName] === $slug)
                return TRUE;
        }

        return FALSE;
    }
}
