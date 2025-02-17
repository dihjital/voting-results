@component('mail::message')

# {{ __('You have been receiving the voting results for _":questionText"_ question!', ['questionText' => $questionText]) }}

@php
    $letters = range('A', 'Z');
@endphp

<img src="{{ $chartUrl }}" alt="Voting results for - {{ $questionText }}" style="display: block; margin: 0 auto;" />

*{{__('The chart is showing results on a logarithmic scale.')}}*

@component('mail::panel')
{{ __('Please find each choice with the corresponding number of votes received in the table below.') }} 
{{ __('If the owner of the question sets a correct answer, we will indicate it with a checkmark.') }}
@endcomponent

@component('mail::table')
|       |   | Choice    | # of votes |
| :---- | - | :-------- | :--------: |
@foreach($voteResults as $result)
| {{ $letters[$loop->index] }}) | @if($question->correct_vote === $result['id']) @component('components.checkmark') @endcomponent @endif | {{ $result['vote_text'] }} | {{ $result['number_of_votes'] }} |
@endforeach
@endcomponent

@if($voteLocations)

@component('mail::panel')
{{ __('The following table includes the number of votes received by location based on your voters geographical data.') }}
@endcomponent

@component('mail::table')
| Country   | City | # of votes |
| :-------- | :--- | :--------: |
@foreach($voteLocations as $location)
| {{ $location['country_name'] }} | <a href="https://www.google.com/maps/{{ "@" . $location['latitude'] }},{{ $location['longitude'] }},12z" target="_blank" rel="noopener noreferrer">{{ $location['city'] }}</a> | {{ $location['vote_count'] }} |
@endforeach
@endcomponent

<img src="{{ $mapUrl }}" style="border-radius: 0.375rem; margin-bottom: 5%;" />

@endif

{{ __('If you would like to check out the voting results on the Web site please click on the button below.') }}
{{ __('Please note that you need to have a registered user on our website for this action to work.') }}

@component('mail::button', ['url' => $resultsUrl])
{{ __('Check out the results') }}
@endcomponent

@component('mail::subcopy')
{{ __('This email was intended for :userName', ['userName' => $userName]) }}. {{ __('You are receiving emails from the ') }} **{{ config('app.name') }}** {{ __(' application.') }}
@endcomponent

@endcomponent