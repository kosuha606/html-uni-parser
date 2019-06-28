var page = require('webpage').create(),
    system = require('system'),
    t, address;

if (system.args.length === 1) {
    console.log('Usage: loadspeed.js <some URL>');
    phantom.exit();
}

t = Date.now();
address = system.args[1];

console.log(address);
page.open(address, function(status) {
    page.onConsoleMessage = function(msg) {
        console.log(msg);
    };
    // debugRequest(page);

    var title = page.evaluate(function(s) {
        console.log("LOCATION: ");
        console.log(window.location.host);
        if (window.location.host === 'www.jeuxvideo.com') {
            if (typeof data !== 'undefined' && data.items) {
                for (var i = 0; i < data.items.length; i++) {
                    $('#navigation-main').append('<img src="' + data.items[i].src + '" />');
                }
            }
        }

        setTimeout(function() {
            var html = document.querySelector('html').outerHTML;
            s(html);
        }, 1000);

        return '';
    }, function(html) {
        console.log(html);
    });

    setTimeout(function() {
        phantom.exit();
    }, 2000);

    console.log(status);
});

function debugRequest(page) {
    page.onResourceRequested = function (request) {
        system.stderr.writeLine('= onResourceRequested()');
        system.stderr.writeLine('  request: ' + JSON.stringify(request, undefined, 4));
    };

    page.onResourceReceived = function(response) {
        system.stderr.writeLine('= onResourceReceived()' );
        system.stderr.writeLine('  id: ' + response.id + ', stage: "' + response.stage + '", response: ' + JSON.stringify(response));
    };

    page.onLoadStarted = function() {
        system.stderr.writeLine('= onLoadStarted()');
        var currentUrl = page.evaluate(function() {
            return window.location.href;
        });
        system.stderr.writeLine('  leaving url: ' + currentUrl);
    };

    page.onLoadFinished = function(status) {
        system.stderr.writeLine('= onLoadFinished()');
        system.stderr.writeLine('  status: ' + status);
    };

    page.onNavigationRequested = function(url, type, willNavigate, main) {
        system.stderr.writeLine('= onNavigationRequested');
        system.stderr.writeLine('  destination_url: ' + url);
        system.stderr.writeLine('  type (cause): ' + type);
        system.stderr.writeLine('  will navigate: ' + willNavigate);
        system.stderr.writeLine('  from page\'s main frame: ' + main);
    };

    page.onResourceError = function(resourceError) {
        system.stderr.writeLine('= onResourceError()');
        system.stderr.writeLine('  - unable to load url: "' + resourceError.url + '"');
        system.stderr.writeLine('  - error code: ' + resourceError.errorCode + ', description: ' + resourceError.errorString );
    };
}