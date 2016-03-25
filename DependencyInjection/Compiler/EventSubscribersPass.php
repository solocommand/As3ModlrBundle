<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection\Compiler;

use As3\Bundle\ModlrBundle\DependencyInjection\Utility;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

/**
 * Adds event subscribers to the event dispatcher.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class EventSubscribersPass implements CompilerPassInterface
{
    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $dispatcher = $container->getDefinition(Utility::getAliasedName('event_dispatcher'));

        $tagged = $container->findTaggedServiceIds(Utility::getAliasedName('event_subscriber'));
        foreach ($tagged as $id => $tags) {
            $dispatcher->addMethodCall('addSubscriber', [new Reference($id)]);
        }
    }
}
