<?php

namespace App\Exports;

use App\Models\GradeItem;
use Illuminate\Support\Collection;

class ProductsExport
{
    public function __construct(protected array $filters = [])
    {
    }

    public function title(): string
    {
        return 'Subjects List';
    }

    public function headings(): array
    {
        return [
            'Code', 'Subject Name', 'Category', 'Units',
            'Grade', 'Threshold', 'Status', 'Instructor (Supplier)',
        ];
    }

    public function rows(): Collection
    {
        return GradeItem::with('supplier')
            ->filter($this->filters)
            ->orderBy('subject_code')
            ->get()
            ->map(fn (GradeItem $item) => [
                $item->subject_code,
                $item->subject_name,
                $item->category,
                $item->units,
                number_format($item->current_grade, 2, '.', ''),
                number_format($item->low_grade_threshold, 2, '.', ''),
                $item->isPending() ? 'Pending' : ($item->isBelowThreshold() ? 'At Risk' : 'Passed'),
                $item->supplier->company_name ?? '',
            ]);
    }
}