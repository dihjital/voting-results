<div
    wire:ignore
    class="w-full p-4"
    x-data="{

        voteTexts: @entangle('vote_texts'),
        voteResults: @entangle('vote_results'),
        questionText: @entangle('question_text'),

        init() {
            const ctx = this.$refs.canvas;

            let chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: this.voteTexts,
                    datasets: [{
                        label: '# of Votes',
                        data: this.voteResults,
                        borderWidth: 0,
                        barThickness: 30,
                    }]
                },
                options: {
                    layout: {
                        padding: {
                            top: 0 // Set the desired padding value in pixels
                        }
                    },
                    scales: {
                        x: {
                            ticks: {
                                callback: function(value, index, ticks_array) {
                                    let characterLimit = 5;
                                    let label = this.getLabelForValue(value);
                                    return label.substring(0, characterLimit) + '...';
                                }
                            },
                            grid: {
                                display: false // Remove x-axis grid
                            },
                            type: 'linear',
                            ticks: {
                                stepSize: 1,
                                precision: 0
                            },
                        },
                        y: {
                            grid: {
                                display: false // Remove y-axis grid,
                            },
                            beginAtZero: true,
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: `${this.questionText}` + ' (' + this.voteResults.reduce((a, b) => a +b) + ')',
                            position: 'top',
                            font: {
                                weight: 'bold',
                                size: 16
                            },
                        },
                        legend: {
                            display: false // Hide the legend
                        }
                    },
                    indexAxis: 'y',
                }
            });
            Livewire.on('chart-refreshed', () => {
                chart.data.labels = this.voteTexts;
                chart.data.datasets[0].data = this.voteResults;
                chart.update();
            });
        }

    }"

>

    @if($error_message)
        <p class="text-lg text-center font-medium text-red-500">{{ $error_message }}</p>
    @else
        <x-button wire:click="$emit('refresh-chart')">Refresh chart</x-button>
        <canvas class="pt-10 mx-40" id="resultsChart" x-ref="canvas"></canvas>
        <x-section-border />
        <div class="mx-40 pb-10">
            <x-table>
                <x-slot name="head">
                    <x-table.heading class="w-2/12">{{ __('Vote number') }}</x-table.heading>
                    <x-table.heading class="w-6/12">{{ __('Vote text') }}</x-table.heading>
                    <x-table.heading class="w-2/12">{{ __('Number of votes received') }}</x-table.heading>
                </x-slot>
                <x-slot name="body">
                    @forelse($votes as $v)
                    <x-table.row wire:loading.class.delay="opacity-75" wire:key="row-{{ $v['id'] }}">
                        <x-table.cell>{{ $v['id'] }}</x-table.cell>
                        <x-table.cell>{{ $v['vote_text'] }}</x-table.cell>
                        <x-table.cell>{{ $v['number_of_votes'] }}</x-table.cell>
                    </x-table.row>
                    @empty
                    <x-table.row wire:key="row-empty">
                        <x-table.cell colspan="4" class="whitespace-nowrap">
                            <div class="flex justify-center items-center">
                                <span class="py-8 text-base text-center font-medium text-gray-400 uppercase">{{ __('There are no votes for this questions in the database') }} ...</span>
                            </div>
                        </x-table.cell>
                    </x-table.row>
                    @endforelse
                </x-slot>
            </x-table>
        </div>
    @endif
    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    @endpush
</div>
