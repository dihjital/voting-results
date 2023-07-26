<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class VotesExport implements FromArray, WithHeadings
{
    protected $votes;

    public function __construct(array $votes)
    {
        $this->votes = $votes;
    }

    public function array(): array
    {
        return array_map(fn($vote) => [
                $vote['id'],
                $vote['vote_text'],
                $vote['number_of_votes'] ?: '0', // No votes received yet
            ] ,$this->votes);
    }

    public function headings(): array
    {
        return [
            '#',
            'Vote text',
            '# of votes',
        ];
    }
}
