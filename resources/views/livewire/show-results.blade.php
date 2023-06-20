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
        <x-button onclick="requestPermission()">Request permission</x-button>
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
                if (!isTokenSentToServer()) {
                    console.log('Sending token to server...');
                    // TODO(developer): Send the current token to your server.
                    const data = {
                        token: currentToken
                    };

                    fetch("http://localhost:8000/subscribe", {
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
                return window.localStorage.getItem('sentToServer') === '1';
            }

            function setTokenSentToServer(sent) {
                window.localStorage.setItem('sentToServer', sent ? '1' : '0');
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
                        console.log('Token deleted.');
                        setTokenSentToServer(false);
                        // Once token is deleted update UI.
                        resetUI();
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

            // resetUI();
        </script>
    @endpush
</div>
