<?php
declare(strict_types=1);

namespace kosuha606\HtmlUniParser;

use Assert\Assertion;
use kosuha606\HtmlUniParser\action\ComposeHtmlAction;
use kosuha606\HtmlUniParser\action\GetFirstMatchAction;
use kosuha606\HtmlUniParser\action\InitializeHtmlUniParserAction;
use kosuha606\HtmlUniParser\action\ValueStubAction;

/**
 * The main intrance point for work with the package
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

    /** @var */
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

    /** @var string */
    protected $typeMech;

    /**
     * @var array
     */
    protected $xpathOnCard = [];

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
     * @throws \Assert\AssertionFailedException
     */
    public function __construct($config, ZendBasedParser $parser)
    {
        parent::__construct($config);
        $this->checkPhpExtensions();
        InitializeHtmlUniParserAction::do($this, $parser);
    }

    /**
     * @throws \Assert\AssertionFailedException
     */
    private function checkPhpExtensions()
    {
        Assertion::keyNotExists(
            get_loaded_extensions(),
            'dom',
            'The dom extension in not loaded in system. HtmlUniParser cant work'
        );
        Assertion::keyNotExists(
            get_loaded_extensions(),
            'iconv',
            'The iconv extension in not loaded in system. HtmlUniParser cant work'
        );
    }

    /**
     * @return array
     */
    public function getXpathOnCard()
    {
        return $this->xpathOnCard;
    }

    /**
     * You can create instances of this class
     * by yourself or you can use this method
     * @param $config
     * @return HtmlUniParser
     * @throws \Assert\AssertionFailedException
     * @throws \ReflectionException
     */
    public static function create($config)
    {
        return Factory::createObject(
            [
                'class' => static::class,
            ],
            [
                $config,
                Factory::createObject(
                    [
                        'class' => ZendBasedParser::class,
                    ]
                ),
            ]
        );
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
     * @throws \Assert\AssertionFailedException
     */
    public function composeHtml($node): string
    {
        return ComposeHtmlAction::do($this, $node);
    }

    /**
     * @param $node
     * @return mixed
     */
    public function queryOuterHtml($node)
    {
        return $node->ownerDocument->saveXML($node);
    }

    /**
     * @param $nodes
     * @return string
     * @throws \Assert\AssertionFailedException
     */
    public function getFirstValue($nodes)
    {
        $val = GetFirstMatchAction::do($nodes);

        return $this->getValue($val);
    }

    /**
     * @param $nodes
     * @return string|string[]|null
     * @throws \Assert\AssertionFailedException
     */
    public function getFirstValueHtml($nodes)
    {
        /** @var \DOMElement $val */
        $val = GetFirstMatchAction::do($nodes);
        $html = $val->ownerDocument->saveHTML($val);
        // Удаляем картинки из спарсенного текста
        $html = preg_replace("/<img[^>]+\>/i", "", $html);

        return $this->proccessValue($html);
    }

    /**
     * @param $val
     * @return string
     * @throws \Assert\AssertionFailedException
     */
    public function getValue($val)
    {
        $result = null;
        if ($val instanceof \DOMAttr) {
            $result = ValueStubAction::do($val, 'value');
        }
        if ($val instanceof \DOMElement) {
            $result = ValueStubAction::do($val, 'nodeValue');
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
            $html = $this->composeHtml($item);
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
                $title = $this->zendParser->dom($this->getEncoding(), $this->getTypeMech())->queryXpath(
                    $this->xpathTitle
                );
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
     * @return bool
     */
    public function isForceOuterHtml(): bool
    {
        return $this->forceOuterHtml;
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
     * @return HtmlUniParser
     */
    public function setXpathTitle($xpathTitle)
    {
        $this->xpathTitle = $xpathTitle;

        return $this;
    }
}
