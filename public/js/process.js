var ws;

$(document).ready(function () {
    if ("WebSocket" in window) {
        debug("WebSocket supported", 'success');
        connect("ws://127.0.0.1:21187/fps");
        if ($('#console_send').length) $('#console_send').removeAttr('disabled');
    } else {
        debug("WebSocket not supported", 'error');
    }

    function ws_send(str) {
        try {
            ws.send(str);
        } catch (err) {
            debug(err, 'error');
        }
    }

    function connect(host) {
        debug("Connecting to " + host + "...");
        try {
            ws = new WebSocket(host);
        } catch (err) {
            debug(err, 'error');
        }

        if ($('#host_connect').length) $('#host_connect').attr('disabled', true);
        if ($('#host_close').length)   $('#host_close').attr('disabled', false);

        ws.onopen = function () {
            debug("Connected successfully", 'success');
            if ($('#statusDot').length)      $('#statusDot').addClass('connected');
            if ($('#connectionText').length) $('#connectionText').text('Connected');
        };

        ws.onmessage = function (evt) {
            debug("Received: " + evt.data, 'response');
            var obj = eval("(" + evt.data + ")");
            var status = document.getElementById("es");

            switch (obj.workmsg) {
                case 1:
                    if (status) status.textContent = "Please open device";
                    break;
                case 2:
                    if (status) status.textContent = "Place finger on scanner";
                    break;
                case 3:
                    if (status) status.textContent = "Lift finger";
                    break;
                case 4:
                    break;
                case 5:
                    // GET TEMPLATE (Capture) - uses data2
                    if (obj.retmsg == 1) {
                        if (status) status.textContent = "Template captured successfully";
                        var templateData = obj.data2 || obj.data1;
                        debug("Capture successful - checking data2: " + (obj.data2 ? "received" : "null"), 'success');
                        if (templateData && templateData != "null" && templateData != "") {
                            if (document.getElementById("e2")) {
                                document.getElementById("e2").value = templateData;
                                debug("Template stored in e2 (length: " + templateData.length + ")", 'success');
                            }
                        } else {
                            debug("Warning: No template data received", 'error');
                        }
                    } else {
                        if (status) status.textContent = "Failed to capture template";
                        debug("Capture failed - retmsg: " + obj.retmsg, 'error');
                    }
                    break;
                case 6:
                    // ENROLL TEMPLATE
                    if (obj.retmsg == 1) {
                        if (status) status.textContent = "Enrollment successful";
                        debug("Enrollment successful - data1: " + (obj.data1 ? "received" : "null"), 'success');
                        if (obj.data1 && obj.data1 != "null" && obj.data1 != "") {
                            if (document.getElementById("e1")) {
                                document.getElementById("e1").value = obj.data1;
                            }
                            var registeredName = document.getElementById("userName") 
                                ? document.getElementById("userName").value.trim() 
                                : "";
                            if (registeredName) {
                                if (document.getElementById("displayName"))
                                    document.getElementById("displayName").textContent = registeredName;
                                if (document.getElementById("currentUser"))
                                    document.getElementById("currentUser").style.display = "block";
                                if (document.getElementById("registrationForm"))
                                    document.getElementById("registrationForm").style.display = "none";
                                debug("✓ " + registeredName + " enrolled successfully", 'success');
                            }
                            debug("Template stored in e1", 'success');
                        } else {
                            debug("Warning: No template data received", 'error');
                        }
                    } else {
                        if (status) status.textContent = "Enrollment failed";
                        debug("Enrollment failed - retmsg: " + obj.retmsg, 'error');
                    }
                    break;
                case 7:
                    // FINGERPRINT IMAGE
                    if (obj.image && obj.image != "null" && obj.image != "") {
                        var img = document.getElementById("imgDiv");
                        if (img) {
                            img.src = "data:image/png;base64," + obj.image;
                            debug("Fingerprint image updated", 'success');
                        }
                    }
                    break;
                case 8:
                    if (status) status.textContent = "Operation timed out";
                    debug("Timeout occurred", 'error');
                    break;
                case 9:
                    var matchScore = parseInt(obj.retmsg);
                    var enrolledName = document.getElementById("displayName") 
                        ? document.getElementById("displayName").textContent 
                        : "Unknown";
                    if (matchScore >= 50 && matchScore <= 100) {
                        if (status) status.textContent = "Match successful! This user is \"" + enrolledName + "\"";
                        debug("✓ Authentication successful - Match score: " + matchScore + " - User: " + enrolledName, 'success');
                    } else {
                        if (status) status.textContent = "This user is not \"" + enrolledName + "\"";
                        debug("✗ Authentication failed - Match score: " + matchScore + " (below threshold)", 'error');
                    }
                    break;
                default:
                    debug("Unknown message type: " + obj.workmsg, 'error');
            }
        };

        ws.onclose = function () {
            debug("Connection closed", 'error');
            if ($('#host_connect').length) $('#host_connect').attr('disabled', false);
            if ($('#host_close').length)   $('#host_close').attr('disabled', true);
            if ($('#statusDot').length)    $('#statusDot').removeClass('connected');
            if ($('#connectionText').length) $('#connectionText').text('Disconnected');
        };
    }

    function debug(msg, type) {
        if ($("#console").length) {
            $("#console").append('<p class="' + (type || '') + '">' + msg + '</p>');
        }
        if ($('.console-wrapper').length) {
            $('.console-wrapper').scrollTop($('.console-wrapper')[0].scrollHeight);
        }
    }

    if ($('#host_connect').length) {
        $('#host_connect').click(function () {
            connect("ws://127.0.0.1:21187/fps");
        });
    }

    if ($('#host_close').length) {
        $('#host_close').click(function () {
            ws.close();
        });
    }
});

