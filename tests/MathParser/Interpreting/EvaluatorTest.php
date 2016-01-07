<?php

use MathParser\StdMathParser;
use MathParser\Interpreting\Interpreter;
use MathParser\Interpreting\Evaluator;
use MathParser\Parsing\Nodes\Node;
use MathParser\Parsing\Nodes\ConstantNode;
use MathParser\Parsing\Nodes\NumberNode;
use MathParser\Parsing\Nodes\VariableNode;
use MathParser\Parsing\Nodes\FunctionNode;
use MathParser\Parsing\Nodes\ExpressionNode;


use MathParser\Exceptions\UnknownVariableException;
use MathParser\Exceptions\UnknownConstantException;
use MathParser\Exceptions\UnknownFunctionException;
use MathParser\Exceptions\UnknownOperatorException;
use MathParser\Exceptions\DivisionByZeroException;

class EvaluatorTest extends PHPUnit_Framework_TestCase
{
    private $parser;
    private $evaluator;
    private $variables;

    public function setUp()
    {
        $this->parser = new StdMathParser();

        $this->variables = array('x' => '0.7', 'y' => '2.1');
        $this->evaluator = new Evaluator($this->variables);
    }

    private function evaluate($f)
    {
        $this->evaluator->setVariables($this->variables);
        return $f->accept($this->evaluator);
    }

    private function assertResult($f, $x)
    {
        $value = $this->evaluate($this->parser->parse($f));
        $this->assertEquals($value, $x);
    }

    public function testCanEvaluateNumber()
    {
        $this->assertResult('3', 3);
        $this->assertResult('-2', -2);
    }

    public function testCanEvaluateConstant()
    {
        $this->assertResult('pi', pi());
        $this->assertResult('e', exp(1));

        $f = new ConstantNode('sdf');
        $this->setExpectedException(UnknownConstantException::class);
        $value = $this->evaluate($f);
    }

    public function testCanEvaluateVariable()
    {
        $this->assertResult('x', $this->variables['x']);

        $this->setExpectedException(UnknownVariableException::class);

        $f = $this->parser->parse("q");
        $value = $this->evaluate($f);
    }

    public function testCanEvaluateAdditiion()
    {
        $x = $this->variables['x'];
        $this->assertResult('3+x', 3+$x);
        $this->assertResult('3+x+1', 3+$x+1);
    }

    public function testCanEvaluateSubtraction()
    {
        $x = $this->variables['x'];
        $this->assertResult('3-x', 3-$x);
        $this->assertResult('3-x-1', 3-$x-1);
    }

    public function testCanEvaluateUnaryMinus()
    {
        $this->assertResult('-x', -$this->variables['x']);
    }

    public function testCanEvaluateMultiplication()
    {
        $x = $this->variables['x'];
        $this->assertResult('3*x', 3*$x);
        $this->assertResult('3*x*2', 3*$x*2);
    }

    public function testCanEvaluateDivision()
    {
        $x = $this->variables['x'];
        $this->assertResult('3/x', 3/$x);
        $this->assertResult('20/x/5', 20/$x/5);
    }

    public function testCannotDivideByZero()
    {
        $f = new ExpressionNode(3, '/', 0);

        $this->setExpectedException(DivisionByZeroException::class);
        $value = $this->evaluate($f);
    }


    public function testCanEvaluateExponentiation()
    {
        $x = $this->variables['x'];
        $this->assertResult('x^3', pow($x,3));
        $this->assertResult('x^x^x', pow($x,pow($x,$x)));
        $this->assertResult('0^0', 1);
        $this->assertResult('(-1)^(-1)', -1);
    }

    public function testExponentiationExceptions()
    {
        $f = $this->parser->parse('0^(-1)');
        $value = $this->evaluate($f);

        $this->assertTrue(is_infinite($value));

        $f = $this->parser->parse('(-1)^(1/2)');
        $value = $this->evaluate($f);

        $this->assertTrue(is_nan($value));
    }

    public function testCanEvaluateSine()
    {
        $this->assertResult('sin(pi)', 0);
        $this->assertResult('sin(pi/2)', 1);
        $this->assertResult('sin(pi/6)', 0.5);
        $this->assertResult('sin(x)', sin($this->variables['x']));
    }

    public function testCanEvaluateCosine()
    {
        $this->assertResult('cos(pi)', -1);
        $this->assertResult('cos(pi/2)', 0);
        $this->assertResult('cos(pi/3)', 0.5);
        $this->assertResult('cos(x)', cos($this->variables['x']));
    }

