<?php

namespace Manubo\Twig\TokenParser;

use Manubo\Twig\Node\CachedNode;

/**
 * Class Cached
 * @package Manubo\Twig\TokenParser
 */
class CachedTokenParser extends \Twig_TokenParser
{
    protected $extension;

    public function __construct($extension)
    {
        $this->extension = $extension;
    }
    /**
     * Parses a token and returns a node.
     *
     * @param \Twig_Token $token A Twig_Token instance
     * @throws \Twig_Error_Syntax
     *
     * @return \Twig_NodeInterface A Twig_NodeInterface instance
     */
    public function parse(\Twig_Token $token)
    {
        $parser  = $this->parser;
        $stream  = $parser->getStream();

        $cacheKey = $this->parser->getExpressionParser()->parseArrayExpression();

        $ttl = null;
        if ($stream->test(\Twig_Token::PUNCTUATION_TYPE, ',')) {
            $stream->next();
            $ttl = $this->parser->getExpressionParser()->parseExpression();
        }

        $stream->expect(\Twig_Token::BLOCK_END_TYPE);
        $body = $this->parser->subparse(array($this, 'testEndTag'), true);
        $stream->expect(\Twig_Token::BLOCK_END_TYPE);

        return new CachedNode(
            $body, [
                'cacheKey' => $cacheKey,
                'ttl' => $ttl,
                'extension' => $this->extension,
            ], $token->getLine(), $this->getTag()
        );
    }

    /**
     * @param \Twig_Token $token
     * @return bool
     */
    public function testEndTag(\Twig_Token $token)
    {
        return $token->test(array('end'.$this->getTag()));
    }

    /**
     * Gets the tag name associated with this token parser.
     *
     * @return string The tag name
     */
    public function getTag()
    {
        return 'cached';
    }
}
 