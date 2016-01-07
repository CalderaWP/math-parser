<?php
/*
 * @package     Parsing
 * @author      Frank Wikström <frank@mossadal.se>
 * @copyright   2015 Frank Wikström
 * @license     http://www.opensource.org/licenses/lgpl-license.php LGPL
 *
 */

/**
 * @namespace MathParser::Parsing::Nodes
 *
 * Node classes for use in the generated abstract syntax trees (AST).
 */
namespace MathParser\Parsing\Nodes;

use MathParser\Interpreting\Visitors\Visitable;
use MathParser\Lexing\Token;
use MathParser\Lexing\TokenType;
use MathParser\Lexing\TokenPrecedence;
use MathParser\Interpreting\Evaluator;

use MathParser\Exceptions\UnknownNodeException;
use MathParser\Exceptions\UnknownOperatorException;

/**
 * Abstract base class for nodes in the abstract syntax tree
 * generated by the Parser (and some AST transformers).
 *
 */
abstract class Node implements Visitable
{
    /**
     * Node factory, creating an appropriate Node from a Token.
     *
     * Based on the provided Token, returns a TerminalNode if the
     * token type is PosInt, Integer, RealNumber, Identifier or Constant
     * otherwise returns null.
     *
     * @param Token $token Provided token
     * @retval Node|null
     */
    public static function factory(Token $token)
    {
        switch($token->getType()) {
            case TokenType::PosInt:
            case TokenType::Integer:
                $x = intval($token->getValue());
                return new NumberNode($x);
            case TokenType::RealNumber:
                $x = floatval($token->getValue());
                return new NumberNode($x);
            case TokenType::Identifier:
                return new VariableNode($token->getValue());
            case TokenType::Constant:
                return new ConstantNode($token->getValue());

            case TokenType::FunctionName:
                return new FunctionNode($token->getValue(), null);
            case TokenType::OpenParenthesis:
                return new SubExpressionNode($token->getValue());

            case TokenType::AdditionOperator:
            case TokenType::SubtractionOperator:
            case TokenType::MultiplicationOperator:
            case TokenType::DivisionOperator:
            case TokenType::ExponentiationOperator:
                return new ExpressionNode(null, $token->getValue(), null);

            default:
                // echo "Node factory returning null on $token\n";
                return null;
        }

    }

    /**
     * Helper function, comparing two ASTs. Useful for testing
     * and also for some AST transformers.
     *
     * @param Node $other Compare to this tree
     * @retval boolean
     */
    public function compareTo($other)
    {
            if ($other === null) return false;

            if ($this instanceof ConstantNode) {
                if (!($other instanceof ConstantNode)) return false;
                return $this->getName() == $other->getName();
            }
            if ($this instanceof ExpressionNode) {
                if (!($other instanceof ExpressionNode)) return false;

                $thisLeft = $this->getLeft();
                $otherLeft = $other->getLeft();
                if ($thisLeft === null) return ($otherLeft === null);

                $thisRight = $this->getRight();
                $otherRight = $other->getRight();
                if ($thisRight === null) return ($otherRight === null);

                return $thisLeft->compareTo($otherLeft) && $thisRight->compareTo($otherRight);
            }
            if ($this instanceof FunctionNode) {
                if (!($other instanceof FunctionNode)) return false;

                $thisOperand = $this->getOperand();
                $otherOperand = $other->getOperand();

                if ($thisOperand === null) return ($otherOperand === null);

                return $thisOperand->compareTo($otherOperand);;
            }
            if ($this instanceof NumberNode) {
                if (!($other instanceof NumberNode)) return false;
                return $this->getValue() == $other->getValue();
            }
            if ($this instanceof VariableNode) {
                if (!($other instanceof VariableNode)) return false;
                return $this->getName() == $other->getName();
            }


            throw new UnknownNodeException();
    }

    /**
     * Convenience function for evaluating a tree, using
     * the Evaluator class.
     *
     * Example usage:
     *
     * ~~~{.php}
     * $parser = new StdMathParser();
     * $node = $parser->parse('sin(x)cos(y)');
     * $functionValue = $node->evaluate( array( 'x' => 1.3, 'y' => 1.4 ) );
     * ~~~
     *
     * @param array $variables key-value array of variable values
     * @retval floatval
     **/
    public function evaluate($variables)
    {
        $evaluator = new Evaluator($variables);
        return $this->accept($evaluator);
    }

    /**
     * Rough estimate of the complexity of the AST.
     *
     * Gives a rough measure of the complexity of an AST. This can
     * be useful to choose between different simplification rules
     * or how to print a tree ("e^{...}" or ("\exp(...)") for example.
     *
     * More precisely, the complexity is computed as the sum of
     * the complexity of all nodes of the AST, and
     *
     * * NumberNodes, VariableNodes and ConstantNodes have complexity 1,
     * * FunctionNodes have complexity 5 (plus the complexity of its operand),
     * * ExpressionNodes have complexity 2 (for `+`, `-`, `*`), 4 (for `/`),
     *  or 8 (for `^`)
     *
     */
    public function complexity()
    {
        if ($this instanceof NumberNode || $this instanceof VariableNode || $this instanceof ConstantNode) {
            return 1;
        } elseif ($this instanceof FunctionNode) {
            return 5+$this->getOperand()->complexity();
        } elseif ($this instanceof ExpressionNode) {
            $operator = $this->getOperator();
            $left = $this->getLeft();
            $right = $this->getRight();
            switch ($operator) {
                case '+':
                case '-':
                case '*':
                    return 2 + $left->complexity() + (($right === null) ? 0 : $right->complexity());

                case '/':
                    return 4 + $left->complexity() + (($right === null) ? 0 : $right->complexity());

                case '^':
                    return 8 + $left->complexity() + (($right === null) ? 0 : $right->complexity());

            }
        }
        // This shouldn't happen under normal circumstances
        return 1000;
    }

    /**
     * Returns true if the node is a terminal node, i.e.
     * a NumerNode, VariableNode or ConstantNode.
     *
     * @retval boolean
     **/
    public function isTerminal()
    {
        if ($this instanceof NumberNode) return true;
        if ($this instanceof VariableNode) return true;
        if ($this instanceof ConstantNode) return true;

        return false;
    }

}
