<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Attachment;

use App\Exports\VotesExport;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as BaseExcel;

use App\Models\User;

class EmailVotingResults extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

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
    { }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address('no-reply@votes365.org', 'votes365.org'),
            subject: 'Download Voting Results',
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
                'resultsUrl' => env('APP_URL').'/questions/'.$this->question->id.'/votes',
            ],
        );
    }

}
