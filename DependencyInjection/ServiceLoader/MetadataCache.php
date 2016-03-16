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
     * Creates the bundle cache warmer definition.
     *
     * @param   string  $warmerName
     * @return  Definition
     */
    private function createBundleCacheWarmer($warmerName)
    {
        $definition = new Definition(
            Utility::getBundleClass('CacheWarmer\MetadataWarmer'),
            [new Reference($warmerName)]
        );
        $definition->setPublic(false);
        $definition->addTag('kernel.cache_warmer');
        return $definition;
    }

    /**
     * Creates the cache clear command definition.
     *
     * @param   string  $warmerName
     * @return  Definition
     */
    private function createCacheClearCommand($warmerName)
    {
        $definition = new Definition(
            Utility::getBundleClass('Command\Metadata\ClearCacheCommand'),
            [new Reference($warmerName)]
        );
        $definition->addTag('console.command');
        return $definition;
    }

    /**
     * Creates the cache warmer service definition.
     *
     * @return  Definition
     */
    private function createCacheWarmer()
    {
        $definition = new Definition(
            Utility::getLibraryClass('Metadata\Cache\CacheWarmer'),
            [new Reference(Utility::getAliasedName('metadata.factory'))]
        );
        $definition->setPublic(false);
        return $definition;
    }

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
        // Load cache warming services.
        // Run always, regardless of cache enabling, so the command the warmers are always there.
        // The underlying cache warmer will not execute if cache is disabled.
        $this->loadCacheWarming($container);

        $cacheName = Utility::getAliasedName('metadata.cache');
        $cacheConfig = $config['metadata']['cache'];
        if (false === $cacheConfig['enabled']) {
            return $this;
        }

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
        // Root cache warmer
        $warmerName = Utility::getAliasedName('metadata.cache.warmer');
        $definition = $this->createCacheWarmer($container);
        $container->setDefinition($warmerName, $definition);

        // Bundle wrapped cache warmer
        $definition = $this->createBundleCacheWarmer($warmerName);
        $container->setDefinition(Utility::getAliasedName('bundle.cache.warmer'), $definition);

        // Cache clear command
        $definition = $this->createCacheClearCommand($warmerName);
        $container->setDefinition(Utility::getAliasedName('command.metadata.cache_clear'), $definition);

        return $this;
    }
}
