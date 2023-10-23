<div class="w-full p-4">

    @if($this->hasErrorMessage())
        <x-error-page code="{{ $this->getStatusCode() }}" message="{{ $this->getErrorMessage() }}"></x-error-page>
    @else
    <x-table>
        <x-slot name="head">
            <x-table.heading class="w-1/12">#</x-table.heading>
            <x-table.heading class="w-1/12"></x-table.heading>
            <x-table.heading class="w-6/12">{{ __('Question text') }}</x-table.heading>
            <x-table.heading class="w-2/12">{{ __('# of answers') }}</x-table.heading>
            <x-table.heading class="w-2/12">{{ __('Last voting') }}</x-table.heading>
        </x-slot>
        <x-slot name="body">
            @forelse($questions as $q)
            <x-table.row wire:loading.class.delay="opacity-75" 
                         wire:key="row-{{ $q['id'] }}"
                         @class([
                            "bg-yellow-100 dark:bg-yellow-100" => $q['is_closed'],
                         ])>
                <x-table.cell>{{ $q['id'] }}</x-table.cell>
                <x-table.cell>
                    @if($q['is_closed'])
                    <i class="fa-solid fa-lock"></i>
                    @endif
                </x-table.cell>
                <x-table.cell>
                    <div class="flex flex-col">
                        @if($q['is_closed'])
                        <span class="text-sm font-medium text-gray-600 dark:text-gray-500 uppercase">
                            {{ __('This question is closed for modification!') }}
                        </span>
                        @endif
                        <a href="/questions/{{ $q['id'] }}/votes">
                            {{ $q['question_text'] }}
                        </a>
                    </div>
                </x-table.cell>
                <x-table.cell>{{ $q['number_of_votes'] }}</x-table.cell>
                <x-table.cell class="text-sm font-medium space-x-2">
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
