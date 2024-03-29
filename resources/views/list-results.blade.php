<x-app-layout>
    <x-slot name="header">
        <div class="flex space-x-2 items-center">
            <a href="{{ route('questions') }}" title=" {{ __('Back to questions') }}" class="inline-block relative group">
                <div class="rounded-full w-12 h-12 flex items-center justify-center p-2 group-hover:bg-gray-200 group-hover:dark:bg-gray-600">
                    <i class="fa-solid fa-arrow-left text-2xl text-gray-400 dark:text-gray-200"></i>
                </div>
            </a>
            <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
                {{ __('Show voting results') }}
            </h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                @livewire('show-results', ['question_id' => $question_id])
            </div>
        </div>
    </div>
</x-app-layout>
