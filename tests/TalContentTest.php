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
require_once PHPTAL_DIR.'PHPTAL/Php/Tales.php';

class DummyToStringObject {
    public function __construct($value){ $this->_value = $value; }
    public function __toString(){ return $this->_value; }
    private $_value;
}

class TalContentTest extends PHPTAL_TestCase 
{
    function testSimple()
    {
        $tpl = $this->newPHPTAL('input/tal-content.01.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-content.01.html');
        $this->assertEquals($exp, $res);
    }

    function testVar()
    {
        $tpl = $this->newPHPTAL('input/tal-content.02.html');
        $tpl->content = 'my content';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-content.02.html');
        $this->assertEquals($exp, $res);
    }

    function testStructure()
    {
        $tpl = $this->newPHPTAL('input/tal-content.03.html');
        $tpl->content = '<foo><bar/></foo>';
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-content.03.html');
        $this->assertEquals($exp, $res);
    }

    function testNothing()
    {
        $tpl = $this->newPHPTAL('input/tal-content.04.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-content.04.html');
        $this->assertEquals($exp, $res);
    }
    
    function testDefault()
    {
        $tpl = $this->newPHPTAL('input/tal-content.05.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-content.05.html');
        $this->assertEquals($exp, $res);
    }

    function testChain()
    {
        $tpl = $this->newPHPTAL('input/tal-content.06.html');
        $res = trim_string($tpl->execute());
        $exp = trim_file('output/tal-content.06.html');
        $this->assertEquals($exp, $res);
    }

    function testEmpty()
    {
        $src = '
<root>
<span tal:content="nullv | falsev | emptystrv | zerov | default">default</span>
<span tal:content="nullv | falsev | emptystrv | default">default</span>
</root>
';
        $exp = '
<root>
<span>0</span>
<span>default</span>
</root>
';
        $tpl = $this->newPHPTAL();
        $tpl->setSource($src);
        $tpl->nullv = null;
        $tpl->falsev = false;
        $tpl->emptystrv = '';
        $tpl->zerov = 0;
        $res = $tpl->execute();
        $this->assertEquals(trim_string($exp), trim_string($res));
    }

    function testObjectEcho()
    {
        $foo = new DummyToStringObject('foo value');
        $src = <<<EOT
<root tal:content="foo"/>
EOT;
        $exp = <<<EOT
<root>foo value</root>
EOT;
        $tpl = $this->newPHPTAL();
        $tpl->setSource($src);
        $tpl->foo = $foo;
        $res = $tpl->execute();
        $this->assertEquals($res, $exp);
    }

    function testObjectEchoStructure()
    {
        $foo = new DummyToStringObject('foo value');
        $src = <<<EOT
<root tal:content="structure foo"/>
EOT;
        $exp = <<<EOT
<root>foo value</root>
EOT;
        $tpl = $this->newPHPTAL();
        $tpl->setSource($src);
        $tpl->foo = $foo;
        $res = $tpl->execute();
        $this->assertEquals($res, $exp);
    }
    
      /**
       * @expectedException PHPTAL_VariableNotFoundException
       */
      function testErrorsThrow()
      {
          $tpl = $this->newPHPTAL();
          $tpl->setSource('<p tal:content="error"/>');
          $tpl->execute();
      }

      /**
       * @expectedException PHPTAL_VariableNotFoundException
       */
      function testErrorsThrow2()
      {
          $tpl = $this->newPHPTAL();
          $tpl->setSource('<p tal:content="error | error"/>');
          $tpl->execute();
      }

      function testErrorsSilenced()
      {
          $tpl = $this->newPHPTAL();
          $tpl->setSource('<p tal:content="error | nothing"/>');
          $this->assertEquals('<p></p>',$tpl->execute());
      }

      function testZeroIsNotEmpty()
      {
          $tpl = $this->newPHPTAL();
          $tpl->zero = '0';
          $tpl->setSource('<p tal:content="zero | error"/>');
          $this->assertEquals('<p>0</p>',$tpl->execute());
      }
}

