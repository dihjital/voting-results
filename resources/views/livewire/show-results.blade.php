<div
    wire:ignore
    class="w-full p-4"
    x-data="{

        voteTexts: @entangle('vote_texts'),
        voteResults: @entangle('vote_results'),
        questionText: @entangle('question_text'),

        votes: @entangle('votes'),
        locations: @entangle('locations'),

        showSubscriptionModal: @entangle('showSubscriptionModal'),
        showUnsubscriptionModal: @entangle('showUnsubscriptionModal'),

        showTable: @entangle('showTable'),
        showMap: @entangle('showMap'),

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

                if (darkMode === 'dark') {
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
                            display: true,
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

            confirmSubscription = () => {
                requestPermission();
                console.log('Closing Subscription modal.');
                this.showSubscriptionModal = ! this.showSubscriptionModal;
                setTimeout(() => Livewire.emit('refresh-page'), 3000);              
            };

            getSubscriptionStatus = () => {
                return isTokenSentToServer() ? '{{ __('Subscribed') }}' : '{{ __('Subscribe') }}';
            };

            isSubscribed = () => {
                return isTokenSentToServer();
            }

            // Listeners
            document.getElementById('themeSelectorButton').addEventListener('click', () => {
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

            Livewire.on('request-permission', () => {
                confirmSubscription();
            });

            Livewire.on('unsubscribe', () => {
                deleteToken();
                console.log('Closing Unsubscription modal.');
                this.showUnsubscriptionModal = ! this.showUnsubscriptionModal;
                setTimeout(() => Livewire.emit('refresh-page'), 3000);              
            });
        }

    }"

>

    @if($this->hasErrorMessage())
        <x-error-page code="{{ $this->getStatusCode() }}" message="{{ $this->getErrorMessage() }}"></x-error-page>
    @else
        <!-- Slider controls -->
        <x-slider />

        <!-- Button Section -->
        <div class="hidden lg:flex items-center">
            <x-button x-show="!isSubscribed()" wire:click="$toggle('showSubscriptionModal')">
                <span>{{ __('Subscribe') }}</span>
            </x-button>
            <x-action-message class="ml-3" on="subscribed">
                {{ __('Subscribed.') }}
            </x-action-message>
            <x-button x-show="isSubscribed()" wire:click="$toggle('showUnsubscriptionModal')">
                <span>{{ __('Unsubscribe') }}</span>
            </x-button>
            <x-action-message class="ml-3" on="unsubscribed">
                {{ __('Unsubscribed.') }}
            </x-action-message>
        </div>

        <!-- Toast for QR code -->
        <div 
            x-data="{ isRight: true }"
            :class="{ 'max-w-xs': !isRight, 'max-w-none': isRight }"
            id="toast-default"
            class="hidden lg:flex fixed bottom-10 left-5 flex items-stretch bg-white rounded-lg shadow dark:text-gray-400 dark:bg-gray-800 z-50" 
            role="alert"
        >
            <button x-on:click="isRight = !isRight" class="flex-none w-10 rounded-l-lg bg-[#ADD8E6] dark:bg-yellow-200">
                <i x-show="!isRight" class="fa-solid fa-chevron-left text-gray-800"></i>
                <i x-show="isRight" class="fa-solid fa-chevron-right text-gray-800"></i>
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
                <canvas class="pt-10 mx-40" id="resultsChart" x-ref="canvas" x-show="voteResults.length > 0"></canvas>
            </div>
        </div>
        
        <!-- Table Section -->
        <div class="mt-5 md:mt-5">
            <div class="px-4 py-5 sm:w-full sm:p-6 bg-white dark:bg-gray-600 shadow sm:rounded-lg">
                <span x-on:click="showTable = ! showTable" class="cursor-pointer"><i x-bind:class="{ 'fa-rotate-180': !showTable }" class="fa-solid fa-chevron-up fa-border hover:bg-gray-600 dark:hover:bg-gray-400" style="color: lightgray; --fa-border-padding: .25em; --fa-border-radius: 25%; --fa-border-width: .15em;"></i></span>
                <span class="text-sm text-gray-400 dark:text-gray-200 font-bold uppercase px-2">{{ __('Table') }}</span>
                <div class="mx-5 lg:mx-40" x-show="voteResults.length > 0 && showTable"> <!-- Move this to an accordion -->
                    <!-- Buttons for the table -->
                    <x-button class="dark:bg-gray-400" wire:click="exportVotes" title="{{ __('Export to Excel') }}" arial-label="{{ __('Export to Excel') }}"><i class="fa-solid fa-file-export fa-sm p-1"></i></x-button>
                    <x-button class="dark:bg-gray-400 ml-1" wire:click="mailVotes" title="{{ __('E-mail results') }}" arial-label="{{ __('E-mail results') }}"><i class="fa-solid fa-envelope fa-sm p-1"></i></x-button>
                    <x-table>
                        <x-slot name="head">
                            <x-table.heading class="hidden lg:table-cell w-2/12">#</x-table.heading>
                            <x-table.heading class="w-8/12">{{ __('Vote text') }}</x-table.heading>
                            <x-table.heading class="w-2/12">{{ __('# of votes') }}</x-table.heading>
                        </x-slot>
                        <x-slot name="body">

                            <template x-for="vote in votes" :key="vote.id">
                                <x-table.row wire:loading.class.delay="opacity-75">
                                    <x-table.cell class="hidden lg:table-cell">
                                        <span class="text-xs" x-text="vote.id"></span>
                                    </x-table.cell>
                                    <x-table.cell>
                                        <span class="text-xs" x-text="vote.vote_text"></span>
                                    </x-table.cell>
                                    <x-table.cell>
                                        <span class="text-xs" x-text="vote.number_of_votes"></span>
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

        <!-- Map Section. Only visible on large screens -->
        <div class="hidden lg:flex mt-5 md:mt-5">
            <div class="px-4 py-5 sm:w-full sm:p-6 bg-white dark:bg-gray-600 shadow sm:rounded-lg">
                <span x-on:click="showMap = ! showMap" class="cursor-pointer"><i x-bind:class="{ 'fa-rotate-180': !showMap }" class="fa-solid fa-chevron-up fa-border hover:bg-gray-600 dark:hover:bg-gray-400" style="color: lightgray; --fa-border-padding: .25em; --fa-border-radius: 25%; --fa-border-width: .15em;"></i></span>
                <span class="text-sm text-gray-400 dark:text-gray-200 font-bold uppercase px-2">{{ __('Map') }}</span>
                <div class="mx-40 flex items-start space-x-5" x-show="locations.length > 0 && showMap">
                    <div id="map" style="width: 50%; height: 50vh; border-radius: 10px; overflow: hidden;"></div>
                    <div class="w-full lg:w-auto overflow-y-auto" style="height: 50vh;">
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
                </div>
            </div>
        </div>

    @endif

    <!-- Subscription Modal -->
    <x-dialog-modal wire:model="showSubscriptionModal">
        <x-slot name="title">
            {{ __('Subscribe for push notifications') }}
        </x-slot>

        <x-slot name="content">
            <p>{{ __('By pressing the Subscribe button you consent to receive push notification when a change happens in the current voting.') }}</p>
            <p class="mt-1">{{ __('Are you sure you would like to subscribe for push notifications?') }}</p>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('showSubscriptionModal')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="requestPermission" wire:loading.attr="disabled">
                {{ __('Subscribe') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>

    <!-- Unsubscribe Modal -->
    <x-dialog-modal wire:model="showUnsubscriptionModal">
        <x-slot name="title">
            {{ __('Unsubscribe from push notifications') }}
        </x-slot>

        <x-slot name="content">
            <p>{{ __('By clicking the Unsubscribe button, you will stop receiving push notifications when the voting is modified.') }}</p>
            <p class="mt-1">{{ __('Are you sure you would like to unsubscribe?') }}</p>
        </x-slot>

        <x-slot name="footer">
            <x-secondary-button wire:click="$toggle('showUnsubscriptionModal')" wire:loading.attr="disabled">
                {{ __('Cancel') }}
            </x-secondary-button>

            <x-danger-button class="ml-3" wire:click="unsubscribe" wire:loading.attr="disabled">
                {{ __('Unsubscribe') }}
            </x-danger-button>
        </x-slot>
    </x-dialog-modal>

    @push('scripts')
        <script>
            // Your web app's Firebase configuration
            const firebaseConfig = {
                apiKey: "AIzaSyCuXCsziu1yv8qMD0RcDPTM38jsz9dbc7Q",
                authDomain: "voting-client-ebb49.firebaseapp.com",
                projectId: "voting-client-ebb49",
                storageBucket: "voting-client-ebb49.appspot.com",
                messagingSenderId: "224379351613",
                appId: "1:224379351613:web:8401ff3d87b74588b97054",
                measurementId: "G-6WRGCD987R"
            };
            // Initialize Firebase
            firebase.initializeApp(firebaseConfig);

            const messaging = firebase.messaging();

            messaging.onMessage((payload) => {
                console.log('Message received. ', payload);
                Livewire.emit('refresh-chart');
                appendMessage(payload);
            });

            function resetUI() {
                clearMessages();
                messaging.getToken({vapidKey: 'BOvLS0bWuSqLtLIJAomSMlGqjpm3IEWwTk568_QrAcJcAJRsRcpcSuRkIiEfpSqRRtl_4TwZvhQD8qosrOnyZic'}).then((currentToken) => {
                    if (currentToken) {
                        console.log(currentToken);
                        sendTokenToServer(currentToken);
                        updateUIForPushEnabled(currentToken);
                    } else {
                        // Show permission request.
                        console.log('No registration token available. Request permission to generate one.');
                        // Show permission UI.
                        updateUIForPushPermissionRequired();
                        setTokenSentToServer(false);
                    }
                }).catch((err) => {
                    console.log('An error occurred while retrieving token. ', err);
                    showToken('Error retrieving registration token. ', err);
                    setTokenSentToServer(false);
                });
            }

            function showToken(currentToken) {
                //
            }

            function sendTokenToServer(currentToken) {
                // TODO: Check token validity on server .... unless it never expires
                if (!isTokenSentToServer()) {
                    console.log('Sending token to server...');
                    const data = {
                        user: '{{ Auth::id() }}',
                        token: currentToken,
                    };

                    fetch("{{ self::getURL() }}/subscribe", {
                        method: "POST",
                        headers: {
                            "Content-Type": "application/json"
                        },
                        body: JSON.stringify(data)
                    }).then(response => {
                        if (response.ok) {
                            console.log("token successfully registered");
                            setTokenSentToServer(true);
                        } else {
                            console.error("token registration failed");
                        }
                    }).catch(error => { 
                        console.error("Error: ", error);
                    });
                } else {
                    console.log('Token already sent to server so won\'t send it again ' +
                        'unless it changes');
                }
            }

            function isTokenSentToServer() {
                return getWithExpiry('{{ md5(Auth::id()) }}') === '1';
            }

            function getItemFromLocalStorage(itemName) {
                return window.localStorage.getItem(itemName) || null;
            }

            function setItemInLocalStorage(itemName, item) {
                window.localStorage.setItem(itemName, JSON.stringify(item));
            }

            function setWithExpiry(key, value, ttl) {                
                let sentToServer = JSON.parse(getItemFromLocalStorage('sentToServer'));

                const now = new Date();
                
                const item = {
                    value: value,
                    expiry: now.getTime() + ttl,
                };

                if (!sentToServer) {
                    sentToServer = {};
                }
                sentToServer[key] = item;

                setItemInLocalStorage('sentToServer', sentToServer);
            }

            function getWithExpiry(key) {
                let sentToServer = JSON.parse(getItemFromLocalStorage('sentToServer'));

                // if the item doesn't exist, return null
                if (!sentToServer) {
                    return null;
                }

                const now = new Date();

                if (!(key in sentToServer)) {
                    return null;
                }

                // Compare the expiry time of the item with the current time
                if (now.getTime() > sentToServer[key].expiry) {
                    delete sentToServer.key;
                    setItemInLocalStorage('sentToServer', sentToServer);
                    deleteToken();
                    return null;
                }

                return sentToServer[key].value;
            }

            function setTokenSentToServer(sent) {
                const oneWeek = 3600000 * 24 * 7;

                setWithExpiry('{{ md5(Auth::id()) }}', sent ? '1' : '0', oneWeek);
            }

            function showHideDiv(divId, show) {
                //
            }

            function requestPermission() {
                console.log('Requesting permission...');
                Notification.requestPermission().then((permission) => {
                    if (permission === 'granted') {
                        console.log('Notification permission granted.');
                        // TODO(developer): Retrieve a registration token for use with FCM.
                        // In many cases once an app has been granted notification permission,
                        // it should update its UI reflecting this.
                        resetUI();
                    } else {
                        console.log('Unable to get permission to notify.');
                    }
                });
            }

            function deleteToken() {
                // Delete registration token.
                messaging.getToken().then((currentToken) => {
                    messaging.deleteToken(currentToken).then(() => {
                        console.log('Deleting token from server...');
                        const data = {
                            user: '{{ Auth::id() }}',
                        };
                        
                        fetch("{{ self::getURL() }}/unsubscribe", {
                            method: "DELETE",
                            headers: {
                                "Content-Type": "application/json"
                            },
                            body: JSON.stringify(data)
                        }).then(response => {
                            if (response.ok) {
                                console.log("token successfully deleted");
                                setTokenSentToServer(false);
                            } else {
                                console.error("token deletion failed");
                            }
                        }).catch(error => {
                            console.error("Error: ", error);
                        });

                        // console.log('Token deleted.');
                        // setTokenSentToServer(false);
                        // Once token is deleted update UI.
                        // resetUI();
                        
                    }).catch((err) => {
                        console.log('Unable to delete token. ', err);
                    });
                }).catch((err) => {
                    console.log('Error retrieving registration token. ', err);
                    showToken('Error retrieving registration token. ', err);
                });
            }

            function appendMessage(payload) {
                //
            }

            function clearMessages() {
                //
            }

            function updateUIForPushEnabled(currentToken) {
                //
            }

            function updateUIForPushPermissionRequired() {
                //
            }

        </script>
    @endpush
</div>
