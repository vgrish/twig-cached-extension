twig-cached-extension
=====================

Extension for caching html fragments

# Usage

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
```twig
{% cached ["set", "your", "cache", "key", "here"] %}
	<p>Hello World!</p>
{% endcached %}
```
