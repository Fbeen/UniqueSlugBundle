# FbeenUniqueSlugBundle

With this bundle you will be able to automatic generate unique slugs inside your entities by simply add a @Slug annotation to a specified field.

### Features include:

* very simple installation
* generate unique slugs from one or more properties in the same entity
* automatic storage into the database
* making the slug unique by adding a digit when needed
* add or check slugs in existing tables with the command prompt
* support for date, time and datetime fields with custom format

## Installation

Using composer:

Add `"fbeen/uniqueslugbundle": "dev-master"` to the require section of your composer.json project file.

```
    "require": {
        ...
 	"fbeen/uniqueslugbundle": "dev-master"
    },
```

run composer update:

    $ composer update

## Adding Slug behavior to your entity

Suppose that you have a Newsitem entity to display some news on your website.
You might have a entity like this:
`<?php

namespace AppBundle\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fbeen\UniqueSlugBundle\Annotation\Slug;

/**
 * Newsitem
 *
 * @ORM\Table()
 * @ORM\Entity
 */
class Newsitem
{
    /**
     * @var integer
     *
     * @ORM\Column(name="id", type="integer")
     * @ORM\Id
     * @ORM\GeneratedValue(strategy="AUTO")
     */
    private $id;

    /**
     * @var string
     *
     * @ORM\Column(name="title", type="string", length=245)
     */
    private $title;

    /**
     * @var string
     * 
     * @Slug("title")
     *
     * @ORM\Column(name="slug", type="string", length=255, unique=true)
     */
    private $slug;

    /**
     * @var string
     *
     * @ORM\Column(name="body", type="text")
     */
    private $body;

    /**
     * @var \DateTime
     *
     * @ORM\Column(name="created", type="datetime")
     */
    private $created;

    public function __construct()
    {
        $this->created = new \DateTime();
    }
}`

Important notes about this example:

* Do not forget the `use Fbeen\UniqueSlugBundle\Annotation\Slug;`
* Add a `@Slug("Title")` annotation to the slug property to tell the application it should create a slug from the $title property
* Add a constructor that sets the $created property to the current date and time
* use the command `php app/console doctrine:generate:entities AppBundle:Newsitem` to add the getters and setters
* use the command `php app/console doctrine:schema:update --force` to update the database


