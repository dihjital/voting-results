<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Traits\WithPerPagePagination;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Http;

use Livewire\Component;

class ShowQuestions extends Component
{
    use WithPerPagePagination;

    public $error_message;

    const URL = 'http://localhost:8000';

    const PAGINATING = TRUE;

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
            
            if (self::PAGINATING) {
                $currentPage = $page ?? request('page', 1);
                $url .= '?page='.$currentPage;
                request('user_id') && $url .= '&user_id='.request('user_id');
            }
            
            $response = Http::get($url)->throwUnlessStatus(200);
            $data = $response->json();
            
            $paginator = self::PAGINATING
                ? new LengthAwarePaginator(
                    collect($data['data']),
                    $data['total'],
                    $data['per_page'],
                    $data['current_page'],
                    ['path' => url('/questions')]
                )
                : $data;
                
            return $paginator;
        } catch (\Exception $e) {
            $this->error_message = $e->getMessage();
        } 
    }

    public function render()
    {
        return view('livewire.show-questions', [
            'questions' => $this->fetchData($this->current_page),
        ]);
    }

}

