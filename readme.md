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

1) Add `"fbeen/uniqueslugbundle": "dev-master"` to the require section of your composer.json project file.

```
    "require": {
        ...
        "fbeen/uniqueslugbundle": "dev-master"
    },
```

2) run composer update:

    $ composer update

3) Add the bundle to the app/AppKernel.php:
```
        $bundles = array(
            ...
            new Fbeen\UniqueSlugBundle\FbeenUniqueSlugBundle(),
        );
```

## Adding Slug behavior to your entity

Suppose that you have a ***"Newsitem"*** entity to display some news on your website.
You might have a entity like this:
```
<?php

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
}
```

**Important notes about this example:**

* Do not forget the `use Fbeen\UniqueSlugBundle\Annotation\Slug;`
* Add a `@Slug("title")` annotation to the slug property to tell the application it should create a slug from the $title property
* Add a constructor that sets the $created property to the current date and time
* use the following commands on the console to add the getters and setters and to update the database:

    `$ php app/console doctrine:generate:entities AppBundle:Newsitem`
    
    `$ php app/console doctrine:schema:update --force`

## Using the Slugs in your routes

From now on if you persist your entity the slug will be automatically generated. To use the slugs into the routes you could simply use the $slug property into the route e.g.
`@Route("news/{slug}", name="newsitem_show")`

And then you have to retrieve the right newsitem using the given slug:
```
    /**
     * Finds and displays a Newsitem entity.
     *
     * @Route("/{slug}", name="newsitem_show")
     * @Method("GET")
     * @Template()
     */
    public function showAction($slug)
    {
        $em = $this->getDoctrine()->getManager();

        $entity = $em->getRepository('AppBundle:Newsitem')->findOneBy(array('slug' => $slug));

        if (!$entity) {
            throw $this->createNotFoundException('Unable to find Newsitem entity.');
        }

        return array(
            'entity'      => $entity,
        );
    }

```

## Advanced Slugs

The slug annotation has some more futures:

1) To generate slugs from more than one property just write an array of properties:
`@Slug({"created", "title"})`

2) To add your own format for **date**, **time** and **datetime** fields use the format parameter: `@Slug({"created", "title"}, format="Y-m-d")`.

## Update the slugs for all records of your table

Using the app/console in the command prompt you are able to generate the cruds for all the records:

    $ php app/console fbeen:generate:slugs
    
You will have to type the entity shortcut and then you need to confirm the update.


## Important notes

1) The slugs will be truncated to the length of the slug field minus 10 characters! the last 10 positions are used for additional digits that will make the slug unique if necessary.
