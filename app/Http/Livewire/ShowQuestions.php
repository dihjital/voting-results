<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Traits\WithLogin;
use App\Http\Livewire\Traits\WithErrorMessage;
use App\Http\Livewire\Traits\WithPerPagePagination;

use Illuminate\Pagination\LengthAwarePaginator;

use Illuminate\Support\Facades\Http;

use Livewire\Component;

class ShowQuestions extends Component
{
    use WithLogin, WithPerPagePagination, WithErrorMessage;

    const URL = 'http://localhost:8000';
    const PAGINATING = TRUE;

    public function mount()
    {
        // Check if the application has logged in to the API back-end successfully ...
        try {
            $this->login();
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

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])->get($url, array_filter([
                    'page' => self::getPAGINATING() ? $page ?? request('page', 1) : '',
                    // 'user_id' => Auth::id(), // Until this becomes mandatory at the back-end
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