function EnrollTemplate() {
    var nameEl = document.getElementById("userName");
    var name = nameEl ? nameEl.value.trim() : "Employee";

    if (!name) {
        var es = document.getElementById("es");
        if (es) es.textContent = "Please enter a name first";
        return;
    }

    try {
        var cmd = "{\"cmd\":\"enrol\",\"data1\":\"\",\"data2\":\"\"}";
        ws.send(cmd);
        var es = document.getElementById("es");
        if (es) es.textContent = "Place finger on scanner";
        debug("Enrolling user: " + name, 'success');
    } catch (err) {
        console.error(err);
    }
}

function GetTemplate() {
    try {
        var cmd = "{\"cmd\":\"capture\",\"data1\":\"\",\"data2\":\"\"}";
        ws.send(cmd);
        var es = document.getElementById("es");
        if (es) es.textContent = "Place finger on scanner";
    } catch (err) {
        console.error(err);
    }
}

function MatchTemplate() {
    var e1 = document.getElementById("e1");
    var e2 = document.getElementById("e2");
    var v1 = e1 ? e1.value : "";
    var v2 = e2 ? e2.value : "";

    if (!v1 || !v2) {
        var es = document.getElementById("es");
        if (es) es.textContent = "Please enroll and capture templates first";
        return;
    }

    try {
        var cmd1 = "{\"cmd\":\"setdata\",\"data1\":\"" + v1 + "\",\"data2\":\"\"}";
        ws.send(cmd1);

        var cmd2 = "{\"cmd\":\"setdata\",\"data1\":\"\",\"data2\":\"" + v2 + "\"}";
        ws.send(cmd2);

        var cmd3 = "{\"cmd\":\"match\",\"data1\":\"\",\"data2\":\"\"}";
        ws.send(cmd3);

        var es = document.getElementById("es");
        if (es) es.textContent = "Verifying...";
    } catch (err) {
        console.error(err);
    }
}

function debug(msg, type) {
    if ($("#console").length) {
        $("#console").append('<p class="' + (type || '') + '">' + msg + '</p>');
    }
    if ($('.console-wrapper').length) {
        $('.console-wrapper').scrollTop($('.console-wrapper')[0].scrollHeight);
    }
}