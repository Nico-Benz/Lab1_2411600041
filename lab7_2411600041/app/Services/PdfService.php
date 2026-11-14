<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;

/**
 * Small wrapper around barryvdh/laravel-dompdf so the rest of the app
 * can check first if the package is installed.
 *   composer require barryvdh/laravel-dompdf
 */
class PdfService
{
    public static function available(): bool
    {
        return class_exists(Pdf::class);
    }

    public static function output(string $view, array $data = [], string $orientation = 'portrait'): string
    {
        return Pdf::loadView($view, $data)->setPaper('a4', $orientation)->output();
    }

    public static function download(string $view, array $data, string $filename, string $orientation = 'portrait')
    {
        return Pdf::loadView($view, $data)->setPaper('a4', $orientation)->download($filename);
    }
}
