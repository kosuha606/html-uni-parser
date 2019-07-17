<?php

namespace kosuha606\HtmlUniParser;

use kosuha606\HtmlUniParser\exceptions\ParserInvalidConfigException;

/**
 * Class HtmlUniParser
 * @package kosuha606\HtmlUniParser
 */
class HtmlUniParser extends BaseObject
{
    /**
     * Парсинг по сценарию каталога
     * @var
     */
    protected $catalogUrl;

    /**
     * Парсинг по сценарию поиска на сайте
     * @var
     */
    protected $searchUrl;

    /**
     * Парсинг по сценарию получения данных от одной страницы
     * @var
     */
    protected $pageUrl;

    /**
     * Заставить парсер получать внешний html
     * @var boolean
     */
    protected $forceOuterHtml = false;

    /**
     * Парсинг по урлам, сгенерированным генератором
     * @var
     */
    protected $urlGenerator;

    /** @var  */
    protected $beforeDomCallback;

    /**
     * Кодировка сайта
     * @var string
     */
    protected $encoding = 'UTF-8';

    /**
     * @var string
     */
    protected $siteBaseUrl = '/';

    /**
     * @var bool
     */
    protected $resultLimit = false;

    /**
     * @var int
     */
    protected $sleepAfterRequest = 0;

    /**
     * @var bool
     */
    protected $goIntoCard = false;

    /**
     * @var string
     */
    protected $xpathItem;

    /**
     * @var string
     */
    protected $xpathLink;

    /**
     * @var string
     */
    protected $xpathTitle;

    /**
     * @var array
     */
    protected $xpathOnCard = [];

    /** @var string */
    protected $typeMech;

    /**
     * @var array
     */
    protected $callbacks = [];

    /**
     * Результат должен быть множественным
     * @var array
     */
    private $xpathOnCardMany = [];

    /**
     * Результат в формате HTML
     * @var array
     */
    private $xpathOnCardHtml = [];

    /** @var ZendBasedParser */
    private $zendParser;

    /**
     * HtmlUniParser constructor.
     * @param $config
     */
    public function __construct($config, ZendBasedParser $parser)
    {
        if  (!in_array  ('dom', get_loaded_extensions())) {
            throw new ParserInvalidConfigException('The dom extension in not loaded in system');
        }
        if  (!in_array  ('iconv', get_loaded_extensions())) {
            throw new ParserInvalidConfigException('The iconv extension in not loaded in system');
        }
        parent::__construct($config);
        $this->zendParser = $parser;
        if (\count($this->xpathOnCard) > 0) {
            foreach ($this->xpathOnCard as $param => &$xpath) {
                if (\strpos($xpath, 'MANY') !== false) {
                    $this->xpathOnCardMany[] = $param;
                    $xpath = \str_replace('MANY', '', $xpath);
                }
                if (\strpos($xpath, 'HTML') !== false) {
                    $this->xpathOnCardHtml[] = $param;
                    $xpath = \str_replace('HTML', '', $xpath);
                }
            }
        }
    }

    /**
     * You can create instances of this class
     * by yourself or you can use this method
     * @param $config
     * @return HtmlUniParser
     */
    public static function create($config)
    {

        return new static($config, new ZendBasedParser());
    }

    /**
     * @param mixed $callbacs
     */
    public function addCallback($callbacs)
    {
        $this->callbacks[] = $callbacs;
    }

    /**
     * @param $node
     * @return string
     */
    public function getHtml($node)
    {
        if ($this->forceOuterHtml) {
            return $this->getOuterHtml($node);
        }
        $innerHTML = '';
        $children = $node->childNodes;
        foreach ($children as $child) {
            $innerHTML .= $child->ownerDocument->saveXML($child);
        }
        return $innerHTML;
    }

    /**
     * @param $node
     * @return mixed
     */
    public function getOuterHtml($node)
    {
        return $node->ownerDocument->saveXML($node);
    }

    /**
     * @param $object
     * @param $method
     * @return mixed|null
     */
    public function valueStub($object, $method)
    {
        if (!\is_object($object)) {
            return null;
        }
        if (\property_exists($object, $method)) {
            return $object->{$method};
        }
        return null;
    }

    /**
     * @param $results
     * @return array
     */
    public function getFirstMatch($results)
    {
        $result = array();
        foreach ($results as $r) {
            $result = $r;
        }
        return $result;
    }

    /**
     * @param $nodes
     * @return string
     */
    public function getFirstValue($nodes)
    {
        $val = $this->getFirstMatch($nodes);
        return $this->getValue($val);
    }

    /**
     * @param $nodes
     * @return string|string[]|null
     */
    public function getFirstValueHtml($nodes)
    {
        /** @var \DOMElement $val */
        $val = $this->getFirstMatch($nodes);
        $html = $val->ownerDocument->saveHTML($val);
        // Удаляем картинки из спарсенного текста
        $html = preg_replace("/<img[^>]+\>/i", "", $html);
        return $this->proccessValue($html);
    }

