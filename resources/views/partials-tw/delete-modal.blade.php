{{-- Global Delete Modal Content — rendered as hidden template, used by delete.js via erpModal() --}}
<template id="globalDeleteModalTemplate">
    <form id="globalDeleteForm" action="" method="POST">
        @csrf
        @method('DELETE')

        <p class="text-sm text-zinc-700">Are you sure you want to delete ?</p>

        {{-- Server errors --}}
        <div id="globalDeleteErrors" class="rounded-lg border border-red-200 bg-red-50 p-3 mt-4" style="display: none;">
            <ul class="text-sm text-red-700 space-y-1" id="globalDeleteErrorList"></ul>
        </div>
    </form>
</template>
