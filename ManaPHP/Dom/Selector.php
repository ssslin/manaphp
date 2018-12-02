<?php
namespace ManaPHP\Dom;

class Selector
{
    /**
     * @var \ManaPHP\Dom\Document
     */
    protected $_document;

    /**
     * @var \DOMElement
     */
    protected $_node;

    /**
     * Selector constructor.
     *
     * @param string|\ManaPHP\Dom\Document $document
     * @param \DOMNode                     $node
     */
    public function __construct($document, $node = null)
    {
        if (is_string($document)) {
            /** @noinspection ExceptionsAnnotatingAndHandlingInspection */
            $document = (new Document())->load($document);
        }

        $this->_document = $document;
        $this->_node = $node;
    }

    /**
     * @return static
     */
    public function root()
    {
        return new Selector($this->_document);
    }

    /**
     * @return \ManaPHP\Dom\Document
     */
    public function document()
    {
        return $this->_document;
    }

    /**
     * @param string|array $query
     *
     * @return \ManaPHP\Dom\SelectorList
     */
    public function xpath($query)
    {
        $nodes = [];
        /**
         * @var \DOMNode $node
         */
        foreach ($this->_document->getQuery()->xpath($query, $this->_node) as $node) {
            $nodes[$node->getNodePath()] = $node;
        }
        return new SelectorList($this->_document, $nodes);
    }

    /**
     * @param string|array $css
     *
     * @return \ManaPHP\Dom\SelectorList
     */
    public function css($css)
    {
        $nodes = [];
        /**
         * @var \DOMNode $node
         */
        foreach ($this->_document->getQuery()->css($css, $this->_node) as $node) {
            $nodes[$node->getNodePath()] = $node;
        }
        return new SelectorList($this->_document, $nodes);
    }

    /**
     * @param string $css
     *
     * @return \ManaPHP\Dom\SelectorList
     */
    public function find($css = null)
    {
        return $this->css('descendant::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return \ManaPHP\Dom\SelectorList
     */
    public function has($css)
    {
        return $this->css('child::' . ($css === null ? '*' : $css));
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function remove($css)
    {
        /**
         * @var \DOMNode $node
         */
        foreach ($this->_document->getQuery()->css($css, $this->_node) as $node) {
            $node->parentNode->removeChild($node);
        }

        return $this;
    }

    /**
     * @param  string      $css
     * @param string|array $attr
     *
     * @return static
     */
    public function removeAttr($css, $attr = null)
    {
        if (is_string($attr)) {
            $attr = (array)preg_split('#[\s,]+#', $attr, -1, PREG_SPLIT_NO_EMPTY);
        }

        /**
         * @var \DOMElement $node
         */
        foreach ($this->_document->getQuery()->css($css, $this->_node) as $node) {
            foreach ($node->attributes as $attribute) {
                if (!$attr || in_array($attribute->name, $attr, true)) {
                    $node->removeAttribute($attribute->name);
                }
            }
        }

        return $this;
    }

    /**
     * @param  string      $css
     * @param string|array $attr
     *
     * @return static
     */
    public function retainAttr($css, $attr)
    {
        if (is_string($attr)) {
            $attr = (array)preg_split('#[\s,]+#', $attr, -1, PREG_SPLIT_NO_EMPTY);
        }

        /**
         * @var \DOMElement $node
         */
        foreach ($this->_document->getQuery()->css($css, $this->_node) as $node) {
            foreach ($node->attributes as $attribute) {
                if (!in_array($attribute->name, $attr, true)) {
                    $node->removeAttribute($attribute->name);
                }
            }
        }

        return $this;
    }

    /**
     * @param string $css
     *
     * @return static
     */
    public function strip($css)
    {
        /**
         * @var \DOMNode $node
         */
        foreach ($this->_document->getQuery()->css($css, $this->_node) as $node) {
            $node->parentNode->replaceChild(new \DOMText($node->textContent), $node);
        }

        return $this;
    }

    /**
     * @param string|array $attr
     *
     * @return string|null
     */
    public function attr($attr = null)
    {
        if ($this->_node instanceof \DOMElement) {
            return $this->_node->getAttribute($attr);
        } else {
            return null;
        }
    }

    /**
     * @param string $name
     *
     * @return bool
     */
    public function hasAttr($name)
    {
        return $this->_node->hasAttribute($name);
    }

    /**
     * @return string
     */
    public function text()
    {
        return (string)$this->_node->textContent;
    }

    /**
     * @param array $rules
     *
     * @return array
     */
    public function extract($rules)
    {
        /** @var \DOMElement $node */
        $node = $this->_node ?: $this->_document;

        $data = [];
        foreach ($rules as $name => $rule) {
            if ($rule[0] === '@') {
                $data[$name] = $node->getAttribute(substr($rule, 1));
            } elseif (($pos = strpos($rule, '@')) === false) {
                $nodes = $this->_document->getQuery()->css($rule, $node);
                $data[$name] = $nodes->length ? $nodes->item(0)->textContent : null;
            } else {
                $nodes = $this->_document->getQuery()->css(substr($rule, 0, $pos), $node);
                $data[$name] = $nodes->length ? $nodes->item(0)->getAttribute(substr($rule, $pos + 1)) : null;
            }
        }

        return $data;
    }

    /**@param bool $as_string
     *
     * @return string|array
     */
    public function element($as_string = false)
    {
        if ($as_string) {
            return $this->html();
        }

        $data = [
            'name' => $this->_node->nodeName,
            'html' => $this->html(),
            'text' => $this->text(),
            'attr' => $this->attr(),
            'xpath' => $this->_node->getNodePath()
        ];

        return $data;
    }

    /**
     * @return string
     */
    public function name()
    {
        return $this->_node->nodeName;
    }

    /**
     * @return string
     */
    public function html()
    {
        return $this->_document->saveHtml($this->_node);
    }

    /**
     * @param string $regex
     *
     * @return array
     */
    public function links($regex = null)
    {
        /**
         * @var \DOMElement $node
         */
        $data = [];
        foreach ($this->_document->getQuery()->xpath('descendant::a[@href]', $this->_node) as $node) {
            $href = $this->_document->absolutizeUrl($node->getAttribute('href'));

            if ($regex && !preg_match($regex, $href)) {
                continue;
            }

            $data[$node->getNodePath()] = ['href' => $href, 'text' => $node->textContent];
        }

        return $data;
    }

    /**
     * @param string $regex
     * @param string attr
     *
     * @return array
     */
    public function images($regex = null, $attr = 'src')
    {
        /**
         * @var \DOMElement $node
         */
        $document = $this->_document;
        $data = [];
        foreach ($document->getQuery()->xpath("descendant::img[@$attr]", $this->_node) as $node) {
            $src = $document->absolutizeUrl($node->getAttribute($attr));

            if ($regex && !preg_match($regex, $src)) {
                continue;
            }

            $data[$node->getNodePath()] = $src;
        }

        return $data;
    }

    /**
     * @return \DOMNode
     */
    public function node()
    {
        return $this->_node;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->_node->getNodePath();
    }
}