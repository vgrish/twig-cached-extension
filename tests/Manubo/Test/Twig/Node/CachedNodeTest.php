<?php

namespace Manubo\Test\Twig\Node;

use Doctrine\Common\Cache\Cache;
use Manubo\Twig\Extension\CachedExtension;

/**
 * Class CachedTest
 * @package Manubo\Test\Twig\Node
 * @coversDefaultClass \Manubo\Twig\Node\CachedNode
 */
class CachedNodeTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @covers ::compile
     */
    public function testCompilingCachedTagCompilesToCodeUsingCacheManager()
    {
        $source = '{% cached ["foo", entity.foo] %}<p>Hello World!</p>{% endcached %}';
        $twig = $this->getEnvironment();
        $compiler = new \Twig_Compiler($twig);
        $parser   = new \Twig_parser($twig);

        $stream = $twig->tokenize($source);
        $cachedNode = $parser->parse($stream)->getNode('body')->getNode(0);
        $cachedNode->compile($compiler);

        $expected = <<<EOF
\$_manubo_cache = \$this->env->getExtension('manubo_twig_cached_extension')->getCache();
\$_manubo_cached_key = \$this->env->getExtension('manubo_twig_cached_extension')->compileKey(array(0 => "foo", 1 => \$this->getAttribute((isset(\$context["entity"]) ? \$context["entity"] : null), "foo", array())));
if (\$_manubo_cache->contains(\$_manubo_cached_key)) {
    \$_manubo_cached_body = \$_manubo_cache->fetch(\$_manubo_cached_key);
} else {
    ob_start();
    // line 1
    echo "<p>Hello World!</p>";
    \$_manubo_cached_body = ob_get_contents();
    ob_end_clean();
    \$_manubo_cached_ttl = 0;
    \$_manubo_cache->save(\$_manubo_cached_key, \$_manubo_cached_body, \$_manubo_cached_ttl);
}
echo \$_manubo_cached_body;

EOF;

        $this->assertEquals(
            $expected,
            $compiler->getSource()
        );
    }

    public function testCompilingCachedTagWithIntTTLCompilesToCodeUsingCache()
    {
        $source = '{% cached ["foo", entity.foo], 10 %}<p>Hello World!</p>{% endcached %}';
        $twig = $this->getEnvironment();
        $compiler = new \Twig_Compiler($twig);
        $parser   = new \Twig_parser($twig);

        $stream = $twig->tokenize($source);
        $cachedNode = $parser->parse($stream)->getNode('body')->getNode(0);
        $cachedNode->compile($compiler);

        $this->assertContains("\$_manubo_cached_ttl = (int) 10;\n", $compiler->getSource());
    }

    public function testCompilingCachedTagWithStringTTLCompilesToCodeUsingCache()
    {
        $source = '{% cached ["foo", entity.foo], "10" %}<p>Hello World!</p>{% endcached %}';
        $twig = $this->getEnvironment();
        $compiler = new \Twig_Compiler($twig);
        $parser   = new \Twig_parser($twig);

        $stream = $twig->tokenize($source);
        $cachedNode = $parser->parse($stream)->getNode('body')->getNode(0);
        $cachedNode->compile($compiler);

        $this->assertContains("\$_manubo_cached_ttl = (int) \"10\";\n", $compiler->getSource());
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
 