    /**
     * @param $val
     * @return string
     */
    public function getValue($val)
    {
        $result = null;
        if ($val instanceof \DOMAttr) {
            $result = $this->valueStub($val, 'value');
        }
        if ($val instanceof \DOMElement) {
            $result = $this->valueStub($val, 'nodeValue');
        }
        return $this->proccessValue(trim($result));
    }

    /**
     * @param $nodes
     * @return array
     */
    public function getAllValues($nodes)
    {
        $result = [];
        foreach ($nodes as $node) {
            $result[] = $this->getValue($node);
        }
        return $result;
    }

    /**
     * Спарсить ссылку, возможно каталог
     */
    public function parseUrl()
    {
        $this->zendParser->setSleepAfterRequest($this->sleepAfterRequest);
        $this->zendParser->setUrl($this->catalogUrl);
        $this->onBeforeDom();
        $items = $this->zendParser->dom($this->getEncoding(), $this->getTypeMech())->queryXpath($this->xpathItem);
        $pageHtml = $this->zendParser->getHtmlBuffer();
        $result = [];
        foreach ($items as $index => $item) {
            $newItem = [];
            $html = $this->getHtml($item);
            $this->zendParser->setRawHtml($html);
            $link = $this->zendParser->dom($this->getEncoding(), $this->getTypeMech())->queryXpath($this->xpathLink);
            $link = $this->getFirstValue($link);
            if (preg_match('/^http(s)?:\/\/.*$/i', $link)) {
                $newItem['link'] = $link;
            } else {
                $newItem['link'] = $this->siteBaseUrl.$link;
            }
            if ($this->xpathTitle) {
                $this->zendParser->setRawHtml($pageHtml);
                $title = $this->zendParser->dom($this->getEncoding(), $this->getTypeMech())->queryXpath($this->xpathTitle);
                $title = $this->getFirstValue($title);
                $newItem['title'] = $title;
            }
            if ($this->goIntoCard && $newItem['link']) {
                $this->zendParser->setUrl($newItem['link']);
                foreach ($this->xpathOnCard as $param => $xpath) {
                    $this->onBeforeDom();
                    $temParam = $this->zendParser->dom($this->getEncoding(), $this->getTypeMech())->queryXpath($xpath);
                    if (in_array($param, $this->xpathOnCardMany)) {
                        $newItem[$param] = $this->getAllValues($temParam);
                    } elseif (in_array($param, $this->xpathOnCardHtml)) {
                        $newItem[$param] = $this->getFirstValueHtml($temParam);
                    } else {
                        $newItem[$param] = $this->getFirstValue($temParam);
                    }
                }
            }
            $this->handleCallbacks($newItem);
            $result[] = $newItem;
            // Не даем спарсить больше предела если установлен предел
            if ($this->resultLimit && $this->resultLimit <= ($index + 1)) {
                break;
            }
        }
        return $result;
    }

    /**
     * Спарсить результаты поиска
     */
    public function parseSearch($textQuery)
    {
        $this->catalogUrl = $this->searchUrl.$textQuery;
        return $this->parseUrl();
    }

    /**
     * Спарсить одну карточку
     */
    public function parseCard()
    {
        $this->zendParser->setUrl($this->pageUrl);
        foreach ($this->xpathOnCard as $param => $xpath) {
            $this->onBeforeDom();
            $temParam = $this->zendParser->dom($this->getEncoding(), $this->getTypeMech())->queryXpath($xpath);
            if (in_array($param, $this->xpathOnCardMany)) {
                $newItem[$param] = $this->getAllValues($temParam);
            } elseif (in_array($param, $this->xpathOnCardHtml)) {
                $newItem[$param] = $this->getFirstValueHtml($temParam);
            } else {
                $newItem[$param] = $this->getFirstValue($temParam);
            }
        }
        $this->handleCallbacks($newItem);
        return $newItem;
    }

    /**
     * @return array
     */
    public function parseGenerator()
    {
        $generator = $this->urlGenerator;
        $urls = $generator();
        $results = [];
        foreach ($urls as $url) {
            $this->pageUrl = $url;
            $results[] = $this->parseCard();
        }
        return $results;
    }

    /**
     * @param $lastItem
     * @return HtmlUniParser
     */
    public function handleCallbacks(&$lastItem)
    {
        foreach ($this->callbacks as &$callbac) {
            $callbac($lastItem, $this->pageUrl);
        }
        return $this;
    }

    /**
     * @return $this
     */
    public function onBeforeDom()
    {
        $callback = $this->beforeDomCallback;
        if ($callback) {
            $callback($this);
        }
        return $this;
    }

    /**
     * @return string
     */
    public function getEncoding(): string
    {
        return $this->encoding;
    }

    /**
     * @param string $encoding
     * @return HtmlUniParser
     */
    public function setEncoding(string $encoding)
    {
        $this->encoding = $encoding;
        return $this;
    }

