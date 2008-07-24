<?php

require_once PHPTAL_DIR.'PHPTAL/Php/Node.php';
require_once PHPTAL_DIR.'PHPTAL/Php/State.php';
require_once PHPTAL_DIR.'PHPTAL/Php/CodeWriter.php';

/**
 * @package phptal.php
 */
class PHPTAL_Php_CodeGenerator
{
    public function __construct($function_name, $source_path)
    {
        $this->_functionName = $function_name;
        $this->_sourceFile = $source_path;
        $this->_state = new PHPTAL_Php_State();
        $this->_writer = new PHPTAL_Php_CodeWriter($this->_state);
    }

    public function setOutputMode($mode)
    { 
        $this->_state->setOutputMode($mode);
    }
    
    public function setEncoding($enc)
    { 
        $this->_state->setEncoding($enc);
    }

    public function generate(PHPTAL_Dom_Tree $tree)
    {
        $treeGen = new PHPTAL_Php_Tree($this->_writer, $tree);

        $this->_writer->doComment('Generated by PHPTAL from '.$this->_sourceFile);
        $this->_writer->doFunction($this->_functionName, '$tpl, $ctx');
        $this->_writer->setFunctionPrefix($this->_functionName . "_");
        $this->_writer->doSetVar('$glb', '$tpl->getGlobalContext()');
        $treeGen->generate();
        $this->_writer->doEnd();
    }

    public function getResult()
    {
        return $this->_writer->getResult();
    }

    private $_functionName;
    private $_sourceFile;
    private $_writer;
    private $_state;
}

?>
