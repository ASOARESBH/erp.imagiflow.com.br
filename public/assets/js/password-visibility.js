(function () {
    'use strict';

    function updateButton(button, visible) {
        var icon = button.querySelector('[data-password-toggle-icon]');
        var showLabel = button.getAttribute('data-show-label') || 'Mostrar senha';
        var hideLabel = button.getAttribute('data-hide-label') || 'Ocultar senha';
        var label = visible ? hideLabel : showLabel;

        button.setAttribute('aria-pressed', visible ? 'true' : 'false');
        button.setAttribute('aria-label', label);
        button.setAttribute('title', label);

        if (icon) {
            icon.className = visible ? 'fa-solid fa-eye-slash' : 'fa-solid fa-eye';
        }
    }

    function bindPasswordToggle(button) {
        var targetId = button.getAttribute('data-password-toggle');
        var input = targetId ? document.getElementById(targetId) : null;

        if (!input) {
            return;
        }

        updateButton(button, input.type === 'text');

        button.addEventListener('click', function () {
            var visible = input.type === 'password';
            input.type = visible ? 'text' : 'password';
            updateButton(button, visible);
            input.focus();
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        var buttons = document.querySelectorAll('[data-password-toggle]');
        for (var index = 0; index < buttons.length; index += 1) {
            bindPasswordToggle(buttons[index]);
        }
    });
}());