    /**
     * @param $value
     * @return false|string
     */
    private function proccessValue($value)
    {
        if ($this->getEncoding() === 'UTF-8') {
            return $value;
        }
        $result = \iconv($this->getEncoding(), 'UTF-8', $value);
        return $result;
    }

    /**
     * @return string
     */
    public function getTypeMech(): string
    {
        return $this->typeMech ?: 'curl';
    }

    /**
     * @return ZendBasedParser
     */
    public function getZendParser(): ZendBasedParser
    {
        return $this->zendParser;
    }

    /**
     * @param string $typeMech
     * @return HtmlUniParser
     */
    public function setTypeMech(string $typeMech)
    {
        $this->typeMech = $typeMech;
        return $this;
    }

    /**
     * @param mixed $catalogUrl
     * @return HtmlUniParser
     */
    public function setCatalogUrl($catalogUrl)
    {
        $this->catalogUrl = $catalogUrl;
        return $this;
    }

    /**
     * @param mixed $searchUrl
     * @return HtmlUniParser
     */
    public function setSearchUrl($searchUrl)
    {
        $this->searchUrl = $searchUrl;
        return $this;
    }

    /**
     * @param mixed $pageUrl
     * @return HtmlUniParser
     */
    public function setPageUrl($pageUrl)
    {
        $this->pageUrl = $pageUrl;
        return $this;
    }

    /**
     * @param bool $forceOuterHtml
     * @return HtmlUniParser
     */
    public function setForceOuterHtml(bool $forceOuterHtml)
    {
        $this->forceOuterHtml = $forceOuterHtml;
        return $this;
    }

    /**
     * @param mixed $urlGenerator
     * @return HtmlUniParser
     */
    public function setUrlGenerator($urlGenerator)
    {
        $this->urlGenerator = $urlGenerator;
        return $this;
    }

    /**
     * @param string $siteBaseUrl
     * @return HtmlUniParser
     */
    public function setSiteBaseUrl(string $siteBaseUrl)
    {
        $this->siteBaseUrl = $siteBaseUrl;
        return $this;
    }

    /**
     * @param bool $resultLimit
     * @return HtmlUniParser
     */
    public function setResultLimit($resultLimit)
    {
        $this->resultLimit = $resultLimit;
        return $this;
    }

    /**
     * @param int $sleepAfterRequest
     * @return HtmlUniParser
     */
    public function setSleepAfterRequest(int $sleepAfterRequest)
    {
        $this->sleepAfterRequest = $sleepAfterRequest;
        return $this;
    }

    /**
     * @param bool $goIntoCard
     * @return HtmlUniParser
     */
    public function setGoIntoCard(bool $goIntoCard)
    {
        $this->goIntoCard = $goIntoCard;
        return $this;
    }

    /**
     * @param string $xpathItem
     * @return HtmlUniParser
     */
    public function setXpathItem(string $xpathItem)
    {
        $this->xpathItem = $xpathItem;
        return $this;
    }

    /**
     * @param string $xpathLink
     * @return HtmlUniParser
     */
    public function setXpathLink(string $xpathLink)
    {
        $this->xpathLink = $xpathLink;
        return $this;
    }

    /**
     * @param array $xpathOnCard
     * @return HtmlUniParser
     */
    public function setXpathOnCard(array $xpathOnCard)
    {
        $this->xpathOnCard = $xpathOnCard;
        return $this;
    }

    /**
     * @param array $callbacks
     * @return HtmlUniParser
     */
    public function setCallbacks(array $callbacks)
    {
        $this->callbacks = $callbacks;
        return $this;
    }

    /**
     * @param array $xpathOnCardMany
     * @return HtmlUniParser
     */
    public function setXpathOnCardMany(array $xpathOnCardMany)
    {
        $this->xpathOnCardMany = $xpathOnCardMany;
        return $this;
    }

    /**
     * @param array $xpathOnCardHtml
     * @return HtmlUniParser
     */
    public function setXpathOnCardHtml(array $xpathOnCardHtml)
    {
        $this->xpathOnCardHtml = $xpathOnCardHtml;
        return $this;
    }

    /**
     * @param ZendBasedParser $zendParser
     * @return HtmlUniParser
     */
    public function setZendParser(ZendBasedParser $zendParser)
    {
        $this->zendParser = $zendParser;
        return $this;
    }

    /**
     * @param mixed $beforeDomCallback
     * @return HtmlUniParser
     */
    public function setBeforeDomCallback($beforeDomCallback)
    {
        $this->beforeDomCallback = $beforeDomCallback;
        return $this;
    }

    /**
     * @return string
     */
    public function getXpathTitle()
    {
        return $this->xpathTitle;
    }

    /**
     * @param string $xpathTitle
     */
    public function setXpathTitle($xpathTitle)
    {
        $this->xpathTitle = $xpathTitle;
        return $this;
    }
}
