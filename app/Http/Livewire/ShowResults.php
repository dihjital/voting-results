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

    public $error_message;

    public $showSubscriptionModal = false;

    protected $listeners = ['refresh-chart' => 'refreshChart'];

    const URL = 'http://localhost:8000';

    public function mount($question_id)
    {
        $this->question_id = $question_id;
        // Get the question text ...
        try {
            $url = self::URL.'/questions/'.$this->question_id;
            
            $response = Http::get($url)->throwUnlessStatus(200)->json();               
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }
        $this->question_text = $response['question_text'];
        $this->fetchData();
    }

    public function requestPermission()
    {
        $this->emit('request-permission');
        $this->emit('subscribed');
    }

    public function refreshChart()
    {
        $this->fetchData();
    }

    public function fetchData(): void
    {
        try {
            $url = self::URL.'/questions/'.$this->question_id.'/votes';
            
            $response = Http::get($url)->throwUnlessStatus(200)->json();               
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        }

        $this->votes = $response;
        $this->vote_texts = $this->getVoteTexts($response);
        $this->vote_results = $this->getVoteResults($response);
        $this->emit('chart-refreshed');
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

    /* public function render()
    {
        return view('livewire.show-results', [
            'votes' => $this->fetchData(),
        ]);
    } */
}
