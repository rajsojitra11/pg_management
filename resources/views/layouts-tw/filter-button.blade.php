{{-- Filter Action Buttons — Tailwind Theme --}}

@if(!empty($search))
<button class="h-9 px-4 rounded-md bg-zinc-900 text-white text-sm font-medium hover:bg-zinc-800 whitespace-nowrap inline-flex items-center search" type="button" title="Search">
    <i class="fa-solid fa-magnifying-glass mr-1.5 text-xs"></i> Search
</button>
@endif

@if(!empty($export))
<button class="h-9 px-4 rounded-md bg-emerald-600 text-white text-sm font-medium hover:bg-emerald-700 whitespace-nowrap inline-flex items-center export" type="submit" title="Download" data-route="{{ $export }}">
    <i class="fa-solid fa-download mr-1.5 text-xs"></i> Export
</button>
@endif

@if(!empty($reset))
<button class="h-9 px-4 rounded-md border border-zinc-200 bg-white text-sm font-medium text-zinc-700 hover:bg-zinc-50 whitespace-nowrap inline-flex items-center reset" type="reset" title="Clear">
    <i class="fa-solid fa-xmark mr-1.5 text-xs"></i> Reset
</button>
@endif
