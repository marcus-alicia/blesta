<?php

class Explode_Tag extends H2o_Node
{
    public $position;
    private $variable;
    private $seperator;
    private $shortcut;
    private $nodelist;
    private $syntax = '/^(?P<var>[\w]+(:?\.[\w\d]+)*)\s+on\s+(?P<sepquote>[\'"])(?P<separator>(?!.*\k<sepquote>.*\k<sepquote>).*)\k<sepquote>\s+as\s+(?P<short>[\w]+(:?\.[\w\d]+)?)$/';

    function __construct($argstring, $parser, $pos = 0)
    {
        if (!preg_match($this->syntax, $argstring, $matches)) {
            throw new TemplateSyntaxError('Invalid explode tag syntax');
        }

        # extract the long name, separator, and shortcut
        $this->variable = $matches['var'];
        $this->seperator = $matches['separator'];
        $this->shortcut = $matches['short'];
        $this->nodelist = $parser->parse('endexplode');
    }

    function render($context, $stream)
    {
        $variable = $context->getVariable($this->variable);
        $exploded = explode($this->seperator, $variable);
        $context->push([$this->shortcut => $exploded]);
        $this->nodelist->render($context, $stream);
        $context->pop();
    }
}

H2o::addTag('explode');
