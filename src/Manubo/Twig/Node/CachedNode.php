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

        $compiler->write("\$_manubo_cached_key = \$this->env->getExtension('$extension')->compileKey(");
        $compiler->subcompile($this->getAttribute('cacheKey'));
        $compiler->raw(");\n");

        $compiler->write(
            "\$_manubo_cached_item = \$this->env->getExtension('$extension')
    ->getCache()->getItem(\$_manubo_cached_key);\n"
        );
        $compiler->write("if (\$_manubo_cached_item->isHit()) {\n");
        $compiler->indent();
        $compiler->write("eval(\$_manubo_cached_item->get());\n");
        $compiler->outdent();
        $compiler->write("} else {\n");
        $compiler->indent();
        $compiler->write("\$_manubo_cached_item->set('");
        $compiler->outdent();
        $compiler->subcompile($this->getNode('body'));
        $compiler->raw("'");
        if (null !== $this->getAttribute('ttl')) {
            $compiler->raw(', ');
            $compiler->subcompile($this->getAttribute('ttl'));
        }
        $compiler->raw(");\n");
        $compiler->indent();
        $compiler->write("\$_manubo_cached_item->save();\n");
        $compiler->write("eval(\$_manubo_cached_item->get());\n");
        $compiler->outdent();
        $compiler->write("}\n");
    }
}
 