<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Illuminate\Support\Facades\Http;

class ShowResults extends Component
{

    public $question_id;
    public $question_text;

    public $votes;
    public $vote_texts;
    public $vote_results;

    public $locations;

    public $error_message;

    public $showSubscriptionModal = false;
    public $showUnsubscriptionModal = false;

    protected $listeners = [
        'refresh-chart' => 'refreshChart',
        'refresh-page'  => '$refresh',
    ];

    const URL = 'http://localhost:8000';

    public function mount($question_id)
    {
        $this->question_id = $question_id;
        // Get the question text ...
        try {
            $url = self::getURL().'/questions/'.$this->question_id;
            
            $response = Http::get($url)->throwUnlessStatus(200)->json();               
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }
        $this->question_text = $response['question_text'];
        $this->fetchData();
    }

    public static function getURL()
    {
        return env('API_ENDPOINT', self::URL);
    }

    public function requestPermission()
    {
        $this->emit('request-permission');
        $this->emit('subscribed'); // Show action message
    }

    public function unsubscribe()
    {
        $this->emit('unsubscribe');
        $this->emit('unsubscribed'); // Show action message        
    }

    public function refreshChart()
    {
        $this->fetchData();
    }

    public function fetchData(): void
    {
        try {
            $url = self::getURL().'/questions/'.$this->question_id.'/votes';
            
            $response = Http::get($url)->throwUnlessStatus(200)->json();               
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }

        $this->fetchLocations();

        $this->votes = $response;
        $this->vote_texts = $this->getVoteTexts($response);
        $this->vote_results = $this->getVoteResults($response) ?: [0]; // Client side can reduce this
        $this->emit('chart-refreshed');
    }

    public function fetchLocations(): void
    {
        try {
            $url = self::getURL().'/questions/'.$this->question_id.'/votes/locations';

            $response = Http::get($url)->throwUnlessStatus(200)->json();
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }

        $this->locations = $response;
        // TODO: If response is empty then handle it at the client side ...
    }

    public function getVoteTexts($results): array
    {
        return array_map(
            fn($vote) => $vote['vote_text'], $results
        );
    }

    public function getVoteResults($results): array
    {
        return array_map(
            fn($vote) => $vote['number_of_votes'], $results
        );
    }

}
