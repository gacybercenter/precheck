<!DOCTYPE html>
<html>

<head>
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no, user-scalable=no" />
    <meta charset="UTF-8" />
    <link rel="shortcut icon" href="favicon.ico">
    <link rel="stylesheet" href="site.css">
    <script type="text/javascript" src="speedtest.js"></script>
    <script type="text/javascript" src="precheck.js"></script>
    <script type="text/javascript">
        function I(i) {
            return document.getElementById(i);
        }
        //INITIALIZE SPEEDTEST
        var s = new Speedtest({
            autostart: true
        }); // create speedtest object with autostart parameter
        <?php if (getenv("TELEMETRY") == "true") { ?>
            s.setParameter("telemetry_level", "basic");
        <?php } ?>
        <?php if (getenv("DISABLE_IPINFO") == "true") { ?>
            s.setParameter("getIp_ispInfo", false);
        <?php } ?>
        <?php if (getenv("DISTANCE")) { ?>
            s.setParameter("getIp_ispInfo_distance", "<?= getenv("DISTANCE") ?>");
        <?php } ?>

        var meterBk = /Trident.*rv:(\d+\.\d+)/i.test(navigator.userAgent) ? "#EAEAEA" : "#80808040";
        var color = "#6060AA",
            colorBad = "#ff0000",
            colorOk = "#e9d103",
            colorGood = "#00ff0a";

        function getFgColor(amount) {
            if (amount < 1.00) {
                return colorBad;
            } else if (amount < 5.00) {
                return colorOk;
            } else {
                return colorGood;
            }
        }
        var progColor = meterBk;

        //CODE FOR GAUGES
        function drawMeter(c, amount, bk, statusNumber, progress, prog) {
            var ctx = c.getContext("2d");
            var dp = window.devicePixelRatio || 1;
            var cw = c.clientWidth * dp,
                ch = c.clientHeight * dp;
            var sizScale = ch * 0.0055;
            if (c.width == cw && c.height == ch) {
                ctx.clearRect(0, 0, cw, ch);
            } else {
                c.width = cw;
                c.height = ch;
            }
            ctx.beginPath();
            ctx.strokeStyle = bk;
            ctx.lineWidth = 12 * sizScale;
            ctx.arc(c.width / 2, c.height - 58 * sizScale, c.height / 1.8 - ctx.lineWidth, -Math.PI * 1.1, Math.PI * 0.1);
            ctx.stroke();
            ctx.beginPath();
            var fgColor = getFgColor(statusNumber);
            ctx.strokeStyle = fgColor;
            ctx.lineWidth = 12 * sizScale;
            ctx.arc(c.width / 2, c.height - 58 * sizScale, c.height / 1.8 - ctx.lineWidth, -Math.PI * 1.1, amount * Math.PI * 1.2 - Math.PI * 1.1);
            ctx.stroke();
            if (typeof progress !== "undefined") {
                ctx.fillStyle = prog;
                ctx.fillRect(c.width * 0.3, c.height - 16 * sizScale, c.width * 0.4 * progress, 4 * sizScale);
            }
        }

        function mbpsToAmount(s) {
            return 1 - (1 / (Math.pow(1.3, Math.sqrt(s))));
        }

        function format(d) {
            d = Number(d);
            if (d < 10) return d.toFixed(2);
            if (d < 100) return d.toFixed(1);
            return d.toFixed(0);
        }

        //UI CODE
        var uiData = null;

        function startStop() {
            if (s.getState() == 3) {
                //speedtest is running, abort
                s.abort();
                data = null;
                I("startStopBtn").className = "";
                initUI();
            } else {
                //test is not running, begin
                I("startStopBtn").className = "running";
                I("shareArea").style.display = "none";
                s.onupdate = function(data) {
                    uiData = data;
                };
                s.onend = function(aborted) {
                    I("startStopBtn").className = "";
                    updateUI(true);
                    if (!aborted) {
                        //if testId is present, show sharing panel, otherwise do nothing
                        try {
                            var testId = uiData.testId;
                            if (testId != null) {
                                var shareURL = window.location.href.substring(0, window.location.href.lastIndexOf("/")) + "/results/?id=" + testId;
                                I("resultsImg").src = shareURL;
                                I("resultsURL").value = shareURL;
                                I("testId").innerHTML = testId;
                                I("shareArea").style.display = "";
                            }
                        } catch (e) {}
                    }
                };
                s.start();
            }
        }
        //this function reads the data sent back by the test and updates the UI
        function updateUI(forced) {
            if (!forced && s.getState() != 3) return;
            if (uiData == null) return;
            var status = uiData.testState;
            I("ipAddress").textContent = uiData.clientIp;
            I("dlText").textContent = (status == 1 && uiData.dlStatus == 0) ? "..." : format(uiData.dlStatus);
            drawMeter(
                I("dlMeter"),
                mbpsToAmount(Number(uiData.dlStatus * (status == 1 ? oscillate() : 1))),
                meterBk,
                Number(uiData.dlStatus),
                Number(uiData.dlProgress),
                progColor);
            I("ulText").textContent = (status == 3 && uiData.ulStatus == 0) ? "..." : format(uiData.ulStatus);
            drawMeter(
                I("ulMeter"),
                mbpsToAmount(Number(uiData.ulStatus * (status == 3 ? oscillate() : 1))),
                meterBk,
                Number(uiData.ulStatus),
                Number(uiData.ulProgress),
                progColor);
            I("pingText").textContent = format(uiData.pingStatus);
            I("jitText").textContent = format(uiData.jitterStatus);
            // Check if the test is complete
            var isSupported = false;
            if (status == 4) {
                if (Number(uiData.dlStatus) < 1.0 && Number(uiData.ulStatus) < 1.0) {
                    I("connection").textContent = "Slow Connection";
                    var isSupported = true;
                } else if (Number(uiData.dlStatus) < 5.0 && Number(uiData.ulStatus) < 5.0) {
                    I("connection").textContent = "Standard Connection";
                    var isSupported = true;
                } else {
                    I("connection").textContent = "Optimal Connection";
                    var isSupported = false;
                }
                testAll();
            }

        }

        function oscillate() {
            return 1 + 0.02 * Math.sin(Date.now() / 100);
        }
        //update the UI every frame
        window.requestAnimationFrame = window.requestAnimationFrame || window.webkitRequestAnimationFrame || window.mozRequestAnimationFrame || window.msRequestAnimationFrame || (function(callback, element) {
            setTimeout(callback, 1000 / 60);
        });

        function frame() {
            requestAnimationFrame(frame);
            updateUI();
        }
        frame(); //start frame loop
        //function to (re)initialize UI
        function initUI() {
            drawMeter(I("dlMeter"), 0, meterBk, color, 0);
            drawMeter(I("ulMeter"), 0, meterBk, color, 0);
            I("dlText").textContent = "";
            I("ulText").textContent = "";
            I("pingText").textContent = "";
            I("jitText").textContent = "";
            I("ipAddress").textContent = "";
        }
    </script>
    <title><?= getenv('TITLE') ?: 'LibreSpeed Example' ?></title>
