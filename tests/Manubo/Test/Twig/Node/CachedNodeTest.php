<?php

namespace Manubo\Test\Twig\Node;

use Psr\Cache\CacheItemPoolInterface;
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
\$_manubo_cached_key = \$this->env->getExtension('manubo_cached_extension')->compileKey(array(0 => "foo", 1 => \$this->getAttribute((isset(\$context["entity"]) ? \$context["entity"] : null), "foo")));
\$_manubo_cached_item = \$this->env->getExtension('manubo_cached_extension')
    ->getCache()->getItem(\$_manubo_cached_key);
if (\$_manubo_cached_item->isHit()) {
    eval(\$_manubo_cached_item->get());
} else {
    \$_manubo_cached_item->set('// line 1
echo "<p>Hello World!</p>";
');
    \$_manubo_cached_item->save();
    eval(\$_manubo_cached_item->get());
}

EOF;

        $this->assertEquals(
            $expected,
            $compiler->getSource()
        );
    }

    public function testCompilingCachedTagWithIntTTLCompilesToCodeUsingCacheManager()
    {
        $source = '{% cached ["foo", entity.foo], 10 %}<p>Hello World!</p>{% endcached %}';
        $twig = $this->getEnvironment();
        $compiler = new \Twig_Compiler($twig);
        $parser   = new \Twig_parser($twig);

        $stream = $twig->tokenize($source);
        $cachedNode = $parser->parse($stream)->getNode('body')->getNode(0);
        $cachedNode->compile($compiler);

        $this->assertContains(', 10', $compiler->getSource());
    }

    public function testCompilingCachedTagWithDateTTLCompilesToCodeUsingCacheManager()
    {
        $source = '{% cached ["foo", entity.foo], "2014-12-31 07:15:45"|date("Y-m-d H:i:s") %}<p>Hello World!</p>{% endcached %}';
        $twig = $this->getEnvironment();
        $compiler = new \Twig_Compiler($twig);
        $parser   = new \Twig_parser($twig);

        $stream = $twig->tokenize($source);
        $cachedNode = $parser->parse($stream)->getNode('body')->getNode(0);
        $cachedNode->compile($compiler);

        $this->assertContains(
            'twig_date_format_filter($this->env, "2014-12-31 07:15:45", "Y-m-d H:i:s")',
            $compiler->getSource()
        );
    }



    protected function getEnvironment()
    {
        $cacheMock = $this->getMockForAbstractClass(CacheItemPoolInterface::class);

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
 