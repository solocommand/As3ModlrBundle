<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection\Compiler;

use As3\Bundle\ModlrBundle\DependencyInjection\Utility;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Creates required directories when applicable.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class DirectoryCompilerPass implements CompilerPassInterface
{
    /**
     * Creates a directory (if nonexistent).
     *
     * @param   string  $dir
     * @return  self
     * @throws  \RuntimeException
     */
    private function createDirectory($dir)
    {
        if (file_exists($dir)) {
            return $this;
        }
        if (false === @mkdir($dir, 0777, true)) {
            throw new \RuntimeException(sprintf('Unable to create directory "%s"', $dir));
        }
        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContainerBuilder $container)
    {
        $name = Utility::getAliasedName('dirs');
        if (false === $container->hasParameter($name)) {
            return;
        }
        foreach ($container->getParameter($name) as $dir) {
            $this->createDirectory($dir);
        }
    }
}
