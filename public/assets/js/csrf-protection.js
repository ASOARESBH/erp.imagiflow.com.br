(function () {
    'use strict';

    var token = document.querySelector('meta[name="csrf-token"]');
    var csrfToken = token ? token.getAttribute('content') : '';
    if (!csrfToken) {
        return;
    }

    window.ERP_CSRF_TOKEN = csrfToken;

    function isSameOrigin(url) {
        try {
            return new URL(url, window.location.origin).origin === window.location.origin;
        } catch (error) {
            return false;
        }
    }

    function methodOf(input, options) {
        if (options && options.method) {
            return String(options.method).toUpperCase();
        }
        if (window.Request && input instanceof Request) {
            return String(input.method || 'GET').toUpperCase();
        }
        return 'GET';
    }

    function urlOf(input) {
        return window.Request && input instanceof Request ? input.url : String(input);
    }

    function protectForm(form) {
        if (!form || String(form.method || 'GET').toUpperCase() !== 'POST') {
            return;
        }

        var field = form.querySelector('input[name="csrf_token"]');
        if (!field) {
            field = document.createElement('input');
            field.type = 'hidden';
            field.name = 'csrf_token';
            form.appendChild(field);
        }
        field.value = csrfToken;
    }

    function protectForms(root) {
        if (!root) {
            return;
        }
        if (root.tagName === 'FORM') {
            protectForm(root);
        }
        var forms = root.querySelectorAll ? root.querySelectorAll('form') : [];
        Array.prototype.forEach.call(forms, protectForm);
    }

    protectForms(document);
    document.addEventListener('DOMContentLoaded', function () {
        protectForms(document);
    });

    if (window.MutationObserver) {
        new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
                Array.prototype.forEach.call(mutation.addedNodes || [], function (node) {
                    if (node.nodeType === 1) {
                        protectForms(node);
                    }
                });
            });
        }).observe(document.documentElement, { childList: true, subtree: true });
    }

    if (window.fetch) {
        var nativeFetch = window.fetch.bind(window);
        window.fetch = function (input, options) {
            var requestOptions = options || {};
            if (methodOf(input, requestOptions) === 'POST' && isSameOrigin(urlOf(input))) {
                var headers = new Headers(requestOptions.headers || (window.Request && input instanceof Request ? input.headers : {}));
                headers.set('X-CSRF-Token', csrfToken);
                requestOptions = Object.assign({}, requestOptions, { headers: headers });
            }
            return nativeFetch(input, requestOptions);
        };
    }

    if (window.jQuery) {
        window.jQuery(document).ajaxSend(function (event, xhr, settings) {
            if (String(settings.type || settings.method || 'GET').toUpperCase() === 'POST' && isSameOrigin(settings.url || window.location.href)) {
                xhr.setRequestHeader('X-CSRF-Token', csrfToken);
            }
        });
    }
})();
