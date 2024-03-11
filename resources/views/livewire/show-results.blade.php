<div
    wire:ignore
    class="w-full p-4"
    x-data="{

        voteTexts: @entangle('vote_texts'),
        voteResults: @entangle('vote_results'),
        highestVote: @entangle('highestVote'),
        questionText: @entangle('question_text'),

        votes: @entangle('votes'),
        locations: @entangle('locations'),

        showLocationDetailsModal: false,

        showTable: @entangle('showTable'),
        showMap: @entangle('showMap'),

        isMobile: false,

        qrCodeImg: @entangle('qrCodeImg'),

        init() {

            const map = L.map('map').setView([51.505, -0.09], 13); // Set initial map view

            const noData = {
                id: 'emptyChart',
                afterDraw(chart, args, options) {
                    const { datasets } = chart.data;
                    let hasData = false;
            
                    for (let dataset of datasets) {
                        //set this condition according to your needs
                        if (dataset.data.length > 0 && dataset.data.some(item => item !== 0)) {
                            hasData = true;
                            break;
                        }
                    }
            
                    if (!hasData) {
                        //type of ctx is CanvasRenderingContext2D
                        //https://developer.mozilla.org/en-US/docs/Web/API/CanvasRenderingContext2D
                        //modify it to your liking
                        const { chartArea: { left, top, right, bottom }, ctx } = chart;
                        const centerX = (left + right) / 2;
                        const centerY = (top + bottom) / 2;
            
                        chart.clear();
                        ctx.save();
                        ctx.textAlign = 'center';
                        ctx.textBaseline = 'middle';
                        ctx.font = 'normal 16px sans-serif';
                        ctx.fillStyle = '#9CA3AF';
                        ctx.fillText('{{ __('No data to display') }}'.toUpperCase(), centerX, centerY);
                        ctx.restore();
                    }
                }
            };

            const layerRegistry = {};

            const addNamedLayer = (name, layer) => {
                layerRegistry[name] = layer;
                layer.addTo(map);
            };

            const isLayerAdded = (name) => {
                return !!layerRegistry[name] && map.hasLayer(layerRegistry[name]);
            };

            const initMap = () => {
                tileLayer = L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 19,
                });
                addNamedLayer('TileLayer', tileLayer);

                const pinnedLocations = 
                    this.locations.map(location => [location.latitude, location.longitude]);

                const bounds = L.latLngBounds(pinnedLocations); // Calculate the bounding box
                map.fitBounds(bounds); // Adjust map view to show all pinned locations

                // Loop through the pinned locations and add markers to the map
                pinnedLocations.forEach(location => L.marker(location).addTo(map));
            };
            this.locations.length > 0 && initMap();

            const refreshMap = () => {
                const allMarkers = [];

                const addLocationToMap = (latitude, longitude) => {
                    const marker = L.marker([latitude, longitude]).addTo(map);
                    allMarkers.push(marker);
                };

                const reZoomMapToFitMarkers = () => {
                    const group = new L.featureGroup(allMarkers);
                    map.fitBounds(group.getBounds());
                };

                if (isLayerAdded('TileLayer')) {
                    this.locations.forEach(location => addLocationToMap(location.latitude, location.longitude));
                    reZoomMapToFitMarkers(); // Re-zoom the map to fit all markers
                } else {
                    initMap();
                }
            };

            const changeChartColorScheme = () => {
                const x = chart.config.options.scales.x;
                const y = chart.config.options.scales.y;

                const colorTheme = localStorage.getItem('color-theme')
                    ? localStorage.getItem('color-theme')
                    : 'dark'; 

                if (colorTheme === 'dark') {
                    Chart.defaults.color = 'white';
                    chart.config.data.datasets[0].backgroundColor = '#FDE68A'; // yellow-200
                    x.ticks.color = y.ticks.color = 'lightgray';
                    x.border.color = y.border.color = 'white';
                } else {
                    Chart.defaults.color = 'black';
                    chart.config.data.datasets[0].backgroundColor = 'lightblue';
                    x.ticks.color = y.ticks.color = 'gray';
                    x.border.color = y.border.color = 'gray';
                }
                
                chart.update();
            };
    
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
                            grid: {
                                display: false, // Remove x-axis grid
                            },
                            border: {
                                color: 'gray',
                                width: .1
                            },
                            type: 'linear',
                            ticks: {
                                stepSize: 1,
                                precision: 0,
                            },
                        },
                        y: {
                            ticks: {
                                callback: function(value, index, values) {
                                    labelValue = this.getLabelForValue(value);
                                    return labelValue.length > 15
                                        ? labelValue.substr(0, 15) + '...'
                                        : labelValue;
                                },
                            },
                            grid: {
                                display: false, // Remove y-axis grid,
                            },
                            border: {
                                color: 'gray',
                                width: .1
                            },
                            beginAtZero: true,
                        }
                    },
                    plugins: {
                        title: {
                            display: false, // Hide the title
                            text: `${this.questionText}` + ' (' + this.voteResults.reduce((a, b) => a + b) + ')',
                            position: 'top',
                            font: {
                                weight: 'bold',
                                size: 16
                            },
                        },
                        legend: {
                            display: false // Hide the legend
                        },
                    },
                    indexAxis: 'y',
                },
                plugins: [noData],
            });

            changeChartColorScheme();

            $watch('isMobile', (value) => {
                this.showTable = this.showTable || value;
            });

            // Listeners
            document.getElementById('theme-toggle').addEventListener('click', () => {
                changeChartColorScheme();
            });

            Livewire.on('chart-refreshed', () => {
                chart.data.labels = this.voteTexts;
                chart.data.datasets[0].data = this.voteResults;
                chart.options.plugins.title.text = `${this.questionText}` + ' (' + this.voteResults.reduce((a, b) => a + b) + ')';
                chart.update();

                // Refresh map
                this.locations.length > 0 && refreshMap();
            });
        }

    }"

