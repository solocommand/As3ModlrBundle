<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection\ServiceLoader;

use As3\Bundle\ModlrBundle\DependencyInjection\Utility;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Loads the metadata cache service.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class MetadataCache implements ServiceLoaderInterface
{
    /**
     * Creates a file cache service definition.
     *
     * @param   string              $subClassName
     * @param   array               $cacheConfig
     * @param   ContainerBuilder    $container
     * @return  Definition
     */
    private function createFileCache($subClassName, array $cacheConfig, ContainerBuilder $container)
    {
        $cacheDir = $this->getFileCacheDir($cacheConfig, $container);
        Utility::appendParameter('dirs', 'metadata_cache_dir', $cacheDir, $container);

        return new Definition(
            Utility::getLibraryClass($subClassName),
            [$cacheDir]
        );
    }

    /**
     * Creates the redis cache service definition.
     *
     * @param   array               $cacheConfig
     * @return  Definition
     */
    private function createRedisCache(array $cacheConfig)
    {
        return new Definition(
            Utility::getLibraryClass('Metadata\Cache\RedisCache'),
            [new Reference($cacheConfig['parameters']['handler'])]
        );
    }

    /**
     * Gets the file cache directory.
     *
     * @param   array               $cacheConfig
     * @param   ContainerBuilder    $container
     * @return  string
     */
    private function getFileCacheDir(array $cacheConfig, ContainerBuilder $container)
    {
        $dir = sprintf('%s/as3_modlr', $container->getParameter('kernel.cache_dir'));
        if (isset($cacheConfig['parameters']['dir'])) {
            $dir = $cacheConfig['parameters']['dir'];
        }
        return $dir;
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $cacheName = Utility::getAliasedName('metadata.cache');
        $cacheConfig = $config['metadata']['cache'];
        if (false === $cacheConfig['enabled']) {
            return $this;
        }

        // Load cache warming services.
        $this->loadCacheWarming($container);

        if (isset($cacheConfig['service'])) {
            // Custom cache service.
            $container->setAlias($cacheName, Utility::cleanServiceName($cacheConfig['service']));
            return $this;
        }

        // Built-in cache service.
        switch ($cacheConfig['type']) {
            case 'file':
                $definition = $this->createFileCache('Metadata\Cache\FileCache', $cacheConfig, $container);
                break;
            case 'binary_file':
                $definition = $this->createFileCache('Metadata\Cache\BinaryFileCache', $cacheConfig, $container);
                break;
            case 'redis':
                $definition = $this->createRedisCache($cacheConfig);
                break;
            default:
                throw new \RuntimeException(sprintf('Unable to create a metadata cache service for type "%s"', $cacheConfig['type']));
        }
        $container->setDefinition($cacheName, $definition);
        return $this;
    }

    /**
     * Loads cache warming services.
     *
     * @param   ContainerBuilder    $container
     * @return  self
     */
    private function loadCacheWarming(ContainerBuilder $container)
    {
        $warmerName = Utility::getAliasedName('metadata.cache.warmer');
        $definition = new Definition(
            Utility::getLibraryClass('Metadata\Cache\CacheWarmer'),
            [new Reference(Utility::getAliasedName('metadata.factory'))]
        );
        $definition->setPublic(false);
        $container->setDefinition($warmerName, $definition);

        $definition = new Definition(
            Utility::getBundleClass('CacheWarmer\MetadataWarmer'),
            [new Reference($warmerName)]
        );
        $definition->setPublic(false);
        $definition->addTag('kernel.cache_warmer');
        $container->setDefinition(Utility::getAliasedName('bundle.cache.warmer'), $definition);
        return $this;
    }
}
