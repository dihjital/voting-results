@component('mail::message')
{{ __('You have been receiving the voting results for _":questionText"_ question!', ['questionText' => $questionText]) }}

<img src="https://quickchart.io/chart/render/zm-710fe8ea-3310-45af-807e-e10634eb78b7?title=
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