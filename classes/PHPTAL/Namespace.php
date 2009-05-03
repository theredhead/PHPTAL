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
 * @link     http://phptal.org/
 */

require_once 'PHPTAL/Php/Attribute.php';
require_once 'PHPTAL/Namespace/TAL.php';
require_once 'PHPTAL/Namespace/METAL.php';
require_once 'PHPTAL/Namespace/I18N.php';
require_once 'PHPTAL/Namespace/PHPTAL.php';
require_once 'PHPTAL/NamespaceAttribute.php';

/**
 * @see PHPTAL_NamespaceAttribute
 * @package PHPTAL
 * @subpackage Namespace
 */
abstract class PHPTAL_Namespace
{
    private $prefix, $namespace_uri;
    protected $_attributes;
    
    public function __construct($prefix, $namespace_uri)
    {
        $this->_attributes = array();
        $this->prefix = $prefix;
        $this->namespace_uri = $namespace_uri;
    }

    public function getPrefix()
    {
        return $this->prefix;
    }

    public function getNamespaceURI()
    {
        return $this->namespace_uri;
    }

    public function hasAttribute($attributeName)
    {
        return array_key_exists(strtolower($attributeName), $this->_attributes);
    }

    public function getAttribute($attributeName)
    {
        return $this->_attributes[strtolower($attributeName)];
    }

    public function addAttribute(PHPTAL_NamespaceAttribute $attribute)
    {
        $attribute->setNamespace($this);
        $this->_attributes[strtolower($attribute->getLocalName())] = $attribute;
    }

    public function getAttributes()
    {
        return $this->_attributes;
    }

    abstract public function createAttributeHandler(PHPTAL_NamespaceAttribute $att, PHPTAL_Dom_Element $tag, $expression);
}

/**
 * @package PHPTAL
 * @subpackage Namespace
 */
class PHPTAL_BuiltinNamespace extends PHPTAL_Namespace
{
    public function createAttributeHandler(PHPTAL_NamespaceAttribute $att, PHPTAL_Dom_Element $tag, $expression)
    {
        $name = $att->getLocalName();
        $name = str_replace('-', '', $name);

        $class = 'PHPTAL_Php_Attribute_'.$this->getPrefix().'_'.$name;
        $result = new $class($tag, $expression);
        return $result;
    }
}
