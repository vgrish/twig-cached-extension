<?php

namespace Manubo\Test\Twig\TokenParser;

use Doctrine\Common\Cache\Cache;
use Manubo\Twig\Node\CachedNode;
use Manubo\Twig\Extension\CachedExtension;

/**
 * Class CachedTest
 * @package Manubo\Test\Twig\TokenParser
 * @coversDefaultClass \Manubo\Twig\TokenParser\CachedTokenParser
 */
class CachedTokenParserTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::parse
     */
    public function testParsingCachedTagGivesCachedNode()
    {
        $twig = $this->getEnvironment();
        $parser = new \Twig_parser($twig);

        $source = '{% cached ["foo", entity.foo] %}<p>Hello World!</p>{% endcached %}';

        $stream = $twig->tokenize($source);

        $cachedNode = $parser->parse($stream)->getNode('body')->getNode(0);

        $this->assertInstanceOf(CachedNode::class, $cachedNode);
        $this->assertEquals('manubo_twig_cached_extension', $cachedNode->getAttribute('extension'));
        $this->assertInstanceOf(\Twig_Node_Expression_Array::class, $cachedNode->getAttribute('cacheKey'));
        $this->assertNull($cachedNode->getAttribute('ttl'));
        $this->assertInstanceOf(\Twig_Node_Text::class, $cachedNode->getNode('body'));
        $this->assertEquals('<p>Hello World!</p>', $cachedNode->getNode('body')->getAttribute('data'));

    }

    /**
     * @covers ::parse
     */
    public function testParsingWithTTL()
    {
        $twig = $this->getEnvironment();
        $parser = new \Twig_parser($twig);

        $source = '{% cached ["foo", entity.foo], 10 %}<p>Hello World!</p>{% endcached %}';

        $stream = $twig->tokenize($source);

        $cachedNode = $parser->parse($stream)->getNode('body')->getNode(0);

        $this->assertInstanceOf(CachedNode::class, $cachedNode);
        $this->assertEquals('manubo_twig_cached_extension', $cachedNode->getAttribute('extension'));
        $this->assertInstanceOf(\Twig_Node_Expression_Array::class, $cachedNode->getAttribute('cacheKey'));
        $this->assertInstanceOf(\Twig_Node_Expression_Constant::class, $cachedNode->getAttribute('ttl'));
        $this->assertEquals(10, $cachedNode->getAttribute('ttl')->getAttribute('value'));
    }

    protected function getEnvironment()
    {
        $cacheMock = $this->getMockForAbstractClass(Cache::class);

        $subEntity = new \stdClass();
        $subEntity->updated_at = 'baz';

        $entity = new \stdClass();
        $entity->foo = $subEntity;

        $twig = new \Twig_Environment(new \Twig_Loader_String(), array('cache' => false, 'autoescape' => false, 'optimizations' => 0));
        $twig->addExtension(new CachedExtension($cacheMock));
        $twig->addGlobal('entity', $entity);

        return $twig;
    }
}
 