function testAll() {
    var html5Element = I('html5');
    var websocketElement = I('websocket');
    var browserElement = I('browser');
    var isHtml5Supported = false;
    var isWebSocketSupported = false;
    var isBrowserSupported = false;
    var browserName = '';
    var browserVersion = '';

    var interval = setInterval(function () {
        if (html5Element && websocketElement && browserElement) {
            clearInterval(interval);

            // test HTML5 support
            try {
                var result = document.createElement('canvas').toDataURL('image/webp').indexOf('data:image/webp') == 0;
                if (result) {
                    html5Element.textContent = 'Supported';
                    isHtml5Supported = true;
                } else {
                    html5Element.textContent = 'Not supported';
                    isHtml5Supported = false;
                }
            } catch (e) {
                isHtml5Supported = false;
            }

            // test WebSocket support
            try {
                var ws;
                var url = window.location.hostname;
                ws = new WebSocket('ws://' + url + ':443');
                ws.onopen = function () {
                    websocketElement.textContent = 'supported';
                    ws.close();
                };
                isWebSocketSupported = true;
                ws.onerror = function () {
                    websocketElement.textContent = 'Not supported';
                    isWebSocketSupported = false;
                };
            } catch (e) {
                websocketElement.textContent = 'Not supported';
                isWebSocketSupported = false;
            }

            // test browser support
            var userAgent = navigator.userAgent.toLowerCase();
            if (userAgent.indexOf('firefox') > -1) {
                browserName = 'Mozilla Firefox';
                browserVersion = userAgent.match(/firefox\/([\d.]+)/)[1];
                isBrowserSupported = true;
            } else if (userAgent.indexOf('chrome') > -1) {
                browserName = 'Google Chrome';
                browserVersion = userAgent.match(/chrome\/([\d.]+)/)[1];
                isBrowserSupported = true;
            } else if (userAgent.indexOf('safari') > -1) {
                browserName = 'Apple Safari';
                browserVersion = userAgent.match(/version\/([\d.]+)/)[1];
                isBrowserSupported = true;
            } else if (userAgent.indexOf('opera') > -1) {
                browserName = 'Opera';
                browserVersion = userAgent.match(/version\/([\d.]+)/)[1];
                isBrowserSupported = true;
            } else if (userAgent.indexOf('msie') > -1 || userAgent.indexOf('trident/') > -1) {
                browserName = 'Microsoft Internet Explorer';
                browserVersion = userAgent.match(/(?:msie |rv:)(\d+(\.\d+)?)/i)[1];
                isBrowserSupported = false;
            } else {
                browserName = 'unknown browser';
                browserVersion = '';
                isBrowserSupported = false;
            }

            // set browser details to element
            if (browserElement) {
                browserElement.textContent = browserName + ' ' + browserVersion;
            }

            // set test complete message based on support
            var testCompleteElement = I('testcomplete');
            var ulddata = Number(uiData.ulStatus);
            var dlddata = Number(uiData.dlStatus);
            if (testCompleteElement) {
                console.log(
                    {
                        html5: isHtml5Supported,
                        webscket: isWebSocketSupported,
                        browser: isBrowserSupported,
                        name: browserName,
                        version: browserVersion,
                        upload: ulddata,
                        download: dlddata
                    }
                )
                if (isHtml5Supported && isWebSocketSupported && isBrowserSupported) {
                    if (ulddata < 4.00 && dlddata < 4.00) {
                        testCompleteElement.style.color = '#ff6418'
                        testCompleteElement.textContent = 'Your Browser Meets the Requirements, but your Internet Speed is Slow';;
                    } else if (ulddata < 10.00 && dlddata < 6.00) {
                        testCompleteElement.style.color = '#00ff0a';
                        testCompleteElement.textContent = 'Your Browser Meets the Requirements, and your Internet Speed is Good';
                    } else {
                        testCompleteElement.style.color = '#00ff0a';
                        testCompleteElement.textContent = 'Your Browser Meets the Requirements, and your Internet Speed is Great';
                    }
                } else {
                    testCompleteElement.style.color = '#ff0000';
                    testCompleteElement.textContent = 'Sorry, you do not meet the Minimum Requirements';
                }
            }
        }
    }, 100);
}