# FbeenUniqueSlugBundle for Symfony 4

With this bundle you will be able to automatic generate unique slugs inside your entities by simply add a @Slug annotation to a specified field.

### Features include:

* very simple installation
* generate unique slugs from **one or more properties** or a **custom method** in an entity 
* automatic storage into the database with always a unique slug
* making the slug unique by adding digits when necessary
* add or regenerate slugs in existing tables with the command prompt
* support for date, time and datetime fields with custom format
* supports custom (language specific) slugifiers

## Requirements
This bundles current release requires Symfony 4. You can use Version 1.1 from this Bundle to use it with Symfony >= 2.7

## Installation


### 1. Run composer:

```
    $ composer require fbeen/uniqueslugbundle
```
### 2. Adding Slug behavior to your entity:
Suppose that you have a **Newsitem** entity to display some news on your website and that you want to generate slugs from the **$title** property.
```
    $ bin/console make:slug Newsitem title
```
You might then have an entity like this:
```
<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fbeen\UniqueSlugBundle\Annotation\Slug;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity(repositoryClass="App\Repository\NewsitemRepository")
 */
class Newsitem
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private $id;

    /**
     * @ORM\Column(type="string", length=64)
     */
    private $title;

    /**
     * @ORM\Column(type="text")
     */
    private $body;

    /**
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @Slug("title")
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $slug;
    
    public function __construct()
    {
        $this->created = new \DateTime();
    }
    
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getBody(): ?string
    {
        return $this->body;
    }

    public function setBody(string $body): self
    {
        $this->body = $body;

        return $this;
    }

    public function getCreated(): ?\DateTimeInterface
    {
        return $this->created;
    }

    public function setCreated(\DateTimeInterface $created): self
    {
        $this->created = $created;

        return $this;
    }

    public function getSlug(): ?string
    {
        return $this->slug;
    }

    public function setSlug(string $slug): self
    {
        $this->slug = $slug;

        return $this;
    }
}
```

**Important notes when you edit your entity manually:**

* Do not forget the `use Fbeen\UniqueSlugBundle\Annotation\Slug;`
* Add a `@Slug("title")` annotation to the slug property to tell the application it should create a slug from the $title property
* Add a constructor that sets the $created property to the current date and time
* use the following commands on the console to add the getters and setters and to update the database:

### 3. Migrate:
Apply the changes to the database:
```
    $ bin/console make:migration
    $ bin/console doctrine:migrations:migrate
```
### 4. Using the Slugs in your routes

From now on if you persist your entity the slug will be automatically generated. To use the slugs into the routes you could simply use the $slug property into the route e.g.
```
    @Route("/{slug}", name="newsitem_show")
```

And then you have to retrieve the right newsitem using the given slug. Fortunately thanks to Symfony's automatic parameter conversion this is as easy as this:
```
    /**
     * @Route("/{slug}", name="newsitem_show", methods={"GET"})
     */
    public function show(Newsitem $newsitem): Response
    {
        return $this->render('newsitem/show.html.twig', [
            'newsitem' => $newsitem,
        ]);
    }

```
Don't forget to pass the slug property to the router when you generate a route to your show action:
```
    <a href="{{ path('newsitem_show', {'slug': newsitem.slug}) }}">show</a>
```
## Advanced Slugs

The slug annotation has some more futures:

1) To generate slugs from more than one property just write an array of properties: `@Slug({"created", "title"})`

2) To add your own format for **date**, **time** and **datetime** fields use the format parameter: `@Slug({"created", "title"}, format="Y-m-d")`.

3) You could also write your own method that builds the slug and apply the method name to the @Slug annotation.
```
    /**
     * @Slug("generateSlug")
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $slug;
    
    public function generateSlug()
    {
        return $this->created->format('Y-m-d') . ' ' . $this->title;
    }
```
## Update the slugs for all records of your table

Using the Symfony console commands you are able to generate the slugs for all the records:
```
    $ php bin/console make:slug Newsitem --regenerate
```
## Create your custom (language specific) slugifier

1. First write your own slugifier class and be sure that your class implements the  `Fbeen\UniqueSlugBundle\Slugifier\SlugifierInterface` e.g.

```
<?php
// App\Service\MyCustomSlugifier.php

namespace App\Service;

use Fbeen\UniqueSlugBundle\Slugifier\SlugifierInterface;

/**
 * My custom slugifier
 */
class MyCustomSlugifier implements SlugifierInterface
{
    public function slugify($text) : string
    {
        // replace non letter or digits by -
        $text = preg_replace('~[^\\pL\d]+~u', '-', $text);

        // trim
        $text = trim($text, '-');

        // transliterate latin characters
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
```
2. If you place your slugifier in the App\Service directory it will be autowired as a service. If not add a service definition in your ***/config/services.yaml*** file
```
    # Only necessary if autowiring is off
    Fbeen\UniqueSlugBundle\Slugifier\Slugifier: ~
```

3. Add a configuration file in the ***config/packages*** directory with the name ***fbeen_unique_slug.yaml***.
```
    # config/packages/fbeen_unique_slug.yaml
    fbeen_unique_slug:
        slugifier_class: App\Service\MyCustomSlugifier
```
Ready! From now on the slugs will be generated with your own slugifier class.

## Important notes

1) The slugs will be truncated to the length of the slug field minus 10 characters! the last 10 positions are used for additional digits that will make the slug unique if necessary.
