@component('mail::message')
{{ __('You have been receiving the voting results for _":questionText"_ question!', ['questionText' => $questionText]) }}

@component('mail::panel')
<img src="data:image/png; base64,{{ 
    base64_encode(
    file_get_contents('https://quickchart.io/chart/render/zm-63e662c8-2be4-4426-9105-cbcd414ea1af?title=' . 
    urlencode('Mi volt Péter jele az oviban') . 
    '?&labels=' . 
    urlencode('Pöttyös labda,Talicska,vagy fésű?') . 
    '&data1=8,4,7')) }}" 
    />
@endcomponent

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