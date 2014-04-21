<?php

namespace Manubo\Test\Twig\Extension;

use Manubo\Cache\Memory\MemoryCacheItem;
use Manubo\Cache\Memory\MemoryCacheItemPool;
use Manubo\Twig\Extension\CachedExtension;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Class CachedExtensionTest
 * @package Manubo\Test\Twig\Extension
 * @coversDefaultClass \Manubo\Twig\Extension\CachedExtension
 */
class CachedExtensionTest extends \PHPUnit_Framework_TestCase
{
    protected $templates = [
        'tpl_first' => '{% cached ["foo", entity.foo] %}<p>Hello World!</p>{% endcached %}',
        'tpl_second' => '{% cached ["foo", entity.foo] %}<p>Hello John!</p>{% endcached %}',
        'tpl_third' => '{% cached ["bar", entity.foo] %}<p>Hello John!</p>{% endcached %}',
        'tpl_int_ttl' => '{% cached ["foo", "bar"], 10 %}<p>Hello John!</p>{% endcached %}',
    ];

    /**
     * @dataProvider getCompileKeyTestData
     * @covers ::compileKey
     */
    public function testCompileKey($cacheKey, $expected)
    {
        $cacheMock = $this->getMockForAbstractClass(CacheItemPoolInterface::class);

        $extension = new CachedExtension($cacheMock);
        $this->assertEquals($expected, $extension->compileKey($cacheKey));
    }

    /**
     * Integration test
     */
    public function testRenderCached()
    {
        $subEntity = new \stdClass();
        $subEntity->updated_at = 'baz';

        $entity = new \stdClass();
        $entity->foo = $subEntity;

        $twig = $this->getEnvironment();
        $result = $twig->loadTemplate('tpl_first')->render(['entity' => $entity]);
        $this->assertEquals('<p>Hello World!</p>', $result);

        $result = $twig->loadTemplate('tpl_second')->render(['entity' => $entity]);
        $this->assertEquals('<p>Hello World!</p>', $result);

        $result = $twig->loadTemplate('tpl_third')->render(['entity' => $entity]);
        $this->assertEquals('<p>Hello John!</p>', $result);
    }

    protected function getEnvironment()
    {
        $loader = new \Twig_Loader_Array($this->templates);
        $twig = new \Twig_Environment($loader, ['debug' => true, 'cache' => false, 'autoescape' => false]);
        $twig->addExtension(new CachedExtension(new MemoryCacheItemPool));

        return $twig;
    }

    public function getCompileKeyTestData()
    {
        $e1 = new \stdClass;
        $e1->updated_at = new \DateTime('2014-12-31 07:15:45');

        $e2 = new \stdClass;
        $e2->last_updated = new \DateTime('2014-12-31 07:15:46');

        return [
            [
                ['foo', 'bar'], 'foo_bar',
            ],
            [
                ['baz', $e1], 'baz_1420006545',
            ],
            [
                ['hage', [$e2, 'bar']], 'hage_1420006546_bar',
            ],
            [
                [new TestEntity(new \DateTime('2014-12-31 07:15:47')), 'paye'], '1420006547_paye',
            ],
        ];
    }
}

class TestEntity
{
    protected $lastUpdated;

    public function __construct(\DateTime $lastUpdated)
    {
        $this->lastUpdated = $lastUpdated;
    }

    public function getLastUpdated()
    {
        return $this->lastUpdated;
    }
}
 