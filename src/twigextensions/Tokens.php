<?php

/* @see https://github.com/marionnewlevant/craft-twig_perversion */

namespace modules\helpers\twigextensions;

use modules\helpers\Helpers;

use Craft;

class Tokens extends \Twig_Extension {

  public function getTokenParsers() {
		return [
			new Break_Parser(),
			new Continue_Parser(),
			new Cleanup_Parser(),
		];
	}
}

////////////////////////////////////////////////////////////////////////////////
// Nodes
////////////////////////////////////////////////////////////////////////////////

class Break_Node extends \Twig_Node {

	public function compile(\Twig_Compiler $compiler)	{
		$compiler->addDebugInfo($this)->write("break;\n");
	}

}

class Continue_Node extends \Twig_Node {

	public function compile(\Twig_Compiler $compiler)	{
		$compiler
			->addDebugInfo($this)
			->write("if (array_key_exists('loop', \$context)) {\n")
			->indent()
				->write("++\$context['loop']['index0'];\n")
				->write("++\$context['loop']['index'];\n")
				->write("\$context['loop']['first'] = false;\n")
				->write("if (isset(\$context['loop']['length'])) {\n")
				->indent()
					->write("--\$context['loop']['revindex0'];\n")
					->write("--\$context['loop']['revindex'];\n")
					->write("\$context['loop']['last'] = 0 === \$context['loop']['revindex0'];\n")
				->outdent()
				->write("}\n")
			->outdent()
			->write("}\n")
			->write("continue;\n");
	}
}

class Cleanup_Node extends \Twig_Node {

  public function compile(\Twig_Compiler $compiler) {
    $compiler
      ->addDebugInfo($this)
      ->write("ob_start();\n")
      ->subcompile($this->getNode('body'))
      ->write("\$_compiledBody = ob_get_clean();\n")
      ->write("echo ".Helpers::class."::\$app->service->cleanup(\$_compiledBody);\n");
  }
}

////////////////////////////////////////////////////////////////////////////////
// Parsers
////////////////////////////////////////////////////////////////////////////////

class Break_Parser extends \Twig_TokenParser {

  public function parse(\Twig_Token $token) {
    $this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);
    return new Break_Node([], [], $token->getLine(), $this->getTag());
  }

  public function getTag() {
    return 'break';
  }
}

class Continue_Parser extends \Twig_TokenParser {

	public function parse(\Twig_Token $token)	{
		$this->parser->getStream()->expect(\Twig_Token::BLOCK_END_TYPE);
		return new Continue_Node([], [], $token->getLine(), $this->getTag());
	}

	public function getTag() {
		return 'continue';
	}

}

class Cleanup_Parser extends \Twig_TokenParser {

  public function parse(\Twig_Token $token) {

    $stream = $this->parser->getStream();

    $stream->expect(\Twig_Token::BLOCK_END_TYPE);
    $nodes['body'] = $this->parser->subparse([$this, 'decideCleanupEnd'], true);
    $stream->expect(\Twig_Token::BLOCK_END_TYPE);

    return new Cleanup_Node($nodes, [], $token->getLine(), $this->getTag());
  }

  public function getTag() {
    return 'cleanup';
  }

  public function decideCleanupEnd(\Twig_Token $token) {
    return $token->test('endcleanup');
  }
}
