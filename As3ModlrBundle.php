<?php

namespace As3\Bundle\ModlrBundle;

use As3\Bundle\ModlrBundle\DependencyInjection\Compiler;
use Symfony\Component\HttpKernel\Bundle\Bundle;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * The root bundle class for Modlr.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class As3ModlrBundle extends Bundle
{
    public function build(ContainerBuilder $container)
    {
        parent::build($container);

        // Create directories
        $container->addCompilerPass(new Compiler\DirectoryCompilerPass());
    }
}
