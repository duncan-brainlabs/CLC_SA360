<?php declare(strict_types=1);

use Phan\AST\AnalysisVisitor;
use Phan\CodeBase;
use Phan\Language\Context;
use Phan\Language\Element\Method;
use Phan\Plugin;
use Phan\Plugin\PluginImplementation;
use ast\Node;
use ast\Node\Decl;

/**
 * This plugin checks that all methods have a Doc block.
 *
 * It is assumed without being checked that plugins aren't
 * mangling state within the passed code base or context.
 */
class MethodTypePlugin extends PluginImplementation {

    /**
     * @param CodeBase $code_base
     * The code base in which the node exists
     *
     * @param Context $context
     * The context in which the node exits. This is
     * the context inside the given node rather than
     * the context outside of the given node
     *
     * @param Node $node
     * The php-ast Node being analyzed.
     *
     * @param Node $node
     * The parent node of the given node (if one exists).
     *
     * @return void
     */
    public function analyzeNode(
        CodeBase $code_base,
        Context $context,
        Node $node,
        Node $parent_node = null
    ) {
        (new MethodTypeVisitor($code_base, $context, $this))(
            $node
        );
    }

    public function analyzeMethod(
        CodeBase $code_base,
        Method $method
    ) {
        // Warn if the parameter types are missing.
        $parameters = $method->getParameterList();
        foreach ($parameters as $parameter) {
            if ($parameter->getUnionType()->isEmpty()) {
                $this->emitIssue(
                    $code_base,
                    $method->getContext(),
                    'MethodTypePluginMissingParameterTyoe',
                    "Parameter {$parameter->getName()} in " .
                    "method {$method->getFQSEN()} is missing type"
                );
            }
        }

        // Warn if the return type is missing.
        if ($method->getUnionType()->isEmpty()) {
            $this->emitIssue(
                $code_base,
                $method->getContext(),
                'MethodTypePluginMissingReturnType',
                "Method {$method->getFQSEN()} is missing return type"
            );
        }

    }
}

/**
 * When __invoke on this class is called with a node, a method
 * will be dispatched based on the `kind` of the given node.
 *
 * Visitors such as this are useful for defining lots of different
 * checks on a node based on its kind.
 */
class MethodTypeVisitor extends AnalysisVisitor {

    /** @var Plugin */
    private $plugin;

    public function __construct(
        CodeBase $code_base,
        Context $context,
        Plugin $plugin
    ) {
        // After constructing on parent, `$code_base` and
        // `$context` will be available as protected properties
        // `$this->code_base` and `$this->context`.
        parent::__construct($code_base, $context);

        // We take the plugin so that we can call
        // `$this->plugin->emitIssue(...)` on it to emit issues
        // to the user.
        $this->plugin = $plugin;
    }

    /**
     * Default visitor that does nothing
     *
     * @param Node $node
     * A node to analyze
     *
     * @return void
     */
    public function visit(Node $node) {
    }
}

// Every plugin needs to return an instance of itself at the
// end of the file in which its defined.
return new MethodTypePlugin;
