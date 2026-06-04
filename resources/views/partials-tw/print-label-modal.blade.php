{{--
    Global Print Label Modal — Tailwind Theme

    Usage: @include('partials-tw.print-label-modal')
    JS: Triggered by print-label.js via .print-label-action button class
--}}

<div id="globalPrintLabelModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="$('#globalPrintLabelModal').addClass('hidden')"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-md rounded-lg border border-zinc-200 bg-white shadow-xl">
            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="globalPrintLabelModalLabel">
                    <span id="printLabelModalTitle">{{ __('lang.common.print_label') ?: 'Print Label' }}</span>
                </h3>
                <button class="text-zinc-400 hover:text-zinc-600" onclick="$('#globalPrintLabelModal').addClass('hidden')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="globalPrintLabelForm" action="" method="POST" target="_blank">
                @csrf

                <div class="p-4">
                    {{-- Info Alert --}}
                    <div id="printLabelAlertContainer">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 mb-4">
                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-print text-blue-500 mt-0.5"></i>
                                <p class="text-sm text-blue-800">
                                    {{ __('lang.common.print_label_info') ?: 'The label will open in a new tab. This page will refresh automatically.' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Remarks (Optional) --}}
                    @include('partials-tw.remarks-field', [
                        'type' => 'custom',
                        'required' => false,
                        'fieldId' => 'global_print_user_remark',
                        'fieldName' => 'user_remark',
                        'colSize' => 'w-full',
                        'rows' => 2,
                        'label' => 'Remark (Optional)',
                        'placeholder' => 'Add an optional remark for this print operation...',
                    ])

                    {{-- Errors --}}
                    <div id="globalPrintLabelErrors" class="rounded-lg border border-red-200 bg-red-50 p-3" style="display: none;">
                        <ul class="text-sm text-red-700 space-y-1" id="globalPrintLabelErrorList"></ul>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                    <button type="button" onclick="$('#globalPrintLabelModal').addClass('hidden')"
                            class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center">
                        {{ __('lang.common.cancel') ?: 'Cancel' }}
                    </button>
                    <button type="submit" id="globalPrintLabelSubmitBtn"
                            class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center">
                        <i class="fa-solid fa-print mr-1.5 text-xs"></i>
                        <span id="printLabelButtonText">{{ __('lang.common.print') ?: 'Print Label' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
