<?php

namespace Fbeen\UniqueSlugBundle\Listener;

use Doctrine\ORM\Event\LifecycleEventArgs;
use Fbeen\UniqueSlugBundle\Custom\SlugUpdater;

/**
 * This class is subscribed as a Doctrine event listener. 
 * The method prePersist will be called on a "prePersist" event.
 * The method preUpdate will be called on a "preUpdate" event.
 * 
 * For more information read: https://www.doctrine-project.org/projects/doctrine-orm/en/2.6/reference/events.html#listening-and-subscribing-to-lifecycle-events
 *
 * @author Frank Beentjes <frankbeen@gmail.com>
 */
class DoctrineEventListener
{
    private $slugUpdater;

    public function __construct(SlugUpdater $slugUpdater)
    {
        $this->slugUpdater = $slugUpdater;
    }

    public function prePersist(LifecycleEventArgs $args) : void
    {
        $this->slugUpdater->updateSlugs($args->getEntity());
    }

    public function preUpdate(LifecycleEventArgs $args) :void
    {
        $this->slugUpdater->updateSlugs($args->getEntity());
    }
}