<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ExportService
{
    /**
     * Gumagawa ng CSV download mula sa kahit anong export class
     * na may headings() at rows().
     */
    public function csv(object $export, string $filename): StreamedResponse
    {
        return response()->streamDownload(function () use ($export) {
            $out = fopen('php://output', 'w');

            // BOM: para tama ang basa ng Excel sa UTF-8
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, $export->headings());

            foreach ($export->rows() as $row) {
                fputcsv($out, $row);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * Gumagawa ng PDF download mula sa parehong export class,
     * gamit ang template na exports/pdf.blade.php (dompdf).
     */
    public function pdf(object $export, string $filename)
    {
        return Pdf::loadView('exports.pdf', [
            'title'    => $export->title(),
            'headings' => $export->headings(),
            'rows'     => $export->rows(),
        ])->setPaper('a4', 'landscape')->download($filename);
    }
}