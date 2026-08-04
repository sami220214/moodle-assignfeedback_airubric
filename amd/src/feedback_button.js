// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * AI feedback button.
 *
 * @module     assignfeedback_airubric/feedback_button
 * @copyright  2026 Sami Simpanen
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['core/ajax', 'core/notification', 'jquery'], function(Ajax, Notification, $) {
    const toException = (error) => {
        if (error && typeof error === 'object') {
            const message = [
                error.message,
                error.error,
                error.exception,
                error.debuginfo
            ].find((value) => typeof value === 'string' && value.trim() !== '');

            if (message && error.message !== message) {
                return new Error(message);
            }
            return error;
        }

        if (typeof error === 'string' && error.trim() !== '') {
            return new Error(error);
        }

        return new Error('AI feedback generation failed.');
    };

    const showModal = (modal) => {
        if (!modal) {
            return;
        }

        if (window.bootstrap && window.bootstrap.Modal) {
            window.bootstrap.Modal.getOrCreateInstance(modal).show();
            return;
        }

        if ($ && $.fn && typeof $.fn.modal === 'function') {
            $(modal).modal('show');
            return;
        }

        // Last-resort fallback for environments without JS modal helpers.
        modal.style.display = 'block';
        modal.classList.add('show');
        modal.removeAttribute('aria-hidden');
    };

    const initOne = (root) => {
        const openBtn = root.querySelector('[data-action="open-modal"]');
        if (!openBtn) {
            return;
        }

        const modal = root.querySelector('[data-region="feedback-modal"]');
        const textarea = root.querySelector('[data-region="feedback-text"]');
        const status = root.querySelector('[data-region="status"]');
        const inputTokens = root.querySelector('[data-region="input-tokens"]');
        const copyBtn = root.querySelector('[data-action="copy-feedback"]');
        if (!modal || !textarea || !status || !inputTokens || !copyBtn) {
            return;
        }

        openBtn.addEventListener('click', () => {
            const cmid = Number(root.dataset.cmid);
            const userid = Number(root.dataset.userid);
            const generating = root.dataset.generatingLabel || 'Generating feedback...';

            status.textContent = generating;
            inputTokens.textContent = '';
            inputTokens.classList.add('d-none');
            textarea.value = '';
            showModal(modal);

            Ajax.call([{
                methodname: 'assignfeedback_airubric_generate_feedback',
                args: {cmid: cmid, userid: userid}
            }])[0].then((result) => {
                textarea.value = result.feedback || '';
                if (Number.isFinite(Number(result.inputtokens))) {
                    inputTokens.textContent = (root.dataset.tokenCountLabel || 'Estimated input tokens: {$a}')
                        .replace('{$a}', result.inputtokens);
                    inputTokens.classList.remove('d-none');
                }
                status.textContent = '';
                return result;
            }).catch((error) => {
                status.textContent = '';
                Notification.exception(toException(error));
            });
        });

        copyBtn.addEventListener('click', () => {
            textarea.select();
            navigator.clipboard.writeText(textarea.value).catch(() => {
                document.execCommand('copy');
            });
        });
    };

    return {
        init: () => {
            document.querySelectorAll('[data-region="assignfeedback-airubric"]').forEach(initOne);
        }
    };
});
