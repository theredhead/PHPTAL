<?php
/**
 * PHPTAL templating engine
 *
 * PHP Version 5
 *
 * @category HTML
 * @package  PHPTAL
 * @author   Laurent Bedubourg <lbedubourg@motion-twin.com>
 * @author   Kornel Lesiński <kornel@aardvarkmedia.co.uk>
 * @license  http://www.gnu.org/licenses/lgpl.html GNU Lesser General Public License
 * @version  SVN: $Id$
 * @link     http://phptal.motion-twin.com/ 
 */
require_once PHPTAL_DIR.'PHPTAL/Php/Transformer.php';

class PhpTransformerTest extends PHPTAL_TestCase
{
    function testBooleanOperators()
    {
        $this->assertEquals('! $a', PHPTAL_Php_Transformer::transform('not a'));
        $this->assertEquals('$a || $b', PHPTAL_Php_Transformer::transform('a or b'));
        $this->assertEquals('($a || $b) && ($z && $x) && (10 < 100)', PHPTAL_Php_Transformer::transform('(a or b) and (z and x) and (10 < 100)'));
    }
    
    function testPathes()
    {
        $this->assertEquals('$a', PHPTAL_Php_Transformer::transform('a'));
        $this->assertEquals('$a->b', PHPTAL_Php_Transformer::transform('a.b'));
        $this->assertEquals('$a->b->c', PHPTAL_Php_Transformer::transform('a.b.c'));
    }
    
    function testFunctionAndMethod()
    {
        $this->assertEquals('a()', PHPTAL_Php_Transformer::transform('a()'));
        $this->assertEquals('$a->b()', PHPTAL_Php_Transformer::transform('a.b()'));
        $this->assertEquals('$a->b[$c]()', PHPTAL_Php_Transformer::transform('a.b[c]()'));
        $this->assertEquals('$a->b[$c]->d()', PHPTAL_Php_Transformer::transform('a.b[c].d()'));
    }

    function testArrays()
    {
        $this->assertEquals('$a[0]', PHPTAL_Php_Transformer::transform('a[0]'));
        $this->assertEquals('$a["my key"]', PHPTAL_Php_Transformer::transform('a["my key"]'));
        $this->assertEquals('$a->b[$c]', PHPTAL_Php_Transformer::transform('a.b[c]'));
    }

    function testConcat()
    {
        $this->assertEquals('$a . $c . $b', PHPTAL_Php_Transformer::transform('a . c . b'));
    }

    function testStrings()
    {
        $this->assertEquals('"prout"', PHPTAL_Php_Transformer::transform('"prout"'));
        $this->assertEquals("'prout'", PHPTAL_Php_Transformer::transform("'prout'"));
        $this->assertEquals('"my string\" still in string"', 
                            PHPTAL_Php_Transformer::transform('"my string\" still in string"'));
        $this->assertEquals("'my string\' still in string'", 
                            PHPTAL_Php_Transformer::transform("'my string\' still in string'"));
    }

    function testStringParams()
    {
        $this->assertEquals('strtolower(\'AAA\')', 
                            PHPTAL_Php_Transformer::transform('strtolower(\'AAA\')')
                           );
    }

    function testEvals()
    {
        $this->assertEquals('${$a}', PHPTAL_Php_Transformer::transform('$a'));
        $this->assertEquals('$a->{$b}->c', PHPTAL_Php_Transformer::transform('a.$b.c'));
        $this->assertEquals('$a->{$x->y}->z', PHPTAL_Php_Transformer::transform('a.{x.y}.z'));
        $this->assertEquals('$a->{$x->y}()', PHPTAL_Php_Transformer::transform('a.{x.y}()'));
    }

    function testOperators()
    {
        $this->assertEquals('$a + 100 / $b == $d', PHPTAL_Php_Transformer::transform('a + 100 / b == d'));
        $this->assertEquals('$a * 10.03', PHPTAL_Php_Transformer::transform('a * 10.03'));
    }

    function testStatics()
    {
        $this->assertEquals('MyClass::method()', PHPTAL_Php_Transformer::transform('MyClass::method()'));
        $this->assertEquals('MyClass::CONSTANT', PHPTAL_Php_Transformer::transform('MyClass::CONSTANT'));
        $this->assertEquals('MyClass::CONSTANT_UNDER', PHPTAL_Php_Transformer::transform('MyClass::CONSTANT_UNDER'));
        $this->assertEquals('MyClass::CONSTANT_UNDER6', PHPTAL_Php_Transformer::transform('MyClass::CONSTANT_UNDER6'));
        $this->assertEquals('MyClass::ConsTant', PHPTAL_Php_Transformer::transform('MyClass::ConsTant'));
        $this->assertEquals('MyClass::$static', PHPTAL_Php_Transformer::transform('MyClass::$static'));
        $this->assertEquals('MyClass::$static->foo()', PHPTAL_Php_Transformer::transform('MyClass::$static.foo()'));
    }

    function testStringEval()
    {
        $this->assertEquals('"xxx {$a->{$b}->c[$x]} xxx"', PHPTAL_Php_Transformer::transform('"xxx ${a.$b.c[x]} xxx"'));
    }

    function testDefines()
    {
        $this->assertEquals('MY_DEFINE . $a->b', PHPTAL_Php_Transformer::transform('@MY_DEFINE . a.b'));
    }

    function testPrefix()
    {
        $this->assertEquals('$C->a->b->c[$C->x]', PHPTAL_Php_Transformer::transform('a.b.c[x]', '$C->'));
        $this->assertEquals('$C->a->{$C->b}->c[$C->x]', PHPTAL_Php_Transformer::transform('a.$b.c[x]', '$C->'));
        $this->assertEquals('"xxx {$C->a->{$C->b}->c[$C->x]} xxx"', PHPTAL_Php_Transformer::transform('"xxx ${a.$b.c[x]} xxx"', '$C->'));
    }

    function testKeywords()
    {
        $this->assertEquals('true != false', PHPTAL_Php_Transformer::transform('true ne false'));
        $this->assertEquals('$test == null', PHPTAL_Php_Transformer::transform('test eq null'));
    }

    function testTernaryOperator()
    {
        $this->assertEquals('($test)?true:false', PHPTAL_Php_Transformer::transform('(test)?true:false'));
    }

    function testInstanceOf()
    {
        $this->assertEquals('$test instanceOf Foo', PHPTAL_Php_Transformer::transform('test instanceOf Foo'));
    }

    function testTransformInString()
    {
        $src = '"do not tranform this ge string lt eq"';
        $this->assertEquals($src, PHPTAL_Php_Transformer::transform($src));
        $src = "'do not tranform this ge string lt eq'";
        $this->assertEquals($src, PHPTAL_Php_Transformer::transform($src));
    }
}

?>