</head>

<body>
    <h1><?= getenv('TITLE') ?: 'LibreSpeed Example' ?></h1>
    <div id="testWrapper">
        <div id="startStopBtn" onclick="startStop()" style="display:none"></div><br />
        <?php if (getenv("TELEMETRY") == "true") { ?>
            <a class="privacy" href="#" onclick="I('privacyPolicy').style.display=''">Privacy</a>
        <?php } ?>
        <div id="test">
            <div class="testGroup">
                <div class="testArea2">
                    <div class="testName">Ping</div>
                    <div id="pingText" class="meterText" style="color:#AA6060"></div>
                    <div class="unit">ms</div>
                </div>
                <div class="testArea2">
                    <div class="testName">Jitter</div>
                    <div id="jitText" class="meterText" style="color:#AA6060"></div>
                    <div class="unit">ms</div>
                </div>
            </div>
            <div class="testGroup">
                <div class="testArea">
                    <div class="testName">Download</div>
                    <canvas id="dlMeter" class="meter"></canvas>
                    <div id="dlText" class="meterText"></div>
                    <div class="unit">Mbps</div>
                </div>
                <div class="testArea">
                    <div class="testName">Upload</div>
                    <canvas id="ulMeter" class="meter"></canvas>
                    <div id="ulText" class="meterText"></div>
                    <div class="unit">Mbps</div>
                </div>
            </div>
            <div id="precheck">
                <table>
                    <tbody>
                        <tr>
                            <td><b>IP Address</td>
                            <td id="ipAddress"></td>
                        </tr>
                        <tr>
                            <td><b>Connection</td>
                            <td id="connection"></td>
                        </tr>
                        <tr>
                            <td><b>HTML5</td>
                            <td id="html5"></td>
                        </tr>
                        <tr>
                            <td><b>Websocket</td>
                            <td id="websocket"></td>
                        </tr>
                        <tr>
                            <td><b>Browser</td>
                            <td id="browser"></td>
                        </tr>
                    </tbody>
                </table>
                <div id="testcomplete">
                </div>
            </div>
            <div id="shareArea" style="display:none">
                <h3>Share results</h3>
                <p>Test ID: <span id="testId"></span></p>
                <input type="text" value="" id="resultsURL" readonly="readonly" onclick="this.select();this.focus();this.select();document.execCommand('copy');alert('Link copied')" />
                <img src="" id="resultsImg" />
            </div>
        </div>
        <!-- <a href="https://github.com/librespeed/speedtest">Source code</a> -->
