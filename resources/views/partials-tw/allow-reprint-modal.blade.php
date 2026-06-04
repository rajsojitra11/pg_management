{{--
    Global Allow Reprint Modal — Tailwind Theme

    Usage: @include('partials-tw.allow-reprint-modal')
    JS: Triggered by allow-reprint.js via .allow-re-print button class
--}}

<div id="globalAllowReprintModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="$('#globalAllowReprintModal').addClass('hidden')"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-md rounded-lg border border-zinc-200 bg-white shadow-xl">
            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="globalAllowReprintModalLabel">
                    {{ __('lang.common.allow_reprint') ?: 'Allow Reprint' }}
                </h3>
                <button class="text-zinc-400 hover:text-zinc-600" onclick="$('#globalAllowReprintModal').addClass('hidden')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="globalAllowReprintForm" action="" method="POST">
                @csrf
                <input type="hidden" name="id" id="global_reprint_id" value="">
                <input type="hidden" name="type" id="global_reprint_type" value="">

                <div class="p-4">
                    {{-- Warning --}}
                    <div class="rounded-lg border border-amber-200 bg-amber-50 p-3 mb-4">
                        <div class="flex items-start gap-2">
                            <i class="fa-solid fa-triangle-exclamation text-amber-500 mt-0.5"></i>
                            <div class="text-sm text-amber-800">
                                <strong>{{ __('lang.common.warning') ?: 'Warning:' }}</strong>
                                {{ __('lang.common.allow_reprint_warning') ?: "You won't be able to revert this! Please provide a reason." }}
                            </div>
                        </div>
                    </div>

                    {{-- Reason (Required) --}}
                    @include('partials-tw.remarks-field', [
                        'type' => 'custom',
                        'required' => true,
                        'fieldId' => 'global_reprint_reason',
                        'fieldName' => 'reason',
                        'colSize' => 'w-full',
                        'rows' => 3,
                        'label' => 'Reason (Description)',
                        'placeholder' => 'Please provide a reason for allowing reprint...',
                    ])

                    {{-- Errors --}}
                    <div id="globalAllowReprintErrors" class="rounded-lg border border-red-200 bg-red-50 p-3" style="display: none;">
                        <ul class="text-sm text-red-700 space-y-1" id="globalAllowReprintErrorList"></ul>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                    <button type="button" onclick="$('#globalAllowReprintModal').addClass('hidden')"
                            class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center">
                        {{ __('lang.common.cancel') ?: 'Cancel' }}
                    </button>
                    <button type="submit" id="globalAllowReprintSubmitBtn"
                            class="h-9 px-4 rounded-md text-white text-sm font-medium whitespace-nowrap inline-flex items-center"
                            style="background-color: var(--erp-warning-bg-solid); color: #fff;"
                            onmouseover="this.style.backgroundColor='var(--erp-warning-bg-dark, #d97706)'"
                            onmouseout="this.style.backgroundColor='var(--erp-warning-bg-solid)'"
                        <i class="fa-solid fa-check mr-1.5 text-xs"></i>
                        <span>{{ __('lang.common.allow_reprint_confirm') ?: 'Yes, Allow it!' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
