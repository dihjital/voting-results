@component('mail::message')
{{ __('You have been receiving the voting results for _":questionText"_ question!', ['questionText' => $questionText]) }}

{{-- @component('mail::panel')
<img src="https://quickchart.io/chart/render/zm-4bab0620-25fd-4265-a098-ca366fc2c2e2?title=
         {{ $questionText }}
         ?&labels={{ implode(',', array_map(fn($r) => $r['vote_text'], $voteResults)) }}
         &data1={{ implode(',', array_map(fn($r) => $r['number_of_votes'], $voteResults)) }}"
/>
@endcomponent --}}

<img src="https://quickchart.io/chart/render/zm-4bab0620-25fd-4265-a098-ca366fc2c2e2?title=Mi volt Péter jele az oviban?&labels=Pöttyös labda,Talicska,vagy fésű?&data1=8,4,7" />

@component('mail::table')
| #     | VOTE TEXT | # OF VOTES |
| :---- | :-------- | :--------: |
@foreach($voteResults as $result)
| {{ $result['id'] }} | {{ $result['vote_text'] }} | {{ $result['number_of_votes'] }} |
@endforeach
@endcomponent

{{ __('If you would like to check out the voting results on the Web site please click on the button below:') }}

@component('mail::button', ['url' => $resultsUrl])
{{ __('Check out the results') }}
@endcomponent

<font size="2">{{ __('This email was intended for :userName', ['userName' => $userName]) }}. {{ __('You are receiving emails from the ') }} **{{ config('app.name') }}** {{ __(' application.') }}</font>
@endcomponent