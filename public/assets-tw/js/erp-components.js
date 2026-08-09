/* ERP Components — Modal, Toast, Confirm, Tabs, Accordion, Wizard, Command Palette */
(function () {
    'use strict';

    /* ── Toast ──────────────────────────────────────────────────── */
    // Ensure container exists
    function getToastContainer() {
        var c = document.getElementById('erp-toast-container');
        if (!c) {
            c = document.createElement('div');
            c.id = 'erp-toast-container';
            c.className = 'erp-toast-container';
            document.body.appendChild(c);
        }
        return c;
    }

    /**
     * erpToast({ title, message, type, duration })
     * type: 'success' | 'error' | 'warning' | 'info'
     */
    window.erpToast = function (opts) {
        opts = opts || {};
        var type = opts.type || 'info';
        var duration = opts.duration || 4000;

        var styles = {
            success: {
                icon: 'fa-circle-check',
                bg: 'bg-emerald-50 border-emerald-200',
                iconColor: 'text-emerald-600',
                title: 'text-emerald-900',
                message: 'text-emerald-700',
                close: 'text-emerald-400 hover:text-emerald-600',
                bar: 'bg-emerald-500',
            },
            error: {
                icon: 'fa-circle-xmark',
                bg: 'bg-red-50 border-red-200',
                iconColor: 'text-red-600',
                title: 'text-red-900',
                message: 'text-red-700',
                close: 'text-red-400 hover:text-red-600',
                bar: 'bg-red-500',
            },
            warning: {
                icon: 'fa-triangle-exclamation',
                bg: 'bg-amber-50 border-amber-200',
                iconColor: 'text-amber-600',
                title: 'text-amber-900',
                message: 'text-amber-700',
                close: 'text-amber-400 hover:text-amber-600',
                bar: 'bg-amber-500',
            },
            info: {
                icon: 'fa-circle-info',
                bg: 'bg-blue-50 border-blue-200',
                iconColor: 'text-blue-600',
                title: 'text-blue-900',
                message: 'text-blue-700',
                close: 'text-blue-400 hover:text-blue-600',
                bar: 'bg-blue-500',
            },
        };

        var s = styles[type] || styles.info;

        var toast = document.createElement('div');
        toast.className = 'erp-toast rounded-lg border shadow-lg max-w-sm w-full overflow-hidden ' + s.bg;
        toast.innerHTML =
            '<div class="flex items-start gap-3 p-4">' +
            '<i class="fa-solid ' + s.icon + ' ' + s.iconColor + ' mt-0.5 text-base"></i>' +
            '<div class="flex-1 min-w-0">' +
            (opts.title ? '<p class="text-sm font-semibold ' + s.title + '">' + opts.title + '</p>' : '') +
            (opts.message ? '<p class="text-sm ' + s.message + ' mt-0.5">' + opts.message + '</p>' : '') +
            '</div>' +
            '<button class="' + s.close + ' shrink-0" onclick="this.closest(\'.erp-toast\').remove()"><i class="fa-solid fa-xmark text-xs"></i></button>' +
            '</div>' +
            '<div class="h-1 ' + s.bar + ' erp-toast-progress" style="animation: erpToastProgress ' + duration + 'ms linear forwards"></div>';

        var container = getToastContainer();
        container.appendChild(toast);

        setTimeout(function () {
            toast.classList.add('removing');
            setTimeout(function () { toast.remove(); }, 200);
        }, duration);
    };

    /* ── Modal ──────────────────────────────────────────────────── */
    /**
     * erpModal({ title, body, size, footer, onClose })
     * size: 'sm' | 'md' | 'lg' | 'xl' | 'full'
     * Returns { el, close }
     */
    window.erpModal = function (opts) {
        opts = opts || {};
        var sizes = {
            sm: 'max-w-sm',
            md: 'max-w-lg',
            lg: 'max-w-2xl',
            xl: 'max-w-4xl',
            full: 'max-w-[90vw]',
        };
        var sizeClass = sizes[opts.size || 'md'];

        var backdrop = document.createElement('div');
        backdrop.className = 'erp-modal-backdrop';
        backdrop.innerHTML =
            '<div class="erp-modal-content bg-white rounded-lg shadow-xl border border-zinc-200 ring-1 ring-zinc-900/5 w-full ' + sizeClass + ' mx-4 max-h-[85vh] flex flex-col">' +
            '<div class="flex items-center justify-between p-4 border-b border-zinc-200 shrink-0">' +
            '<h3 class="text-lg font-semibold text-zinc-900">' + (opts.title || '') + '</h3>' +
            '<button class="erp-modal-close p-1 rounded-md text-zinc-400 hover:text-zinc-600 hover:bg-zinc-100"><i class="fa-solid fa-xmark"></i></button>' +
            '</div>' +
            '<div class="p-4 overflow-y-auto flex-1 text-zinc-700">' + (opts.body || '') + '</div>' +
            (opts.footer ? '<div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200 shrink-0">' + opts.footer + '</div>' : '') +
            '</div>';

        document.body.appendChild(backdrop);

        // Bridge for legacy listeners that expect Bootstrap modal events
        if (typeof jQuery !== 'undefined') jQuery(backdrop).trigger('shown.bs.modal');

        function close() {
            if (typeof jQuery !== 'undefined') jQuery(backdrop).trigger('hidden.bs.modal');
            backdrop.remove();
            if (opts.onClose) opts.onClose();
        }

        backdrop.querySelector('.erp-modal-close').addEventListener('click', close);
        backdrop.addEventListener('click', function (e) {
            if (e.target === backdrop) close();
        });

        // Escape key
        function escHandler(e) {
            if (e.key === 'Escape') {
                close();
                document.removeEventListener('keydown', escHandler);
            }
        }
        document.addEventListener('keydown', escHandler);

        return { el: backdrop, close: close };
    };

    /* ── Confirm Dialog ─────────────────────────────────────────── */
    /**
     * erpConfirm({ title, message, confirmText, cancelText, type })
     * Returns Promise<boolean>
     */
    window.erpConfirm = function (opts) {
        opts = opts || {};
        var type = opts.type || 'default';
        var btnClass = type === 'destructive'
            ? 'bg-red-500 hover:bg-red-600 text-white'
            : 'bg-zinc-900 hover:bg-zinc-800 text-white';

        return new Promise(function (resolve) {
            var resolved = false;
            var modal = erpModal({
                title: opts.title || 'Confirm',
                body: '<p class="text-sm text-zinc-600">' + (opts.message || 'Are you sure?') + '</p>',
                size: 'sm',
                footer:
                    '<button class="erp-confirm-cancel px-4 py-2 text-sm font-medium rounded-md border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50">' + (opts.cancelText || 'Cancel') + '</button>' +
                    '<button class="erp-confirm-ok px-4 py-2 text-sm font-medium rounded-md ' + btnClass + '">' + (opts.confirmText || 'Confirm') + '</button>',
                onClose: function () { if (!resolved) { resolved = true; resolve(false); } },
            });

            modal.el.querySelector('.erp-confirm-cancel').addEventListener('click', function () {
                resolved = true;
                modal.close();
                resolve(false);
            });
            modal.el.querySelector('.erp-confirm-ok').addEventListener('click', function () {
                resolved = true;
                modal.close();
                resolve(true);
            });
        });
    };

    /* ── Prompt Dialog ──────────────────────────────────────────── */
    /**
     * erpPrompt({ title, message, placeholder, defaultValue, minLength, maxLength,
     *            inputType, confirmText, cancelText, type })
     * inputType: 'text' (default) | 'textarea'
     * Returns Promise<string|null> — null when cancelled / closed.
     */
    window.erpPrompt = function (opts) {
        opts = opts || {};
        var min = opts.minLength != null ? opts.minLength : 0;
        var max = opts.maxLength != null ? opts.maxLength : 1000;
        var type = opts.type || 'default';
        var btnClass = type === 'destructive'
            ? 'bg-red-500 hover:bg-red-600 text-white'
            : 'bg-zinc-900 hover:bg-zinc-800 text-white';
        var inputType = opts.inputType === 'textarea' ? 'textarea' : 'text';
        var inputId = 'erp-prompt-input-' + Date.now();
        var errorId = 'erp-prompt-error-' + Date.now();
        var defaultVal = (opts.defaultValue || '').replace(/"/g, '&quot;');
        var placeholder = (opts.placeholder || '').replace(/"/g, '&quot;');

        var inputHtml = inputType === 'textarea'
            ? '<textarea id="' + inputId + '" rows="3" maxlength="' + max + '" placeholder="' + placeholder + '"' +
              ' class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-zinc-400 focus:ring-0">' + defaultVal + '</textarea>'
            : '<input id="' + inputId + '" type="text" maxlength="' + max + '" placeholder="' + placeholder + '" value="' + defaultVal + '"' +
              ' class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-zinc-400 focus:ring-0">';

        return new Promise(function (resolve) {
            var resolved = false;
            var modal = erpModal({
                title: opts.title || 'Enter a value',
                body:
                    (opts.message ? '<p class="text-sm text-zinc-600 mb-2">' + opts.message + '</p>' : '') +
                    inputHtml +
                    '<p id="' + errorId + '" class="hidden mt-1 text-xs text-red-600"></p>',
                size: 'sm',
                footer:
                    '<button class="erp-prompt-cancel px-4 py-2 text-sm font-medium rounded-md border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50">' + (opts.cancelText || 'Cancel') + '</button>' +
                    '<button class="erp-prompt-ok px-4 py-2 text-sm font-medium rounded-md ' + btnClass + '">' + (opts.confirmText || 'OK') + '</button>',
                onClose: function () { if (!resolved) { resolved = true; resolve(null); } },
            });

            var $input = modal.el.querySelector('#' + inputId);
            var $err = modal.el.querySelector('#' + errorId);
            if ($input) { setTimeout(function () { $input.focus(); }, 30); }

            function tryAccept() {
                var val = ($input.value || '').trim();
                if (val.length < min) {
                    $err.textContent = 'A value of at least ' + min + ' character' + (min === 1 ? '' : 's') + ' is required.';
                    $err.classList.remove('hidden');
                    $input.classList.add('border-red-500');
                    $input.focus();
                    return;
                }
                resolved = true;
                modal.close();
                resolve(val);
            }

            modal.el.querySelector('.erp-prompt-cancel').addEventListener('click', function () {
                resolved = true;
                modal.close();
                resolve(null);
            });
            modal.el.querySelector('.erp-prompt-ok').addEventListener('click', tryAccept);
            if ($input && inputType === 'text') {
                $input.addEventListener('keydown', function (e) {
                    if (e.key === 'Enter') { e.preventDefault(); tryAccept(); }
                });
            }
        });
    };

    /**
     * erpApproveModal(opts) — Approval-specific dialog. Two fields, both required:
     *   remark (textarea, min 3 chars)               → approval reason
     *   effective_date (date, default today, future-allowed) → version semantic
     *
     * Per ICH Q12 / GMP convention every approval must record both: WHY (remark)
     * and WHEN it becomes effective (date). A future date marks the new version
     * as approved-but-not-yet-effective; downstream pickers keep using the prior
     * version until a daily cron flips is_current when the date arrives.
     *
     * Options:
     *   title, message, confirmText, cancelText           (display)
     *   minRemarkLength            default 3
     *   maxRemarkLength            default 1000
     *   defaultEffectiveDate       'YYYY-MM-DD' to pre-fill (e.g. from a linked CR)
     *   minDate                    'YYYY-MM-DD' or 'today' (default 'today')
     *
     * Returns Promise resolving to { remark, effective_date: 'YYYY-MM-DD' } on
     * confirm, or null on cancel/dismiss. effective_date is always YYYY-MM-DD
     * regardless of how flatpickr displays it (dd-mm-YYYY by default in this app).
     */
    window.erpApproveModal = function (opts) {
        opts = opts || {};
        var minRemark = opts.minRemarkLength != null ? opts.minRemarkLength : 3;
        var maxRemark = opts.maxRemarkLength != null ? opts.maxRemarkLength : 1000;
        var minDate = opts.minDate || 'today';
        var btnClass = 'bg-emerald-600 hover:bg-emerald-700 text-white';

        var stamp = Date.now();
        var remarkId = 'erp-approve-remark-' + stamp;
        var dateId = 'erp-approve-date-' + stamp;
        var remarkErrId = 'erp-approve-remark-err-' + stamp;
        var dateErrId = 'erp-approve-date-err-' + stamp;
        var okClass = 'erp-approve-ok-' + stamp;
        var cancelClass = 'erp-approve-cancel-' + stamp;

        return new Promise(function (resolve) {
            var resolved = false;
            // Track YYYY-MM-DD form value separately from the display string —
            // flatpickr renders dd-mm-YYYY but we always emit ISO format to the
            // server (matches Laravel's date validation default).
            var isoDate = opts.defaultEffectiveDate || '';

            var modal = erpModal({
                title: opts.title || 'Approve',
                body:
                    (opts.message ? '<p class="text-sm text-zinc-600 mb-3">' + opts.message + '</p>' : '') +
                    '<div class="space-y-3">' +
                    '  <div>' +
                    '    <label for="' + remarkId + '" class="block text-sm font-medium text-zinc-700 mb-1">Reason / signature meaning <span class="text-red-500">*</span></label>' +
                    '    <textarea id="' + remarkId + '" rows="3" maxlength="' + maxRemark + '" placeholder="Why is this being approved? Recorded in the audit trail."' +
                    '      class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-zinc-400 focus:ring-0"></textarea>' +
                    '    <p id="' + remarkErrId + '" class="hidden mt-1 text-xs text-red-600"></p>' +
                    '  </div>' +
                    '  <div>' +
                    '    <label for="' + dateId + '" class="block text-sm font-medium text-zinc-700 mb-1">Effective date <span class="text-red-500">*</span></label>' +
                    '    <input id="' + dateId + '" type="text" placeholder="dd-mm-yyyy" autocomplete="off"' +
                    '      class="w-full rounded-md border border-zinc-200 bg-white px-3 py-2 text-sm focus:border-zinc-400 focus:ring-0">' +
                    '    <p class="mt-1 text-xs text-zinc-500">Today = effective immediately. A future date marks this approval as pending; the new version takes effect when the date arrives.</p>' +
                    '    <p id="' + dateErrId + '" class="hidden mt-1 text-xs text-red-600"></p>' +
                    '  </div>' +
                    '</div>',
                size: 'sm',
                footer:
                    '<button class="' + cancelClass + ' px-4 py-2 text-sm font-medium rounded-md border border-zinc-200 bg-white text-zinc-700 hover:bg-zinc-50">' + (opts.cancelText || 'Cancel') + '</button>' +
                    '<button class="' + okClass + ' px-4 py-2 text-sm font-medium rounded-md ' + btnClass + ' disabled:opacity-50 disabled:cursor-not-allowed" disabled>' + (opts.confirmText || 'Approve') + '</button>',
                onClose: function () { if (!resolved) { resolved = true; resolve(null); } },
            });

            var $remark = modal.el.querySelector('#' + remarkId);
            var $date = modal.el.querySelector('#' + dateId);
            var $remarkErr = modal.el.querySelector('#' + remarkErrId);
            var $dateErr = modal.el.querySelector('#' + dateErrId);
            var $ok = modal.el.querySelector('.' + okClass);

            // Initialize flatpickr on the date input. Display dd-mm-Y (project
            // convention); altInput surfaces the human format while the hidden
            // backing input captures Y-m-d for server submission.
            if (typeof flatpickr === 'function') {
                flatpickr($date, {
                    dateFormat: 'Y-m-d',
                    altInput: true,
                    altFormat: 'd-m-Y',
                    minDate: minDate,
                    defaultDate: opts.defaultEffectiveDate || null,
                    onChange: function (selectedDates, dateStr) {
                        isoDate = dateStr || '';
                        validate();
                    },
                });
            } else {
                // Fallback to native HTML5 date input if flatpickr isn't loaded.
                $date.type = 'date';
                if (opts.defaultEffectiveDate) $date.value = opts.defaultEffectiveDate;
                if (minDate === 'today') {
                    var today = new Date().toISOString().slice(0, 10);
                    $date.setAttribute('min', today);
                } else if (minDate) {
                    $date.setAttribute('min', minDate);
                }
                $date.addEventListener('change', function () {
                    isoDate = $date.value || '';
                    validate();
                });
            }

            function validate() {
                var remarkVal = ($remark.value || '').trim();
                var remarkOk = remarkVal.length >= minRemark;
                var dateOk = !!isoDate;

                $remarkErr.classList.toggle('hidden', remarkOk);
                $remark.classList.toggle('border-red-500', !remarkOk && remarkVal.length > 0);
                if (!remarkOk) {
                    $remarkErr.textContent = 'A reason of at least ' + minRemark + ' character' + (minRemark === 1 ? '' : 's') + ' is required.';
                }

                $dateErr.classList.toggle('hidden', dateOk);
                if (!dateOk) {
                    $dateErr.textContent = 'An effective date is required. Pick today for immediate effect, or a future date.';
                }

                $ok.disabled = !(remarkOk && dateOk);
                return remarkOk && dateOk;
            }

            $remark.addEventListener('input', validate);
            // Validate once on open in case defaultEffectiveDate was supplied.
            validate();
            setTimeout(function () { $remark.focus(); }, 30);

            modal.el.querySelector('.' + cancelClass).addEventListener('click', function () {
                resolved = true;
                modal.close();
                resolve(null);
            });

            $ok.addEventListener('click', function () {
                if (!validate()) return;
                resolved = true;
                modal.close();
                resolve({
                    remark: ($remark.value || '').trim(),
                    effective_date: isoDate,
                });
            });
        });
    };

    /* ── Tabs ───────────────────────────────────────────────────── */
    /**
     * Auto-init tabs: add data-tabs-group to container, data-tab-target on triggers, data-tab-panel on panels
     */
    window.initErpTabs = function (container) {
        container = container || document;
        container.querySelectorAll('[data-tabs-group]').forEach(function (group) {
            var triggers = group.querySelectorAll('[data-tab-target]');
            var panels = group.querySelectorAll('[data-tab-panel]');

            triggers.forEach(function (trigger) {
                trigger.addEventListener('click', function (e) {
                    e.preventDefault();
                    var target = this.getAttribute('data-tab-target');

                    triggers.forEach(function (t) {
                        t.classList.remove('border-zinc-900', 'text-zinc-900');
                        t.classList.add('border-transparent', 'text-zinc-500');
                    });
                    this.classList.add('border-zinc-900', 'text-zinc-900');
                    this.classList.remove('border-transparent', 'text-zinc-500');

                    panels.forEach(function (p) {
                        p.style.display = (p.getAttribute('data-tab-panel') !== target) ? 'none' : '';
                    });
                });
            });
        });
    };

    /* ── Accordion ──────────────────────────────────────────────── */
    window.initErpAccordion = function (container) {
        container = container || document;
        container.querySelectorAll('[data-accordion]').forEach(function (acc) {
            acc.querySelectorAll('[data-accordion-trigger]').forEach(function (trigger) {
                trigger.addEventListener('click', function () {
                    var content = this.nextElementSibling;
                    var icon = this.querySelector('.accordion-icon');
                    if (content) {
                        var isHidden = content.classList.contains('hidden') || content.style.display === 'none';
                        content.classList.remove('hidden');
                        content.style.display = isHidden ? '' : 'none';
                        if (icon) icon.classList.toggle('rotate-180', isHidden);
                    }
                });
            });
        });
    };

    /* ── Wizard ─────────────────────────────────────────────────── */
    /**
     * erpWizard(containerId) — manages multi-step wizard
     */
    window.erpWizard = function (containerId) {
        var container = document.getElementById(containerId);
        if (!container) return;

        var steps = container.querySelectorAll('[data-wizard-step]');
        var panels = container.querySelectorAll('[data-wizard-panel]');
        var currentStep = 0;

        function goTo(index) {
            if (index < 0 || index >= steps.length) return;
            currentStep = index;

            steps.forEach(function (step, i) {
                var circle = step.querySelector('.wizard-circle');
                var label = step.querySelector('.wizard-label');
                var line = step.querySelector('.wizard-line');

                if (i < currentStep) {
                    // Completed
                    if (circle) { circle.className = 'wizard-circle h-8 w-8 rounded-full bg-zinc-900 text-white flex items-center justify-center text-sm font-medium'; circle.innerHTML = '<i class="fa-solid fa-check text-xs"></i>'; }
                    if (label) label.className = 'wizard-label text-xs font-medium text-zinc-900 mt-1';
                    if (line) line.className = 'wizard-line flex-1 h-0.5 bg-zinc-900 mx-2';
                    step.classList.add('completed');
                } else if (i === currentStep) {
                    // Current
                    if (circle) { circle.className = 'wizard-circle h-8 w-8 rounded-full bg-zinc-900 text-white flex items-center justify-center text-sm font-medium'; circle.textContent = '' + (i + 1); }
                    if (label) label.className = 'wizard-label text-xs font-medium text-zinc-900 mt-1';
                    if (line) line.className = 'wizard-line flex-1 h-0.5 bg-zinc-200 mx-2';
                    step.classList.remove('completed');
                } else {
                    // Future
                    if (circle) { circle.className = 'wizard-circle h-8 w-8 rounded-full bg-zinc-100 text-zinc-400 flex items-center justify-center text-sm font-medium border border-zinc-200'; circle.textContent = '' + (i + 1); }
                    if (label) label.className = 'wizard-label text-xs font-medium text-zinc-400 mt-1';
                    if (line) line.className = 'wizard-line flex-1 h-0.5 bg-zinc-200 mx-2';
                    step.classList.remove('completed');
                }
            });

            panels.forEach(function (panel, i) {
                panel.style.display = (i !== currentStep) ? 'none' : '';
            });
        }

        // Bind next/prev buttons
        container.querySelectorAll('[data-wizard-next]').forEach(function (btn) {
            btn.addEventListener('click', function () { goTo(currentStep + 1); });
        });
        container.querySelectorAll('[data-wizard-prev]').forEach(function (btn) {
            btn.addEventListener('click', function () { goTo(currentStep - 1); });
        });

        goTo(0);
        return { goTo: goTo, getCurrent: function () { return currentStep; } };
    };

    /* ── Command Palette (Ctrl+K) ───────────────────────────────── */
    // Source: live sidebar/horizontal-nav DOM. Whatever links the server
    // already rendered (after permission/module/route checks) become the
    // searchable index — no separate JS nav array to maintain.
    function _collectNavItems() {
        var items = [];
        var seen = {};

        function pushLink(link, groupName, groupIcon) {
            if (!link || !link.getAttribute) return;
            var href = link.getAttribute('href');
            if (!href || href === '#' || href.indexOf('javascript:') === 0) return;
            if (seen[href]) return;
            var nameEl = link.querySelector('.erp-sidebar-label, span');
            var name = (nameEl ? nameEl.textContent : link.textContent || '').trim();
            if (!name) return;
            var iconEl = link.querySelector('i.fa-solid, i.fa-regular, i.fa-brands, i.fas, i.far, i.fab, i[class*="fa-"]');
            var icon = '';
            if (iconEl) {
                var cls = iconEl.getAttribute('class') || '';
                var m = cls.match(/fa-[a-z0-9-]+/g);
                if (m) icon = m.filter(function (c) {
                    return c !== 'fa-solid' && c !== 'fa-regular' && c !== 'fa-brands';
                }).join(' ');
            }
            seen[href] = true;
            items.push({ name: name, href: href, group: groupName || '', icon: icon || 'fa-circle' });
        }

        // Vertical sidebar
        var sidebar = document.getElementById('erp-sidebar-nav');
        if (sidebar) {
            sidebar.querySelectorAll('.erp-nav-group').forEach(function (group) {
                var groupBtn = group.querySelector('.erp-sidebar-group');
                var groupLabel = groupBtn ? (groupBtn.querySelector('.erp-sidebar-label') || groupBtn).textContent.trim() : '';
                var groupIcon = groupBtn ? (groupBtn.querySelector('i[class*="fa-"]') || {}).className : '';
                group.querySelectorAll('a[href]').forEach(function (a) { pushLink(a, groupLabel, groupIcon); });
            });
            // Standalone top-level links (outside .erp-nav-group)
            sidebar.querySelectorAll('nav > div > a.erp-sidebar-link[href]').forEach(function (a) {
                pushLink(a, '', '');
            });
        }

        // Horizontal nav (top scroll + mega dropdowns)
        var hnav = document.getElementById('hnav-scroll');
        if (hnav) {
            hnav.querySelectorAll('a[href]').forEach(function (a) { pushLink(a, '', ''); });
        }
        document.querySelectorAll('.erp-hnav-dropdown').forEach(function (panel) {
            var menuId = panel.getAttribute('data-hnav-panel');
            var btn = menuId ? document.querySelector('[data-hnav-menu="' + menuId + '"]') : null;
            var groupLabel = btn ? (btn.querySelector('span') || btn).textContent.trim() : '';
            panel.querySelectorAll('a[href]').forEach(function (a) { pushLink(a, groupLabel, ''); });
        });

        return items;
    }

    window.erpCommandPalette = function () {
        var existing = document.getElementById('erp-command-palette');
        if (existing) { existing.remove(); return; }

        var allModules = _collectNavItems();

        var el = document.createElement('div');
        el.id = 'erp-command-palette';
        el.className = 'erp-command-palette';
        el.innerHTML =
            '<div class="bg-white rounded-lg shadow-2xl border border-zinc-200 w-full max-w-lg mx-4 overflow-hidden" onclick="event.stopPropagation()">' +
            '<div class="flex items-center gap-2 px-4 py-3 border-b border-zinc-200">' +
            '<i class="fa-solid fa-magnifying-glass text-zinc-400 text-sm"></i>' +
            '<input id="command-palette-input" class="erp-command-input flex-1 text-sm text-zinc-900 placeholder-zinc-400 bg-transparent" placeholder="Search modules, pages, masters..." autofocus>' +
            '<kbd class="text-2xs px-1.5 py-0.5 rounded border border-zinc-200 bg-zinc-50 text-zinc-400 font-mono">ESC</kbd>' +
            '</div>' +
            '<div id="command-palette-results" class="overflow-y-auto p-2" style="max-height: 20rem;"></div>' +
            '<div class="px-4 py-2 border-t border-zinc-100 flex items-center justify-between text-2xs text-zinc-400">' +
                '<span><kbd class="text-2xs px-1.5 py-0.5 rounded border border-zinc-200 bg-zinc-50 font-mono">↑↓</kbd> navigate · <kbd class="text-2xs px-1.5 py-0.5 rounded border border-zinc-200 bg-zinc-50 font-mono">↵</kbd> open</span>' +
                '<span id="command-palette-count"></span>' +
            '</div>' +
            '</div>';

        document.body.appendChild(el);

        var input = document.getElementById('command-palette-input');
        var results = document.getElementById('command-palette-results');
        var countEl = document.getElementById('command-palette-count');
        var filtered = allModules.slice();
        var active = 0;

        function navigateTo(mod) {
            el.remove();
            document.removeEventListener('keydown', keyHandler);
            if (mod && mod.href) window.location.href = mod.href;
        }

        function highlightActive() {
            var btns = results.querySelectorAll('.cmd-result');
            btns.forEach(function (b) {
                var idx = parseInt(b.getAttribute('data-cmd-idx'), 10);
                b.classList.toggle('bg-zinc-100', idx === active);
            });
            var activeBtn = results.querySelector('[data-cmd-idx="' + active + '"]');
            if (activeBtn) activeBtn.scrollIntoView({ block: 'nearest' });
        }

        function renderResults(query) {
            if (query) {
                var q = query.toLowerCase();
                filtered = allModules.filter(function (m) {
                    return m.name.toLowerCase().indexOf(q) >= 0 ||
                        (m.group || '').toLowerCase().indexOf(q) >= 0;
                });
            } else {
                filtered = allModules.slice();
            }
            active = 0;

            if (!filtered.length) {
                results.innerHTML = '<p class="text-sm text-zinc-400 text-center py-6">No modules found.</p>';
                countEl.textContent = '0 results';
                return;
            }
            countEl.textContent = filtered.length + ' result' + (filtered.length === 1 ? '' : 's');

            var html = '';
            var lastGroup = null;
            filtered.forEach(function (mod, i) {
                var grp = mod.group || 'General';
                if (grp !== lastGroup) {
                    html += '<p class="px-3 pt-2 pb-1 text-2xs font-semibold uppercase tracking-wider text-zinc-400">' + grp + '</p>';
                    lastGroup = grp;
                }
                html += '<button data-cmd-idx="' + i + '" class="cmd-result w-full flex items-center gap-3 px-3 py-2 text-sm rounded-md hover:bg-zinc-100 text-left">' +
                    '<i class="fa-solid ' + mod.icon + ' text-zinc-400 w-4"></i>' +
                    '<div class="flex-1 min-w-0">' +
                        '<p class="text-zinc-900 font-medium truncate">' + mod.name + '</p>' +
                        '<p class="text-xs text-zinc-400 truncate">' + grp + '</p>' +
                    '</div>' +
                    '<i class="fa-solid fa-arrow-turn-down rotate-90 text-zinc-300 text-2xs"></i>' +
                '</button>';
            });
            results.innerHTML = html;

            results.querySelectorAll('.cmd-result').forEach(function (btn) {
                btn.addEventListener('mouseenter', function () {
                    active = parseInt(btn.getAttribute('data-cmd-idx'), 10);
                    highlightActive();
                });
                btn.addEventListener('click', function () {
                    var idx = parseInt(btn.getAttribute('data-cmd-idx'), 10);
                    navigateTo(filtered[idx]);
                });
            });
            highlightActive();
        }

        function move(delta) {
            if (!filtered.length) return;
            active = (active + delta + filtered.length) % filtered.length;
            highlightActive();
        }

        function keyHandler(e) {
            if (!document.getElementById('erp-command-palette')) {
                document.removeEventListener('keydown', keyHandler);
                return;
            }
            if (e.key === 'Escape') { el.remove(); document.removeEventListener('keydown', keyHandler); }
            else if (e.key === 'ArrowDown') { e.preventDefault(); move(+1); }
            else if (e.key === 'ArrowUp') { e.preventDefault(); move(-1); }
            else if (e.key === 'Enter') { e.preventDefault(); navigateTo(filtered[active]); }
        }

        renderResults('');
        input.addEventListener('input', function () { renderResults(this.value); });
        el.addEventListener('click', function (e) { if (e.target === el) el.remove(); });
        document.addEventListener('keydown', keyHandler);
        input.focus();
    };

    // Global Ctrl+K shortcut
    document.addEventListener('keydown', function (e) {
        if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
            e.preventDefault();
            erpCommandPalette();
        }
    });

    /* ── SweetAlert-style Alert ─────────────────────────────────── */
    /**
     * erpAlert({ type, title, message, confirmText, cancelText, showCancel })
     * type: 'success' | 'error' | 'warning' | 'info'
     * Returns Promise<boolean>
     */
    window.erpAlert = function (opts) {
        opts = opts || {};
        var type = opts.type || 'info';
        var icons = {
            success: { icon: 'fa-check', size: 'text-3xl' },
            error: { icon: 'fa-xmark', size: 'text-3xl' },
            warning: { icon: 'fa-exclamation', size: 'text-3xl' },
            info: { icon: 'fa-info', size: 'text-3xl' },
        };
        var ic = icons[type] || icons.info;

        return new Promise(function (resolve) {
            var backdrop = document.createElement('div');
            backdrop.className = 'erp-modal-backdrop';
            backdrop.innerHTML =
                '<div class="erp-modal-content bg-white rounded-lg shadow-xl border border-zinc-200 ring-1 ring-zinc-900/5 w-full max-w-sm mx-4 text-center">' +
                '<div class="p-6 pb-2">' +
                '<div class="erp-sweetalert-icon ' + type + '"><i class="fa-solid ' + ic.icon + ' ' + ic.size + '"></i></div>' +
                '<h3 class="text-lg font-semibold text-zinc-900 mt-2">' + (opts.title || '') + '</h3>' +
                (opts.message ? '<p class="text-sm text-zinc-500 mt-2">' + opts.message + '</p>' : '') +
                '</div>' +
                '<div class="flex items-center justify-center gap-2 p-4">' +
                (opts.showCancel ? '<button class="erp-alert-cancel h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50">' + (opts.cancelText || 'Cancel') + '</button>' : '') +
                '<button class="erp-alert-ok h-9 px-6 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800">' + (opts.confirmText || 'OK') + '</button>' +
                '</div>' +
                '</div>';

            document.body.appendChild(backdrop);

            function close(val) { backdrop.remove(); resolve(val); }

            backdrop.querySelector('.erp-alert-ok').addEventListener('click', function () { close(true); });
            var cancelBtn = backdrop.querySelector('.erp-alert-cancel');
            if (cancelBtn) cancelBtn.addEventListener('click', function () { close(false); });
            backdrop.addEventListener('click', function (e) { if (e.target === backdrop) close(false); });
        });
    };

    /* ── Searchable Select (Select2-style) ─────────────────────── */
    /**
     * erpSearchSelect(selector, { options, placeholder, multiple, onChange, onSearch, freshPrefetch })
     * options: [{ value, label, group? }] or ['label1','label2']
     * onSearch: function(term, callback) — for AJAX; callback receives [{ value, label, data? }]
     * freshPrefetch: { url, limit?, sort?, extraData? } — on first dropdown open in the page session,
     *   fires GET <url>?limit=<limit>&sort=<sort>&<extraData> to populate options. Subsequent opens
     *   reuse the loaded set. A refresh button (↻) in the dropdown header forces a re-fetch. When the
     *   user types a term ≥ 2 chars and zero client-side matches exist, a fallback AJAX fires
     *   <url>?q=<term> for long-tail records outside the prefetched window.
     */
    window.erpSearchSelect = function (selector, opts) {
        opts = opts || {};
        var el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!el) return;

        var multiple = opts.multiple || false;
        var placeholder = opts.placeholder || 'Select...';
        var items = (opts.options || []).map(function (o) {
            if (typeof o === 'string') return { value: o, label: o };
            return o;
        });
        var selected = multiple ? [] : '';
        // freshPrefetch: explicit opts wins, then fall back to the <select>'s
        // data-fresh-prefetch attribute. This way cascade-engine callers,
        // initSearchSelect, and direct erpSearchSelect users all honor the
        // declarative attribute uniformly without each one wiring it manually.
        var freshPrefetch = opts.freshPrefetch || null;
        if (!freshPrefetch && el && el.getAttribute && el.getAttribute('data-fresh-prefetch')) {
            freshPrefetch = {
                url: el.getAttribute('data-fresh-prefetch'),
                limit: parseInt(el.getAttribute('data-prefetch-limit'), 10) || 300,
                sort: el.getAttribute('data-prefetch-sort') || '-updated_at',
                extraData: (function (qs) {
                    if (!qs) return {};
                    var out = {};
                    qs.split('&').forEach(function (pair) {
                        var i2 = pair.indexOf('=');
                        if (i2 === -1) return;
                        var k = decodeURIComponent(pair.slice(0, i2));
                        var v = decodeURIComponent(pair.slice(i2 + 1));
                        if (k) out[k] = v;
                    });
                    return out;
                })(el.getAttribute('data-prefetch-extra'))
            };
        }
        var fallbackAjaxTimeout = null;

        // Hide original
        el.style.display = 'none';

        // Clean up any previous instance for this element
        if (el._erpSelectDropdown) {
            try { document.body.removeChild(el._erpSelectDropdown); } catch (e) { }
        }
        if (el._erpSelectWrapper) {
            try { el._erpSelectWrapper.parentNode.removeChild(el._erpSelectWrapper); } catch (e) { }
        }

        // Build wrapper
        var wrapper = document.createElement('div');
        wrapper.className = 'erp-select-wrapper';
        el.parentNode.insertBefore(wrapper, el.nextSibling);
        el._erpSelectWrapper = wrapper;

        // Trigger
        var allowClear = opts.allowClear || false;
        var trigger = document.createElement('div');
        trigger.className = 'flex items-center h-9 w-full rounded-md border border-zinc-200 bg-transparent px-3 text-sm cursor-pointer hover:border-zinc-300';
        trigger.innerHTML = '<span class="erp-select-display flex-1 truncate text-zinc-500">' + placeholder + '</span>' +
            (allowClear ? '<button type="button" class="erp-select-clear" style="display:none;"><i class="fa-solid fa-xmark"></i></button>' : '') +
            '<i class="fa-solid fa-chevron-down text-xs text-zinc-400 ml-2"></i>';
        wrapper.appendChild(trigger);

        // Clear (×) button — single-select only; clears the value on click.
        var clearBtn = trigger.querySelector('.erp-select-clear');
        if (clearBtn) {
            clearBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                e.preventDefault();
                selected = multiple ? [] : '';
                if (el) el.value = '';
                updateDisplay(null, false); // fire onChange so dependents react
                renderOptions('');
            });
        }

        // Dropdown — appended to body with position:fixed to avoid overflow clipping
        var dropdown = document.createElement('div');
        dropdown.className = 'erp-select-dropdown';
        dropdown.style.display = 'none';
        dropdown.style.position = 'fixed';
        dropdown.style.zIndex = '9999';
        dropdown.innerHTML =
            '<div class="erp-select-search">' +
                '<input type="text" placeholder="Search...">' +
                ((freshPrefetch || opts.onRefresh)
                    ? '<button type="button" class="erp-select-refresh" title="Sync from server"><i class="fa-solid fa-arrows-rotate"></i></button>'
                    : '') +
            '</div>' +
            '<div class="erp-select-options"></div>';
        document.body.appendChild(dropdown);
        el._erpSelectDropdown = dropdown;

        var searchInput = dropdown.querySelector('input');
        var optionsDiv = dropdown.querySelector('.erp-select-options');
        var refreshBtn = dropdown.querySelector('.erp-select-refresh');

        function renderOptions(filter) {
            var q = (filter || '').toLowerCase();
            var html = '';
            items.forEach(function (item) {
                if (q && item.label.toLowerCase().indexOf(q) === -1) return;
                var isSel = multiple ? selected.indexOf(item.value) > -1 : selected === item.value;
                html += '<div class="erp-select-option' + (isSel ? ' selected' : '') + '" data-value="' + item.value + '">';
                if (multiple) html += '<i class="fa-' + (isSel ? 'solid' : 'regular') + ' fa-square' + (isSel ? '-check text-zinc-900' : ' text-zinc-300') + ' text-sm"></i>';
                html += '<span>' + item.label + '</span>';
                html += '</div>';
            });
            if (!html) html = '<div class="px-3 py-4 text-xs text-zinc-400 text-center">No results</div>';
            optionsDiv.innerHTML = html;
        }

        function findLabel(val) {
            for (var i = 0; i < items.length; i++) {
                if (String(items[i].value) === String(val)) return items[i].label;
            }
            // Fallback: check native <select> element's options
            if (el && el.options) {
                for (var j = 0; j < el.options.length; j++) {
                    if (String(el.options[j].value) === String(val)) return el.options[j].textContent.trim();
                }
            }
            return val; // last resort fallback
        }

        function updateDisplay(selectedItem, silent) {
            var display = trigger.querySelector('.erp-select-display');
            if (multiple) {
                if (selected.length === 0) {
                    display.innerHTML = '<span class="text-zinc-500">' + placeholder + '</span>';
                } else {
                    display.innerHTML = selected.map(function (v) {
                        var label = findLabel(v);
                        return '<span class="erp-select-tag">' + label + ' <button data-remove="' + v + '" class="text-zinc-400 hover:text-zinc-700 ml-1"><i class="fa-solid fa-xmark text-[10px]"></i></button></span>';
                    }).join(' ');
                    display.className = 'erp-select-display erp-select-tags flex-1 flex flex-wrap gap-1';
                }
            } else {
                if (!selected) {
                    display.innerHTML = '<span class="text-zinc-500">' + placeholder + '</span>';
                } else {
                    display.innerHTML = '<span class="text-zinc-900">' + findLabel(selected) + '</span>';
                }
            }
            if (clearBtn) {
                var hasVal = multiple ? selected.length > 0 : !!selected;
                clearBtn.style.display = hasVal ? '' : 'none';
            }
            if (!silent && opts.onChange) opts.onChange(selected, selectedItem);
        }

        function positionDropdown() {
            var rect = trigger.getBoundingClientRect();
            var dropH = dropdown.offsetHeight || 260;
            var spaceBelow = window.innerHeight - rect.bottom;
            var openAbove = spaceBelow < dropH && rect.top > spaceBelow;
            dropdown.style.left = rect.left + 'px';
            dropdown.style.width = rect.width + 'px';
            if (openAbove) {
                dropdown.style.top = '';
                dropdown.style.bottom = (window.innerHeight - rect.top) + 'px';
            } else {
                dropdown.style.top = rect.bottom + 'px';
                dropdown.style.bottom = '';
            }
        }

        // ── Fresh-prefetch lifecycle ───────────────────────────────────────
        // Per-page-session: on the FIRST open we hit the lookup endpoint with
        // limit/sort/extraData and stash the result on the native <select>
        // (el.dataset.freshPrefetched). Subsequent opens reuse the loaded
        // set — no re-fetch — until the user clicks the refresh icon.
        //
        // Edit-page invariant: any <option> server-rendered into the native
        // <select> (typically the currently-selected value) is preserved on
        // top of the prefetched set — even if the selection is older than
        // the 300-row recency window — so the user always sees their current
        // pick in the dropdown list, not just in the trigger.
        function collectPreservedFromNative(skipValues) {
            var out = [];
            if (!el || !el.options) return out;
            for (var k = 0; k < el.options.length; k++) {
                var opt = el.options[k];
                if (!opt.value) continue;                  // skip placeholder
                if (skipValues[String(opt.value)]) continue; // already in prefetch
                out.push({ value: String(opt.value), label: opt.textContent.trim() });
            }
            return out;
        }

        function performFreshPrefetch(force) {
            if (!freshPrefetch || !freshPrefetch.url) return;
            if (!force && el.dataset.freshPrefetched === '1') {
                renderOptions('');
                return;
            }
            optionsDiv.innerHTML = '<div class="px-3 py-4 text-xs text-zinc-400 text-center"><i class="fa-solid fa-spinner fa-spin mr-1.5"></i>Loading…</div>';
            var data = $.extend({
                limit: freshPrefetch.limit || 300,
                sort: freshPrefetch.sort || '-updated_at'
            }, freshPrefetch.extraData || {});
            $.ajax({
                url: freshPrefetch.url, data: data, dataType: 'json',
                success: function (rows) {
                    rows = rows || [];
                    var prefetched = rows.map(function (r) {
                        if (typeof r === 'string') return { value: r, label: r };
                        // Preserve any extra fields (order_no, job_name, client_name, …) so
                        // onChange consumers can still read them after a fresh-prefetch.
                        return Object.assign({}, r, { value: String(r.value), label: r.label });
                    });
                    var prefetchedKeys = {};
                    for (var p = 0; p < prefetched.length; p++) prefetchedKeys[prefetched[p].value] = true;
                    var preserved = collectPreservedFromNative(prefetchedKeys);
                    items = preserved.concat(prefetched);
                    el.dataset.freshPrefetched = '1';
                    renderOptions('');
                    // Refresh the trigger display so any value previously
                    // set via setValue (which fell back to showing the raw
                    // number because items was empty) now shows the label.
                    updateDisplay(null, true);
                },
                error: function () {
                    optionsDiv.innerHTML = '<div class="px-3 py-4 text-xs text-rose-500 text-center">Failed to load — click <i class="fa-solid fa-arrows-rotate mx-1"></i> to retry</div>';
                }
            });
        }

        if (refreshBtn) {
            refreshBtn.addEventListener('click', function (e) {
                e.stopPropagation();
                if (freshPrefetch) { performFreshPrefetch(true); return; }
                // onRefresh: caller re-fetches and hands back fresh options via the
                // callback. The current selection is preserved if still present.
                if (opts.onRefresh) {
                    var icon = refreshBtn.querySelector('i');
                    if (icon) icon.classList.add('fa-spin');
                    api.setLoading(true); // loading circle on the select trigger
                    optionsDiv.innerHTML = '<div class="px-3 py-4 text-xs text-zinc-400 text-center"><i class="fa-solid fa-spinner fa-spin mr-1.5"></i>Syncing…</div>';
                    opts.onRefresh(function (newOptions) {
                        api.setOptions(newOptions || [], true); // keepValue: preserve selection
                        // setOptions clears native <option>.selected for multiselects —
                        // re-assert them so the value still round-trips on submit.
                        if (multiple && el && el.multiple) {
                            selected.forEach(function (v) {
                                for (var i = 0; i < el.options.length; i++) {
                                    if (String(el.options[i].value) === String(v)) el.options[i].selected = true;
                                }
                            });
                        }
                        if (icon) icon.classList.remove('fa-spin');
                        api.setLoading(false);
                        renderOptions(searchInput.value);
                    });
                }
            });
        }

        function openDropdown() {
            // Close all other open selects first
            document.querySelectorAll('.erp-select-dropdown').forEach(function (d) {
                if (d !== dropdown) d.style.display = 'none';
            });
            dropdown.style.display = '';
            positionDropdown();
            searchInput.value = '';
            if (freshPrefetch) {
                performFreshPrefetch(false);
            } else if (opts.onSearch) {
                optionsDiv.innerHTML = '<div class="px-3 py-4 text-xs text-zinc-400 text-center">Type to search…</div>';
            } else {
                renderOptions('');
            }
            searchInput.focus();
        }

        function closeDropdown() {
            dropdown.style.display = 'none';
        }

        // Toggle
        trigger.addEventListener('click', function (e) {
            if (e.target.closest('[data-remove]')) {
                var val = e.target.closest('[data-remove]').getAttribute('data-remove');
                selected = selected.filter(function (v) { return v !== val; });
                updateDisplay(); renderOptions(searchInput.value); return;
            }
            if (dropdown.style.display === 'none') { openDropdown(); }
            else { closeDropdown(); }
        });

        // Search
        var searchTimeout = null;
        searchInput.addEventListener('input', function () {
            var term = this.value;
            if (freshPrefetch) {
                // Hybrid mode: client-side filter the prefetched 300 first.
                renderOptions(term);
                // Long-tail fallback: when zero client matches AND the term is
                // long enough to be a real query, fire <url>?q=<term> against
                // the full table. New rows are appended to `items` so further
                // typing in this session searches the augmented set instantly.
                if (term.length >= 2) {
                    var q = term.toLowerCase();
                    var clientMatches = 0;
                    for (var ii = 0; ii < items.length; ii++) {
                        if (items[ii].label.toLowerCase().indexOf(q) > -1) { clientMatches++; break; }
                    }
                    if (clientMatches === 0) {
                        if (fallbackAjaxTimeout) clearTimeout(fallbackAjaxTimeout);
                        fallbackAjaxTimeout = setTimeout(function () {
                            optionsDiv.innerHTML = '<div class="px-3 py-4 text-xs text-zinc-400 text-center"><i class="fa-solid fa-spinner fa-spin mr-1.5"></i>Searching all records…</div>';
                            var data = $.extend({ q: term, limit: freshPrefetch.limit || 300 }, freshPrefetch.extraData || {});
                            $.ajax({
                                url: freshPrefetch.url, data: data, dataType: 'json',
                                success: function (rows) {
                                    rows = rows || [];
                                    var seen = {};
                                    for (var i = 0; i < items.length; i++) seen[String(items[i].value)] = true;
                                    rows.forEach(function (r) {
                                        var v = String(r.value);
                                        if (!seen[v]) items.push({ value: v, label: r.label });
                                    });
                                    renderOptions(term);
                                },
                                error: function () {
                                    optionsDiv.innerHTML = '<div class="px-3 py-4 text-xs text-rose-500 text-center">Search failed — try the refresh icon</div>';
                                }
                            });
                        }, 250);
                    }
                }
            } else if (opts.onSearch) {
                if (searchTimeout) clearTimeout(searchTimeout);
                optionsDiv.innerHTML = '<div class="px-3 py-4 text-xs text-zinc-400 text-center">Searching…</div>';
                searchTimeout = setTimeout(function () {
                    opts.onSearch(term, function (newOptions) {
                        items = (newOptions || []).map(function (o) {
                            if (typeof o === 'string') return { value: o, label: o };
                            return o;
                        });
                        renderOptions('');
                    });
                }, 250);
            } else {
                renderOptions(term);
            }
        });

        // Select
        optionsDiv.addEventListener('click', function (e) {
            var opt = e.target.closest('.erp-select-option');
            if (!opt) return;
            var val = opt.getAttribute('data-value');
            var selectedItem = null;
            for (var i = 0; i < items.length; i++) {
                if (String(items[i].value) === String(val)) { selectedItem = items[i]; break; }
            }
            if (multiple) {
                var idx = selected.indexOf(val);
                if (idx > -1) selected.splice(idx, 1); else selected.push(val);
                renderOptions(searchInput.value);
            } else {
                selected = val;
                closeDropdown();
            }
            // Sync the picked value back to the native <select> so form submission
            // sends the right id. For AJAX-driven dropdowns the chosen option may
            // not exist in the native <select> yet — inject it on the fly. Without
            // this, server-side validation pre-checks against pre-rendered options
            // and either silently falls back to the placeholder OR rejects with a
            // wrong-id error after the user searched.
            if (el && el.tagName === 'SELECT' && !multiple) {
                var hasOpt = false;
                for (var k = 0; k < el.options.length; k++) {
                    if (String(el.options[k].value) === String(val)) { hasOpt = true; break; }
                }
                if (!hasOpt && selectedItem) {
                    el.add(new Option(selectedItem.label, selectedItem.value));
                }
                el.value = val || '';
                el.dispatchEvent(new Event('change', { bubbles: true }));
            } else if (el && el.tagName === 'SELECT' && multiple) {
                // Multi-select: walk the native options, toggling selection state
                // to mirror the JS-side `selected[]` array. Inject any missing
                // AJAX-injected options first so all chosen ids round-trip.
                selected.forEach(function (v) {
                    var present = false;
                    for (var m = 0; m < el.options.length; m++) {
                        if (String(el.options[m].value) === String(v)) { present = true; break; }
                    }
                    if (!present) {
                        var lbl = findLabel(v);
                        el.add(new Option(lbl, v));
                    }
                });
                for (var n = 0; n < el.options.length; n++) {
                    el.options[n].selected = selected.indexOf(el.options[n].value) > -1;
                }
                el.dispatchEvent(new Event('change', { bubbles: true }));
            }
            updateDisplay(selectedItem);
        });

        // Close on outside click (dropdown is in body, not wrapper)
        document.addEventListener('click', function (e) {
            if (!wrapper.contains(e.target) && !dropdown.contains(e.target)) closeDropdown();
        });

        // Reposition on scroll/resize (dropdown is fixed)
        var reposTimer;
        function onRepos() { clearTimeout(reposTimer); reposTimer = setTimeout(function () { if (dropdown.style.display !== 'none') positionDropdown(); }, 10); }
        window.addEventListener('scroll', onRepos, true);
        window.addEventListener('resize', onRepos);

        // Keyboard
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') closeDropdown();
        });

        renderOptions('');

        // silent=true — initial render must not fire onChange; user hasn't
        // interacted yet, and propagating it dispatches a phantom native change
        // event that wipes downstream cascading dropdowns on edit pages.
        updateDisplay(null, true);

        var api = {
            getValue: function () { return selected; },
            // Swap the trigger's chevron for a spinner (loading circle) while async work runs.
            setLoading: function (on) {
                var chev = trigger.lastElementChild; // chevron <i> is always last
                if (!chev) return;
                chev.className = on
                    ? 'fa-solid fa-spinner fa-spin text-xs text-zinc-400 ml-2'
                    : 'fa-solid fa-chevron-down text-xs text-zinc-400 ml-2';
            },
            setValue: function (val, silent) {
                selected = val;
                if (el) el.value = multiple ? '' : (val || '');
                updateDisplay(null, silent);
                renderOptions('');
            },
            setOptions: function (newOpts, keepValue) {
                items = (newOpts || []).map(function (o) {
                    if (typeof o === 'string') return { value: o, label: o };
                    return o;
                });
                if (!keepValue) { selected = multiple ? [] : ''; }
                // Sync to native <select> so FormData includes the value
                if (el && el.tagName === 'SELECT') {
                    var frag = document.createDocumentFragment();
                    var ph = document.createElement('option');
                    ph.value = ''; ph.textContent = placeholder;
                    frag.appendChild(ph);
                    for (var i = 0; i < items.length; i++) {
                        var o = document.createElement('option');
                        o.value = items[i].value; o.textContent = items[i].label;
                        frag.appendChild(o);
                    }
                    el.innerHTML = '';
                    el.appendChild(frag);
                    if (keepValue && multiple && Array.isArray(selected)) {
                        for (var n = 0; n < el.options.length; n++) {
                            el.options[n].selected = selected.indexOf(el.options[n].value) > -1;
                        }
                    } else {
                        el.value = keepValue ? (multiple ? '' : selected) : '';
                    }
                }
                renderOptions('');
                updateDisplay(null, true);  // silent — setOptions is programmatic, not user-initiated
            },
            destroy: function () {
                if (el._erpSelectDropdown) {
                    try { document.body.removeChild(el._erpSelectDropdown); } catch (e) { }
                    el._erpSelectDropdown = null;
                }
                if (wrapper.parentNode) wrapper.parentNode.removeChild(wrapper);
                el.style.display = '';
            },
        };
        return api;
    };

    /**
     * getOptionsFromSelect(selector) — Reads options from a native <select> element
     * Returns sorted array of { value, label } objects suitable for erpSearchSelect.
     */
    window.getOptionsFromSelect = function (selector) {
        var opts = [];
        var el = typeof selector === 'string' ? document.querySelector(selector) : selector;
        if (!el) return opts;
        for (var i = 0; i < el.options.length; i++) {
            if (el.options[i].value) {
                opts.push({ value: String(el.options[i].value), label: el.options[i].textContent.trim() });
            }
        }
        opts.sort(function (a, b) { return a.label.localeCompare(b.label); });
        return opts;
    };

    /**
     * initErpSelect(selector, opts) — Drop-in replacement for $(selector).select2()
     * Reads options from native <select>, inits erpSearchSelect, syncs value back.
     * Works on multiple elements (jQuery selector or CSS selector string).
     * opts: { placeholder, onChange, onSearch, freshPrefetch }
     *
     * Declarative attributes (read off the <select> when opts doesn't supply them):
     *   data-fresh-prefetch="<url>"        — opt-in to per-session prefetch + hybrid fallback
     *   data-prefetch-limit="<n>"          — default 300
     *   data-prefetch-sort="-updated_at"   — default '-updated_at'
     *   data-prefetch-extra="use_as=F&x=1" — querystring fragment merged into both prefetch + fallback
     */
    window.initErpSelect = function (selector, opts) {
        opts = opts || {};
        var elements = [];
        if (typeof selector === 'string') {
            elements = document.querySelectorAll(selector);
        } else if (selector instanceof jQuery) {
            selector.each(function () { elements.push(this); });
        } else if (selector instanceof NodeList) {
            elements = selector;
        } else if (selector instanceof HTMLElement) {
            elements = [selector];
        }

        var instances = [];
        for (var i = 0; i < elements.length; i++) {
            (function (el) {
                // Skip if already initialized and not stale
                if (el._erpSelectWrapper && el._erpSelectWrapper.parentNode) return;

                // Skip disabled selects — render the native disabled <select>
                // instead. Without this, the custom search wrapper stays clickable
                // even when the underlying select is locked (e.g., GMP read-only
                // mode on Approved specs/routes/stages where @disabled is set on
                // form fields but the wrapper would otherwise let the user pick).
                if (el.disabled) return;

                var selectOpts = [];
                var selectedVal = '';
                for (var j = 0; j < el.options.length; j++) {
                    var opt = el.options[j];
                    if (opt.value) {
                        selectOpts.push({ value: String(opt.value), label: opt.textContent.trim() });
                    }
                    if (opt.selected && opt.value) selectedVal = String(opt.value);
                }

                // Resolve freshPrefetch from opts OR data-attributes. Caller-supplied
                // opts win; data-* is the declarative path for blade authors.
                var freshPrefetch = opts.freshPrefetch || null;
                if (!freshPrefetch) {
                    var prefetchUrl = el.getAttribute('data-fresh-prefetch');
                    if (prefetchUrl) {
                        freshPrefetch = {
                            url: prefetchUrl,
                            limit: parseInt(el.getAttribute('data-prefetch-limit'), 10) || 300,
                            sort: el.getAttribute('data-prefetch-sort') || '-updated_at',
                            extraData: parseQueryString(el.getAttribute('data-prefetch-extra'))
                        };
                    }
                }

                var inst = erpSearchSelect(el, {
                    options: selectOpts,
                    placeholder: opts.placeholder || el.options[0] && !el.options[0].value ? el.options[0].textContent.trim() : 'Select...',
                    onSearch: opts.onSearch || null,
                    onRefresh: opts.onRefresh || null,
                    allowClear: opts.allowClear || false,
                    freshPrefetch: freshPrefetch,
                    onChange: function (val) {
                        el.value = val;
                        // Dispatch native change event for existing handlers
                        var evt = new Event('change', { bubbles: true });
                        el.dispatchEvent(evt);
                        if (opts.onChange) opts.onChange(val);
                    }
                });

                if (inst) {
                    el._erpSelectInst = inst;
                    instances.push(inst);
                }

                if (selectedVal && inst) {
                    // silent=true suppresses onChange so we don't dispatch a native
                    // `change` event on init — that would fire downstream cascades and
                    // wipe pre-selected child dropdowns on edit pages (see §10.30).
                    inst.setValue(selectedVal, true);
                }
            })(elements[i]);
        }

        return instances.length === 1 ? instances[0] : instances;
    };

    function parseQueryString(qs) {
        if (!qs) return {};
        var out = {};
        qs.split('&').forEach(function (pair) {
            var idx = pair.indexOf('=');
            if (idx === -1) return;
            var k = decodeURIComponent(pair.slice(0, idx));
            var v = decodeURIComponent(pair.slice(idx + 1));
            if (k) out[k] = v;
        });
        return out;
    }

    /**
     * cleanupErpSelect(container) — Remove erpSearchSelect instances inside a container,
     * OR strip a single <select>'s wrapper when called with the select directly.
     * Call before replacing HTML, cloning rows, or re-initializing the same select
     * with new options (e.g. after an AJAX prefetch). Without this, initErpSelect's
     * "skip if already initialized" guard short-circuits and leaves the prior
     * wrapper (including any setSelectLoading state) in the DOM.
     */
    window.cleanupErpSelect = function (container) {
        var el = typeof container === 'string' ? document.querySelector(container) : container;
        if (!el) return;
        if (container instanceof jQuery) el = container[0];

        function stripSelect(s) {
            if (s._erpSelectDropdown) {
                try { document.body.removeChild(s._erpSelectDropdown); } catch (e) { }
                s._erpSelectDropdown = null;
            }
            if (s._erpSelectWrapper) {
                try { s._erpSelectWrapper.parentNode.removeChild(s._erpSelectWrapper); } catch (e) { }
                s._erpSelectWrapper = null;
            }
            s.style.display = '';
        }

        // Handle the single-<select> form (caller passes the <select> itself,
        // not its container). querySelectorAll only walks descendants, so we
        // need to special-case the element-is-the-select case.
        if (el.tagName === 'SELECT') {
            stripSelect(el);
            return;
        }

        var selects = el.querySelectorAll('select');
        for (var i = 0; i < selects.length; i++) stripSelect(selects[i]);

        // Also clean orphaned wrappers (from cloned rows)
        var wrappers = el.querySelectorAll('.erp-select-wrapper');
        for (var j = 0; j < wrappers.length; j++) {
            wrappers[j].parentNode.removeChild(wrappers[j]);
        }
    };

    /* ── Cascade Engine — moved to erp-cascade.js ───────────────── */
    // All cascade functions (setSelectLoading, initSearchSelect, erpCascadeChain,
    // erpLocationCascade, erpEntityLocationCascade, erpBatchCascade, erpFormulationCascade)
    // are now in public/assets-tw/js/erp-cascade.js

    /* ── Tooltip (simple) ───────────────────────────────────────── */
    window.initErpTooltips = function () {
        document.querySelectorAll('[data-tooltip]').forEach(function (el) {
            el.addEventListener('mouseenter', function () {
                var text = this.getAttribute('data-tooltip');
                var tip = document.createElement('div');
                tip.className = 'fixed z-[9999] px-2 py-1 text-xs font-medium text-white bg-zinc-900 rounded-md shadow-lg pointer-events-none animate-fade-in';
                tip.textContent = text;
                tip.id = 'erp-tooltip-active';
                document.body.appendChild(tip);
                var rect = this.getBoundingClientRect();
                tip.style.top = (rect.top - tip.offsetHeight - 6) + 'px';
                tip.style.left = (rect.left + rect.width / 2 - tip.offsetWidth / 2) + 'px';
            });
            el.addEventListener('mouseleave', function () {
                var tip = document.getElementById('erp-tooltip-active');
                if (tip) tip.remove();
            });
        });
    };

    /* ── Copy Code Button ──────────────────────────────────────── */
    /**
     * Auto-init: wraps each [data-copyable] section with a "Copy Code" button.
     * The button copies the inner HTML of the [data-copyable] element.
     */
    /**
     * initCopyCode — adds "Copy Code" button to [data-copyable] elements
     * AND adds "View Code" button to every <section> with an id on components page
     */
    window.initCopyCode = function () {
        // [data-copyable] — direct copy button
        document.querySelectorAll('[data-copyable]').forEach(function (el) {
            if (el.querySelector('.erp-copy-btn')) return;
            var btn = document.createElement('button');
            btn.className = 'erp-copy-btn absolute top-2 right-2 h-7 px-2.5 rounded-md border border-zinc-200 bg-white text-xs font-medium text-zinc-500 hover:bg-zinc-50 hover:text-zinc-700 flex items-center gap-1 shadow-sm z-10';
            btn.innerHTML = '<i class="fa-regular fa-copy text-[10px]"></i> Copy';
            btn.addEventListener('click', function () {
                var tmp = document.createElement('div');
                tmp.innerHTML = el.getAttribute('data-copyable-code') || el.innerHTML;
                tmp.querySelectorAll('.erp-copy-btn, .erp-view-code-btn').forEach(function (b) { b.remove(); });
                var text = tmp.innerHTML.replace(/^\s*\n/, '').replace(/\n\s*$/, '');
                navigator.clipboard.writeText(text).then(function () {
                    btn.innerHTML = '<i class="fa-solid fa-check text-[10px] text-emerald-500"></i> Copied!';
                    setTimeout(function () { btn.innerHTML = '<i class="fa-regular fa-copy text-[10px]"></i> Copy'; }, 2000);
                });
            });
            el.style.position = 'relative';
            el.appendChild(btn);
        });

        // Every <section id="..."> — "View Code" button in heading
        // JS code is sourced from <script type="text/plain" class="erp-js-example"> inside the section
        document.querySelectorAll('section[id] > h2').forEach(function (h2) {
            if (h2.querySelector('.erp-view-code-btn')) return;
            var section = h2.parentElement;

            var btn = document.createElement('button');
            btn.className = 'erp-view-code-btn h-7 px-2.5 rounded-md border border-zinc-200 bg-white text-xs font-medium text-zinc-500 hover:bg-zinc-50 hover:text-zinc-700 inline-flex items-center gap-1 ml-3 align-middle';
            btn.innerHTML = '<i class="fa-solid fa-code text-[10px]"></i> View Code';
            btn.addEventListener('click', function () {
                // HTML — first content div after h2
                var content = section.querySelector('.rounded-lg, .grid, .flex');
                if (!content) return;
                var tmp = document.createElement('div');
                tmp.innerHTML = content.outerHTML;
                tmp.querySelectorAll('.erp-copy-btn, .erp-view-code-btn, template, .erp-js-example').forEach(function (b) { b.remove(); });
                var rawHtml = tmp.innerHTML;
                var htmlCode = rawHtml.replace(/</g, '&lt;').replace(/>/g, '&gt;');

                // JS — from <script type="text/plain" class="erp-js-example"> inside section
                var jsTpl = section.querySelector('.erp-js-example');
                var jsCode = jsTpl ? jsTpl.textContent.trim() : '';
                var jsEscaped = jsCode ? jsCode.replace(/</g, '&lt;').replace(/>/g, '&gt;') : '';

                var hasJs = !!jsEscaped;

                var body = '<div data-tabs-group>' +
                    '<div class="border-b border-zinc-200 -mx-4 -mt-4 mb-4 px-4"><div class="flex gap-0 -mb-px">' +
                    '<button data-tab-target="vc-html" class="px-4 py-2 text-sm font-medium border-b-2 border-zinc-900 text-zinc-900">HTML</button>' +
                    (hasJs ? '<button data-tab-target="vc-js" class="px-4 py-2 text-sm font-medium border-b-2 border-transparent text-zinc-500 hover:text-zinc-700">JavaScript</button>' : '') +
                    '</div></div>' +
                    '<div data-tab-panel="vc-html"><pre class="text-xs leading-relaxed text-zinc-700 bg-zinc-50 p-4 rounded-md overflow-auto max-h-[55vh] font-mono whitespace-pre-wrap break-all">' + htmlCode + '</pre></div>' +
                    (hasJs ? '<div data-tab-panel="vc-js" class="hidden"><pre class="text-xs leading-relaxed text-zinc-700 bg-zinc-50 p-4 rounded-md overflow-auto max-h-[55vh] font-mono whitespace-pre-wrap break-all">' + jsEscaped + '</pre></div>' : '') +
                    '</div>';

                var footerBtns = '<button class="erp-cc-html h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50"><i class="fa-regular fa-copy mr-1.5 text-xs"></i>Copy HTML</button>';
                if (hasJs) footerBtns += '<button class="erp-cc-js h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50"><i class="fa-regular fa-copy mr-1.5 text-xs"></i>Copy JS</button>';
                footerBtns += '<button class="erp-cc-all h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800"><i class="fa-regular fa-copy mr-1.5 text-xs"></i>Copy All</button>';

                var modal = erpModal({ title: h2.childNodes[0].textContent.trim(), size: 'xl', body: body, footer: footerBtns });
                initErpTabs(modal.el);

                function flash(sel) {
                    var b = modal.el.querySelector(sel); var o = b.innerHTML;
                    b.innerHTML = '<i class="fa-solid fa-check mr-1.5 text-xs text-emerald-500"></i>Copied!';
                    setTimeout(function () { b.innerHTML = o; }, 1500);
                }

                modal.el.querySelector('.erp-cc-html').addEventListener('click', function () {
                    navigator.clipboard.writeText(rawHtml); flash('.erp-cc-html');
                });
                if (hasJs) {
                    modal.el.querySelector('.erp-cc-js').addEventListener('click', function () {
                        navigator.clipboard.writeText(jsCode); flash('.erp-cc-js');
                    });
                }
                modal.el.querySelector('.erp-cc-all').addEventListener('click', function () {
                    var all = '<!-- HTML -->\n' + rawHtml + (jsCode ? '\n\n<script>\n' + jsCode + '\n<\/script>' : '');
                    navigator.clipboard.writeText(all); flash('.erp-cc-all');
                });
            });
            h2.style.display = 'inline-flex';
            h2.style.alignItems = 'center';
            h2.appendChild(btn);
        });
    };

    /* ── Auto-init on DOM ready ─────────────────────────────────── */
    function autoInit() {
        initErpTabs();
        initErpAccordion();
        initErpTooltips();
        initCopyCode();
        // Wizards auto-init
        document.querySelectorAll('[data-wizard]').forEach(function (w) {
            erpWizard(w.id);
        });
        // Hybrid fresh-prefetch selects auto-init (lazy AJAX + refresh icon).
        // Blade authors just put `data-fresh-prefetch="<url>"` on a <select>
        // and the helper wires the per-session prefetch + hybrid fallback.
        document.querySelectorAll('select[data-fresh-prefetch]').forEach(function (sel) {
            if (sel.disabled) return;
            initErpSelect(sel);
        });
        // Flatpickr auto-init — calendar opens on click anywhere in the input.
        if (typeof flatpickr === 'function') {
            document.querySelectorAll('.flatpickr-date').forEach(function (el) {
                if (el._flatpickr) return;
                flatpickr(el, { dateFormat: 'd-m-Y', allowInput: true, clickOpens: true });
            });
        }
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', autoInit);
    } else {
        // Delay to let partials load first
        setTimeout(autoInit, 300);
    }

    /* ── KPI Card Helper ─────────────────────────────────────── */
    /**
     * erpKpiCard({ label, value, trend, trendDir, icon, iconBg, iconColor, valueColor })
     * Returns HTML string for a standard KPI/stat summary card.
     *
     * @param {string}  label      — Metric label (e.g. "Total Revenue")
     * @param {string}  value      — Metric value (e.g. "$1,245,600")
     * @param {string}  [trend]    — Trend text (e.g. "8.2% vs prior")
     * @param {string}  [trendDir] — "up" (green) | "down" (red) | "neutral" (zinc). Default "up"
     * @param {string}  [icon]     — FontAwesome icon class (e.g. "fa-chart-line")
     * @param {string}  [iconBg]   — Icon container bg (e.g. "bg-blue-50"). Default "bg-blue-50"
     * @param {string}  [iconColor]— Icon color (e.g. "text-blue-600"). Default "text-blue-600"
     * @param {string}  [valueColor]— Value text color. Default "text-zinc-900"
     */
    window.erpKpiCard = function (opts) {
        opts = opts || {};
        var dir = opts.trendDir || 'up';
        var trendColor = dir === 'down' ? 'text-red-600' : dir === 'neutral' ? 'text-zinc-500' : 'text-emerald-600';
        var trendIcon = dir === 'down' ? 'fa-arrow-down' : dir === 'neutral' ? 'fa-minus' : 'fa-arrow-up';
        var valueColor = opts.valueColor || 'text-zinc-900';
        var iconBg = opts.iconBg || 'bg-blue-50';
        var iconColor = opts.iconColor || 'text-blue-600';

        return '<div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">' +
            '<div class="flex items-center justify-between">' +
            '<div>' +
            '<p class="text-sm font-medium text-zinc-500">' + (opts.label || '') + '</p>' +
            '<p class="text-2xl font-bold ' + valueColor + ' mt-1">' + (opts.value || '') + '</p>' +
            (opts.trend ? '<p class="text-sm ' + trendColor + ' mt-1"><i class="fa-solid ' + trendIcon + ' text-xs"></i> ' + opts.trend + '</p>' : '') +
            '</div>' +
            (opts.icon ? '<div class="h-10 w-10 rounded-md ' + iconBg + ' flex items-center justify-center"><i class="fa-solid ' + opts.icon + ' ' + iconColor + '"></i></div>' : '') +
            '</div>' +
            '</div>';
    };

    /* ── Contact Info Row Helper ─────────────────────────────── */
    /**
     * erpContactInfo({ type, value, href })
     * Returns HTML string for a contact info row with colored icon container.
     *
     * @param {string} type  — "email" | "phone" | "location" | "website"
     * @param {string} value — Display text
     * @param {string} [href]— Link URL (auto-detected for email/phone if omitted)
     */
    window.erpContactInfo = function (opts) {
        opts = opts || {};
        var typeMap = {
            email: { icon: 'fa-envelope', bg: 'bg-blue-50', color: 'text-blue-500' },
            phone: { icon: 'fa-phone', bg: 'bg-emerald-50', color: 'text-emerald-500' },
            location: { icon: 'fa-location-dot', bg: 'bg-amber-50', color: 'text-amber-500' },
            website: { icon: 'fa-globe', bg: 'bg-purple-50', color: 'text-purple-500' }
        };
        var t = typeMap[opts.type] || typeMap.email;
        var href = opts.href || (opts.type === 'email' ? 'mailto:' + opts.value : opts.type === 'phone' ? 'tel:' + opts.value : '');
        var valHtml = href
            ? '<a href="' + href + '" class="text-xs text-zinc-600 hover:text-zinc-900 truncate">' + (opts.value || '') + '</a>'
            : '<span class="text-xs text-zinc-600 truncate">' + (opts.value || '') + '</span>';

        return '<div class="flex items-center gap-3 overflow-hidden">' +
            '<div class="h-6 w-6 rounded ' + t.bg + ' flex items-center justify-center shrink-0">' +
            '<i class="fa-solid ' + t.icon + ' ' + t.color + '" style="font-size:10px"></i>' +
            '</div>' +
            valHtml +
            '</div>';
    };

    /* ── Avatar Helper ───────────────────────────────────────── */
    /**
     * erpAvatar({ name, size, color, image })
     * Returns HTML for a user avatar (initials or image).
     */
    window.erpAvatar = function (opts) {
        opts = opts || {};
        var sizes = { xs: 'h-6 w-6 text-[10px]', sm: 'h-8 w-8 text-xs', md: 'h-10 w-10 text-sm', lg: 'h-14 w-14 text-lg', xl: 'h-16 w-16 text-xl' };
        var sizeClass = sizes[opts.size || 'md'] || sizes.md;
        var colors = {
            blue: 'bg-blue-100 text-blue-700', emerald: 'bg-emerald-100 text-emerald-700',
            purple: 'bg-purple-100 text-purple-700', amber: 'bg-amber-100 text-amber-700',
            red: 'bg-red-100 text-red-700', pink: 'bg-pink-100 text-pink-700',
            zinc: 'bg-zinc-900 text-white', indigo: 'bg-indigo-100 text-indigo-700'
        };
        var colorClass = colors[opts.color || 'zinc'] || colors.zinc;
        var name = opts.name || '';
        var initials = name.split(' ').map(function (w) { return w[0]; }).join('').substring(0, 2).toUpperCase();

        if (opts.image) {
            return '<img src="' + opts.image + '" alt="' + name + '" class="' + sizeClass + ' rounded-full object-cover">';
        }
        return '<div class="' + sizeClass + ' rounded-full ' + colorClass + ' flex items-center justify-center shrink-0 font-bold">' + initials + '</div>';
    };

    /* ── Icon Box Helper ─────────────────────────────────────── */
    /**
     * erpIconBox({ icon, bg, color, size })
     * Returns HTML for a colored icon container.
     */
    window.erpIconBox = function (opts) {
        opts = opts || {};
        var sizes = { sm: 'h-6 w-6 text-[10px]', md: 'h-8 w-8 text-xs', lg: 'h-10 w-10 text-sm' };
        var sizeClass = sizes[opts.size || 'md'] || sizes.md;
        var bg = opts.bg || 'bg-blue-50';
        var color = opts.color || 'text-blue-600';
        return '<div class="' + sizeClass + ' rounded-md ' + bg + ' flex items-center justify-center shrink-0">' +
            '<i class="fa-solid ' + (opts.icon || 'fa-circle') + ' ' + color + '"></i>' +
            '</div>';
    };

    /* ── Empty State Helper ──────────────────────────────────── */
    /**
     * erpEmptyState({ icon, title, message, action: { label, href, onClick } })
     * Returns HTML for a no-data placeholder.
     */
    window.erpEmptyState = function (opts) {
        opts = opts || {};
        var actionHtml = '';
        if (opts.action) {
            var tag = opts.action.href ? 'a href="' + opts.action.href + '"' : 'button type="button"';
            var endTag = opts.action.href ? 'a' : 'button';
            actionHtml = '<' + tag + ' class="mt-4 h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center">' +
                (opts.action.icon ? '<i class="fa-solid ' + opts.action.icon + ' mr-1.5 text-xs"></i>' : '') +
                (opts.action.label || 'Create') +
                '</' + endTag + '>';
        }
        return '<div class="py-12 text-center">' +
            '<div class="h-12 w-12 rounded-full bg-zinc-100 flex items-center justify-center mx-auto mb-4">' +
            '<i class="fa-solid ' + (opts.icon || 'fa-inbox') + ' text-xl text-zinc-400"></i>' +
            '</div>' +
            '<h3 class="text-sm font-semibold text-zinc-900">' + (opts.title || 'No records found') + '</h3>' +
            (opts.message ? '<p class="text-sm text-zinc-500 mt-1 max-w-sm mx-auto">' + opts.message + '</p>' : '') +
            actionHtml +
            '</div>';
    };

    /* ── Progress Bar Helper ─────────────────────────────────── */
    /**
     * erpProgress({ value, color, label, size })
     * Returns HTML for a progress bar.
     */
    window.erpProgress = function (opts) {
        opts = opts || {};
        var value = Math.min(100, Math.max(0, opts.value || 0));
        var colorMap = {
            emerald: 'bg-emerald-500', blue: 'bg-blue-500', amber: 'bg-amber-500',
            red: 'bg-red-500', purple: 'bg-purple-500', zinc: 'bg-zinc-500', indigo: 'bg-indigo-500'
        };
        var barColor = colorMap[opts.color || 'blue'] || colorMap.blue;
        var sizeClass = opts.size === 'sm' ? 'erp-progress-sm' : opts.size === 'lg' ? 'erp-progress-lg' : '';

        var html = '';
        if (opts.label) {
            html += '<div class="flex items-center justify-between mb-1.5">' +
                '<span class="text-xs font-medium text-zinc-700">' + opts.label + '</span>' +
                '<span class="text-xs font-medium text-zinc-500">' + value + '%</span>' +
                '</div>';
        }
        html += '<div class="erp-progress ' + sizeClass + '">' +
            '<div class="erp-progress-bar ' + barColor + '" style="width:' + value + '%"></div>' +
            '</div>';
        return html;
    };

    /* ── Alert Banner Helper ─────────────────────────────────── */
    /**
     * erpAlertBanner({ type, message, title, dismissible, icon })
     * Returns HTML for an inline page-level alert (not a modal).
     */
    window.erpAlertBanner = function (opts) {
        opts = opts || {};
        var type = opts.type || 'info';
        var icons = { info: 'fa-circle-info', success: 'fa-circle-check', warning: 'fa-triangle-exclamation', error: 'fa-circle-xmark' };
        var iconColors = { info: 'text-blue-500', success: 'text-emerald-500', warning: 'text-amber-500', error: 'text-red-500' };

        return '<div class="erp-alert-banner erp-alert-banner-' + type + '">' +
            '<i class="fa-solid ' + (opts.icon || icons[type]) + ' ' + iconColors[type] + ' mt-0.5 shrink-0"></i>' +
            '<div class="flex-1 min-w-0">' +
            (opts.title ? '<p class="text-sm font-semibold">' + opts.title + '</p>' : '') +
            '<p class="text-sm">' + (opts.message || '') + '</p>' +
            '</div>' +
            (opts.dismissible ? '<button class="shrink-0 opacity-50 hover:opacity-100" onclick="this.closest(\'.erp-alert-banner\').remove()"><i class="fa-solid fa-xmark text-xs"></i></button>' : '') +
            '</div>';
    };

    /* ── Chart Card Helper ───────────────────────────────────── */
    /**
     * erpChartCard({ id, title, subtitle, height, legendHtml })
     * Returns HTML for a chart container card with canvas.
     */
    window.erpChartCard = function (opts) {
        opts = opts || {};
        var h = opts.height || 260;
        return '<div class="rounded-lg border border-zinc-200 bg-white shadow-sm">' +
            '<div class="p-4 border-b border-zinc-200">' +
            '<h3 class="text-sm font-semibold text-zinc-900">' + (opts.title || '') + '</h3>' +
            (opts.subtitle ? '<p class="text-xs text-zinc-500 mt-0.5">' + opts.subtitle + '</p>' : '') +
            (opts.legendHtml || '') +
            '</div>' +
            '<div class="p-4" style="height:' + h + 'px">' +
            '<canvas id="' + (opts.id || 'chart-' + Math.random().toString(36).substr(2, 6)) + '"></canvas>' +
            '</div>' +
            '</div>';
    };

    /* ── Quick Actions Card Helper ───────────────────────────── */
    /**
     * erpQuickActions({ title, actions: [{ icon, label, href, color }] })
     * Returns HTML for a dashboard quick actions card.
     */
    window.erpQuickActions = function (opts) {
        opts = opts || {};
        var title = opts.title || 'Quick Actions';
        var items = opts.actions || [];
        var colorMap = {
            blue: { bg: 'bg-blue-50', text: 'text-blue-600' },
            emerald: { bg: 'bg-emerald-50', text: 'text-emerald-600' },
            amber: { bg: 'bg-amber-50', text: 'text-amber-600' },
            red: { bg: 'bg-red-50', text: 'text-red-600' },
            purple: { bg: 'bg-purple-50', text: 'text-purple-600' },
            zinc: { bg: 'bg-zinc-100', text: 'text-zinc-600' }
        };

        var html = '<div class="rounded-lg border border-zinc-200 bg-white shadow-sm">' +
            '<div class="p-4 border-b border-zinc-200"><h3 class="text-sm font-semibold text-zinc-900">' + title + '</h3></div>' +
            '<div class="p-3 space-y-1">';

        items.forEach(function (a) {
            var c = colorMap[a.color || 'blue'] || colorMap.blue;
            html += '<a href="' + (a.href || '#') + '" class="flex items-center gap-3 p-2.5 rounded-md text-sm text-zinc-700 hover:bg-zinc-50 transition-colors">' +
                '<div class="h-8 w-8 rounded-md ' + c.bg + ' flex items-center justify-center shrink-0">' +
                '<i class="fa-solid ' + (a.icon || 'fa-circle') + ' ' + c.text + ' text-xs"></i>' +
                '</div>' + (a.label || '') +
                '</a>';
        });

        html += '</div></div>';
        return html;
    };

    /* ── Filter Bar Helper ───────────────────────────────────── */
    /**
     * erpFilterBar({ target, search, searchPlaceholder, filters: [{ id, label, options }], onApply, onReset })
     * Returns HTML string for a standard filter bar. Caller appends to DOM.
     */
    window.erpFilterBar = function (opts) {
        opts = opts || {};
        var html = '<div class="rounded-lg border border-zinc-200 bg-white p-4 shadow-sm mb-4">' +
            '<div class="flex flex-col lg:flex-row lg:items-end gap-3">';

        if (opts.search !== false) {
            html += '<div class="flex-1">' +
                '<label class="block text-xs font-medium text-zinc-500 mb-1">Search</label>' +
                '<div class="relative">' +
                '<i class="fa-solid fa-search absolute left-3 top-1/2 -translate-y-1/2 text-zinc-400 text-xs"></i>' +
                '<input type="text" class="erp-filter-search w-full h-9 pl-9 pr-3 rounded-md border border-zinc-200 bg-white text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-1" placeholder="' + (opts.searchPlaceholder || 'Search...') + '">' +
                '</div>' +
                '</div>';
        }

        (opts.filters || []).forEach(function (f) {
            html += '<div>' +
                '<label class="block text-xs font-medium text-zinc-500 mb-1">' + (f.label || '') + '</label>' +
                '<select class="erp-filter-select h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-1" data-filter-id="' + (f.id || '') + '">';
            (f.options || []).forEach(function (o, i) {
                var val = typeof o === 'object' ? o.value : o;
                var lbl = typeof o === 'object' ? o.label : o;
                html += '<option value="' + val + '">' + lbl + '</option>';
            });
            html += '</select></div>';
        });

        html += '<div class="flex items-end gap-2">' +
            '<button type="button" class="erp-filter-apply h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center">Apply</button>' +
            '<button type="button" class="erp-filter-reset h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center">Reset</button>' +
            '</div></div></div>';
        return html;
    };

    /* ── Skeleton Loader Helper ──────────────────────────────── */
    /**
     * erpSkeleton({ type, rows, lines })
     * type: 'table' | 'card' | 'text' | 'kpi'
     */
    window.erpSkeleton = function (opts) {
        opts = opts || {};
        var type = opts.type || 'text';
        var html = '';

        if (type === 'text') {
            var lines = opts.lines || 3;
            html = '<div class="space-y-3">';
            for (var i = 0; i < lines; i++) {
                var w = i === lines - 1 ? 'w-2/3' : 'w-full';
                html += '<div class="erp-skeleton h-3 ' + w + '"></div>';
            }
            html += '</div>';
        } else if (type === 'card') {
            html = '<div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm space-y-4">' +
                '<div class="flex items-center gap-3"><div class="erp-skeleton-circle h-10 w-10"></div><div class="flex-1 space-y-2"><div class="erp-skeleton h-3 w-1/3"></div><div class="erp-skeleton h-2.5 w-1/2"></div></div></div>' +
                '<div class="space-y-2"><div class="erp-skeleton h-2.5 w-full"></div><div class="erp-skeleton h-2.5 w-full"></div><div class="erp-skeleton h-2.5 w-2/3"></div></div>' +
                '</div>';
        } else if (type === 'table') {
            var rows = opts.rows || 5;
            html = '<div class="rounded-lg border border-zinc-200 bg-white shadow-sm overflow-hidden">' +
                '<div class="p-4 border-b border-zinc-200 flex gap-4">' +
                '<div class="erp-skeleton h-3 w-20"></div><div class="erp-skeleton h-3 w-24"></div><div class="erp-skeleton h-3 w-16"></div><div class="erp-skeleton h-3 w-20"></div>' +
                '</div>';
            for (var r = 0; r < rows; r++) {
                html += '<div class="p-4 border-b border-zinc-100 flex gap-4">' +
                    '<div class="erp-skeleton h-2.5 w-16"></div><div class="erp-skeleton h-2.5 w-32"></div><div class="erp-skeleton h-2.5 w-20"></div><div class="erp-skeleton h-2.5 w-16"></div>' +
                    '</div>';
            }
            html += '</div>';
        } else if (type === 'kpi') {
            html = '<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">';
            for (var k = 0; k < 4; k++) {
                html += '<div class="rounded-lg border border-zinc-200 bg-white p-6 shadow-sm">' +
                    '<div class="flex items-center justify-between"><div class="space-y-3"><div class="erp-skeleton h-2.5 w-20"></div><div class="erp-skeleton h-5 w-24"></div><div class="erp-skeleton h-2 w-16"></div></div>' +
                    '<div class="erp-skeleton-circle h-10 w-10"></div></div></div>';
            }
            html += '</div>';
        }
        return html;
    };

    /* ── Timeline Helper ─────────────────────────────────────── */
    /**
     * erpTimeline({ items: [{ icon, color, title, desc, time }] })
     * Returns HTML for a vertical timeline.
     */
    window.erpTimeline = function (opts) {
        opts = opts || {};
        var items = opts.items || [];
        var colorMap = {
            emerald: 'bg-emerald-500', blue: 'bg-blue-500', amber: 'bg-amber-500',
            red: 'bg-red-500', purple: 'bg-purple-500', zinc: 'bg-zinc-400'
        };

        var html = '<div class="erp-timeline">';
        items.forEach(function (item) {
            var dotColor = colorMap[item.color || 'blue'] || colorMap.blue;
            html += '<div class="erp-timeline-item">' +
                '<div class="erp-timeline-dot ' + dotColor + '">' +
                '<i class="fa-solid ' + (item.icon || 'fa-circle') + ' text-white" style="font-size:8px"></i>' +
                '</div>' +
                '<div>' +
                '<div class="flex items-center gap-2">' +
                '<p class="text-sm font-medium text-zinc-900">' + (item.title || '') + '</p>' +
                (item.time ? '<span class="text-xs text-zinc-400">' + item.time + '</span>' : '') +
                '</div>' +
                (item.desc ? '<p class="text-xs text-zinc-500 mt-0.5">' + item.desc + '</p>' : '') +
                '</div>' +
                '</div>';
        });
        html += '</div>';
        return html;
    };

    /* ── Number Formatter ────────────────────────────────────── */
    /**
     * erpNumber(value, opts)
     * opts.style: 'decimal' (default) | 'percent' | 'bytes'
     * opts.decimals: number of decimal places
     */
    window.erpNumber = function (value, opts) {
        opts = opts || {};
        var n = parseFloat(value);
        if (isNaN(n)) return '0';

        if (opts.style === 'percent') {
            return new Intl.NumberFormat('en-US', { style: 'percent', minimumFractionDigits: opts.decimals || 1, maximumFractionDigits: opts.decimals || 1 }).format(n);
        }
        if (opts.style === 'bytes') {
            var units = ['B', 'KB', 'MB', 'GB', 'TB'];
            var i = 0;
            var b = Math.abs(n);
            while (b >= 1024 && i < units.length - 1) { b /= 1024; i++; }
            return b.toFixed(i === 0 ? 0 : 1) + ' ' + units[i];
        }
        return new Intl.NumberFormat('en-US', {
            minimumFractionDigits: opts.decimals || 0,
            maximumFractionDigits: opts.decimals || 0
        }).format(n);
    };

    /* ── Copy to Clipboard ───────────────────────────────────── */
    /**
     * erpCopyToClipboard(text)
     * Copies text and shows a success toast.
     */
    window.erpCopyToClipboard = function (text) {
        navigator.clipboard.writeText(text).then(function () {
            if (window.erpToast) {
                erpToast({ type: 'success', title: 'Copied', message: 'Copied to clipboard', duration: 2000 });
            }
        });
    };

    /* ── Dropdown Menu ───────────────────────────────────────── */
    /**
     * erpDropdown(trigger, { items: [{ icon, label, onClick, danger, divider }], align })
     * Attaches a dropdown menu to a trigger element.
     */
    window.erpDropdown = function (trigger, opts) {
        opts = opts || {};
        var $trigger = typeof trigger === 'string' ? document.querySelector(trigger) : trigger;
        if (!$trigger) return;

        var menu = document.createElement('div');
        menu.className = 'erp-dropdown-menu hidden';
        menu.style.right = opts.align === 'left' ? 'auto' : '0';
        menu.style.left = opts.align === 'left' ? '0' : 'auto';
        menu.style.top = '100%';
        menu.style.marginTop = '4px';

        (opts.items || []).forEach(function (item) {
            if (item.divider) {
                var div = document.createElement('div');
                div.className = 'erp-dropdown-divider';
                menu.appendChild(div);
                return;
            }
            var el = document.createElement('div');
            el.className = 'erp-dropdown-item' + (item.danger ? ' erp-dropdown-item-danger' : '');
            el.innerHTML = (item.icon ? '<i class="fa-solid ' + item.icon + ' text-xs w-4 text-center"></i>' : '') +
                '<span>' + (item.label || '') + '</span>';
            el.addEventListener('click', function () {
                menu.classList.add('hidden');
                if (item.onClick) item.onClick();
            });
            menu.appendChild(el);
        });

        $trigger.style.position = 'relative';
        $trigger.appendChild(menu);

        $trigger.addEventListener('click', function (e) {
            e.stopPropagation();
            menu.classList.toggle('hidden');
        });
        document.addEventListener('click', function () { menu.classList.add('hidden'); });

        return { el: menu, close: function () { menu.classList.add('hidden'); } };
    };

    /* ── Dashboard Date Range Filter ─────────────────────────── */
    /**
     * erpDateRangeFilter({ onChange, defaultRange })
     * Returns HTML for a date range dropdown with presets.
     * defaultRange: 'this-month' | 'this-quarter' | 'this-year' | 'last-month' | 'last-quarter' | 'ytd'
     */
    window.erpDateRangeFilter = function (opts) {
        opts = opts || {};
        var id = 'erp-dr-' + Math.random().toString(36).substr(2, 6);
        var ranges = [
            { value: 'today', label: 'Today' },
            { value: 'this-week', label: 'This Week' },
            { value: 'this-month', label: 'This Month' },
            { value: 'this-quarter', label: 'This Quarter' },
            { value: 'this-year', label: 'This Year' },
            { value: 'last-month', label: 'Last Month' },
            { value: 'last-quarter', label: 'Last Quarter' },
            { value: 'ytd', label: 'Year to Date' },
            { value: 'custom', label: 'Custom Range...' }
        ];
        var defaultRange = opts.defaultRange || 'this-month';

        var html = '<div class="relative inline-block" id="' + id + '">' +
            '<select class="erp-date-range-select h-9 rounded-md border border-zinc-200 bg-white pl-3 pr-8 text-sm focus:outline-none focus:ring-2 focus:ring-zinc-900 focus:ring-offset-1">';
        ranges.forEach(function (r) {
            html += '<option value="' + r.value + '"' + (r.value === defaultRange ? ' selected' : '') + '>' + r.label + '</option>';
        });
        html += '</select></div>';
        return html;
    };

    /* ── Dashboard Header ────────────────────────────────────── */
    /**
     * erpDashboardHeader({ title, subtitle, dateRange, exportBtn, refreshBtn, actions })
     * Returns HTML for a standardized dashboard page header with date filter.
     */
    window.erpDashboardHeader = function (opts) {
        opts = opts || {};
        var html = '<div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4 mb-6">' +
            '<div>' +
            '<h1 class="text-xl sm:text-2xl font-bold text-zinc-900">' + (opts.title || '') + '</h1>' +
            (opts.subtitle ? '<p class="text-sm text-zinc-500 mt-1">' + opts.subtitle + '</p>' : '') +
            '</div>' +
            '<div class="flex items-center gap-2">';

        if (opts.dateRange !== false) {
            html += erpDateRangeFilter({ defaultRange: opts.defaultRange || 'this-month' });
        }
        if (opts.refreshBtn !== false) {
            html += '<button class="h-9 w-9 rounded-md border border-zinc-200 bg-white text-zinc-500 hover:bg-zinc-50 inline-flex items-center justify-center" title="Refresh"><i class="fa-solid fa-arrows-rotate text-xs"></i></button>';
        }
        if (opts.exportBtn !== false) {
            html += '<button class="h-9 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center"><i class="fa-solid fa-download mr-1.5 text-xs"></i> Export</button>';
        }
        if (opts.actions) {
            opts.actions.forEach(function (a) {
                html += '<a href="' + (a.href || '#') + '" class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center">' +
                    (a.icon ? '<i class="fa-solid ' + a.icon + ' mr-1.5 text-xs"></i>' : '') + (a.label || '') + '</a>';
            });
        }
        html += '</div></div>';
        return html;
    };

    /* ── Audit Info Card ─────────────────────────────────────── */
    /**
     * erpBadge(status)
     * Returns HTML for a colored status badge. Auto-maps status strings to colors.
     * Defined here so it's available even without erp-datatable.js.
     */
    if (!window.erpBadge) {
        window.erpBadge = function (status) {
            var map = {
                active: { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700' },
                approved: { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700' },
                completed: { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700' },
                paid: { bg: 'bg-emerald-50', border: 'border-emerald-200', text: 'text-emerald-700' },
                pending: { bg: 'bg-amber-50', border: 'border-amber-200', text: 'text-amber-700' },
                draft: { bg: 'bg-zinc-50', border: 'border-zinc-200', text: 'text-zinc-700' },
                inactive: { bg: 'bg-zinc-50', border: 'border-zinc-200', text: 'text-zinc-700' },
                rejected: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700' },
                cancelled: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700' },
                overdue: { bg: 'bg-red-50', border: 'border-red-200', text: 'text-red-700' },
                'in-progress': { bg: 'bg-blue-50', border: 'border-blue-200', text: 'text-blue-700' },
                processing: { bg: 'bg-blue-50', border: 'border-blue-200', text: 'text-blue-700' }
            };
            var key = (status || '').toLowerCase().replace(/\s+/g, '-');
            var style = map[key] || { bg: 'bg-zinc-50', border: 'border-zinc-200', text: 'text-zinc-700' };
            return '<span class="inline-flex items-center whitespace-nowrap rounded-md border px-2.5 py-0.5 text-xs font-medium ' +
                style.bg + ' ' + style.border + ' ' + style.text + '">' + status + '</span>';
        };
    }

    /**
     * erpAuditInfo({ createdBy, createdDate, modifiedBy, modifiedDate, version, status })
     * Returns HTML for a read-only audit trail card (typically at bottom of forms).
     */
    window.erpAuditInfo = function (opts) {
        opts = opts || {};
        return '<div class="rounded-lg border border-zinc-200 bg-zinc-50 p-4 mt-6">' +
            '<h4 class="text-xs font-semibold text-zinc-500 uppercase tracking-wider mb-3">Audit Information</h4>' +
            '<div class="grid grid-cols-2 md:grid-cols-4 gap-4">' +
            '<div><p class="text-xs text-zinc-400">Created By</p><p class="text-sm text-zinc-700 mt-0.5">' + (opts.createdBy || 'System') + '</p></div>' +
            '<div><p class="text-xs text-zinc-400">Created Date</p><p class="text-sm text-zinc-700 mt-0.5">' + (opts.createdDate || '—') + '</p></div>' +
            '<div><p class="text-xs text-zinc-400">Last Modified By</p><p class="text-sm text-zinc-700 mt-0.5">' + (opts.modifiedBy || '—') + '</p></div>' +
            '<div><p class="text-xs text-zinc-400">Last Modified Date</p><p class="text-sm text-zinc-700 mt-0.5">' + (opts.modifiedDate || '—') + '</p></div>' +
            '</div>' +
            (opts.version || opts.status ? '<div class="flex items-center gap-4 mt-3 pt-3 border-t border-zinc-200">' +
                (opts.version ? '<span class="text-xs text-zinc-400">Version: <strong class="text-zinc-600">' + opts.version + '</strong></span>' : '') +
                (opts.status ? '<span class="text-xs text-zinc-400">Status: ' + erpBadge(opts.status) + '</span>' : '') +
                '</div>' : '') +
            '</div>';
    };

    /* ── Form Validation Helper ──────────────────────────────── */
    /**
     * erpValidateForm(formSelector, rules)
     * Validates form fields and shows inline error messages.
     * rules: { '#fieldId': { required: true, email: true, minLength: 3, pattern: /regex/, message: 'Custom error' } }
     * Returns true if all valid, false otherwise.
     */
    window.erpValidateForm = function (formSelector, rules) {
        var form = document.querySelector(formSelector);
        if (!form) return true;
        var valid = true;

        // Clear previous errors
        form.querySelectorAll('.erp-field-error').forEach(function (el) { el.remove(); });
        form.querySelectorAll('.erp-input-error').forEach(function (el) { el.classList.remove('erp-input-error'); });

        Object.keys(rules).forEach(function (selector) {
            var field = form.querySelector(selector);
            if (!field) return;
            var rule = rules[selector];
            var value = (field.value || '').trim();
            var error = '';

            if (rule.required && !value) {
                error = rule.message || 'This field is required';
            } else if (rule.email && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                error = rule.message || 'Please enter a valid email address';
            } else if (rule.minLength && value.length < rule.minLength) {
                error = rule.message || 'Minimum ' + rule.minLength + ' characters required';
            } else if (rule.min !== undefined && parseFloat(value) < rule.min) {
                error = rule.message || 'Value must be at least ' + rule.min;
            } else if (rule.max !== undefined && parseFloat(value) > rule.max) {
                error = rule.message || 'Value must be at most ' + rule.max;
            } else if (rule.pattern && !rule.pattern.test(value)) {
                error = rule.message || 'Invalid format';
            }

            if (error) {
                valid = false;
                field.classList.add('erp-input-error');
                var errEl = document.createElement('p');
                errEl.className = 'erp-field-error';
                errEl.textContent = error;
                field.parentNode.insertBefore(errEl, field.nextSibling);
            }
        });

        return valid;
    };

    /* ── Import Modal ────────────────────────────────────────── */
    /**
     * erpImportModal({ title, templateUrl, fields, onImport })
     * Opens a modal with file upload, template download link, and import button.
     */
    window.erpImportModal = function (opts) {
        opts = opts || {};
        var body = '<div class="space-y-4">';

        if (opts.templateUrl) {
            body += '<div class="flex items-center gap-3 p-3 rounded-md bg-blue-50 border border-blue-200">' +
                '<i class="fa-solid fa-circle-info text-blue-500"></i>' +
                '<div class="flex-1"><p class="text-sm text-blue-700">Download the template to ensure your data is formatted correctly.</p></div>' +
                '<a href="' + opts.templateUrl + '" class="py-1.5 px-3 rounded-md border border-blue-300 bg-white text-xs font-medium text-blue-700 hover:bg-blue-50 whitespace-nowrap inline-flex items-center">' +
                '<i class="fa-solid fa-download mr-1.5 text-[10px]"></i>Download Template</a>' +
                '</div>';
        }

        body += '<div>' +
            '<label class="block text-sm font-medium text-zinc-700 mb-1.5">Upload File</label>' +
            '<div class="border-2 border-dashed border-zinc-300 rounded-lg p-6 text-center hover:border-zinc-400 transition-colors cursor-pointer">' +
            '<i class="fa-solid fa-cloud-arrow-up text-2xl text-zinc-400 mb-2"></i>' +
            '<p class="text-sm text-zinc-600">Drag & drop your file here, or <span class="text-zinc-900 font-medium underline">browse</span></p>' +
            '<p class="text-xs text-zinc-400 mt-1">Supports CSV, XLSX, XLS (max 10MB)</p>' +
            '<input type="file" class="erp-import-file hidden" accept=".csv,.xlsx,.xls">' +
            '</div>' +
            '</div>';

        if (opts.fields && opts.fields.length) {
            body += '<div><label class="block text-sm font-medium text-zinc-700 mb-1.5">Field Mapping Preview</label>' +
                '<div class="rounded-md border border-zinc-200 overflow-hidden"><table class="w-full text-sm">' +
                '<thead><tr class="bg-zinc-50"><th class="px-3 py-2 text-left text-xs font-medium text-zinc-500">Source Column</th><th class="px-3 py-2 text-left text-xs font-medium text-zinc-500">Maps To</th></tr></thead><tbody>';
            opts.fields.forEach(function (f) {
                body += '<tr class="border-t border-zinc-100"><td class="px-3 py-2 text-zinc-600">' + (f.source || f) + '</td><td class="px-3 py-2 text-zinc-900 font-medium">' + (f.target || f) + '</td></tr>';
            });
            body += '</tbody></table></div></div>';
        }

        body += '</div>';

        var modal = erpModal({
            title: opts.title || 'Import Data',
            size: 'md',
            body: body,
            footer: '<button class="erp-import-cancel py-1.5 px-3 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center">Cancel</button>' +
                '<button class="erp-import-submit py-1.5 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center"><i class="fa-solid fa-upload mr-1.5 text-xs"></i>Import</button>'
        });

        // Wire up file drop zone click
        var dropZone = modal.el.querySelector('.border-dashed');
        var fileInput = modal.el.querySelector('.erp-import-file');
        if (dropZone && fileInput) {
            dropZone.addEventListener('click', function () { fileInput.click(); });
            fileInput.addEventListener('change', function () {
                if (fileInput.files.length) {
                    dropZone.innerHTML = '<i class="fa-solid fa-file-check text-2xl text-emerald-500 mb-2"></i>' +
                        '<p class="text-sm text-zinc-900 font-medium">' + fileInput.files[0].name + '</p>' +
                        '<p class="text-xs text-zinc-400 mt-1">' + (fileInput.files[0].size / 1024).toFixed(1) + ' KB</p>';
                }
            });
        }

        modal.el.querySelector('.erp-import-cancel').addEventListener('click', function () { modal.close(); });
        modal.el.querySelector('.erp-import-submit').addEventListener('click', function () {
            if (opts.onImport) opts.onImport(fileInput ? fileInput.files[0] : null);
            erpToast({ type: 'success', title: 'Import Started', message: 'Your file is being processed.' });
            modal.close();
        });

        return modal;
    };

/* ── Status toggle for table rows ────────────────────────────── */
if (!window.erpStatusToggle) {
    window.erpStatusToggle = function(status, id, toggleRoute) {
      var on = status === 'Active';
      var routeAttr = toggleRoute ? ' data-toggle-route="' + toggleRoute.replace(/'/g, '&#39;') + '"' : '';
      return '<label class="erp-status-switch" title="Toggle status">' +
             '<input type="checkbox" data-action="toggle-status" data-id="' + (id == null ? '' : id) + '"' + routeAttr + ' ' + (on ? 'checked' : '') + '>' +
             '<span class="erp-status-track"><span class="erp-status-thumb"></span></span>' +
             '<span class="erp-status-pill">' + (status || (on ? 'Active' : 'Inactive')) + '</span>' +
             '</label>';
    };
  }

/* ── Global status-toggle change handler ────────────────────
   Modules set window.erpToggleMessages in their page script:
     window.erpToggleMessages = {
       confirmTitle: '...',
       confirmBody:   '...',   // :name and :status placeholders
       confirmYes:   '...',
       statusActive:   '...',
       statusInactive: '...',
     };
   English defaults used when not set. */
$(document).on('change', '[data-action="toggle-status"]', function() {
    var $cb = $(this);
    var id = $cb.data('id');
    var newStatus = $cb.is(':checked') ? 'Active' : 'Inactive';
    var msg = window.erpToggleMessages || {};
    var newLabel = newStatus === 'Active'
        ? (msg.statusActive || 'Active')
        : (msg.statusInactive || 'Inactive');

    var rowData = null;
    try { rowData = window.table ? table.row($cb.closest('tr')).data() : null; } catch(e) {}
    var itemName = rowData && rowData.name ? rowData.name : '#' + id;

    erpConfirm({
        title: msg.confirmTitle || 'Change Status',
        message: (msg.confirmBody || 'Are you sure you want to change status of ":name" to :status?')
            .replace(':name', itemName).replace(':status', newLabel),
        confirmText: msg.confirmYes || 'Yes, change',
        cancelText: msg.cancelText || 'Cancel',
    }).then(function(confirmed) {
        if (!confirmed) {
            $cb.prop('checked', !$cb.is(':checked'));
            return;
        }

        var route = $cb.data('toggle-route');
        if (!route) {
            toastr.error('Toggle route not configured.', 'Error');
            $cb.prop('checked', !$cb.is(':checked'));
            return;
        }

        $cb.prop('disabled', true);
        $.ajax({
            type: 'POST',
            url: route,
            data: { _token: $('meta[name="csrf-token"]').attr('content') || '' },
            dataType: 'json',
            success: function(response) {
                if (response.status_code == 200) {
                    toastr.success(itemName + ' is now ' + newLabel, 'Success');
                } else {
                    toastr.error(response.message, 'Error');
                }
                if (window.table) table.ajax.reload(null, false);
            },
            error: function() {
                toastr.error('Something went wrong.', 'Error');
                if (window.table) table.ajax.reload(null, false);
            }
        });
    });
});

/* ── Master View Modal helper (read-only key/value list) ────────
   opts: { title, size, rows: [{label, value|html}], onClose } */
if (!window.erpMasterViewModal) {
    window.erpMasterViewModal = function (opts) {
      opts = opts || {};
      function esc(s) {
        return String(s == null ? '' : s)
          .replace(/&/g, '&amp;')
          .replace(/"/g, '&quot;')
          .replace(/</g, '&lt;');
      }
      var rows = (opts.rows || []).map(function (r) {
        return '<div class="flex items-start gap-3 py-2 border-b border-zinc-100 last:border-b-0">' +
          '<div class="w-1/3 text-xs font-medium text-zinc-500 uppercase tracking-wide pt-0.5">' + esc(r.label) + '</div>' +
          '<div class="flex-1 text-sm text-zinc-900 break-words">' + (r.html ? r.html : esc(r.value)) + '</div>' +
        '</div>';
      }).join('');
      var modal = window.erpModal({
        title: opts.title || 'Details',
        size: opts.size || 'md',
        body: '<div class="divide-y divide-zinc-100">' + rows + '</div>',
        footer: '<button class="erp-mv-close h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800">Close</button>',
        onClose: opts.onClose
      });
      modal.el.querySelector('.erp-mv-close').addEventListener('click', function () { modal.close(); });
      return modal;
    };
  }
})();
