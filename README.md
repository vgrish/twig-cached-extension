twig-cached-extension
=====================

Extension for caching html fragments

## Usage

Using this Twig extension can be done as follows.

1. Register the Cached Extension
```php
use Doctrine\Common\Cache\RedisCache;
use Manubo\Twig\Extension\CachedExtension;

$redis = new \Redis;
$redis->connect('127.0.0.1', 6379);

$cache = new RedisCache;
$cache->setRedis($redis);

$cachedExtension = new CachedExtension($cache);

$loader = new Twig_Loader_String(); // use any other loader strategy as required
$twig = new Twig_Environment($loader);
$twig->addExtension($cachedExtension);
```

2. Cache HTML includes
```html
{% cached ["set", "your", "cache", "key", "here"] %}
	<p>Hello World!</p>
{% endcached %}
```

## The `cached` tag
The `cached` tag accepts two parameters
* an array defining the cache key
* an integer as the expiry time in seconds, if supported by the cache

### Cache key
If the cache key contains an object, any of the following properties and its' equivalent getters are tested for composing the final cache key:
* updated_at, updatedAt, get_updated_at(), getUpdatedAt()
* modified_at, modifiedAt, get_modified_at(), getModifiedAt()
* last_updated, lastUpdated, get_last_updated(), getLastUpdated()
* last_modified, lastModified, get_last_modified(), getLastModified()

For example, the following cache key

`['foo', model]`

where `model` is an object with property `model.updated_at = \DateTime("2014-09-23 15:15:15")`, the resulting key would be:

`"foo_1411478115"`.

