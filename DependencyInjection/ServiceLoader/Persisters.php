<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection\ServiceLoader;

use As3\Bundle\ModlrBundle\DependencyInjection\Utility;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Loads persister services.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class Persisters implements ServiceLoaderInterface
{
    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $managerDef = $container->getDefinition(Utility::getAliasedName('storage_manager'));

        foreach ($config['persisters'] as $name => $persisterConfig) {
            $persisterName = Utility::getAliasedName(sprintf('persister.%s', $name));
            if (isset($persisterConfig['service'])) {
                // Custom persister.
                $container->setAlias($persisterName, Utility::cleanServiceName($persisterConfig['service']));
            } else {
                // Built-in persister.
                switch ($persisterConfig['type']) {
                    case 'mongodb':
                        $definition = $this->createMongoDbPersister($persisterName, $persisterConfig, $container);
                        break;
                    default:
                        throw new \RuntimeException(sprintf('The persister type "%s" is currently not supported.', $persisterConfig['type']));
                }
                $container->setDefinition($persisterName, $definition);
            }
            $managerDef->addMethodCall('addPersister', [new Reference($persisterName)]);
        }
        return $this;
    }

    /**
     * Creates the connection service definition.
     *
     * @param   array   $persisterConfig
     * @return  Definition
     */
    private function createConnection(array $persisterConfig)
    {
        $options = isset($persisterConfig['parameters']['options']) && is_array($persisterConfig['parameters']['options']) ? $persisterConfig['parameters']['options'] : [];
        $definition = new Definition(
            'Doctrine\MongoDB\Connection',
            [$persisterConfig['parameters']['host'], $options]
        );
        $definition->setPublic(false);
        return $definition;
    }

    /**
     * Creates the persistence formatter service definition.
     *
     * @return  Definition
     */
    private function createFormatter()
    {
        $definition = new Definition(
            Utility::getLibraryClass('Persister\MongoDb\Formatter')
        );
        $definition->setPublic(false);
        return $definition;
    }

    /**
     * Creates the MongoDB persister service definition.
     * Will also load support services.
     *
     * @param   string              $persisterName
     * @param   array               $persisterConfig
     * @param   ContainerBuilder    $container
     * @return  Definition
     */
    private function createMongoDbPersister($persisterName, array $persisterConfig, ContainerBuilder $container)
    {
        // Storage metadata
        $smfName = sprintf('%s.metadata', $persisterName);
        $definition = $this->createSmf();
        $container->setDefinition($smfName, $definition);

        // Connection
        $conName = sprintf('%s.connection', $persisterName);
        $definition = $this->createConnection($persisterConfig);
        $container->setDefinition($conName, $definition);

        // Formatter
        $formatterName = sprintf('%s.formatter', $persisterName);
        $definition = $this->createFormatter();
        $container->setDefinition($formatterName, $definition);

        // Query
        $queryName = sprintf('%s.query', $persisterName);
        $definition = $this->createQuery($conName, $formatterName);
        $container->setDefinition($queryName, $definition);

        // Persister
        return new Definition(
            Utility::getLibraryClass('Persister\MongoDb\Persister'),
            [new Reference($queryName), new Reference($smfName)]
        );
    }

    /**
     * Creates the storage metadata factory service definition.
     *
     * @return  Definition
     */
    private function createSmf()
    {
        $definition = new Definition(
            Utility::getLibraryClass('Persister\MongoDb\StorageMetadataFactory'),
            [new Reference(Utility::getAliasedName('util.entity'))]
        );
        $definition->setPublic(false);
        return $definition;
    }

    private function createQuery($conName, $formatterName)
    {
        $definition = new Definition(
            Utility::getLibraryClass('Persister\MongoDb\Query'),
            [new Reference($conName), new Reference($formatterName)]
        );
        return $definition;
    }
}
