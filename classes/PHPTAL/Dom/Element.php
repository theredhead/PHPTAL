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

/**
 * For backwards compatibility only. Do not use!
 * @deprecated
 */
interface PHPTAL_Php_Tree
{
}

/**
 * Document Tag representation.
 *
 * @package PHPTAL
 * @subpackage dom
 */
class PHPTAL_Dom_Element extends PHPTAL_Dom_Node implements PHPTAL_Php_Tree
{
    protected $qualifiedName, $namespace_uri;
    private $attribute_nodes = array();
    protected $replaceAttributes = array();
    protected $contentAttributes = array();
    protected $surroundAttributes = array();
    public $headFootDisabled = false;
    public $headPrintCondition = false;
    public $footPrintCondition = false;
    public $hidden = false;
    public $childNodes = array();

    /**
     * @param string $qname           qualified name of the element, e.g. "tal:block"
     * @param string $namespace_uri   namespace of this element
     * @param array  $attribute_nodes array of PHPTAL_Dom_Attr elements
     * @param object $xmlns           object that represents namespaces/prefixes known in element's context
     */
    public function __construct($qname, $namespace_uri, array $attribute_nodes, PHPTAL_Dom_XmlnsState $xmlns)
    {
        $this->qualifiedName = $qname;
        $this->attribute_nodes = $attribute_nodes;
        $this->namespace_uri = $namespace_uri;
        $this->xmlns = $xmlns;

        // implements inheritance of element's namespace to tal attributes (<metal: use-macro>)
        foreach($attribute_nodes as $index => $attr)
        {
            // it'll work only when qname == localname, which is good
            if ($this->xmlns->isValidAttributeNS($namespace_uri, $attr->getQualifiedName()))
            {
                $this->attribute_nodes[$index] = new PHPTAL_Dom_Attr($attr->getQualifiedName(), $namespace_uri, $attr->getValueEscaped(), $attr->getEncoding());
            }
        }

        if ($this->xmlns->isHandledNamespace($this->namespace_uri))
        {
            $this->headFootDisabled = true;
        }

        $talAttributes = $this->separateAttributes();
        $this->orderTalAttributes($talAttributes);
    }

    /**
     * returns object that represents namespaces known in element's context
     */
    public function getXmlnsState()
    {
        return $this->xmlns;
    }

    /**
     * Replace <script> foo &gt; bar </script>
     * with <script>/*<![CDATA[* / foo > bar /*]]>* /</script>
     * This avoids gotcha in text/html.
     *
     * Note that PHPTAL_Dom_CDATASection::generate() does reverse operation, if needed!
     *
     * @return void
     */
    private function replaceTextWithCDATA()
    {
        $isCDATAelement = PHPTAL_Dom_Defs::getInstance()->isCDATAElementInHTML($this->getNamespaceURI(), $this->getLocalName());

        if (!$isCDATAelement) {
            return;
        }

        $valueEscaped = ''; // sometimes parser generates split text nodes. "normalisation" is needed.
        $value = '';
        foreach($this->childNodes as $node)
        {
            // leave it alone if there is CDATA, comment, or anything else.
            if (!$node instanceOf PHPTAL_Dom_Text) return;

            $value .= $node->getValue();
            $valueEscaped .= $node->getValueEscaped();

            $encoding = $node->getEncoding(); // encoding of all nodes is the same
        }

        // only add cdata if there are entities
        // and there's no ${structure} (because it may rely on cdata syntax)
        if (false === strpos($valueEscaped,'&') || preg_match('/<\?|\${structure/', $value)) {
            return;
        }

        $this->childNodes = array();

        // appendChild sets parent
        $this->appendChild(new PHPTAL_Dom_Text('/*', $encoding));
        $this->appendChild(new PHPTAL_Dom_CDATASection('*/'.$value.'/*', $encoding));
        $this->appendChild(new PHPTAL_Dom_Text('*/', $encoding));
    }

    /**
     * support <?php ?> inside attributes
     */
    private function replacePHPAttributes()
    {
        foreach($this->attribute_nodes as $attr) {
            $split = preg_split("/<\?(php|=|)(.*?)\?>/", $attr->getValueEscaped(), NULL, PREG_SPLIT_DELIM_CAPTURE);
            if (count($split)==1) continue;

            $new_value = '';
            for($i=0; $i < count($split); $i += 3) {
                if (strlen($split[$i])) {
                    $new_value .= 'echo \''.str_replace('\'', '\\\'', $split[$i]).'\';';
                }

                if (isset($split[$i+2])) {
                    if ($split[$i+1] === '=') $new_value .= 'echo ';
                    $new_value .= rtrim($split[$i+2], "; \n\r").';';
                }
            }
            $attr->overwriteValueWithCode($new_value);
        }
    }

    public function appendChild(PHPTAL_Dom_Node $child)
    {
        $child->parentNode = $this;
        $this->childNodes[] = $child;
    }