>

    @if($this->hasErrorMessage())
        <x-error-page code="{{ $this->getStatusCode() }}" message="{{ $this->getErrorMessage() }}"></x-error-page>
    @else
        <!-- Slider controls -->
        <x-slider />

        <!-- Question header -->
        <div class="py-4 flex justify-center items-center">
            <h3
                x-text="`${questionText}` + ' (' + voteResults.reduce((a, b) => a + b) + ')'"
                class="text-lg text-center font-medium text-gray-900 dark:text-gray-100">
            </h3>
        </div>
    
        <!-- Toast for QR code -->
        <div 
            x-data="{ isRight: true }"
            :class="{ 'max-w-xs': !isRight, 'max-w-none': isRight }"
            id="toast-default"
            class="hidden lg:flex fixed bottom-10 right-5 flex items-stretch bg-white rounded-lg shadow dark:text-gray-400 dark:bg-gray-800 z-50" 
            role="alert"
        >
            <button x-on:click="isRight = !isRight" class="flex-none w-10 rounded-l-lg bg-[#ADD8E6] dark:bg-yellow-200">
                <i x-show="isRight" class="fa-solid fa-chevron-left text-gray-800"></i>
                <i x-show="!isRight" class="fa-solid fa-chevron-right text-gray-800"></i>
            </button>
            <div
                x-show="!isRight"
                x-text="'{{ __('Scan this QR code to vote for') }} ' + questionText"
                class="p-4 flex text-sm font-medium items-center text-gray-800 dark:text-gray-200 bg-white dark:bg-gray-800"
            >
            </div>
            <!-- QR Code Block -->
            <div class="rounded-r-lg bg-white dark:bg-gray-800 p-2 font-normal flex-none flex items-center justify-center">
                <img class="w-24 h-24" x-bind:src="'data:image/png;base64,' + qrCodeImg" x-bind:alt="'{{ __('QR code for web based voting client for') }} ' + questionText">
            </div>
        </div>
        
        <!-- Chart Section -->
        <div class="hidden lg:block mt-5 md:mt-5">
            <div class="px-4 py-5 sm:p-6 bg-white dark:bg-gray-600 shadow sm:rounded-lg">
                <canvas class="mx-40" id="resultsChart" x-ref="canvas" x-show="voteResults.length > 0"></canvas>
            </div>
        </div>
       
        <!-- Table Section Only visible on large screens -->
        <div class="hidden lg:flex mt-5 md:mt-5">
            <div x-screen="isMobile = ($width < 1024)" class="px-4 py-5 sm:w-full sm:p-6 bg-white dark:bg-gray-600 shadow sm:rounded-lg">
                <span x-on:click="showTable = ! showTable" class="cursor-pointer"><i x-bind:class="{ 'fa-rotate-180': !showTable }" class="fa-solid fa-chevron-up fa-border hover:bg-gray-600 dark:hover:bg-gray-400" style="color: lightgray; --fa-border-padding: .25em; --fa-border-radius: 25%; --fa-border-width: .15em;"></i></span>
                <span class="text-sm text-gray-400 dark:text-gray-200 font-bold uppercase px-2">{{ __('Table') }}</span>
                <span x-show="votes.length > 0"><i class="fa-solid fa-table text-sm text-gray-400 dark:text-gray-200"></i></span>
                <div class="mx-5 lg:mx-40 mt-5" x-show="voteResults.length > 0 && showTable"> <!-- Move this to an accordion -->
                    <!-- Buttons for the table -->
                    <x-button class="dark:bg-gray-400" wire:click="exportVotes" title="{{ __('Export to Excel') }}" arial-label="{{ __('Export to Excel') }}"><i class="fa-solid fa-file-export fa-sm p-1"></i></x-button>
                    <x-button class="dark:bg-gray-400 ml-1" wire:click="mailVotes" title="{{ __('E-mail results') }}" arial-label="{{ __('E-mail results') }}"><i class="fa-solid fa-envelope fa-sm p-1"></i></x-button>
                    <x-table>
                        <x-slot name="head">
                            <x-table.heading class="hidden lg:table-cell w-auto">{{ __('Visual') }}</x-table.heading>
                            <x-table.heading class="w-4/12">{{ __('Vote text') }}</x-table.heading>
                            <x-table.heading class="w-auto">{{ __('# of votes') }}</x-table.heading>
                        </x-slot>
                        <x-slot name="body">

                            <template x-for="vote in votes" :key="vote.id">
                                <x-table.row wire:loading.class.delay="opacity-75">
                                    <x-table.cell class="hidden lg:table-cell">
                                        <div class="w-64 h-32 lg:w-128 overflow-hidden border-2 border-gray-200 rounded-lg dark:border-gray-700 hover:bg-gray-50"
                                            x-show="vote.image_path">
                                            <img class="
                                                    w-full h-full 
                                                    object-scale-down 
                                                    transition duration-300 ease-in-out 
                                                    hover:scale-125" 
                                                :src="vote.image_url" alt="Thumbnail" />
                                        </div>
                                        <i class="fa-regular fa-image text-sm text-gray-400 dark:text-gray-200" x-show="! vote.image_path"></i>
                                    </x-table.cell>
                                    <x-table.cell>
                                        <span class="text-xs" x-text="vote.vote_text"></span>
                                    </x-table.cell>
                                    <x-table.cell>
                                        <span class="text-xs mr-2" x-text="vote.number_of_votes"></span>
                                        <i 
                                            class="fa-solid fa-champagne-glasses text-sm text-gray-400 dark:text-gray-200" 
                                            x-show="vote.number_of_votes != 0 && highestVote == vote.number_of_votes">
                                        </i>
                                    </x-table.cell>
                                </x-table.row>
                            </template>

                            <x-table.row wire:key="row-empty" x-show="!votes.length">
                                <x-table.cell colspan="4" class="whitespace-nowrap">
                                    <div class="flex justify-center items-center">
                                        <span class="py-8 text-base text-center font-medium text-gray-400 uppercase">{{ __('There are no votes for this question in the database') }} ...</span>
                                    </div>
                                </x-table.cell>
                            </x-table.row>

                        </x-slot>
                    </x-table>
                </div>
            </div>
        </div>

        <!--  Cards Section Only visible on small screens -->
        <div class="lg:hidden mt-5 md:mt-5">
            <template x-for="vote in votes" :key="vote.id">
                <a href="#" class="block max-w-sm p-6 mt-5 bg-white border border-gray-200 rounded-lg shadow hover:bg-gray-100 dark:bg-gray-800 dark:border-gray-700 dark:hover:bg-gray-700">
                    <i 
                        class="fa-solid fa-champagne-glasses mb-2 text-2xl text-gray-400 dark:text-gray-200" 
                        x-show="vote.number_of_votes != 0 && highestVote == vote.number_of_votes">
                    </i>

                    <div class="w-full h-full relative">
                        <div class="p-2">
                            <div class="w-full flex justify-center mb-2 overflow-hidden border-2 border-gray-200 rounded-lg dark:border-gray-700 hover:bg-gray-50"
                                    x-show="vote.image_path">
                                    <img class="
                                            w-full h-full 
                                            object-scale-down 
                                            transition duration-300 ease-in-out 
                                            hover:scale-125" 
                                        :src="vote.image_url" alt="Thumbnail" />
                            </div>

                            <h5 class="text-2xl font-bold tracking-tight text-gray-900 dark:text-white"
                                x-text="vote.vote_text + ': ' + vote.number_of_votes">
                            </h5>

                            {{-- <p class="font-normal text-gray-700 dark:text-gray-400"
                                x-text="'{{ __('Number of votes') }}' + ': ' + vote.number_of_votes">
                            </p> --}}
                        </div>
                        <!-- Background color to indicate vote -->
                        <div class="absolute top-0 left-0 bottom-0 bg-gray-400 rounded-lg opacity-25" x-bind:style="{ width: ((vote.number_of_votes / voteResults.reduce((a, b) => a + b)) * 100) + '%' }"></div>
                    </div>
                </a>
            </template>
        </div>

        <!-- Map Section. Only visible on large screens -->
        <div class="hidden lg:flex mt-5 md:mt-5">
            <div class="px-4 py-5 sm:w-full sm:p-6 bg-white dark:bg-gray-600 shadow sm:rounded-lg">
                <span x-on:click="showMap = ! showMap" class="cursor-pointer"><i x-bind:class="{ 'fa-rotate-180': !showMap }" class="fa-solid fa-chevron-up fa-border hover:bg-gray-600 dark:hover:bg-gray-400" style="color: lightgray; --fa-border-padding: .25em; --fa-border-radius: 25%; --fa-border-width: .15em;"></i></span>
                <span class="text-sm text-gray-400 dark:text-gray-200 font-bold uppercase px-2">{{ __('Map') }}</span>
                <span x-show="locations.length > 0">
                    <button wire:click="$toggle('showLocationDetailsModal')">
                        <i class="fa-solid fa-location-dot text-sm text-gray-400 dark:text-gray-200"></i>
                    </button>
                </span>
                <div class="mx-5 lg:mx-40 mt-5 flex flex-col" x-show="locations.length > 0 && showMap">
                    <div id="map" class="flew-grow w-full rounded-lg z-0" style="height: 50vh; overflow: hidden;"></div>
                </div>
            </div>
        </div>

    @endif

    <!-- Location Details Modal -->
    <x-dialog-modal wire:model="showLocationDetailsModal">
        <x-slot name="title">
            {{ __('Voter locations details') }}
        </x-slot>

        <x-slot name="content">
            <div class="w-full">
                <x-table>
                    <x-slot name="head">
                        <x-table.heading class="w-4/12">{{ __('Country') }}</x-table.heading>
                        <x-table.heading class="w-4/12">{{ __('City') }}</x-table.heading>
                        <x-table.heading class="w-4/12">{{ __('# of votes') }}</x-table.heading>
                    </x-slot>
                    <x-slot name="body">
                        <template x-for="location in locations" :key="location.id">
                            <x-table.row wire:loading.class.delay="opacity-75">
                                <x-table.cell>
                                    <span class="text-xs" x-text="location.country_name"></span>
                                </x-table.cell>
                                <x-table.cell>
                                    <span class="text-xs" x-text="location.city"></span>
                                </x-table.cell>
                                <x-table.cell>
                                    <span class="text-xs" x-text="location.vote_count"></span>
                                </x-table.cell>
                            </x-table.row>
                        </template>
                    </x-slot>
                </x-table>
            </div>  
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('showLocationDetailsModal')" wire:loading.attr="disabled">
                {{ __('OK') }}
            </x-secondary-button>
        </x-slot>
    </x-dialog-modal>

</div>
