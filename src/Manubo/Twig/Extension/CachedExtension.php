<?php

namespace Manubo\Twig\Extension;

use Psr\Cache\CacheItemPoolInterface;
use Manubo\Twig\TokenParser\CachedTokenParser;

/**
 * Class CachedIncludesExtension
 * @package Chuntos\CachedBundle\Twig
 */
class CachedExtension extends \Twig_Extension
{
    protected $cache;

    public function __construct(CacheItemPoolInterface $cache)
    {
        $this->cache = $cache;
    }

    public function getTokenParsers()
    {
        return [
            new CachedTokenParser($this->getName()),
        ];
    }

    public function getName()
    {
        return "manubo_cached_extension";
    }

    public function getCache()
    {
        return $this->cache;
    }

    public function compileKey(array $cacheKeys)
    {
        $compiled = [];
        foreach ($cacheKeys as $key) {
            $compiled[] = $this->doCompileKey($key);
        }

        return implode('_', $compiled);
    }

    protected function doCompileKey($key)
    {
        if (empty($key)) {
            return '';
        }

        if (is_string($key)) {
            return $key;
        }

        if (is_array($key)) {
            $compiled = [];
            foreach ($key as $subKey) {
                $compiled[] = $this->doCompileKey($subKey);
            }
            return implode('_', $compiled);
        }

        if ($key instanceof \DateTime) {
            return $key->getTimestamp();
        }

        if (is_object($key)) {
            $attrs = ['updated_at', 'modified_at', 'last_updated', 'last_modified'];
            foreach ($attrs as $attr) {
                if (null !== ($prop = $this->getAttribute($key, $attr))) {
                    return $this->doCompileKey($prop);
                }
            }
        }

        return '';
    }

    /**
     * @param $entity
     * @param $attr
     * @return mixed
     */
    protected function getAttribute($entity, $attr)
    {
        $camelized = str_replace(' ', '', ucwords(str_replace('_', ' ', $attr)));
        $camelizedAttr = lcfirst($camelized);

        $objectVars = get_object_vars($entity);

        if (array_key_exists($camelizedAttr, $objectVars)) {
            return $entity->$camelizedAttr;
        }

        $objectMethods = get_class_methods($entity);

        $getter = 'get'.$camelized;
        if (in_array($getter, $objectMethods)) {
            return $entity->$getter();
        }

        if (array_key_exists($attr, $objectVars)) {
            return $entity->$attr;
        }

        $getter = 'get_'.$attr;
        if (in_array($getter, $objectMethods)) {
            return $entity->$getter();
        }

        return null;
    }

}
 