    public function generateCode(PHPTAL_Php_CodeWriter $codewriter)
    {
        // For backwards compatibility only!
        self::$_codewriter_bc_hack_ = $codewriter; // FIXME

        try
        {
            /// self-modifications

            $this->replacePHPAttributes();
            if ($codewriter->getOutputMode() === PHPTAL::XHTML) {
                $this->replaceTextWithCDATA();
            }

            /// code generation

            if ($codewriter->isDebugOn()) {
                $codewriter->pushCode('$ctx->_line = '.$this->getSourceLine());
                $codewriter->doComment('tag "'.$this->qualifiedName.'" from line '.$this->getSourceLine());
            }

            if (count($this->replaceAttributes) > 0) {
                $this->generateSurroundHead($codewriter);
                foreach($this->replaceAttributes as $att) {
                    $att->before($codewriter);
                    $att->after($codewriter);
                }
                $this->generateSurroundFoot($codewriter);
                return;
            }

            $this->generateSurroundHead($codewriter);
            // a surround tag may decide to hide us (tal:define for example)
            if (!$this->hidden) {
                $this->generateHead($codewriter);
                $this->generateContent($codewriter);
                $this->generateFoot($codewriter);
            }
            $this->generateSurroundFoot($codewriter);
        }
        catch(PHPTAL_TemplateException $e)
        {
            $e->hintSrcPosition($this->getSourceFile(), $this->getSourceLine());
            throw $e;
        }
    }

    public function getAttributeNodes()
    {
        return $this->attribute_nodes;
    }

    /** Returns true if the element contains specified PHPTAL attribute. */
    public function hasAttribute($qname)
    {
        foreach($this->attribute_nodes as $attr) if ($attr->getQualifiedName() == $qname) return true;
        return false;
    }

    public function hasAttributeNS($ns_uri, $localname)
    {
        return NULL !== $this->getAttributeNodeNS($ns_uri, $localname);
    }

    public function getAttributeNodeNS($ns_uri, $localname)
    {
        foreach($this->attribute_nodes as $attr) {
            if ($attr->getNamespaceURI() === $ns_uri && $attr->getLocalName() === $localname) return $attr;
        }
        return NULL;
    }

    public function getAttributeNode($qname)
    {
        foreach($this->attribute_nodes as $attr) if ($attr->getQualifiedName() === $qname) return $attr;
        return NULL;
    }

    public function getOrCreateAttributeNode($qname)
    {
        if ($attr = $this->getAttributeNode($qname)) return $attr;

        $attr = new PHPTAL_Dom_Attr($qname, "", NULL, 'UTF-8'); // FIXME: should find namespace and encoding
        $this->attribute_nodes[] = $attr;
        return $attr;
    }

    /** Returns textual (unescaped) value of specified element attribute. */
    public function getAttributeNS($namespace_uri, $localname)
    {
        if ($n = $this->getAttributeNodeNS($namespace_uri, $localname)) {
            return $n->getValue();
        }
        return '';
    }

    /**
     * Returns true if this element or one of its PHPTAL attributes has some
     * content to print (an empty text node child does not count).
     */
    public function hasRealContent()
    {
        if (count($this->contentAttributes) > 0) return true;

        foreach($this->childNodes as $node) {
            if (!$child instanceOf PHPTAL_Dom_Text || $child->getValueEscaped() !== '') return true;
        }
    }

    public function hasRealAttributes()
    {
        if ($this->hasAttributeNS('http://xml.zope.org/namespaces/tal', 'attributes')) return true;
        foreach($this->attribute_nodes as $attr) {
            if ($attr->getReplacedState() !== PHPTAL_Dom_Attr::HIDDEN) return true;
        }
        return false;
    }

    // ~~~~~ Generation methods may be called by some PHPTAL attributes ~~~~~

    public function generateSurroundHead(PHPTAL_Php_CodeWriter $codewriter)
    {
        foreach($this->surroundAttributes as $att) {
            $att->before($codewriter);
        }
    }

    public function generateHead(PHPTAL_Php_CodeWriter $codewriter)
    {
        if ($this->headFootDisabled) return;
        if ($this->headPrintCondition) {
            $codewriter->doIf($this->headPrintCondition);
        }

        $codewriter->pushHTML('<'.$this->qualifiedName);
        $this->generateAttributes($codewriter);

        if ($codewriter->getOutputMode() !== PHPTAL::HTML5 && $this->isEmptyNode($codewriter->getOutputMode())) {
            $codewriter->pushHTML('/>');
        }
        else {
            $codewriter->pushHTML('>');
        }

        if ($this->headPrintCondition) {
            $codewriter->doEnd();
        }
    }

    public function generateContent(PHPTAL_Php_CodeWriter $codewriter = NULL, $realContent=false)
    {
        // For backwards compatibility only!
        if ($codewriter===NULL) $codewriter = self::$_codewriter_bc_hack_; // FIXME!

        if (!$this->isEmptyNode($codewriter->getOutputMode())) {
            if ($realContent || !count($this->contentAttributes)) {
                foreach($this->childNodes as $child) {
                    $child->generateCode($codewriter);
                }
            }
            else foreach($this->contentAttributes as $att) {
                $att->before($codewriter);
                $att->after($codewriter);
            }
        }
    }

