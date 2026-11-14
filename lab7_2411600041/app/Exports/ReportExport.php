<?php

namespace App\Exports;

/** Every export class gives a title, column headings and rows. CSV and PDF both use the same data. */
interface ReportExport
{
    public function title(): string;

    public function filename(): string;

    /** @return array<int, string> */
    public function headings(): array;

    /** @return iterable<int, array<int, string|int|float|null>> */
    public function rows(): iterable;
}
