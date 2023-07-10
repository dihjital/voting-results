<div
    wire:ignore
    class="w-full p-4"
    x-data="{

        voteTexts: @entangle('vote_texts'),
        voteResults: @entangle('vote_results'),
        questionText: @entangle('question_text'),
        showSubscriptionModal: @entangle('showSubscriptionModal'),
        showUnsubscriptionModal: @entangle('showUnsubscriptionModal'),

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
                            ticks: {
                                callback: function(value, index, values) {
                                    labelValue = this.getLabelForValue(value);
                                    return labelValue.length > 15
                                        ? labelValue.substr(0, 15) + '...'
                                        : labelValue;
                                }
                            },
                            grid: {
                                display: false // Remove y-axis grid,
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
                        }
                    },
                    indexAxis: 'y',
                }
            });
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
            Livewire.on('chart-refreshed', () => {
                chart.data.labels = this.voteTexts;
                chart.data.datasets[0].data = this.voteResults;
                chart.options.plugins.title.text = `${this.questionText}` + ' (' + this.voteResults.reduce((a, b) => a + b) + ')';
                chart.update();
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

    @if($error_message)
        <p class="text-lg text-center font-medium text-red-500">{{ $error_message }}</p>
    @else
        <div class="flex items-center">
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
        <canvas class="pt-10 mx-40" id="resultsChart" x-ref="canvas"></canvas>
        <x-section-border />
        <div class="mx-40 pb-10">
            <x-table>
                <x-slot name="head">
                    <x-table.heading class="bg-blue-300 dark:bg-blue-400 w-2/12">{{ __('Vote #') }}</x-table.heading>
                    <x-table.heading class="bg-blue-300 dark:bg-blue-400 w-8/12">{{ __('Vote text') }}</x-table.heading>
                    <x-table.heading class="bg-blue-300 dark:bg-blue-400 w-2/12">{{ __('# of votes received') }}</x-table.heading>
                </x-slot>
                <x-slot name="body">
                    @forelse($votes as $v)
                    <x-table.row wire:loading.class.delay="opacity-75" wire:key="row-{{ $v['id'] }}">
                        <x-table.cell>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $v['id'] }}
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $v['vote_text'] }}
                            </div>
                        </x-table.cell>
                        <x-table.cell>
                            <div class="text-sm text-gray-600 dark:text-gray-400">
                                <span x-text="voteResults[{{ $loop->index }}]"></span>
                            </div>
                        </x-table.cell>
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
                        token: currentToken
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
                        console.log('Deleting token from server...');
                        
                        fetch("{{ self::getURL() }}/unsubscribe", {
                            method: "DELETE",
                            headers: {
                                "Content-Type": "application/json"
                            },
                        }).then(response => {
                            if (response.ok) {
                                console.log("Token successfully deleted");
                                setTokenSentToServer(false);
                            } else {
                                console.error("Token deletion failed");
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
