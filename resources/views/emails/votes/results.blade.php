@component('mail::message')
{{ __('You have been receiving the voting results for ":questionText" question!', ['questionText' => $questionText]) }}

@component('mail::table')
| #     | VOTE TEXT | # OF VOTES |
| :---- | :-------: | --------:  |
@foreach($voteResults as $result)
| {{ $result['id'] }} | {{ $result['vote_text'] }} | {{ $result['number_of_votes'] }} |
@endforeach
@endcomponent

{{ __('If you would like to check out the voting results on the Web site please click on the button below:') }}

@component('mail::button', ['url' => $resultsUrl])
{{ __('Check out the results') }}
@endcomponent

{{ __('If you did not expect to receive an invitation to this team, you may discard this email.') }}
@endcomponent