    public function generateFoot(PHPTAL_Php_CodeWriter $codewriter)
    {
        if ($this->headFootDisabled)
            return;
        if ($this->isEmptyNode($codewriter->getOutputMode()))
            return;

        if ($this->footPrintCondition) {
            $codewriter->doIf($this->footPrintCondition);
        }

        $codewriter->pushHTML( '</'.$this->qualifiedName.'>' );

        if ($this->footPrintCondition) {
            $codewriter->doEnd();
        }
    }

    public function generateSurroundFoot(PHPTAL_Php_CodeWriter $codewriter)
    {
        for ($i = (count($this->surroundAttributes)-1); $i >= 0; $i--) {
            $this->surroundAttributes[$i]->after($codewriter);
        }
    }

    // ~~~~~ Private members ~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~~

    private function generateAttributes(PHPTAL_Php_CodeWriter $codewriter)
    {
        foreach($this->getAttributeNodes() as $attr) {
            switch($attr->getReplacedState()) {
                case PHPTAL_Dom_Attr::NOT_REPLACED:
                    $codewriter->pushHTML(' '.$attr->getQualifiedName());
                    if ($codewriter->getOutputMode() !== PHPTAL::HTML5 || !PHPTAL_Dom_Defs::getInstance()->isBooleanAttribute($attr->getQualifiedName())) {
                        $codewriter->pushHTML('='.$codewriter->quoteAttributeValue($codewriter->interpolateHTML($attr->getValueEscaped())));
                    }
                    break;

                case PHPTAL_Dom_Attr::HIDDEN:
                    break;

                case PHPTAL_Dom_Attr::FULLY_REPLACED:
                    $codewriter->pushHTML($attr->getValueEscaped());
                    break;

                case PHPTAL_Dom_Attr::VALUE_REPLACED:
                    $codewriter->pushHTML(' '.$attr->getQualifiedName().'="');
                    $codewriter->pushHTML($attr->getValueEscaped());
                    $codewriter->pushHTML('"');
                    break;
            }
        }
    }

    private function isEmptyNode($mode)
    {
        return (($mode === PHPTAL::XHTML || $mode === PHPTAL::HTML5) && PHPTAL_Dom_Defs::getInstance()->isEmptyTag($this->qualifiedName)) ||
               ( $mode === PHPTAL::XML   && !$this->hasContent());
    }

    private function hasContent()
    {
        return count($this->childNodes) > 0 || count($this->contentAttributes) > 0;
    }

    private function separateAttributes()
    {
        $talAttributes = array();
        foreach($this->attribute_nodes as $index => $attr) {
            // remove handled xml namespaces
            if (PHPTAL_Dom_Defs::getInstance()->isHandledXmlNs($attr->getQualifiedName(), $attr->getValueEscaped()))
            {
                unset($this->attribute_nodes[$index]);
            }
            else if ($this->xmlns->isHandledNamespace($attr->getNamespaceURI())) {
                $talAttributes[$attr->getQualifiedName()] = $attr;
                $attr->hide();
            }
            else if (PHPTAL_Dom_Defs::getInstance()->isBooleanAttribute($attr->getQualifiedName())) {
                $attr->setValue($attr->getLocalName());
            }
        }
        return $talAttributes;
    }

    private function orderTalAttributes(array $talAttributes)
    {
        $temp = array();
        foreach($talAttributes as $domattr) {
            $nsattr = PHPTAL_Dom_Defs::getInstance()->getNamespaceAttribute($domattr->getNamespaceURI(), $domattr->getLocalName());
            if (array_key_exists($nsattr->getPriority(), $temp)) {
                throw new PHPTAL_TemplateException(sprintf("Attribute conflict in '%s' at line '%d', '%s' cannot appear with '%s'",
                               $this->qualifiedName,
                               $this->getSourceLine(),
                               $key,
                               $temp[$nsattr->getPriority()][0]
                               ));
            }
            $temp[$nsattr->getPriority()] = array($nsattr, $domattr);
        }
        ksort($temp);

        $this->talHandlers = array();
        foreach($temp as $prio => $dat) {
            list($nsattr, $domattr) = $dat;
            $handler = $nsattr->createAttributeHandler($this, $domattr->getValue());
            $this->talHandlers[$prio] = $handler;

            if ($nsattr instanceOf PHPTAL_NamespaceAttributeSurround)
                $this->surroundAttributes[] = $handler;
            else if ($nsattr instanceOf PHPTAL_NamespaceAttributeReplace)
                $this->replaceAttributes[] = $handler;
            else if ($nsattr instanceOf PHPTAL_NamespaceAttributeContent)
                $this->contentAttributes[] = $handler;
            else
                throw new PHPTAL_ParserException("Unknown namespace attribute class ".get_class($nsattr));

        }
    }

    function getQualifiedName()
    {
        return $this->qualifiedName;
    }

    function getNamespaceURI()
    {
        return $this->namespace_uri;
    }

    function getLocalName()
    {
        $n = explode(':', $this->qualifiedName,2);
        return end($n);
    }
}

