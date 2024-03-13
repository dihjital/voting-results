<?php

namespace App\Http\Livewire;

use App\Http\Livewire\Traits\WithLogin;
use App\Http\Livewire\Traits\WithErrorMessage;
use App\Http\Livewire\Traits\WithPerPagePagination;
use App\Http\Livewire\Traits\WithUUIDSession;

use Illuminate\Pagination\LengthAwarePaginator;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

use Illuminate\Http\Client\PendingRequest;

use Livewire\Component;

class ShowQuestions extends Component
{
    use WithLogin, WithUUIDSession, WithPerPagePagination, WithErrorMessage;

    const PAGINATING = TRUE;

    public $quizzes;

    public $filters = [
        'closed' => true,
        'quizzes' => true,
    ];

    protected function initializeFiltering()
    {
        $filters = session()->get('showQuestions.filters', $this->filters);
        foreach($filters as $key => $value) {
            $this->filters[$key] = $value;
        }
    }

    public function updatedFilters($value, $key)
    {
        session()->put('showQuestions.filters', $this->filters);
    }

    public function mount()
    {
        $this->initializeFiltering();

        try {
            list(
                'access_token' => $this->access_token, 
                'refresh_token' => $this->refresh_token) = $this->getTokensFromCache();
            $this->session_id = $this->startSessionIfRequired($this->access_token);

            $this->quizzes = $this->getAllQuizzes();
        } catch (\Exception $e) {
            $this->error_message = $this->parseErrorMessage($e->getMessage());
        }
    }

    public static function getPAGINATING(): bool
    {
        return self::PAGINATING;
    }

    protected function getAllQuizzes()
    {
        // Populate cache with Quizzes
        $key = 'quizzes' . $this->id;
        $seconds = 3600; // 1 hour...
 
        return Cache::remember($key, $seconds, function () {
            try {
                $url = config('services.api.endpoint',
                    fn() => throw new \Exception('No API endpoint is defined')
                ).'/quizzes';
    
                $response = Http::withToken($this->access_token)
                    ->withHeaders([
                        'session-id' => $this->session_id
                    ])
                    ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                        return $this->retryCallback($e, $request);
                    }) 
                    ->get($url)
                    ->throwUnlessStatus(200);
               
                return $response->json();               
            } catch (\Exception $e) {
                $this->error_message = $this->parseErrorMessage($e->getMessage());
            }
        });   
    }

    public function getQuizName($quizzes, $question_id): ?array
    {
        return 
            array_map(
                fn($quiz) => $quiz['name'], 
                array_filter(
                    $quizzes, 
                    function($quiz) use ($question_id) {
                        return 
                            count(
                                array_filter(
                                    $quiz['questions'], 
                                    function($question) use ($question_id) {
                                        return $question['id'] === $question_id;
                                    }
                                )
                            );
                    }
                )
            );
    }

    public function fetchData($page = null)
    {
        try {
            $url = config('services.api.endpoint',
                fn() => throw new \Exception('No API endpoint is defined')
            ).'/questions';

            $response = Http::withToken($this->access_token)
                ->withHeaders([
                    'session-id' => $this->session_id
                ])
                ->retry(3, 500, function (\Exception $e, PendingRequest $request) {
                    return $this->retryCallback($e, $request);
                }) 
                ->get($url, array_filter([
                        'page' => self::getPAGINATING() ? $page ?? request('page', 1) : '',
                        'closed' => $this->filters['closed'] ?? null,
                        'quizzes' => $this->filters['quizzes'] ?? null,
                    ])
                )
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