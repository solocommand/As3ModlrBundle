<?php

namespace As3\Bundle\ModlrBundle\CacheWarmer;

use As3\Modlr\Metadata\Cache\CacheWarmer;
use Symfony\Component\HttpKernel\CacheWarmer\CacheWarmerInterface;

/**
 * Symfony cache warmer wrapper for model metadata.
 *
 * @author  Jacob Bare <jacob.bare@gmail.com>
 */
class MetadataWarmer implements CacheWarmerInterface
{
    /**
     * The Modlr cache warmer.
     *
     * @var CacheWarmer
     */
    protected $warmer;

    /**
     * Constructor.
     *
     * @param   CacheWarmer     $warmer
     */
    public function __construct(CacheWarmer $warmer)
    {
        $this->warmer = $warmer;
    }

    /**
     * {@inheritdoc}
     */
    public function warmUp($cacheDir)
    {
        $this->warmer->warm();
    }

    /**
     * {@inheritdoc}
     */
    public function isOptional()
    {
        return false;
    }
}
