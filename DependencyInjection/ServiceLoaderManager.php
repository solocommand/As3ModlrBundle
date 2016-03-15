<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection;

use As3\Bundle\ModlrBundle\DependencyInjection\ServiceLoader\ServiceLoaderInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Loads services for the Modlr bundle
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class ServiceLoaderManager
{
    /**
     * @var ContainerBuilder
     */
    private $container;

    /**
     * @var ServiceLoaderInterface[]
     */
    private $loaders = [];

    /**
     * Constructor.
     *
     * @param   ContainerBuilder    $container
     */
    public function __construct(ContainerBuilder $container)
    {
        $this->container = $container;
        $this->initLoaders();
    }

    /**
     * Adds a service loader.
     *
     * @param   ServiceLoaderInterface  $loader
     * @return  self
     */
    public function addLoader(ServiceLoaderInterface $loader)
    {
        $this->loaders[] = $loader;
        return $this;
    }

    /**
     * Initializes all service loaders.
     *
     * @return  self
     */
    private function initLoaders()
    {
        $namespace = sprintf('%s\\ServiceLoader', __NAMESPACE__);
        $classes = ['MetadataCache', 'MetadataDrivers', 'MetadataFactory', 'Persisters', 'Rest', 'SearchClients'];

        foreach ($classes as $class) {
            $fqcn = sprintf('%s\\%s', $namespace, $class);
            $this->addLoader(new $fqcn);
        }
        return $this;
    }

    /**
     * Loads services from all loaders.
     *
     * @param   array   $config
     * @return  self
     */
    public function loadServices(array $config)
    {
        foreach ($this->loaders as $loader) {
            $loader->load($config, $this->container);
        }
        return $this;
    }
}
