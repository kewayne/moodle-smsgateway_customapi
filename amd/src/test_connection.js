define(['core/ajax', 'core/str', 'core/notification'], function(ajax, str, notification) {
    'use strict';

    return {
        init: function() {
            var testBtn = document.getElementById('customapi_test_btn');
            var jsonTextarea = document.querySelector('[name="json_body"]');
            var formatBtn = document.getElementById('customapi_btn_format_json');
            var badgeEl = document.getElementById('json_syntax_badge');
            var chipBtns = document.querySelectorAll('.customapi-chip');

            // Preload strings required for live tester UI.
            var stringKeys = [
                { key: 'test_warning', component: 'smsgateway_customapi' },
                { key: 'test_error_missing_url', component: 'smsgateway_customapi' },
                { key: 'test_loading', component: 'smsgateway_customapi' },
                { key: 'test_status', component: 'smsgateway_customapi' },
                { key: 'test_latency', component: 'smsgateway_customapi' },
                { key: 'test_successful', component: 'smsgateway_customapi' },
                { key: 'test_failed', component: 'smsgateway_customapi' },
                { key: 'test_note', component: 'smsgateway_customapi' },
                { key: 'test_response_body', component: 'smsgateway_customapi' },
                { key: 'test_request_sent', component: 'smsgateway_customapi' },
                { key: 'test_response_headers', component: 'smsgateway_customapi' },
                { key: 'test_empty_body', component: 'smsgateway_customapi' },
                { key: 'test_url', component: 'smsgateway_customapi' },
                { key: 'test_headers_sent', component: 'smsgateway_customapi' },
                { key: 'test_body_sent', component: 'smsgateway_customapi' },
                { key: 'test_none', component: 'smsgateway_customapi' },
                { key: 'test_no_headers', component: 'smsgateway_customapi' },
                { key: 'test_error_ajax', component: 'smsgateway_customapi' },
                { key: 'json_valid', component: 'smsgateway_customapi' },
                { key: 'json_invalid', component: 'smsgateway_customapi' }
            ];

            // Helper: Validate JSON syntax with placeholders.
            function validateJsonSyntax(rawText) {
                if (!rawText || !rawText.trim()) {
                    return { valid: true, empty: true };
                }

                // Replace placeholders with safe dummy strings to test JSON validity.
                var sanitized = rawText
                    .replace(/\{\{\s*recipient\s*\}\}/g, '1234567890')
                    .replace(/\{\{\s*message\s*\}\}/g, 'test_message');

                try {
                    JSON.parse(sanitized);
                    return { valid: true, empty: false };
                } catch (e) {
                    return { valid: false, empty: false, error: e.message };
                }
            }

            // Helper: Update live syntax badge.
            function updateSyntaxBadge() {
                if (!jsonTextarea || !badgeEl) {
                    return;
                }
                var val = jsonTextarea.value;
                var result = validateJsonSyntax(val);

                str.get_strings(stringKeys).then(function(strings) {
                    var strValid = strings[18] || 'Valid JSON Syntax';
                    var strInvalid = strings[19] || 'Invalid JSON Syntax';

                    if (result.empty) {
                        badgeEl.innerHTML = '';
                    } else if (result.valid) {
                        badgeEl.innerHTML = '<span class="badge bg-success"><i class="fa fa-check me-1"></i>' + escapeHtml(strValid) + '</span>';
                    } else {
                        badgeEl.innerHTML = '<span class="badge bg-danger" title="' + escapeHtml(result.error) + '"><i class="fa fa-exclamation-triangle me-1"></i>' + escapeHtml(strInvalid) + ': ' + escapeHtml(result.error) + '</span>';
                    }
                });
            }

            // Live syntax checking as user types.
            if (jsonTextarea) {
                jsonTextarea.addEventListener('input', updateSyntaxBadge);
                jsonTextarea.addEventListener('change', updateSyntaxBadge);
                updateSyntaxBadge();
            }

            // Auto-format / Beautify JSON button handler.
            if (formatBtn && jsonTextarea) {
                formatBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    var rawText = jsonTextarea.value;
                    if (!rawText || !rawText.trim()) {
                        return;
                    }

                    // Temporary placeholder tokens for stringify.
                    var tokenRecipient = '__RECIPIENT_PLACEHOLDER__';
                    var tokenMessage = '__MESSAGE_PLACEHOLDER__';

                    var sanitized = rawText
                        .replace(/\{\{\s*recipient\s*\}\}/g, tokenRecipient)
                        .replace(/\{\{\s*message\s*\}\}/g, tokenMessage);

                    try {
                        var parsed = JSON.parse(sanitized);
                        var formatted = JSON.stringify(parsed, null, 2);
                        formatted = formatted
                            .replace(new RegExp(tokenRecipient, 'g'), '{{recipient}}')
                            .replace(new RegExp(tokenMessage, 'g'), '{{message}}');

                        jsonTextarea.value = formatted;
                        updateSyntaxBadge();
                    } catch (err) {
                        notification.addNotification({
                            message: 'Cannot format: ' + err.message,
                            type: 'error'
                        });
                    }
                });
            }

            // Placeholder Chip Insertion Handler.
            if (chipBtns) {
                chipBtns.forEach(function(chip) {
                    chip.addEventListener('click', function(e) {
                        e.preventDefault();
                        var textToInsert = chip.getAttribute('data-insert');
                        if (!textToInsert) {
                            return;
                        }

                        // Determine active textarea (json_body or post_body_parameters or active element).
                        var activeEl = document.activeElement;
                        var targetTa = (activeEl && activeEl.tagName === 'TEXTAREA') ? activeEl : jsonTextarea;
                        if (!targetTa) {
                            targetTa = document.querySelector('[name="post_body_parameters"]');
                        }

                        if (targetTa) {
                            var startPos = targetTa.selectionStart || 0;
                            var endPos = targetTa.selectionEnd || 0;
                            var currentVal = targetTa.value;

                            targetTa.value = currentVal.substring(0, startPos) + textToInsert + currentVal.substring(endPos);
                            targetTa.selectionStart = targetTa.selectionEnd = startPos + textToInsert.length;
                            targetTa.focus();

                            updateSyntaxBadge();
                        }
                    });
                });
            }

            // ---------------------------------------------------------
            // Test Gateway Connection Runner
            // ---------------------------------------------------------
            if (!testBtn) {
                return;
            }

            testBtn.addEventListener('click', function(e) {
                e.preventDefault();

                var getValue = function(name) {
                    var el = document.querySelector('[name="' + name + '"]');
                    return el ? el.value.trim() : '';
                };

                var apiUrl = getValue('api_url');
                var requestType = getValue('request_type') || 'GET';
                var bodyType = getValue('body_type') || 'form';
                var headers = getValue('headers');
                var queryParameters = getValue('query_parameters');
                var postBodyParameters = getValue('post_body_parameters');
                var jsonBody = getValue('json_body');
                var successCondition = getValue('success_condition');
                var countryCode = getValue('countrycode');
                var testNumber = getValue('test_number');
                var testMessage = getValue('test_message');

                var resultContainer = document.getElementById('customapi_test_results');
                if (!resultContainer) {
                    return;
                }

                str.get_strings(stringKeys).then(function(strings) {
                    var s = {
                        warning: strings[0],
                        errorMissingUrl: strings[1],
                        loading: strings[2],
                        status: strings[3],
                        latency: strings[4],
                        successful: strings[5],
                        failed: strings[6],
                        note: strings[7],
                        responseBody: strings[8],
                        requestSent: strings[9],
                        responseHeaders: strings[10],
                        emptyBody: strings[11],
                        url: strings[12],
                        headersSent: strings[13],
                        bodySent: strings[14],
                        none: strings[15],
                        noHeaders: strings[16],
                        errorAjax: strings[17]
                    };

                    if (!apiUrl) {
                        resultContainer.innerHTML = '<div class="alert alert-warning mt-3">' +
                            '<strong>' + escapeHtml(s.warning) + ':</strong> ' + escapeHtml(s.errorMissingUrl) +
                            '</div>';
                        return;
                    }

                    resultContainer.innerHTML = '<div class="card mt-3 border-info">' +
                        '<div class="card-body text-center p-4">' +
                        '<div class="spinner-border text-primary me-2" role="status"><span class="visually-hidden">...</span></div>' +
                        '<span class="fw-bold">' + escapeHtml(s.loading) + '</span>' +
                        '</div>' +
                        '</div>';

                    ajax.call([{
                        methodname: 'smsgateway_customapi_test_connection',
                        args: {
                            api_url: apiUrl,
                            request_type: requestType,
                            body_type: bodyType,
                            headers: headers,
                            query_parameters: queryParameters,
                            post_body_parameters: postBodyParameters,
                            json_body: jsonBody,
                            success_condition: successCondition,
                            countrycode: countryCode,
                            test_number: testNumber,
                            test_message: testMessage
                        }
                    }])[0].then(function(response) {
                        var isSuccess = response.success;
                        var statusCode = response.statuscode;
                        var statusBadgeClass = isSuccess ? 'bg-success' : (statusCode >= 200 && statusCode < 300 ? 'bg-warning text-dark' : 'bg-danger');

                        var prettyBody = response.responsebody;
                        try {
                            var parsedJson = JSON.parse(response.responsebody);
                            prettyBody = JSON.stringify(parsedJson, null, 2);
                        } catch (err) {
                            // Not JSON.
                        }

                        var html = '<div class="card mt-3 shadow-sm border-' + (isSuccess ? 'success' : 'danger') + '">' +
                            '<div class="card-header bg-light d-flex justify-content-between align-items-center py-2">' +
                            '<div>' +
                            '<span class="badge ' + statusBadgeClass + ' me-2 fs-6">' + (statusCode || '0') + ' ' + escapeHtml(s.status) + '</span>' +
                            '<span class="badge bg-secondary me-2"><i class="fa fa-clock-o me-1"></i>' + response.executiontime + ' ms</span>' +
                            '</div>' +
                            '<div>' +
                            '<span class="fw-bold text-' + (isSuccess ? 'success' : 'danger') + '">' +
                            (isSuccess ? '<i class="fa fa-check-circle me-1"></i>' + escapeHtml(s.successful) : '<i class="fa fa-times-circle me-1"></i>' + escapeHtml(s.failed)) +
                            '</span>' +
                            '</div>' +
                            '</div>' +
                            '<div class="card-body p-3">';

                        if (response.errormessage) {
                            html += '<div class="alert alert-' + (isSuccess ? 'info' : 'danger') + ' py-2 small mb-3">' +
                                '<strong>' + escapeHtml(s.note) + ':</strong> ' + escapeHtml(response.errormessage) +
                                '</div>';
                        }

                        html += '<ul class="nav nav-tabs mb-3" role="tablist">' +
                            '<li class="nav-item" role="presentation">' +
                            '<button class="nav-link active py-1 px-3" id="tab-body-btn" data-bs-toggle="tab" data-bs-target="#tab-body" type="button" role="tab">' + escapeHtml(s.responseBody) + '</button>' +
                            '</li>' +
                            '<li class="nav-item" role="presentation">' +
                            '<button class="nav-link py-1 px-3" id="tab-req-btn" data-bs-toggle="tab" data-bs-target="#tab-req" type="button" role="tab">' + escapeHtml(s.requestSent) + '</button>' +
                            '</li>' +
                            '<li class="nav-item" role="presentation">' +
                            '<button class="nav-link py-1 px-3" id="tab-hdrs-btn" data-bs-toggle="tab" data-bs-target="#tab-hdrs" type="button" role="tab">' + escapeHtml(s.responseHeaders) + '</button>' +
                            '</li>' +
                            '</ul>' +
                            '<div class="tab-content">' +
                            '<div class="tab-pane fade show active" id="tab-body" role="tabpanel">' +
                            '<pre class="bg-dark text-light p-3 rounded" style="max-height: 250px; overflow-y: auto; font-size: 0.85rem;"><code>' + escapeHtml(prettyBody || s.emptyBody) + '</code></pre>' +
                            '</div>' +
                            '<div class="tab-pane fade" id="tab-req" role="tabpanel">' +
                            '<div class="bg-light p-3 border rounded small">' +
                            '<div class="mb-2"><strong>' + escapeHtml(s.url) + ':</strong> <code>' + escapeHtml(response.requestsent_method) + ' ' + escapeHtml(response.requestsent_url) + '</code></div>' +
                            '<div class="mb-2"><strong>' + escapeHtml(s.headersSent) + ':</strong><pre class="bg-white p-2 border rounded mb-0 mt-1"><code>' + escapeHtml(response.requestsent_headers || s.none) + '</code></pre></div>' +
                            '<div><strong>' + escapeHtml(s.bodySent) + ':</strong><pre class="bg-white p-2 border rounded mb-0 mt-1"><code>' + escapeHtml(response.requestsent_body || s.none) + '</code></pre></div>' +
                            '</div>' +
                            '</div>' +
                            '<div class="tab-pane fade" id="tab-hdrs" role="tabpanel">' +
                            '<pre class="bg-light p-3 border rounded small" style="max-height: 200px; overflow-y: auto;"><code>' + escapeHtml(response.responseheaders || s.noHeaders) + '</code></pre>' +
                            '</div>' +
                            '</div>' +
                            '</div>' +
                            '</div>';

                        resultContainer.innerHTML = html;

                        var tabButtons = resultContainer.querySelectorAll('[data-bs-toggle="tab"]');
                        tabButtons.forEach(function(btn) {
                            btn.addEventListener('click', function(evt) {
                                evt.preventDefault();
                                tabButtons.forEach(function(b) {
                                    b.classList.remove('active');
                                    var targetId = b.getAttribute('data-bs-target');
                                    if (targetId) {
                                        var pane = resultContainer.querySelector(targetId);
                                        if (pane) {
                                            pane.classList.remove('show', 'active');
                                        }
                                    }
                                });
                                btn.classList.add('active');
                                var myTarget = resultContainer.querySelector(btn.getAttribute('data-bs-target'));
                                if (myTarget) {
                                    myTarget.classList.add('show', 'active');
                                }
                            });
                        });

                    }).catch(function(err) {
                        resultContainer.innerHTML = '<div class="alert alert-danger mt-3">' +
                            '<strong>Error:</strong> ' + escapeHtml(s.errorAjax) + ' ' +
                            escapeHtml(err.message || err.error || '') +
                            '</div>';
                    });
                });
            });
        }
    };

    function escapeHtml(str) {
        if (!str) {
            return '';
        }
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#039;');
    }
});
