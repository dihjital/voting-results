<?php

namespace App\Http\Livewire\Traits;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

trait WithLogin
{
    public $access_token;
    public $refresh_token;

    public $session_id; // Back-end session-id

    protected function login()
    {
        Gate::authorize('hasApiAccessToken');

        Log::debug('Passed hasApiAccessToken gate successfully');

        $this->access_token = session()->get('access_token');
        $this->refresh_token = session()->get('refresh_token');

        Gate::authorize('hasApiSessionId');

        $this->session_id = session()->get(Auth::id().':session_id');

        Log::debug('Passed hasApiSessionId gate successfully');
    }
}