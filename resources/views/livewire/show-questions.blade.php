<div class="w-full p-4">

    @if($this->hasErrorMessage())
        <x-error-page code="{{ $this->getStatusCode() }}" message="{{ $this->getErrorMessage() }}"></x-error-page>
    @else
    <!--  Cards Section Only visible on small screens -->
    <div class="lg:hidden mt-5 md:mt-5">
        @forelse($questions as $q)
            <a href="/questions/{{ $q['id'] }}/votes" class="block max-w-sm p-6 mt-5 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700">
                @if($q['is_closed'])
                    <x-icons.lock class="text-2xl" title="{{ __('This question is closed') }}" />
                @endif

                @if($q['belongs_to_quiz'])
                    <x-icons.trophy class="ml-2 text-2xl" title="{{ __('This question belongs to a quiz') }}" />
                @endif
                
                <h5 class="mb-2 text-2xl font-bold tracking-tight text-gray-900 dark:text-white">
                    {{ $q['question_text'] }}
                </h5>

                <p class="font-normal text-gray-700 dark:text-gray-400">
                    @php
                        $carbonDate = $q['last_vote_at']
                            ? \Carbon\Carbon::parse($q['last_vote_at'])
                            : null; 
                        $humanReadable = $carbonDate?->diffForHumans();
                    @endphp
                    {{ __('Last voted at') }}: {{ $humanReadable ?? __('Never') }}
                </p>
            </a>
        @empty
        @endforelse
    </div>

    <x-table class="hidden lg:table">
        <x-slot name="head">
            <x-table.heading class="hidden md:table-cell w-1/12"></x-table.heading>
            <x-table.heading class="w-8/12">{{ __('Question text') }}</x-table.heading>
            <x-table.heading class="hidden lg:table-cell w-auto">{{ __('# of answers') }}</x-table.heading>
            <x-table.heading class="hidden lg:table-cell w-auto">{{ __('Last voting') }}</x-table.heading>
        </x-slot>
        <x-slot name="body">
            @forelse($questions as $q)
            <x-table.row 
                wire:loading.class.delay="opacity-75" 
                wire:key="row-{{ $q['id'] }}"
                @class([
                    "bg-gray-200 dark:bg-gray-400" => $q['is_closed'],
                ])
            >
                <x-table.cell class="hidden md:table-cell">
                    @if($q['is_closed'])
                        <x-icons.lock title="{{ __('This question is closed') }}" />
                    @endif
                    @if($q['belongs_to_quiz'])
                        <x-icons.trophy class="ml-2" title="{{ __('This question belongs to a quiz') }}" />
                    @endif
                </x-table.cell>
                <x-table.cell>
                    @if($q['is_closed'])
                    <span class="md:hidden mr-5">
                        <i class="fa-solid fa-lock text-gray-600 dark:text-gray-500"></i>
                    </span>
                    @endif
                    <a href="/questions/{{ $q['id'] }}/votes">
                        {{ $q['question_text'] }}
                    </a>
                </x-table.cell>
                <x-table.cell class="hidden lg:table-cell">{{ $q['number_of_votes'] }}</x-table.cell>
                <x-table.cell class="hidden lg:table-cell text-sm font-medium space-x-2">
                    @php
                        $carbonDate = $q['last_vote_at']
                            ? \Carbon\Carbon::parse($q['last_vote_at'])
                            : null; 
                        $humanReadable = $carbonDate?->diffForHumans();
                    @endphp
                    {{ $humanReadable ?? __('Never') }}
                </x-table.cell>
            </x-table.row>
            @empty
            <x-table.row wire:key="row-empty">
                <x-table.cell colspan="4" class="whitespace-nowrap">
                    <div class="flex justify-center items-center">
                        <span class="py-8 text-base text-center font-medium text-gray-400 uppercase">{{ __('There are no questions in the database') }} ...</span>
                    </div>
                </x-table.cell>
            </x-table.row>
            @endforelse
        </x-slot>
    </x-table>

    <div class="mt-4">
        @if(self::PAGINATING)
            {{ $questions->links() }}
        @endif
    </div>

    @endif

</div>
