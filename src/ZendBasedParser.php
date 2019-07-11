<?php

namespace kosuha606\HtmlUniParser;

use kosuha606\HtmlUniParser\exceptions\ParserInvalidConfigException;
use PhantomInstaller\PhantomBinary;
use Zend\Dom\Query;

/**
 * Class ZendBasedParser
 * @package app\Parsers
 */
class ZendBasedParser
{
    /**
     * @var string
     */
    private $url;

    /**
     * @var string
     */
    private $lastUrl;

    /**
     * @var string
     */
    private $htmlBuffer;

    /**
     * @var int
     */
    private $sleepAfterRequest = 0;

    /**
     * @var string
     */
    private $userAgent = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36";

    /**
     * @param $url
     */
    public function setUrl($url)
    {
        $this->url = $url;
    }

    /**
     * @param int $sleepAfterRequest
     */
    public function setSleepAfterRequest($sleepAfterRequest)
    {
        $this->sleepAfterRequest = $sleepAfterRequest;
    }

    /**
     * @param $search
     * @return mixed
     * @throws ParserInvalidConfigException
     */
    public function query($search)
    {
        return $this->dom()->execute($search);
    }

    /**
     * @return Query
     */
    public function filegetcontentsDom()
    {
        if ($this->getLastUrl() !== $this->getUrl()) {
            $this
                ->setHtmlBuffer('<meta charset="UTF-8" />'.file_get_contents($this->getUrl()))
                ->setLastUrl($this->getUrl())
            ;
            sleep($this->sleepAfterRequest);
        }
        $dom = new Query($this->htmlBuffer);
        return $dom;
    }

    /**
     * @return Query
     */
    public function phantomjsDom()
    {
        if ($this->getLastUrl() !== $this->getUrl()) {
            $bin = PhantomBinary::BIN;
            $command = $bin.' '.__DIR__.'/nodejs/loadspeed.js ' . $this->url;
            $result = shell_exec($command);
            $this
                ->setHtmlBuffer('<meta charset="UTF-8" />'.$result)
                ->setLastUrl($this->getUrl())
            ;
            sleep($this->sleepAfterRequest);
        }
        $dom = new Query($this->htmlBuffer);
        return $dom;
    }

    /**
     * @return Query
     */
    public function wgetDom()
    {
        if ($this->getLastUrl() !== $this->getUrl()) {
            $command = 'wget -qO- '.$this->url.' --no-check-certificate';
            $result = shell_exec($command);
            $this
                ->setHtmlBuffer('<meta charset="UTF-8" />'.$result)
                ->setLastUrl($this->getUrl())
            ;
            sleep($this->sleepAfterRequest);
        }
        $dom = new Query($this->htmlBuffer);
        return $dom;
    }

    /**
     * @return Query
     * @throws ParserInvalidConfigException
     */
    public function dom($encoding = 'UTF-8', $type='curl')
    {
        if ($type==='curl') {
            if (!in_array('curl', get_loaded_extensions())) {
                throw new ParserInvalidConfigException('The curl extension in not loaded in system');
            }
            if ($this->getLastUrl() !== $this->getUrl()) {
                $ch = \curl_init($this->getUrl());
                \curl_setopt($ch, CURLOPT_USERAGENT, $this->userAgent);
                \curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                \ob_start();
                \ob_implicit_flush();
                \curl_exec($ch);
                \curl_close($ch);
                $htmlBuffer = '<meta charset="UTF-8" />';
                $htmlBuffer .= \ob_get_clean();
                $this->setHtmlBuffer($htmlBuffer);
                $this->setLastUrl($this->getUrl());
                \sleep($this->getSleepAfterRequest());
            }
            $dom = new Query($this->getHtmlBuffer(), $encoding);
            return $dom;
        } else {
            $method = $type.'Dom';
            return $this->$method();
        }
    }

    /**
     * @return string
     */
    public function getRawHtml()
    {
        return $this->htmlBuffer;
    }

    /**
     * @param $html
     * @return ZendBasedParser
     */
    public function setRawHtml($html)
    {
        $this->htmlBuffer = $html;
        return $this;
    }

    /**
     * @return string
     */
    public function getLastUrl()
    {
        return $this->lastUrl;
    }

    /**
     * @return string
     */
    public function getUrl()
    {
        return $this->url;
    }

    /**
     * @return string
     */
    public function getHtmlBuffer()
    {
        return $this->htmlBuffer;
    }

    /**
     * @return int
     */
    public function getSleepAfterRequest()
    {
        return $this->sleepAfterRequest;
    }

    /**
     * @return string
     */
    public function getUserAgent()
    {
        return $this->userAgent;
    }

    /**
     * @param string $lastUrl
     * @return ZendBasedParser
     */
    public function setLastUrl($lastUrl)
    {
        $this->lastUrl = $lastUrl;
        return $this;
    }

    /**
     * @param string $htmlBuffer
     * @return ZendBasedParser
     */
    public function setHtmlBuffer($htmlBuffer)
    {
        $this->htmlBuffer = $htmlBuffer;
        return $this;
    }

    /**
     * @param string $userAgent
     * @return ZendBasedParser
     */
    public function setUserAgent($userAgent)
    {
        $this->userAgent = $userAgent;
        return $this;
    }
}