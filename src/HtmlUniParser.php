<?php

namespace kosuha606\HtmlUniParser;

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
    public $catalogUrl;

    /**
     * Парсинг по сценарию поиска на сайте
     * @var
     */
    public $searchUrl;

    /**
     * Парсинг по сценарию получения данных от одной страницы
     * @var
     */
    public $pageUrl;

    /**
     * Парсинг по урлам, сгенерированным генератором
     * @var
     */
    public $urlGenerator;

    /**
     * @var
     */
    protected $class;

    /**
     * @var string
     */
    protected $type;

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
    protected $xpathPagination;

    /**
     * @var string
     */
    protected $xpathTitle;

    /**
     * @var string
     */
    protected $xpathLink;

    /**
     * @var array
     */
    protected $xpathOnCard = [];

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

    /**
     * @var array
     */
    public $callbacks = [];

    /** @var ZendBasedParser */
    public $zendParser;

    /**
     * HtmlUniParser constructor.
     * @param $config
     */
    public function __construct($config, ZendBasedParser $parser)
    {
        if  (!in_array  ('dom', get_loaded_extensions())) {
            throw new ParserInvalidConfigException('The dom extension in not loaded in system');
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
    function getHtml($node)
    {
        $innerHTML = '';
        $children = $node->childNodes;
        foreach ($children as $child) {
            $innerHTML .= $child->ownerDocument->saveXML($child);
        }
        return $innerHTML;
    }

    /**
     * @param $object
     * @param $method
     * @return |null
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
        return $html;
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
        return trim($result);
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
        $items = $this->zendParser->dom()->queryXpath($this->xpathItem);
        $result = [];
        foreach ($items as $index => $item) {
            $newItem = [];
            $html = $this->getHtml($item);
            $this->zendParser->setRawHtml($html);
            $link = $this->zendParser->dom()->queryXpath($this->xpathLink);
            $link = $this->getFirstValue($link);
            if (preg_match('/^http(s)?:\/\/.*$/i', $link)) {
                $newItem['link'] = $link;
            } else {
                $newItem['link'] = $this->siteBaseUrl.$link;
            }
            $title = $this->zendParser->dom()->queryXpath($this->xpathTitle);
            // $newItem['title'] = $this->getFirstValue($title);
            if ($this->goIntoCard && $newItem['link']) {
                $this->zendParser->setUrl($newItem['link']);
                foreach ($this->xpathOnCard as $param => $xpath) {
                    $temParam = $this->zendParser->dom()->queryXpath($xpath);
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
            $temParam = $this->zendParser->dom()->queryXpath($xpath);
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
     *
     */
    public function parseGenerator()
    {
        $generator = $this->urlGenerator;
        $urls = $generator();
        foreach ($urls as $url) {
            $this->pageUrl = $url;
            $this->parseCard();
        }
    }

    /**
     * @param $lastItem
     */
    public function handleCallbacks(&$lastItem)
    {
        foreach ($this->callbacks as &$callbac) {
            $callbac($lastItem, $this->pageUrl);
        }
    }
}
