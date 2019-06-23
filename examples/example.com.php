<?php

/**
 * Example of parsing a site example.com
 */

use kosuha606\HtmlUniParser\HtmlUniParser;

require 'bootstrap.php';

$results = HtmlUniParser::create([
    'pageUrl' => 'http://example.com',
    'siteBaseUrl' => 'http://example.com',
    'xpathOnCard' => [
        'h1' => '//h1',
        'description' => 'HTML//p'
    ]
])->parseCard();

echo "See the results \n";
var_dump($results);