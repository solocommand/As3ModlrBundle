<?php

namespace As3\Bundle\ModlrBundle\DependencyInjection;

use Symfony\Component\DependencyInjection\ContainerBuilder;

/**
 * Provides utility/helper methods for DI related tasks.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class Utility
{
    /**
     * The library root namespace.
     */
    const LIBRARY_NS = 'As3\\Modlr';

    /**
     * The bundle root namespace.
     */
    const BUNDLE_NS  = 'As3\\Bundle\\ModlrBundle';

    /**
     * The bundle alias.
     */
    const BUNDLE_ALIAS = 'as3_modlr';

    /**
     * Prevent instantiation.
     */
    private function __construct()
    {
    }

    /**
     * Appends a key/value pair to a container parameter.
     * If the parameter name currently isn't set, the parameter is created.
     *
     * @param   string              $name
     * @param   string              $key
     * @param   mixed               $value
     * @param   ContainerBuilder    $container
     */
    public static function appendParameter($name, $key, $value, ContainerBuilder $container)
    {
        $name = self::getAliasedName($name);

        if (false === $container->hasParameter($name)) {
            $current = [];
        } else {
            $current = (Array) $container->getParameter($name);
        }
        $current[$key] = $value;
        $container->setParameter($name, $current);
    }

    /**
     * Cleans a service name by removing any '@' characters.
     *
     * @param   string  $name
     * @return  string
     */
    public static function cleanServiceName($name)
    {
        return str_replace('@', '', $name);
    }

    /**
     * Gets the fully qualified class name for a Modlr bundle class.
     *
     * @static
     * @param   string  $subClass
     * @return  string
     */
    public static function getBundleClass($subClass)
    {
        return sprintf('%s\\%s', self::BUNDLE_NS, $subClass);
    }

    /**
     * Gets the fully qualified class name for a Modlr library class.
     *
     * @static
     * @param   string  $subClass
     * @return  string
     */
    public static function getLibraryClass($subClass)
    {
        return sprintf('%s\\%s', self::LIBRARY_NS, $subClass);
    }

    /**
     * Gets the fully-qualified bundle alias name.
     *
     * @static
     * @param   string  $name
     * @return  string
     */
    public static function getAliasedName($name)
    {
        return sprintf('%s.%s', self::BUNDLE_ALIAS, $name);
    }

    /**
     * Locates a file resource path for a given config path.
     * Is needed in order to retrieve a bundle's directory, if used.
     *
     *
     * @static
     * @param   string              $path
     * @param   ContainerBuilder    $container
     * @return  string
     * @throws  \RuntimeException
     */
    public static function locateResource($path, ContainerBuilder $container)
    {
        if (0 === stripos($path, '@')) {
            // Treat as a bundle path, e.g. @SomeBundle/path/to/something.

            // Correct backslashed paths.
            $path = str_replace('\\', '/', $path);

            $parts = explode('/', $path);
            $bundle = array_shift($parts);

            $bundleName = str_replace('@', '', $bundle);
            $bundleClass = null;
            foreach ($container->getParameter('kernel.bundles') as $name => $class) {
                if ($name === $bundleName) {
                    $bundleClass = $class;
                    break;
                }
            }

            if (null === $bundleClass) {
                throw new \RuntimeException(sprintf('Unable to find a bundle named "%s" for resource path "%s"', $bundleName, $path));
            }

            $refl = new \ReflectionClass($bundleClass);
            $bundleDir = dirname($refl->getFileName());
            return sprintf('%s/%s', $bundleDir, implode('/', $parts));
        }
        return $path;
    }
}
