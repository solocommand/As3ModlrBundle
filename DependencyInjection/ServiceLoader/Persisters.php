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
        $definition = new Definition(
            Utility::getLibraryClass('Persister\MongoDb\StorageMetadataFactory'),
            [new Reference(Utility::getAliasedName('util.entity'))]
        );
        $definition->setPublic(false);
        $container->setDefinition($smfName, $definition);

        // Connection
        $conName = sprintf('%s.connection', $persisterName);
        $options = isset($persisterConfig['parameters']['options']) && is_array($persisterConfig['parameters']['options']) ? $persisterConfig['parameters']['options'] : [];
        $definition = new Definition(
            'Doctrine\MongoDB\Connection',
            [$persisterConfig['parameters']['host'], $options]
        );
        $definition->setPublic(false);
        $container->setDefinition($conName, $definition);

        // Persister
        return new Definition(
            Utility::getLibraryClass('Persister\MongoDb\Persister'),
            [new Reference($conName), new Reference($smfName)]
        );
    }

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
}
