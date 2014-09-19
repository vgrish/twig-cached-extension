<?php

namespace Manubo\Twig\Node;

/**
 * Class Cached
 * @package Manubo\Twig\Node
 */
class CachedNode extends \Twig_Node
{
    protected $cacheKey;

    public function __construct($body, array $attributes = array(), $lineno = 0, $tag = null)
    {
        $nodes = ['body' => $body];
        parent::__construct($nodes, $attributes, $lineno, $tag);
    }

    /**
     * @param \Twig_Compiler $compiler
     * @return $this
     */
    public function compile(\Twig_Compiler $compiler)
    {
        $extension = $this->getAttribute('extension');

        $compiler->write("\$_manubo_cache = \$this->env->getExtension('$extension')->getCache();\n");
        $compiler->write("\$_manubo_cached_key = \$this->env->getExtension('$extension')->compileKey(");
        $compiler->subcompile($this->getAttribute('cacheKey'));
        $compiler->raw(");\n");

        $compiler->write("if (\$_manubo_cache->contains(\$_manubo_cached_key)) {\n");
        $compiler->indent();
        $compiler->write("\$_manubo_cached_body = \$_manubo_cache->fetch(\$_manubo_cached_key);\n");
        $compiler->outdent();
        $compiler->write("} else {\n");
        $compiler->indent();
        $compiler->write("ob_start();\n");
        $compiler->subcompile($this->getNode('body'));
        $compiler->write("\$_manubo_cached_body = ob_get_contents();\n");
        $compiler->write("ob_end_clean();\n");
        if (null !== $this->getAttribute('ttl')) {
            $compiler->write("\$_manubo_cached_ttl = (int) ");
            $compiler->subcompile($this->getAttribute('ttl'));
            $compiler->raw(";\n");
        } else {
            $compiler->write("\$_manubo_cached_ttl = 0;\n");
        }
        $compiler->write("\$_manubo_cache->save(\$_manubo_cached_key, \$_manubo_cached_body, \$_manubo_cached_ttl);\n");
        $compiler->outdent();
        $compiler->write("}\n");
        $compiler->write("echo \$_manubo_cached_body;\n");
    }
}