    public function testCanEvaluateTangent()
    {
        $this->assertResult('tan(pi)', 0);
        $this->assertResult('tan(pi/4)', 1);
        $this->assertResult('tan(x)', tan($this->variables['x']));
    }

    public function testCanEvaluateCotangent()
    {
        $this->assertResult('cot(pi/2)', 0);
        $this->assertResult('cot(pi/4)', 1);
        $this->assertResult('cot(x)', 1/tan($this->variables['x']));
    }

    public function testCanEvaluateArcsin()
    {
        $this->assertResult('arcsin(1)', pi()/2);
        $this->assertResult('arcsin(1/2)', pi()/6);
        $this->assertResult('arcsin(x)', asin($this->variables['x']));

        $f = $this->parser->parse('arcsin(2)');
        $value = $this->evaluate($f);

        $this->assertNaN($value);

    }

    public function testCanEvaluateArccos()
    {
        $this->assertResult('arccos(0)', pi()/2);
        $this->assertResult('arccos(1/2)', pi()/3);
        $this->assertResult('arccos(x)', acos($this->variables['x']));

        $f = $this->parser->parse('arccos(2)');
        $value = $this->evaluate($f);

        $this->assertNaN($value);

    }

    public function testCanEvaluateArctan()
    {
        $this->assertResult('arctan(1)', pi()/4);
        $this->assertResult('arctan(x)', atan($this->variables['x']));
    }

    public function testCanEvaluateArccot()
    {
        $this->assertResult('arccot(1)', pi()/4);
        $this->assertResult('arccot(x)', pi()/2-atan($this->variables['x']));
    }

    public function testCanEvaluateExp()
    {
        $this->assertResult('exp(x)', exp($this->variables['x']));
    }

    public function testCanEvaluateLog()
    {
        $this->assertResult('log(x)', log($this->variables['x']));

        $f = $this->parser->parse('log(-1)');
        $value = $this->evaluate($f);

        $this->assertNaN($value);

    }

    public function testCanEvaluateLog10()
    {
        $this->assertResult('log10(x)', log($this->variables['x'])/log(10));
    }

    public function testCanEvaluateSqrt()
    {
        $this->assertResult('sqrt(x)', sqrt($this->variables['x']));

        $f = $this->parser->parse('sqrt(-2)');
        $value = $this->evaluate($f);

        $this->assertNaN($value);
    }


    public function testCanEvaluateHyperbolicFunctions()
    {
        $x = $this->variables['x'];

        $this->assertResult('sinh(0)', 0);
        $this->assertResult('sinh(x)', sinh($x));

        $this->assertResult('cosh(0)', 1);
        $this->assertResult('cosh(x)', cosh($x));

        $this->assertResult('tanh(0)', 0);
        $this->assertResult('tanh(x)', tanh($x));

        $this->assertResult('coth(x)', 1/tanh($x));

        $this->assertResult('arsinh(0)', 0);
        $this->assertResult('arsinh(x)', asinh($x));

        $this->assertResult('arcosh(1)', 0);
        $this->assertResult('arcosh(3)', acosh(3));

        $this->assertResult('artanh(0)', 0);
        $this->assertResult('artanh(x)', atanh($x));

        $this->assertResult('arcoth(3)', atanh(1/3));
    }

    public function testCannotEvalauateUnknownFunction()
    {
        $f = new FunctionNode('sdf', new NumberNode(1));

        $this->setExpectedException(UnknownFunctionException::class);
        $value = $this->evaluate($f);

    }

    public function testCannotEvaluateUnknownOperator()
    {
        $node = new ExpressionNode(new NumberNode(1), '+', new VariableNode('x'));
        // We need to cheat here, since the ExpressionNode contructor already
        // throws an UnknownOperatorException when called with, say '%'
        $node->setOperator('%');
        $this->setExpectedException(UnknownOperatorException::class);

        $this->evaluate($node);

    }

    public function testCanCreateTemporaryUnaryMinusNode()
    {
        $node = new ExpressionNode(null, '~', null);
        $this->assertEquals($node->getOperator(), '~');
        $this->assertNull($node->getRight());
        $this->assertNull($node->getLeft());
        $this->assertEquals($node->getPrecedence(), 25);
    }

    public function testUnknownException()
    {
        $this->setExpectedException(UnknownOperatorException::class);
        $node = new ExpressionNode(null, '%', null);
    }
}
