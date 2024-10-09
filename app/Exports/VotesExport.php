<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VotesExport implements FromArray, WithHeadings
{
    public function __construct(
        protected array $votes, 
        protected array $question)
    {
        //
    }

    public function array(): array
    {
        return array_map(
            function($vote) {
                $base = [
                    $vote['id'],
                    $vote['vote_text'],
                    $vote['number_of_votes'] ?: '0', // No votes received yet
                ];
        
                return $this->question['correct_vote']
                    ? [...$base, $this->question['correct_vote'] === $vote['id'] ?? 1]
                    : $base;
            },
            $this->votes
        );        
    }

    public function headings(): array
    {
        $base = [
            '#',
            'Vote text',
            '# of votes',
        ];

        return $this->question['correct_vote'] ? [...$base, 'Correct vote'] : $base;
    }
}
