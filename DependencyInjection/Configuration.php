<?php

namespace Fbeen\UniqueSlugBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html#cookbook-bundles-extension-config-class}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder('fbeen_unique_slug');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('slugifier_class')->defaultValue('fbeen_unique_slug.slugifier')->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
