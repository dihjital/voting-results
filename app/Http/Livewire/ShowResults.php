<?php

namespace App\Http\Livewire;

use Livewire\Component;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

use App\Exports\VotesExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Http\Livewire\Traits\WithErrorMessage;
use App\Http\Livewire\Traits\WithOAuthLogin;
use App\Http\Livewire\Traits\WithUUIDSession;

use App\Mail\EmailVotingResults;

class ShowResults extends Component
{
    use WithOAuthLogin, WithUUIDSession, WithErrorMessage;

    public $access_token;
    public $refresh_token;

    public $question_id;
    public $question_text;

    public $votes;
    public $vote_texts;
    public $vote_results;

    public $locations;

    public $showSubscriptionModal = false;
    public $showUnsubscriptionModal = false;

    public $showTable = false;
    public $showMap = false;

    protected $listeners = [
        'refresh-chart' => 'refreshChart',
        'refresh-page'  => '$refresh',
    ];

    const URL = 'http://localhost:8000';

    public function mount($question_id)
    {
        $this->question_id = $question_id;

        // Try to log in ...
        try {
            list($this->access_token, $this->refresh_token) = $this->login();

            // Send over the current user uuid and get a session id back
            $this->registerUUIDInSession($this->access_token);

            // Get the question text ...
            try {
                $url = self::getURL().'/questions/'.$this->question_id;
                
                $response = Http::withHeaders([
                    'session-id' => $this->session_id,
                ])->get($url, [
                    'user_id' => Auth::id(), // Until it is not mandatory
                ])->throwUnlessStatus(200)->json();   

                $this->question_text = $response['question_text'];
                $this->fetchData();

                $this->showTable = session()->get($question_id.':showTable');
                $this->showMap = session()->get($question_id.':showMap');
            } catch (\Exception $e) {
                $this->error_message = $this->parseErrorMessage($e->getMessage());
            }
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public function updatedshowTable()
    {
        session()->put($this->question_id.':showTable', $this->showTable);
    }

    public function updatedshowMap()
    {
        session()->put($this->question_id.':showMap', $this->showMap);
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
            
            $response = Http::withHeaders([
                'session-id' => $this->session_id,
            ])->get($url, [
                'user_id' => Auth::id(), // Until it is not mandatory
            ])->throwUnlessStatus(200)->json();
            
            $this->fetchLocations();

            $this->votes = $response;
            $this->vote_texts = $this->getVoteTexts($response);
            $this->vote_results = $this->getVoteResults($response) ?: [0]; // Client side can reduce this
            $this->emit('chart-refreshed');
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public function fetchLocations(): void
    {
        try {
            $url = self::getURL().'/questions/'.$this->question_id.'/votes/locations';

            // TODO: If response is empty then handle it at the client side ...
            $this->locations = Http::withHeaders([
                'session-id' => $this->session_id,
            ])->get($url, [
                'user_id' => Auth::id(), // Until it is not mandatory
            ])->throwUnlessStatus(200)->json();
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
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

    public function exportVotes()
    {
        $export = new VotesExport($this->votes);
    
        return Excel::download($export, 'votes.xlsx');
    }

    public function mailVotes()
    {
        $attachment = new VotesExport($this->votes);
    
        Mail::to(Auth::user()->email)->send(new EmailVotingResults(
            Auth::user(),
            $attachment,
            $this->votes,
            (object) [
                'id' => $this->question_id,
                'text' => $this->question_text,
            ],
        ));

        // session()->flash('message', 'Voting results has been sent successfully!');
    }
}
