HtmlUniParser
--

Universal html parser which can parse every kind of html page

**Installation**

To install this plugin use composer:

```bash
$ composer require kosuha606/html-uni-parser
```

**Usage**

There is four available types of parsing html.

1. Parse one page.

Example:
```php
$results = HtmlUniParser::create([
    'pageUrl' => 'http://example.com',
    'xpathOnCard' => [
        'h1' => '//h1',
        'description' => 'HTML//p'
    ]
])->parseCard();
```

2. Parse list of links on the pange, then parser automaticaly goes into each page and 
parse that page for data.

3. Parse search. It is the same as parse list, but here you are 
set the qurey string that can change separately from url.

4. Parse generated urls. This kind of parsing used if you need
to parse pagination or something similar

**Examples**
For more examples see the `examples/` direcotry

**Run tests**

To run tests you can use this command:
```bash
./vendor/bin/phpunit
```
