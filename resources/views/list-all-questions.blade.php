<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 dark:text-gray-200 leading-tight">
            {{ __('List of questions') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-xl sm:rounded-lg">
                @livewire('show-questions')
            </div>

            <!-- This is a legend for all the icons we use for questions in the list page //-->
            <div class="hidden lg:flex flex-wrap space-x-6 text-sm ml-2 mt-4 text-gray-500 dark:text-gray-400">
                <div class="flex items-center space-x-2">
                    <x-icons.lock />
                    <span>{{ __('The question is closed') }}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <x-icons.trophy />
                    <span>{{ __('The question belongs to a quiz') }}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <x-icons.secure />
                    <span>{{ __('A valid e-mail address is required to vote') }}</span>
                </div>
                <div class="flex items-center space-x-2">
                    <x-icons.show-votes />
                    <span>{{ __('Current votes will NOT be shown during voting') }}</span>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>