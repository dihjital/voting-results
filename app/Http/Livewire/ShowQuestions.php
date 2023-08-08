<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Traits\WithErrorMessage;
use App\Http\Livewire\Traits\WithOAuthLogin;
use App\Http\Livewire\Traits\WithPerPagePagination;
use App\Http\Livewire\Traits\WithUUIDSession;

use Illuminate\Pagination\LengthAwarePaginator;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

use Livewire\Component;

class ShowQuestions extends Component
{
    use WithOAuthLogin, WithUUIDSession, WithPerPagePagination, WithErrorMessage;

    const URL = 'http://localhost:8000';
    const PAGINATING = TRUE;

    public $access_token;
    public $refresh_token;

    public function mount()
    {
        try {
            // OAuth login process
            list($this->access_token, $this->refresh_token) = $this->login();

            // Send over the current user uuid and get a session id back
            $this->registerUUIDInSession($this->access_token);
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public static function getURL()
    {
        return env('API_ENDPOINT', self::URL);
    }

    public static function getPAGINATING(): bool
    {
        return self::PAGINATING;
    }

    public function fetchData($page = null)
    {
        try {
            $url = self::getURL().'/questions';

            $response = Http::withHeaders([
                'session-id' => $this->session_id
                ])->get($url, array_filter([
                    'page' => self::getPAGINATING() ? $page ?? request('page', 1) : '',
                    'user_id' => Auth::id(), // Until this becomes mandatory at the back-end
                ]))
                ->throwUnlessStatus(200);
           
            $data = $response->json();
            
            return self::PAGINATING
                ? new LengthAwarePaginator(
                    collect($data['data']),
                    $data['total'],
                    $data['per_page'],
                    $data['current_page'],
                    ['path' => url('/questions')]
                )
                : $data;                
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        } 
    }

    public function render()
    {
        return view('livewire.show-questions', [
            'questions' => $this->fetchData($this->current_page),
        ]);
    }
}