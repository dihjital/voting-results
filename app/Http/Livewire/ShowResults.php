<?php

namespace App\Http\Livewire;

use Livewire\Component;

use SimpleSoftwareIO\QrCode\Facades\QrCode;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Auth;

use App\Exports\VotesExport;
use Maatwebsite\Excel\Facades\Excel;

use App\Http\Livewire\Traits\WithLogin;
use App\Http\Livewire\Traits\WithUUIDSession;
use App\Http\Livewire\Traits\WithErrorMessage;

use Illuminate\Http\Client\PendingRequest;

use App\Mail\EmailVotingResults;
use Laravel\Jetstream\InteractsWithBanner;

class ShowResults extends Component
{
    use InteractsWithBanner, WithLogin, WithUUIDSession, WithErrorMessage;

    public $question;
    public $question_id;
    public $question_text;

    public $votes;
    public $vote_texts;
    public $vote_results;
    public $highestVote = 0;
    public $locations;
    public $qrCodeImg;
    public $showLocationDetailsModal = false;

    public $showTable = false;
    public $showMap = false;

    public function getListeners()
    {
        return [
            'refresh-chart' => 'refreshChart',
            'refresh-page'  => '$refresh',
            'echo:user.' . Auth::user()->id . ',VoteReceived' => 'refreshChart',
        ];
    }

    public function mount($question_id)
    {
        $this->question_id = $question_id;

        try {
            list(
                'access_token' => $this->access_token, 
                'refresh_token' => $this->refresh_token) = $this->getTokensFromCache();
            $this->session_id = $this->startSessionIfRequired($this->access_token);

            // Get the question text ...
            try {
                $url = $url = config('services.api.endpoint',
                    fn() => throw new \Exception('No API endpoint is defined')
                ).'/questions/'.$this->question_id;
                
                $response = Http::withToken($this->access_token)
                    ->withHeaders([
                        'session-id' => $this->session_id,
                    ])
                    ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                        return $this->retryCallback($e, $request);
                    })
                    ->get($url)
                    ->throwUnlessStatus(200)
                    ->json();

                $this->question = $response;

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

    public function refreshChart()
    {
        $this->fetchData();
    }

    public function fetchData(): void
    {
        try {
            $url = $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/questions/'.$this->question_id.'/votes';
            
            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id,
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->get($url)
                ->throwUnlessStatus(200)
                ->json();
            
            $this->fetchLocations();

            $this->votes = $response;
            $this->highestVote = 
                array_reduce($this->votes, fn ($highestVote, $vote) =>
                    $vote['number_of_votes'] > $highestVote
                        ? $vote['number_of_votes']
                        : $highestVote, 
                0);

            $this->vote_texts = $this->getVoteTexts($response);
            $this->vote_results = $this->getVoteResults($response) ?: [0]; // Client side can reduce this
            
            $this->generateQrCode();

            $this->emit('chart-refreshed');
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public function fetchLocations(): void
    {
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/questions/'.$this->question_id.'/votes/locations';

            // TODO: If response is empty then handle it at the client side ...
            $this->locations = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id,
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                })
                ->get($url)
                ->throwUnlessStatus(200)
                ->json();
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

    public function caruselPrev()
    {
        $this->mount($this->question['previous_id'] ?? $this->question_id);
    }

    public function caruselNext()
    {
        $this->mount($this->question['next_id'] ?? $this->question_id);
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
            $this->locations,
            (object) [
                'id' => $this->question_id,
                'text' => $this->question_text,
            ],
        ));

        $this->banner(__('Voting results are sent successfully!'));
    }

    public function generateQrCode($question_id = null)
    {
        // TODO: Move this to a separate method
        $url = env('CLIENT_URL', 'https://voting-client.votes365.org');
        $url .= '/questions/'.($question_id ?: $this->question_id).'/votes?uuid='.Auth::id();

        $this->qrCodeImg = 
            base64_encode(QrCode::format('png')
                ->size(200)
                ->generate($url));
    }
}