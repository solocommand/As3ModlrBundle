<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection\ServiceLoader;

use As3\Bundle\ModlrBundle\DependencyInjection\Utility;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Loads search client services.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class SearchClients implements ServiceLoaderInterface
{
    /**
     * Creates the Elastic search client service definition.
     * Will also load support services.
     *
     * @param   string              $clientName
     * @param   ContainerBuilder    $container
     * @return  Definition
     */
    private function createElasticClient($clientName, ContainerBuilder $container)
    {
        // Storage metadata
        $smfName = sprintf('%s.metadata', $clientName);
        $definition = new Definition(
            Utility::getLibraryClass('Search\Elastic\StorageMetadataFactory')
        );
        $definition->setPublic(false);
        $container->setDefinition($smfName, $definition);

        // Client
        return new Definition(
            Utility::getLibraryClass('Search\Elastic\Client'),
            [new Reference($smfName)]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function load(array $config, ContainerBuilder $container)
    {
        $managerDef = $container->getDefinition(Utility::getAliasedName('storage_manager'));

        foreach ($config['search_clients'] as $name => $clientConfig) {
            $clientName = Utility::getAliasedName(sprintf('search_client.%s', $name));
            if (isset($clientConfig['service'])) {
                // Custom search client.
                $container->setAlias($clientName, Utility::cleanServiceName($clientConfig['service']));
            } else {
                // Built-in client.
                switch ($clientConfig['type']) {
                    case 'elastic':
                        $definition = $this->createElasticClient($clientName, $container);
                        break;
                    default:
                        throw new \RuntimeException(sprintf('The search client type "%s" is currently not supported.', $clientConfig['type']));
                }
                $container->setDefinition($clientName, $definition);
            }
            $managerDef->addMethodCall('addSearchClient', [new Reference($clientName)]);
        }
        return $this;
    }
}
