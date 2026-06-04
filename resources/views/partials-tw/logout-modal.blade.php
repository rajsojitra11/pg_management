{{-- Global Logout Modal — Tailwind Theme --}}
<div id="globalLogoutModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="$('#globalLogoutModal').addClass('hidden')"></div>
    <div class="relative flex items-center justify-center min-h-screen p-4">
        <div class="relative w-full max-w-sm rounded-lg border border-zinc-200 bg-white shadow-xl">
            {{-- Header --}}
            <div class="flex items-center justify-between p-4 border-b border-zinc-200">
                <h3 class="text-lg font-semibold text-zinc-900" id="globalLogoutModalLabel">
                    {{ __('user::message.logout_confirmation_title') ?: 'Confirm Logout' }}
                </h3>
                <button class="text-zinc-400 hover:text-zinc-600" onclick="$('#globalLogoutModal').addClass('hidden')">
                    <i class="fa-solid fa-xmark"></i>
                </button>
            </div>

            <form id="globalLogoutForm" action="{{ route('logout') }}" method="POST">
                @csrf

                <div class="p-4">
                    {{-- Message --}}
                    <div id="logoutAlertContainer">
                        <div class="rounded-lg border border-blue-200 bg-blue-50 p-3 mb-4">
                            <div class="flex items-start gap-2">
                                <i class="fa-solid fa-circle-info text-blue-500 mt-0.5"></i>
                                <p class="text-sm text-blue-800">
                                    {{ __('user::message.logout_confirmation_text') ?: 'Are you sure you want to logout?' }}
                                </p>
                            </div>
                        </div>
                    </div>

                    {{-- Errors --}}
                    <div id="globalLogoutErrors" class="rounded-lg border border-red-200 bg-red-50 p-3" style="display: none;">
                        <ul class="text-sm text-red-700 space-y-1" id="globalLogoutErrorList"></ul>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="flex items-center justify-end gap-2 p-4 border-t border-zinc-200">
                    <button type="button" onclick="$('#globalLogoutModal').addClass('hidden')"
                            class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center">
                        {{ __('lang.common.cancel') ?: 'Cancel' }}
                    </button>
                    <button type="submit" id="globalLogoutSubmitBtn"
                            class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center">
                        <i class="fa-solid fa-right-from-bracket mr-1.5 text-xs"></i>
                        <span id="logoutButtonText">{{ __('user::message.Logout') ?: 'Yes, logout!' }}</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
