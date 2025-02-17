<?php

namespace App\Mail;

use App\Models\User;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

use App\Exports\VotesExport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as BaseExcel;

use Exception;

class EmailVotingResults extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    protected $letters;
    protected array $serverlessFunction = [
        'Url',
        'Auth',
    ];

    protected array $staticMap = [
        'Url',
        'Key',
    ];

    /**
     * Create a new message instance.
     */
    public function __construct(
        public User $user,
        public VotesExport $votesExport,
        public $voteResults,
        public $voteLocations,
        public $question,
    )
    { 
        $this->letters = range('A', 'Z');
        $this->serverlessFunction['Url'] = config('services.digital-ocean.serverless-functions.quickchart.url');
        $this->serverlessFunction['Auth'] = config('services.digital-ocean.serverless-functions.quickchart.auth');

        $this->staticMap['Url'] = config('services.google.static-map.url');
        $this->staticMap['Key'] = config('services.google.static-map.api-key');
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('no-reply@votes365.org', 'votes365.org'),
            subject: "Voting Results for '{$this->question->text}'",
            tags: ['voting-results'],
            metadata: [
                'question_id' => $this->question->id,
            ],
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [
            Attachment::fromData(fn() => Excel::raw(
                $this->votesExport,
                BaseExcel::XLSX
            ), 'votes.xlsx')
            ->withMime('application/vnd.ms-excel'),
        ];
    }

    protected function getChartLabels(): array
    {
        return collect($this->question->votes)->map(fn($vote, $index) => $this->letters[$index] . ') ')->toArray();
    }

    protected function getChartData(): array
    {
        // Chart is using a logarithmic scale on the Y-axis
        // We multiply the number_of_votes with a 5 so votes with 1 vote will be shown 'correctly' on the chart
        // Otherwise they would have a value of 0 (e.g. logn 1 is 0)
        return collect($this->question->votes)->map(fn($vote) => $vote['number_of_votes'] * 5)->toArray();
    }

    protected function getChartDataBackgroundColor(): array
    {
        return collect($this->question->votes)->map(
            fn($vote) => $this->question->correct_vote === $vote['id']
                ? 'rgb(104, 117, 246)'
                : 'rgb(0, 146, 255)'
        )->toArray();
    }

    protected function getChartUrl(): ?string
    {        
        try {
            $response = 
                Http::withHeaders([
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                    'Authorization' => $this->serverlessFunction['Auth'],
                ])
                ->post($this->serverlessFunction['Url'], [
                    'labels' => $this->getChartLabels(),
                    'data' => $this->getChartData(),
                    'backgroundColor' => $this->getChartDataBackgroundColor(),
                ])
                ->throwUnlessStatus(200);

                $response->json('statusCode') >= 400 &&
                    throw new Exception($response->json('body'), $response->json('statusCode'));

                return $response->json('body');
        } catch (Exception $e) {
            Log::error('getChartUrl: ' . $e->getMessage());
        }
        
        return null;
    }

    protected function getStaticMapUrl(): string
    {
        $query = http_build_query([
            'size' => '320x200',
            'zoom' => 'auto',
            'key' => $this->staticMap['Key'],
            'maptype' => 'hybrid',
        ]);

        if (is_array($this->voteLocations) && ! empty($this->voteLocations)) {
            $markers = implode('&markers=', 
                array_map(
                    fn($location) => 
                        "label:{$location['city'][0]}|{$location['latitude']},{$location['longitude']}", 
                    $this->voteLocations
                )
            );
        }

        return isset($markers)
           ? "{$this->staticMap['Url']}?{$query}&markers={$markers}"
           : "{$this->staticMap['Url']}?{$query}";
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            markdown: 'emails.votes.results',
            with: [
                'userName' => $this->user->name,
                'questionId' => $this->question->id,
                'questionText' => $this->question->text,
                'voteResults' => $this->voteResults,
                'voteLocations' => $this->voteLocations,
                'chartUrl' => $this->getChartUrl(),
                'resultsUrl' => env('APP_URL') . "/questions/{$this->question->id}/votes",
                'mapUrl' => $this->getStaticMapUrl(),
            ],
        );
    }
}
