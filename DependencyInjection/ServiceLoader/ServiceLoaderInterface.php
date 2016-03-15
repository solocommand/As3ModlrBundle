<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection\ServiceLoader;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Defines the implementation requirements for a service loader.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
interface ServiceLoaderInterface
{
    /**
     * Loads services from the bundle configuration.
     *
     * @param   array               $config
     * @param   ContainerBuilder    $container
     * @return  self
     * @throws  \RuntimeException   On any loading errors.
     */
    public function load(array $config, ContainerBuilder $container);
}
