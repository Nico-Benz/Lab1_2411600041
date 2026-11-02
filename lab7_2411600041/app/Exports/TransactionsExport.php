<?php

namespace App\Exports;

use App\Models\GradeTransaction;
use Illuminate\Support\Collection;

class TransactionsExport
{
    public function __construct(protected array $filters = [])
    {
    }

    public function title(): string
    {
        return 'Grade Transactions';
    }

    public function headings(): array
    {
        return [
            'Date/Time', 'Subject Code', 'Subject Name', 'Type',
            'Quantity', 'Previous Grade', 'New Grade', 'Reference', 'By', 'Notes',
        ];
    }

    public function rows(): Collection
    {
        return GradeTransaction::with(['gradeItem', 'user'])
            ->filter($this->filters)
            ->recent()
            ->orderByDesc('id')
            ->get()
            ->map(fn (GradeTransaction $t) => [
                $t->created_at->format('Y-m-d H:i'),
                $t->gradeItem->subject_code ?? '',
                $t->gradeItem->subject_name ?? '',
                ucfirst($t->type),
                number_format($t->quantity, 2, '.', ''),
                number_format($t->previous_grade, 2, '.', ''),
                number_format($t->new_grade, 2, '.', ''),
                $t->reference_number ?? '',
                $t->user->name ?? '',
                $t->notes ?? '',
            ]);
    }
}