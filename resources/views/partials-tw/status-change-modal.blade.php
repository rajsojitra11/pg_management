{{--
    Global Status Change Modal — Tailwind Theme

    Usage: @include('partials-tw.status-change-modal')
    JS: Triggered by status-with-remarks.js via .change-status-with-remarks button class
--}}

<div id="globalStatusChangeModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50 status-modal-close"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-md rounded-lg border border-zinc-200 bg-white shadow-xl">
            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="globalStatusChangeModalLabel">
                    <span id="statusChangeModalTitle">{{ __('lang.common.confirm_status_change') ?: 'Confirm Status Change' }}</span>
                </h3>
                <button class="text-zinc-400 hover:text-zinc-600 status-modal-close">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="globalStatusChangeForm" action="" method="POST">
                @csrf

                <div class="p-4">
                    {{-- Info Alert --}}
                    <div id="statusChangeAlertContainer">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 mb-4">
                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                                <div class="text-sm text-blue-800">
                                    <strong>{{ __('lang.common.note') ?: 'Note:' }}</strong>
                                    <span id="statusChangeMessage">{{ __('lang.common.status_change_warning') ?: 'Please provide a reason for this status change.' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Remarks --}}
                    @include('partials-tw.remarks-field', [
                        'type' => 'custom',
                        'required' => true,
                        'fieldId' => 'global_status_user_remark',
                        'fieldName' => 'user_remark',
                        'colSize' => 'w-full',
                        'rows' => 3,
                        'label' => 'Reason for Status Change',
                        'placeholder' => 'Please provide a reason for this status change...',
                    ])

                    {{-- Errors --}}
                    <div id="globalStatusChangeErrors" class="rounded-lg border border-red-200 bg-red-50 p-3" style="display: none;">
                        <ul class="text-sm text-red-700 space-y-1" id="globalStatusChangeErrorList"></ul>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                    <button type="button"
                            class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center status-modal-close">
                        {{ __('lang.common.cancel') ?: 'Cancel' }}
                    </button>
                    <button type="submit" id="globalStatusChangeSubmitBtn"
                            class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center">
                        <i class="fa-solid fa-check mr-1.5 text-xs"></i>
                        <span id="statusChangeButtonText">{{ __('lang.common.confirm') ?: 'Confirm' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
