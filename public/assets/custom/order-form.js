/* ──────────────────────────────────────────────────────────────────
   OrderForm — uses jQuery Repeater plugin (jquery.repeater.min.js)
   and the project's shared save.js / update.js submit handlers.

   Scope of this file:
   - Populate every <select data-lookup="X"> from window.URL_LOOKUP[X]
   - Initialise erpSearchSelect (multi on machines, single elsewhere)
   - Init jQuery Repeater on:
       * #paperRepeater          → papers[i][...]
       * #printingJobsRepeater   → printing_jobs[i][...]
       * .pp-repeater (×4)       → post_press[cat][i][...]
   - Refresh A/B/C labels on printing-job cards after add/remove
   - Multi-machine cascade: hide #sec-plate, show .pj-machine-plate-row
     inside every card, populate per-card machine select
   - Live sheets calc, otherCoating toggle, plate-washing Yes/No label
   - Quick-add modals for paper/finish/gsm/size/jobsize/format
   - Edit-mode rehydration from window.ORDER_FORM_DATA

   NOT in this file:
   - Form submission (delegated to public/assets/custom/save.js + update.js
     via class="save" / class="update" on the bottom button)
   ──────────────────────────────────────────────────────────────────── */

(function ($) {
    'use strict';

    var LOOKUPS = window.URL_LOOKUP || {};
    var MODE = window.ORDER_FORM_MODE || 'create';
    var INITIAL = window.ORDER_FORM_DATA || null;

    var lookupCache = {};

    // Quick-add modal config from the inline <script id="quick-add-config">
    var QUICK_ADD = {};
    try {
        var raw = document.getElementById('quick-add-config');
        if (raw) QUICK_ADD = JSON.parse(raw.textContent || raw.innerText || '{}');
    } catch (e) { QUICK_ADD = {}; }

    /* ── Lookup fetch + populate ─────────────────────────────── */
    // opts: { cacheKey, data } — cacheKey lets a single lookup endpoint be cached
    // under several buckets (e.g. post-press per category_slug); data is merged
    // into the request query.
    function fetchLookup(key, force, opts) {
        opts = opts || {};
        var cacheKey = opts.cacheKey || key;
        if (!force && lookupCache[cacheKey]) {
            return $.Deferred().resolve(lookupCache[cacheKey]).promise();
        }
        var url = LOOKUPS[key];
        if (!url) return $.Deferred().resolve([]).promise();
        var data = $.extend({ limit: 50 }, opts.data || {});
        return $.ajax({ url: url, type: 'GET', dataType: 'json', data: data })
            .then(function (rows) { lookupCache[cacheKey] = rows || []; return lookupCache[cacheKey]; });
    }

    // Post-press selects carry data-pp-category — narrow the shared post-press
    // lookup to that parent category. Returns fetchLookup opts (or null).
    function lookupFetchOpts($select) {
        var ppCat = $select.data('pp-category');
        if (!ppCat) return null;
        var key = $select.data('lookup');
        return { cacheKey: key + ':' + ppCat, data: { category_slug: ppCat } };
    }

    function populateSelect($select, options) {
        options = options || {};
        var key = $select.data('lookup');
        if (!key) return;
        var el = $select[0];
        var preserveValue = options.preserveValue ? $select.val() : null;
        // Show a loading circle on the trigger while the master list loads.
        if (el._erpSelectInst && el._erpSelectInst.setLoading) el._erpSelectInst.setLoading(true);
        fetchLookup(key, options.force, lookupFetchOpts($select)).done(function (rows) {
            $select.find('option[value!=""]').remove();
            rows.forEach(function (r) { $select.append(new Option(r.label, r.value, false, false)); });
            if (preserveValue) $select.val(preserveValue);
            // If this select is already an erpSearchSelect, refresh its searchable
            // list to mirror the freshly-loaded native options. This must run for
            // multiselects too (#machine_ids): the component reads options at init,
            // so a lookup that resolves after init would otherwise leave it empty.
            var inst = el._erpSelectInst;
            if (inst) {
                var newOpts = rows.map(function (r) { return { value: String(r.value), label: r.label }; });
                if (el.multiple) {
                    var keep = $select.val() || []; // current selection (array)
                    inst.setOptions(newOpts, true);
                    // setOptions clears native <option>.selected for multiselects —
                    // re-assert so the value still round-trips on submit.
                    keep.forEach(function (v) {
                        for (var i = 0; i < el.options.length; i++) {
                            if (String(el.options[i].value) === String(v)) el.options[i].selected = true;
                        }
                    });
                    if (keep.length) inst.setValue(keep.map(String), true);
                } else {
                    inst.setOptions(newOpts, true);
                    if (preserveValue) inst.setValue(preserveValue, true);
                }
            }
            if (inst && inst.setLoading) inst.setLoading(false);
        });
    }

    function populateAllLookups($scope) {
        $scope.find('select[data-lookup]').each(function () { populateSelect($(this)); });
    }

    function refreshLookupSelects(key) {
        fetchLookup(key, true).done(function () {
            $('select[data-lookup="' + key + '"]').each(function () {
                populateSelect($(this), { preserveValue: true });
            });
        });
    }

    /* ── erpSearchSelect init / strip ─────────────────────── */
    // Builds an onRefresh callback (renders the in-dropdown sync ↻ button) that
    // re-fetches the master list for a data-lookup select and hands back options.
    // Category-aware: post-press selects re-fetch only their parent category.
    function lookupRefresh($select) {
        var key = $select.data('lookup');
        if (!key) return null;
        var fetchOpts = lookupFetchOpts($select);
        return function (cb) {
            fetchLookup(key, true, fetchOpts).done(function (rows) {
                cb((rows || []).map(function (r) { return { value: String(r.value), label: r.label }; }));
            });
        };
    }

    function initErpSelects($scope) {
        // Single selects — drop-in init (skips disabled / already-wrapped).
        $scope.find('select').not('#machine_ids').each(function () {
            var el = this;
            if (el.disabled) return;
            if (el._erpSelectWrapper && el._erpSelectWrapper.parentNode) return;
            var placeholder = ($(el).find('option[value=""]').first().text() || 'Select');
            initErpSelect(el, {
                placeholder: placeholder,
                allowClear: !el.required,
                onRefresh: lookupRefresh($(el)) // sync button on lookup selects
            });
        });
        // #machine_ids is a multiselect — use raw erpSearchSelect. (initErpSelect's
        // onChange does `el.value = array`, which corrupts a <select multiple>.)
        // The component dispatches a native `change`, so the delegated
        // $form.on('change', '#machine_ids', onMachineChange) drives the cascade.
        var mEl = $scope.find('#machine_ids')[0];
        if (mEl && !(mEl._erpSelectWrapper && mEl._erpSelectWrapper.parentNode)) {
            mEl._erpSelectInst = erpSearchSelect(mEl, {
                multiple: true,
                options: getOptionsFromSelect(mEl),
                placeholder: 'Select machines',
                allowClear: false,
                onRefresh: lookupRefresh($(mEl))
            });
        }
    }

    function stripErpSelects($scope) {
        // Removes wrappers + body-appended dropdowns for all selects in scope
        // (used before re-init on repeater clones to avoid orphaned dropdowns).
        cleanupErpSelect($scope[0]);
    }

    /* ── Repeater show/hide callbacks ─────────────────────── */
    function onRepeaterShow() {
        var $item = $(this);
        // The plugin clones the first item; strip stale erpSearchSelect wrappers, reset values.
        stripErpSelects($item);
        $item.find('select').each(function () {
            this.selectedIndex = 0;
            this._erpSelectInst = null; // drop the cloned (now-dead) instance ref
        });
        $item.find('input[type="text"], input[type="number"], input[type="date"]').val('');
        $item.find('input[type="checkbox"]').prop('checked', false);
        // Disable Other Coating text input by default on a fresh card.
        $item.find('[data-role="otherCoating"]').prop('disabled', true).val('');
        // Repopulate every lookup-driven select inside the new item.
        populateAllLookups($item);
        setTimeout(function () { initErpSelects($item); }, 50);
        // Slide in.
        $item.slideDown();
    }

    function onRepeaterHide(deleteElement) {
        $(this).slideUp(deleteElement);
    }

    /* ── Post-press duplicate-type filter ──────────────────── */
    // Prevents the same post-press type from being selected in multiple rows
    // within the same category (lamination / postpress / process / uv).

    function getPpFullOpts($col) {
        var opts = $col.data('ppFullOpts');
        if (opts) return opts;
        opts = [];
        $col.find('select[data-pp-category]').each(function () {
            Array.from(this.options).forEach(function (opt) {
                if (opt.value && !opts.some(function (o) { return o.value === opt.value; })) {
                    opts.push({ value: opt.value, label: opt.textContent.trim() });
                }
            });
        });
        // Only cache if we got actual options — an empty array means the AJAX
        // lookup hasn't resolved yet; don't cache it, otherwise the filter will
        // permanently see no options and never deduplicate.
        if (opts.length) {
            $col.data('ppFullOpts', opts);
        }
        return opts;
    }

    function applyPostPressFilter($col) {
        var fullOpts = getPpFullOpts($col);
        if (!fullOpts.length) return;
        $col.find('select[data-pp-category]').each(function () {
            var el = this;
            var inst = el._erpSelectInst;
            if (!inst) return;
            var currentVal = $(this).val();
            var othersTaken = {};
            $col.find('select[data-pp-category]').each(function () {
                if (this === el) return;
                var v = $(this).val();
                if (v) othersTaken[String(v)] = true;
            });
            var allowed = fullOpts.filter(function (o) {
                return !othersTaken[String(o.value)];
            });
            inst.setOptions(allowed, true);
        });
    }

    /* ── Post-press + / - toggle ─────────────────────────── */
    function refreshPostPressButtons() {
        $('.pp-col').each(function () {
            var $rows = $(this).find('.pp-row');
            $rows.each(function (i) {
                var isLast = i === $rows.length - 1;
                var isFirst = i === 0;
                $(this).find('.pp-btn-add').toggle(isLast);
                $(this).find('.pp-btn-rm').toggle(!isLast);
            });
        });
    }

    /* ── Printing-job letter rebadging ─────────────────────── */
    function refreshJobLetters() {
        $('#printingJobs > .printing-job-card').each(function (i) {
            var letter = String.fromCharCode(65 + i);
            $(this).find('.job-letter').text('— ' + letter);
            $(this).find('.job-letter-row').text(letter);
        });
    }

    /* Hide the per-card delete (−) button while only one card remains. */
    function refreshJobDeleteButtons() {
        var $cards = $('#printingJobs > .printing-job-card');
        $cards.find('.job-delete').toggle($cards.length > 1);
    }

    /* ── Multi-machine cascade ─────────────────────────────── */
    function onMachineChange() {
        var selected = $('#machine_ids').val() || [];
        var multiMode = selected.length > 1;

        if (multiMode) {
            $('#sec-plate').hide();
            $('.pj-machine-plate-row').show();

            var machinesCache = lookupCache['machines'] || [];
            var idToLabel = {};
            machinesCache.forEach(function (m) { idToLabel[String(m.value)] = m.label; });

            $('.pj-machine').each(function () {
                var el = this;
                var prev = el._erpSelectInst ? el._erpSelectInst.getValue() : el.value;
                cleanupErpSelect(el);          // strip prior wrapper + body dropdown
                el._erpSelectInst = null;
                el.innerHTML = '';
                el.add(new Option('-- Select Machine --', '', false, false));
                var opts = [];
                selected.forEach(function (id) {
                    var lbl = idToLabel[String(id)] || ('#' + id);
                    el.add(new Option(lbl, String(id), false, false));
                    opts.push({ value: String(id), label: lbl });
                });
                var inst = erpSearchSelect(el, {
                    options: opts,
                    placeholder: '-- Select Machine --',
                    allowClear: false,
                    onChange: function (val) { el.value = val; }
                });
                el._erpSelectInst = inst;
                if (prev && selected.indexOf(String(prev)) !== -1) {
                    inst.setValue(String(prev), true);
                    el.value = String(prev);
                }
            });
        } else {
            $('#sec-plate').show();
            $('.pj-machine-plate-row').hide();
        }
    }

    /* ── Sheets calc, otherCoating, plate-washing ─────────── */
    function onSheetsInput($input) {
        var $card = $input.closest('.printing-job-card');
        var f = parseInt($card.find('[data-role="finalSheets"]').val(), 10) || 0;
        var w = parseInt($card.find('[data-role="wastage"]').val(), 10) || 0;
        $card.find('[data-role="totalSheets"]').val(f + w);
    }

    function onOtherCoatingToggle($chk) {
        var $input = $chk.closest('.printing-job-card').find('[data-role="otherCoating"]');
        if ($chk.is(':checked')) $input.prop('disabled', false).focus();
        else $input.prop('disabled', true).val('');
    }

    function onPlateWashingToggle($chk, $label) {
        if ($chk.is(':checked')) $label.text('Yes').removeClass('text-zinc-500').addClass('text-emerald-700');
        else $label.text('No').removeClass('text-emerald-700').addClass('text-zinc-500');
    }

    /* ── Quick-add modal ──────────────────────────────────── */
    function openQuickAddModal(master) {
        var cfg = QUICK_ADD[master];
        if (!cfg) return;
        if (typeof window.erpModal !== 'function') { alert('Modal helper missing'); return; }

        var bodyHtml = ''
            + '<div class="space-y-3 p-1">'
            +   '<div>'
            +     '<label class="block text-xs font-medium text-zinc-500 mb-1">Name <span class="text-red-500">*</span></label>'
            +     '<input type="text" class="qa-name w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm" placeholder="Type name" required>'
            +     '<p class="qa-err text-xs text-red-500 mt-1 hidden"></p>'
            +   '</div>'
            +   '<div>'
            +     '<label class="block text-xs font-medium text-zinc-500 mb-1">Status</label>'
            +     '<select class="qa-status w-full h-9 rounded-md border border-zinc-200 bg-white px-3 text-sm">'
            +       '<option value="Active" selected>Active</option>'
            +       '<option value="Inactive">Inactive</option>'
            +     '</select>'
            +   '</div>'
            + '</div>';

        var footerHtml = ''
            + '<button class="qa-cancel inline-flex items-center justify-center h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50"><i class="fa-solid fa-xmark mr-1.5 text-xs"></i> Cancel</button>'
            + '<button class="qa-save inline-flex items-center justify-center h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800"><i class="fa-solid fa-check mr-1.5 text-xs"></i> Save</button>';

        var qaStatusEl;
        var modal = window.erpModal({
            title: cfg.title || 'Quick Add', size: 'sm', body: bodyHtml, footer: footerHtml,
            // erpSearchSelect appends its dropdown to <body>; clean it up on every
            // close path (Cancel / Save / X) so it isn't orphaned.
            onClose: function () { if (qaStatusEl) cleanupErpSelect(qaStatusEl); }
        });
        var $modal = $(modal.el);

        // Render the Status dropdown as a searchable select. No dropdownParent
        // needed — the dropdown is body-appended with position:fixed (no clipping).
        qaStatusEl = $modal.find('.qa-status')[0];
        if (qaStatusEl) initErpSelect(qaStatusEl, { placeholder: 'Status' });

        $modal.find('.qa-cancel').on('click', function () { modal.close(); });
        $modal.find('.qa-save').on('click', function () {
            var $name = $modal.find('.qa-name'), $status = $modal.find('.qa-status'), $err = $modal.find('.qa-err');
            var name = ($name.val() || '').trim();
            $err.addClass('hidden').text('');
            if (!name) { $err.removeClass('hidden').text('Name is required.'); $name.focus(); return; }
            var $save = $modal.find('.qa-save').prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin mr-1.5 text-xs"></i> Saving…');
            $.ajax({
                url: cfg.storeRoute,
                type: 'POST',
                data: {
                    name: name, status: $status.val(),
                    _token: $('meta[name="csrf-token"]').attr('content') || $('input[name="_token"]').first().val()
                },
                dataType: 'json',
                headers: { 'Accept': 'application/json' }
            }).done(function (resp) {
                if (resp.status_code === 200) {
                    if (window.toastr) toastr.success(resp.message || (cfg.title + ' saved'), 'Success');
                    refreshLookupSelects(cfg.lookup);
                    modal.close();
                } else {
                    if (window.toastr) toastr.warning(resp.message || 'Save failed', 'Warning');
                    $save.prop('disabled', false).html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> Save');
                }
            }).fail(function (xhr) {
                var msg = (xhr.responseJSON && xhr.responseJSON.message) || 'Save failed';
                if (window.toastr) toastr.error(msg, 'Error');
                $save.prop('disabled', false).html('<i class="fa-solid fa-check mr-1.5 text-xs"></i> Save');
            });
        });
    }

    /* ── Edit-mode rehydration ────────────────────────────── */
    // Sets a value SILENTLY (no change event) once the option exists, retrying
    // while AJAX-loaded options arrive. The one cascade that matters (machines)
    // is driven by the explicit setTimeout(onMachineChange) in rehydrate.
    function setErpValue($select, value, attempt) {
        if (!$select.length || value == null || value === '') return;
        var el = $select[0];
        attempt = attempt || 0;
        var isArr = Array.isArray(value);
        var hasOption = isArr
            ? value.every(function (v) { return $select.find('option[value="' + v + '"]').length > 0; })
            : $select.find('option[value="' + value + '"]').length > 0;
        if (!hasOption && attempt < 10) {
            setTimeout(function () { setErpValue($select, value, attempt + 1); }, 200);
            return;
        }
        var inst = el._erpSelectInst;
        if (inst) {
            inst.setValue(isArr ? value.map(String) : String(value), true);
            // setValue does not set native <option>.selected flags for multiselects;
            // do it so $(el).val() and form submit round-trip the array.
            if (isArr && el.multiple) $select.val(value.map(String));
        } else {
            $select.val(value);
        }
    }

    function addRow(repeaterSelector) {
        $(repeaterSelector).find('[data-repeater-create]').first().trigger('click');
    }

    function rehydrateFromInitial() {
        var data = INITIAL || {};

        if (data.order_no)        $('#orderNoPreview').text(data.order_no);
        if (data.job_name)        $('#job_name').val(data.job_name);
        if (data.delivery_detail) $('[name="delivery_detail"]').val(data.delivery_detail);
        if (data.notes)           $('[name="notes"]').val(data.notes);
        setErpValue($('#client_id'), data.client_id);
        setErpValue($('#year_id'),   data.year_id);
        setErpValue($('#machine_ids'), (data.machine_ids || []).map(String));

        // Paper rows
        var papers = data.papers || [];
        for (var i = 1; i < papers.length; i++) addRow('#paperRepeater');
        $('#paperList > .paper-block').each(function (idx) {
            var p = papers[idx]; if (!p) return;
            var $row = $(this);
            setErpValue($row.find('select[data-lookup="papers"]'),        p.paper_id);
            setErpValue($row.find('select[data-lookup="paper-finishes"]'), p.paper_finish_id);
            setErpValue($row.find('select[data-lookup="paper-gsms"]'),     p.paper_gsm_id);
            setErpValue($row.find('select[data-lookup="sheet-sizes"]'),    p.sheet_size_id);
            $row.find('input[name$="[qty]"], input[name="qty"]').val(p.qty);
            setErpValue($row.find('select[data-lookup="vendors"]'),        p.vendor_id);
            $row.find('input[name$="[vendor_note]"], input[name="vendor_note"]').val(p.vendor_note || '');
        });

        // Printing job cards
        var jobs = data.printing_jobs || [];
        for (var j = 1; j < jobs.length; j++) addRow('#printingJobsRepeater');
        $('#printingJobs > .printing-job-card').each(function (idx) {
            var job = jobs[idx]; if (!job) return;
            var $card = $(this);
            $card.find('input[name$="[job_description]"], input[name="job_description"]').val(job.job_description || '');
            setErpValue($card.find('select[data-lookup="printings"]'),         job.print_type_id);
            setErpValue($card.find('.paper-grid select[data-lookup="papers"]'),         job.paper_id);
            setErpValue($card.find('.paper-grid select[data-lookup="paper-finishes"]'), job.paper_finish_id);
            setErpValue($card.find('.paper-grid select[data-lookup="paper-gsms"]'),     job.paper_gsm_id);
            setErpValue($card.find('.paper-grid select[data-lookup="sheet-sizes"]'),    job.sheet_size_id);
            $card.find('.paper-grid input[name$="[qty]"], .paper-grid input[name="qty"]').val(job.qty);
            setErpValue($card.find('select[data-lookup="job-sizes"]'),         job.job_size_id);
            setErpValue($card.find('select[data-lookup="printing-formats"]'),  job.printing_format_id);
            $card.find('input[name$="[no_of_jobs]"], input[name="no_of_jobs"]').val(job.no_of_jobs);
            $card.find('input[name$="[final_sheets]"], input[name="final_sheets"]').val(job.final_sheets);
            $card.find('input[name$="[wastage_sheets]"], input[name="wastage_sheets"]').val(job.wastage_sheets);
            $card.find('input[name$="[total_sheets]"], input[name="total_sheets"]').val(job.total_sheets);
            setErpValue($card.find('select[data-lookup="paper-coatings"]'),    job.paper_coating_id);
            $card.find('input[name$="[other_coating]"], input[name="other_coating"]').val(job.other_coating || '');
            $card.find('input[name$="[cutting_details]"], input[name="cutting_details"]').val(job.cutting_details || '');
            setErpValue($card.find('select.pj-machine'),                       job.machine_id);
            setErpValue($card.find('select[data-lookup="plate-details"]'),     job.plate_detail_id);
            $card.find('.pj-plate-washing').prop('checked', !!job.plate_washing);
            if (job.other_coating) {
                $card.find('[data-role="otherCoatingChk"]').prop('checked', true);
                $card.find('[data-role="otherCoating"]').prop('disabled', false);
            }
            onPlateWashingToggle($card.find('.pj-plate-washing'), $card.find('.pj-plate-washing-label'));
        });

        // Header plate detail
        if (data.plate_detail) {
            setErpValue($('#sec-plate select[name="plate_detail[plate_detail_id]"]'), data.plate_detail.plate_detail_id);
            $('#plateWashing').prop('checked', !!data.plate_detail.plate_washing);
            onPlateWashingToggle($('#plateWashing'), $('#plateWashingLabel'));
        }

        // Post-press
        var pp = data.post_press || {};
        ['lamination', 'postpress', 'process', 'uv'].forEach(function (cat) {
            var rows = pp[cat] || [];
            var $repeater = $('.pp-repeater[data-postpress="' + cat + '"]');
            for (var k = 1; k < rows.length; k++) {
                $repeater.find('[data-repeater-create]').first().trigger('click');
            }
            $repeater.find('.pp-row').each(function (idx) {
                var row = rows[idx]; if (!row) return;
                setErpValue($(this).find('select[data-pp-category="' + cat + '"]'), row.post_press_id);
            });
        });

        setTimeout(onMachineChange, 200);
        refreshJobLetters();
        refreshJobDeleteButtons();
        refreshPostPressButtons();
        // Apply post-press filter synchronously for rows that already have values.
        $('.pp-col').each(function () { applyPostPressFilter($(this)); });
        // Re-apply after setErpValue retries (200ms) + initErpSelects (50ms) settle.
        setTimeout(function () {
            $('.pp-col').each(function () { applyPostPressFilter($(this)); });
        }, 600);
    }

    /* ── Section tabs: smooth scroll + scroll spy ────────── */
    function initSectionTabs($form) {
        var $tabs = $('#ofTabs .of-tab');
        if (!$tabs.length) return;

        function setActive(id) {
            $tabs.removeClass('active').filter('[href="' + id + '"]').addClass('active');
        }

        $tabs.on('click', function (e) {
            e.preventDefault();
            var id = $(this).attr('href');
            var $target = $(id);
            if (!$target.length) return;
            setActive(id);
            var offset = $target.offset().top - 100;
            $('html, body').animate({ scrollTop: offset }, 250);
        });

        // Resolve each tab to its target section element (skip missing anchors).
        var sections = $tabs.map(function () {
            return document.querySelector($(this).attr('href'));
        }).get().filter(Boolean);
        if (!sections.length) return;

        // Activation line measured from the top of the viewport — sits just below
        // the fixed top bar + sticky tabs (~56px header + ~46px tabs ≈ 150px).
        var ACTIVATION_LINE = 150;

        // Pick the last section whose top has scrolled above the activation line.
        // Uses getBoundingClientRect (viewport-relative) so it works no matter
        // which element actually scrolls — window or an inner overflow container.
        function spy() {
            var current = '#' + sections[0].id;
            sections.forEach(function (el) {
                if (el.getBoundingClientRect().top <= ACTIVATION_LINE) current = '#' + el.id;
            });
            setActive(current);
        }

        // IntersectionObserver fires on scroll regardless of the scroll container,
        // covering layouts where window 'scroll' never fires.
        if ('IntersectionObserver' in window) {
            var io = new IntersectionObserver(spy, { threshold: [0, 1] });
            sections.forEach(function (el) { io.observe(el); });
        }
        // Fallback / continuous tracking — capture phase catches scrolls on any container.
        window.addEventListener('scroll', spy, true);
        spy();
    }

    /* ── Save & Print handler ───────────────────────────── */
    function initSavePrint() {
        $(document).on('click', '.save-print', function () {
            var $btn = $(this);
            var $form = $('#orderForm');
            var route = $btn.data('route');
            if (!$form.length || !route) return;

            if (typeof validateFormFields === 'function' && validateFormFields($form, false).length > 0) {
                if (typeof setButtonError === 'function') setButtonError($btn);
                return false;
            }

            var formData = new FormData($form[0]);

            $.ajax({
                type: 'POST',
                url: route,
                data: formData,
                dataType: 'json',
                cache: false,
                contentType: false,
                processData: false,
                headers: { 'Accept': 'application/json' },
                beforeSend: function () {
                    if (typeof setButtonLoading === 'function') setButtonLoading($btn);
                },
                success: function (response) {
                    if (response.status_code === 200 || response.status_code === 201) {
                        if (window.toastr) toastr.success(response.message || 'Saved', 'Success');
                        _safeUnblock();
                        setTimeout(function () {
                            if (response.data && typeof response.data === 'string') {
                                location.href = response.data;
                            } else {
                                location.href = route.replace(/\/orderforms$/, '/orderforms');
                            }
                        }, 800);
                    } else {
                        _safeUnblock();
                        if (typeof setButtonError === 'function') setButtonError($btn);
                        if (typeof showFormError === 'function') showFormError($form, response.message || 'Save failed');
                    }
                },
                error: function (xhr) {
                    _safeUnblock();
                    if (typeof setButtonError === 'function') setButtonError($btn);
                    if (typeof handleAjaxErrors === 'function') handleAjaxErrors($form, xhr);
                }
            });
        });
    }

    /* ── Bootstrap ───────────────────────────────────────── */
    $(function () {
        var $form = $('#orderForm');
        if (!$form.length) return;

        // 1. Populate every lookup-driven select on first render.
        populateAllLookups($form);

        // 2. Init searchable selects after options load.
        setTimeout(function () { initErpSelects($form); }, 300);

        // 3. Init section tabs.
        initSectionTabs($form);

        // 4. Init save & print handler.
        initSavePrint();

        // 5. Init repeaters.
        if ($.fn.repeater) {
            $('#paperRepeater').repeater({
                show: onRepeaterShow,
                hide: onRepeaterHide,
                isFirstItemUndeletable: true
            });

            $('#printingJobsRepeater').repeater({
                show: function () {
                    onRepeaterShow.call(this);
                    refreshJobLetters();
                    refreshJobDeleteButtons();
                    onMachineChange();
                },
                hide: function (deleteElement) {
                    // At least one printing-job card must always remain.
                    if ($('#printingJobs > .printing-job-card').length <= 1) {
                        if (window.toastr) toastr.warning('This card cannot be deleted.');
                        return;
                    }
                    var el = this;
                    $(el).slideUp(function () {
                        deleteElement();
                        refreshJobLetters();
                        refreshJobDeleteButtons();
                    });
                },
                isFirstItemUndeletable: false
            });

            $('.pp-repeater').each(function () {
                $(this).repeater({
                    show: function () {
                        onRepeaterShow.call(this);
                        refreshPostPressButtons();
                        var $col = $(this).closest('.pp-col');
                        setTimeout(function () { applyPostPressFilter($col); }, 200);
                    },
                    hide: function (deleteElement) {
                        var $col = $(this).closest('.pp-col');
                        var el = this;
                        $(el).slideUp(function () {
                            deleteElement();
                            refreshPostPressButtons();
                            applyPostPressFilter($col);
                        });
                    },
                    isFirstItemUndeletable: false
                });
            });

            // Delegate + clicks inside pp-items to the hidden external data-repeater-create button.
            $form.on('click', '.pp-btn-add', function () {
                $(this).closest('.pp-repeater').find('.pp-create-trigger').trigger('click');
            });
        }

        // 6. Multi-machine cascade.
        $form.on('change', '#machine_ids', onMachineChange);

        // 7. Sheets calc (delegated).
        $form.on('input', '[data-role="finalSheets"], [data-role="wastage"]', function () {
            onSheetsInput($(this));
        });

        // 8. Other Coating toggle.
        $form.on('change', '[data-role="otherCoatingChk"]', function () {
            onOtherCoatingToggle($(this));
        });

        // 9. Plate-washing label flip — header + per-card.
        $form.on('change', '#plateWashing', function () {
            onPlateWashingToggle($(this), $('#plateWashingLabel'));
        });
        $form.on('change', '.pj-plate-washing', function () {
            onPlateWashingToggle($(this), $(this).siblings('.pj-plate-washing-label'));
        });

        // 10. Post-press type dedup filter (delegated).
        $form.on('change', 'select[data-pp-category]', function () {
            applyPostPressFilter($(this).closest('.pp-col'));
        });

        // 11. Live error clearing — hide validation messages as soon as the
        //     user fixes the field. Works for static fields and repeater rows.
        function clearFieldError($f) {
            var name = $f.attr('name') || '';
            var cid = 'error_' + name.replace(/[\[\]]/g, '_').replace(/__+/g, '_').replace(/_$/, '');
            $('#' + cid).html('');
            $f.removeClass('border-red-500');
            $f.next('.erp-field-error').remove();
            $f.next('.erp-select-wrapper').next('.erp-field-error').remove();
            var $wrapper = $f.next('.erp-select-wrapper');
            if ($wrapper.length) $wrapper.children().first().removeClass('border-red-500');
            var $s2 = $f.next('.select2-container');
            if ($s2.length) {
                $s2.find('.select2-selection').removeClass('border-red-500');
                $s2.next('.erp-field-error').remove();
            }
        }
        $form.on('input', 'input[type="text"], input[type="number"], input[type="email"], input[type="tel"], input[type="date"], textarea', function () {
            clearFieldError($(this));
        });
        $form.on('change', 'select, input[type="checkbox"], input[type="radio"]', function () {
            clearFieldError($(this));
        });

        // 12. Quick-add (delegated so cloned cards work too).
        $form.on('click', '.pj-quick-add', function () {
            openQuickAddModal($(this).data('master'));
        });

        // Initial cascade pass — keep things tidy on load.
        setTimeout(onMachineChange, 400);

        // Initial post-press button state.
        setTimeout(refreshPostPressButtons, 500);

        // Initial printing-job delete (−) button visibility.
        setTimeout(refreshJobDeleteButtons, 500);


        // Edit/show-mode rehydration. Defer so lookup loads + repeater init have settled.
        if ((MODE === 'edit' || MODE === 'show') && INITIAL) {
            setTimeout(rehydrateFromInitial, 700);
        }

        // Global safety pass: re-apply post-press filter after everything is settled
        // (catches edge cases where setErpValue retries or initErpSelects delay the
        // value being committed).
        setTimeout(function () {
            $('.pp-col').each(function () { applyPostPressFilter($(this)); });
        }, 2500);
    });

})(jQuery);
