@component('mail::message')

# {{ __('You have been receiving the voting results for _":questionText"_ question!', ['questionText' => $questionText]) }}

<img src="https://quickchart.io/chart/render/zm-ac035d4b-9b47-4b23-a90e-1a8d0f8e4c4c?title=
    {{ urlencode($questionText) }}
    &labels={{ 
        implode(',', 
            array_map(
                fn($r) => 
                    urlencode('#' . $r['id']),
                $voteResults
            )
        )
    }}
    &data1={{ implode(',', array_map(fn($r) => $r['number_of_votes'], $voteResults)) }}"
/>

@component('mail::panel')
{{ __('Please find each choice with the corresponding number of votes received in the table below.') }}
@endcomponent

@component('mail::table')
| #     | Choice    | # of votes |
| :---- | :-------- | :--------: |
@foreach($voteResults as $result)
| {{ $result['id'] }} | {{ $result['vote_text'] }} | {{ $result['number_of_votes'] }} |
@endforeach
@endcomponent

@component('mail::panel')
{{ __('The following table includes the number of votes received by location based on your voters geographical data.') }}
@endcomponent

@component('mail::table')
| Country   | City | # of votes | Location |
| :-------- | :--- | :--------: | :------: |
@foreach($voteLocations as $location)
| {{ $location['country_name'] }} | {{ $location['city'] }} | {{ $location['vote_count'] }} | <a href="https://www.google.com/maps/search/?api=1&query={{ $location['latitude'] }},{{ $location['longitude'] }}" target="_blank" rel="noopener noreferrer"><img src="{{ $message->embed(asset('storage/' . 'images/pin.jpeg')) }}" width="32" height="32" alt="{{ __('Display on Google Maps') }}" /></a> |
@endforeach
@endcomponent

{{ __('If you would like to check out the voting results on the Web site please click on the button below:') }}

@component('mail::button', ['url' => $resultsUrl])
{{ __('Check out the results') }}
@endcomponent

<font size="2">{{ __('This email was intended for :userName', ['userName' => $userName]) }}. {{ __('You are receiving emails from the ') }} **{{ config('app.name') }}** {{ __(' application.') }}</font>
@endcomponent