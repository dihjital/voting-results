<button type="button" class="hidden lg:flex fixed left-0 top-1/2 transform -translate-y-1/2 z-30 items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" wire:click="caruselPrev()">
    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-400/30 dark:bg-gray-400/30 group-hover:bg-gray-400/50 dark:group-hover:bg-gray-400/60 group-focus:ring-4 group-focus:ring-white dark:group-focus:ring-gray-400/70 group-focus:outline-none">
        <svg class="w-4 h-4 text-white dark:text-gray-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 1 1 5l4 4"/>
        </svg>
        <span class="sr-only">{{ __('Previous') }}</span>
    </span>
</button>
<button type="button" class="hidden lg:flex fixed right-0 top-1/2 transform -translate-y-1/2 z-30 items-center justify-center h-full px-4 cursor-pointer group focus:outline-none" wire:click="caruselNext()">
    <span class="inline-flex items-center justify-center w-10 h-10 rounded-full bg-gray-400/30 dark:bg-gray-400/30 group-hover:bg-gray-400/50 dark:group-hover:bg-gray-400/60 group-focus:ring-4 group-focus:ring-white dark:group-focus:ring-gray-400/70 group-focus:outline-none">
        <svg class="w-4 h-4 text-white dark:text-gray-800" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 6 10">
            <path stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m1 9 4-4-4-4"/>
        </svg>
        <span class="sr-only">{{ __('Next') }}</span>
    </span>
</button>