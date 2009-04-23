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

require 'PHPTAL/Php/Attribute/PHPTAL/Tales.php';
require 'PHPTAL/Php/Attribute/PHPTAL/Debug.php';
require 'PHPTAL/Php/Attribute/PHPTAL/Id.php';
require 'PHPTAL/Php/Attribute/PHPTAL/Cache.php';

/**
 * @package PHPTAL.namespace
 */
class PHPTAL_Namespace_PHPTAL extends PHPTAL_BuiltinNamespace
{
    public function __construct()
    {
        parent::__construct('phptal', 'http://phptal.motion-twin.com/ns/phptal');
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('tales', -1));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('debug', -2));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('id', 7));
        $this->addAttribute(new PHPTAL_NamespaceAttributeSurround('cache', -3));
    }
}

PHPTAL_Dom_Defs::getInstance()->registerNamespace(new PHPTAL_Namespace_PHPTAL());

?>
