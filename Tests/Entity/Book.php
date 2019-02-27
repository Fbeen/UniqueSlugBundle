<?php

namespace Fbeen\UniqueSlugBundle\Tests\Entity;

use Doctrine\ORM\Mapping as ORM;
use Fbeen\UniqueSlugBundle\Annotation\Slug;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

/**
 * @ORM\Entity
 */
class Book
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
     * @ORM\Column(type="datetime")
     */
    private $created;

    /**
     * @Slug("title")
     * @ORM\Column(type="string", length=255, unique=true)
     */
    private $slug;

    /**
     * @Slug("created", format="Y-m-d")
     * @ORM\Column(type="string", length=32)
     */
    private $dateSlug;
    
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

    public function getDateSlug(): ?string
    {
        return $this->dateSlug;
    }

    public function setDateSlug(string $dateSlug): self
    {
        $this->dateSlug = $dateSlug;

        return $this;
    }
}
