<?php

namespace Fbeen\UniqueSlugBundle\Custom;

/**
 * Description of Slugifier
 *
 * @author Frank Beentjes
 */
class Slugifier
{
    private $tableName;
    private $columnName;
    private $fieldlength;
    private $entityManager;
    private $additionalChars;


    public function __construct($tableName, $columnName, $fieldlength, $entityManager, $additionalChars = 10)
    {
        $this->tableName = $tableName;
        $this->columnName = $columnName;
        $this->fieldlength = $fieldlength;
        $this->entityManager = $entityManager;
        $this->additionalChars = $additionalChars;
    }

    public function generateSlug($text, $oldSlug = NULL, $transliterate)
    {
        return $this->makeSlugUnique( $this->truncateSlug( $this->Slugify($text, $transliterate) ), $oldSlug );;
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

    private function Slugify($text, $transliterate)
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate
        switch ($transliterate) {
            case 'remove':
                $text = iconv('utf-8', 'us-ascii//TRANSLIT', $text);
                break;
            case 'keep':
                $text = transliterator_transliterate('Any-Latin; Latin-ASCII; [\u0100-\u7fff] remove', $text);
                break;
        }


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
