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
                
                /*
                 * The Slugifier class that will be used to generate (non unique) slugs. See documentation on https://github.com/Fbeen/UniqueSlugBundle
                 */
                ->scalarNode('slugifier_class')->defaultValue('fbeen_unique_slug.slugifier')->end()
                
                /*
                 * The maximum amount of additional digits on the end of the slug to make the slug unique. 
                 * Defaultvalue is 8 which means that a slug can occur 10 million times. Most times more then enough...
                 */
                ->integerNode('maximum_digits')->defaultValue(8)->min(1)->max(12)->end()
                
                /*
                 * Slug properties are of type string. we need space in the table to store slugs plus additonal characters 
                 * This is about the MINIMAL length that the database column can store (SLUG + DIGITS). 
                 * If you make the slug property with a column length lower than this value then the validator will generate an exception.
                 * The validator will also make an exception when this value is lower or equal with 'maximum_digits'.
                 */
                ->integerNode('minimum_slug_length')->defaultValue(16)->min(5)->max(255)->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
