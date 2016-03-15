<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Symfony\Component\Config\Definition\Exception\InvalidConfigurationException;

/**
 * Validates and merges configuration for Modlr.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritDoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $treeBuilder->root('as3_modlr')
            ->children()
                ->append($this->getAdapterNode())
                ->append($this->getMetadataNode())
                ->append($this->getPersistersNode())
                ->append($this->getRestNode())
                ->append($this->getSearchClientsNode())
            ->end()
        ;
        return $treeBuilder;
    }

    /**
     * Formats the root REST endpoint.
     *
     * @param   string  $endpoint
     * @return  string
     */
    private function formatRestEndpoint($endpoint)
    {
        return sprintf('%s', trim($endpoint, '/'));
    }

    /**
     * Gets the api adapter configuration node.
     *
     * @return  \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function getAdapterNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('adapter');
        return $node
            ->isRequired()
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('type')->end()
                ->scalarNode('service')->end()
            ->end()
            ->validate()
                ->always(function($v) {
                    $this->validateAdapter($v);
                    return $v;
                })
            ->end()
        ;
    }

    /**
     * Gets the metadata configuration node.
     *
     * @return  \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function getMetadataNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('metadata');
        return $node
            ->addDefaultsIfNotSet()
            ->children()
                ->arrayNode('drivers')
                    ->defaultValue(['default' => ['type' => 'yml']])
                    ->useAttributeAsKey('name')
                    ->prototype('array')
                        ->performNoDeepMerging()
                        ->children()

                            ->enumNode('type')->defaultValue('file')
                            ->values(['yml', null])
                            ->end()
                            ->scalarNode('service')->cannotBeEmpty()->end()

                            ->arrayNode('parameters')
                                ->prototype('variable')->end()
                            ->end()

                        ->end()
                    ->end()
                    ->validate()
                        ->always(function($v) {
                            $this->validateMetadataDrivers($v);
                            return $v;
                        })
                    ->end()
                ->end()

                ->arrayNode('cache')
                    ->canBeDisabled()
                    ->children()

                        ->enumNode('type')->defaultValue('file')
                            ->values(['file', 'binary_file', 'redis', null])
                        ->end()
                        ->scalarNode('service')->cannotBeEmpty()->end()

                        ->arrayNode('parameters')
                            ->prototype('variable')->end()
                        ->end()

                    ->end()

                    ->validate()
                        ->always(function($v) {
                            $this->validateMetadataCache($v);
                            return $v;
                        })
                    ->end()
                ->end()
            ->end()
        ;
    }

    /**
     * Gets the persisters configuration node.
     *
     * @return  \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function getPersistersNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('persisters');
        return $node
            ->isRequired()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()

                    ->scalarNode('type')->cannotBeEmpty()->end()
                    ->scalarNode('service')->cannotBeEmpty()->end()

                    ->arrayNode('parameters')
                        ->prototype('variable')->end()
                    ->end()
                ->end()
            ->end()
            ->validate()
                ->always(function($v) {
                    $this->validatePersisters($v);
                    return $v;
                })
            ->end()
        ;
    }

    /**
     * Gets the rest configuration node.
     *
     * @return  \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function getRestNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('rest');
        return $node
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('root_endpoint')->isRequired()->cannotBeEmpty()->defaultValue('modlr/api')
                    ->validate()
                        ->always(function($v) {
                            $v = $this->formatRestEndpoint($v);
                            return $v;
                        })
                    ->end()
                ->end()
            ->end()

        ;
    }

    /**
     * Gets the search clients configuration node.
     *
     * @return  \Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition
     */
    private function getSearchClientsNode()
    {
        $treeBuilder = new TreeBuilder();
        $node = $treeBuilder->root('search_clients');
        return $node
            ->isRequired()
            ->useAttributeAsKey('name')
            ->prototype('array')
                ->children()

                    ->scalarNode('type')->cannotBeEmpty()->end()
                    ->scalarNode('service')->cannotBeEmpty()->end()

                    ->arrayNode('parameters')
                        ->prototype('variable')->end()
                    ->end()
                ->end()
            ->end()
            ->validate()
                ->always(function($v) {
                    $this->validateSearchClients($v);
                    return $v;
                })
            ->end()
        ;
    }

    /**
     * Validates the api adapter config.
     *
     * @param   array   $adapter
     * @return  self
     * @throws  InvalidConfigurationException
     */
    private function validateAdapter(array $adapter)
    {
        $this->validateTypeAndService($adapter, 'as3_modlr.adapter');

        if (isset($adapter['type'])) {
            if ('jsonapiorg' === $adapter['type']) {
                if (false === class_exists('As3\Modlr\Api\JsonApiOrg\Adapter')) {
                    throw new InvalidConfigurationException('The jsonapi.org adapter class was not found for "as3_modlr.adapter.type" - was the library installed?');
                }
            } else {
                throw new InvalidConfigurationException('An unrecognized adapter type was set for "as3_modlr.adapter.type"');
            }
        }
        return $this;
    }

    /**
     * Validates the metadata cache config.
     *
     * @param   array   $config
     * @return  self
     * @throws  InvalidConfigurationException
     */
    private function validateMetadataCache(array $config)
    {
        if (false === $config['enabled']) {
            return $this;
        }

        $this->validateTypeAndService($config, 'as3_modlr.metadata.cache');
        if (isset($config['type']) && 'redis' === $config['type'] && !isset($config['parameters']['handler'])) {
            throw new InvalidConfigurationException('A Redis handler service name must be defined for "as3_modlr.metadata.cache.parameters.handler"');
        }
        return $this;
    }

    /**
     * Validates the metadata drivers config.
     *
     * @param   array   $drivers
     * @return  self
     * @throws  InvalidConfigurationException
     */
    private function validateMetadataDrivers(array $drivers)
    {
        foreach ($drivers as $name => $config) {
            $this->validateTypeAndService($config, sprintf('as3_modlr.metadata.drivers.%s', $name));
        }
        return $this;
    }

    /**
     * Validates the persisters config.
     *
     * @param   array   $persisters
     * @return  self
     * @throws  InvalidConfigurationException
     */
    private function validatePersisters(array $persisters)
    {
        foreach ($persisters as $name => $config) {
            $this->validateTypeAndService($config, sprintf('as3_modlr.persisters.%s', $name));
            if (isset($config['type'])) {
                if ('mongodb' === $config['type']) {
                    if (false === class_exists(Utility::getLibraryClass('Persister\MongoDb\Persister'))) {
                        throw new InvalidConfigurationException(sprintf('The MongoDB persister library class was not found for "as3_modlr.persisters.%s.type" - was the library installed?', $name));
                    }
                    if (!isset($config['parameters']['host'])) {
                        throw new InvalidConfigurationException(sprintf('The MongoDB persister requires a value for "as3_modlr.persisters.%s.parameters.host"', $name));
                    }
                } else {
                    throw new InvalidConfigurationException(sprintf('An unrecognized persister type was set for "as3_modlr.persisters.%s.type"', $name));
                }
            }
        }
    }

    /**
     * Validates the search clients config.
     *
     * @param   array   $clients
     * @return  self
     * @throws  InvalidConfigurationException
     */
    private function validateSearchClients(array $clients)
    {
        foreach ($clients as $name => $config) {
            $this->validateTypeAndService($config, sprintf('as3_modlr.search_clients.%s', $name));
            if (isset($config['type'])) {
                if ('elastic' === $config['type']) {
                    if (false === class_exists(Utility::getLibraryClass('Search\Elastic\Client'))) {
                        throw new InvalidConfigurationException(sprintf('The Elastic persister library class was not found for "as3_modlr.search_clients.%s.type" - was the library installed?', $name));
                    }
                } else {
                    throw new InvalidConfigurationException(sprintf('An unrecognized search type was set for "as3_modlr.search_clients.%s.type"', $name));
                }
            }
        }
    }

    private function validateTypeAndService(array $config, $path)
    {
        if (!isset($config['type']) && !isset($config['service'])) {
            throw new InvalidConfigurationException(sprintf('You must set one of "type" or "service" for "%s"', $path));
        }
        if (isset($config['type']) && isset($config['service'])) {
            throw new InvalidConfigurationException(sprintf('You cannot set both "type" and "service" for "%s" - please choose one.', $path));
        }
    }
}
