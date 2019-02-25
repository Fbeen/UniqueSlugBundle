<?php

namespace Fbeen\UniqueSlugBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\OptionsResolver\Exception\InvalidArgumentException;

/**
 * This is the class that loads and manages your bundle configuration
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/extension.html}
 */
class FbeenUniqueSlugExtension extends Extension
{
    /**
     * {@inheritdoc}
     */
    public function load(array $configs, ContainerBuilder $container)
    {
        $configuration = new Configuration();
        $config = $this->processConfiguration($configuration, $configs);
        
        // if we lose 8 characters for adding numbers to make the slug unique then we want to keep a minimum of 9 for the length of the slug column.
        if($config['minimum_slug_length'] <= $config['maximum_digits'])
        {
            throw new InvalidArgumentException('Check your configuration. Parameter "minimum_slug_length" must be higher than "maximum_digits".');
        }
        

        $loader = new Loader\YamlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.yaml');
        
        // Let the slugupdater service use a slugifier class depending on the configuration of this bundle
        $this->replaceServiceArgument($container, 'fbeen_unique_slug.slugupdater', 'slugifier_class', $config['slugifier_class']);

        $this->registerContainerParametersRecursive($container, $this->getAlias(), $config);
    }

    protected function registerContainerParametersRecursive(ContainerBuilder $container, $alias, $config)
    {
        $iterator = new \RecursiveIteratorIterator(new \RecursiveArrayIterator($config),
            \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $value) {
            $path = array( );
            for ($i = 0; $i <= $iterator->getDepth(); $i++) {
                $path[] = $iterator->getSubIterator($i)->key();
            }
            $key = $alias . '.' . implode(".", $path);
            $container->setParameter($key, $value);
        }
    }
    
    private function replaceServiceArgument(ContainerBuilder $container, $serviceId, $oldArgument, $newArgument)
    {
        $definition = $container->getDefinition($serviceId);

        // try to find the argument 'security.user_checker.main' and replace its value for the value from 'user_checker' configuration variable (see Configuration.php)
        $arguments = $definition->getArguments();
        for($i = 0 ; $i < count($arguments) ; $i++) {
            if($arguments[$i] === $oldArgument) {
                $definition->replaceArgument($i, new Reference($newArgument));
                break;
            }
        }
    }
}