<p>Powered by <a href="https://gitlab.com/gacybercenter/open/precheck"><b>Gergia Cyber Range System PreCheck</b></a><br>
            and <a href="https://github.com/librespeed/speedtest"><b>LibreSpeed Speedtest</b></a></p>    </div>
    <div id="privacyPolicy" style="display:none">
        <h2>Privacy Policy</h2>
        <p>This HTML5 Speedtest server is configured with telemetry enabled.</p>
        <h4>What data we collect</h4>
        <p>
            At the end of the test, the following data is collected and stored:
        <ul>
            <li>Test ID</li>
            <li>Time of testing</li>
            <li>Test results (download and upload speed, ping and jitter)</li>
            <li>IP address</li>
            <li>ISP information</li>
            <li>Approximate location (inferred from IP address, not GPS)</li>
            <li>User agent and browser locale</li>
            <li>Test log (contains no personal information)</li>
        </ul>
        </p>
        <h4>How we use the data</h4>
        <p>
            Data collected through this service is used to:
        <ul>
            <li>Allow sharing of test results (sharable image for forums, etc.)</li>
            <li>To improve the service offered to you (for instance, to detect problems on our side)</li>
        </ul>
        No personal information is disclosed to third parties.
        </p>
        <h4>Your consent</h4>
        <p>
            By starting the test, you consent to the terms of this privacy policy.
        </p>
        <h4>Data removal</h4>
        <p>
            If you want to have your information deleted, you need to provide either the ID of the test or your IP address. This is the only way to identify your data, without this information we won't be able to comply with your request.<br /><br />
            Contact this email address for all deletion requests: <a href="mailto:<?= getenv("EMAIL") ?>"><?= getenv("EMAIL") ?></a>.
        </p>
        <br /><br />
        <a class="privacy" href="#" onclick="I('privacyPolicy').style.display='none'">Close</a><br />
    </div>
    <script type="text/javascript">
        setTimeout(function() {
            initUI()
        }, 100);
        window.onload = function() {
            const get = document.getElementById("startStopBtn");
            get.click();
        };
    </script>
</body>

</html>