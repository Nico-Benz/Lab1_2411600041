<?php

namespace App\Exports;

use App\Services\SearchService;
use Illuminate\Http\Request;

class ProductsExport implements ReportExport
{
    public function __construct(private Request $request)
    {
    }

    public function title(): string
    {
        return 'Products List';
    }

    public function filename(): string
    {
        return 'products-' . now()->format('Ymd-His');
    }

    public function headings(): array
    {
        return ['SKU', 'Product', 'Brand', 'Category', 'Supplier', 'Quantity', 'Reorder Level', 'Unit Price', 'Status'];
    }

    public function rows(): iterable
    {
        foreach ((new SearchService)->products($this->request)->cursor() as $product) {
            yield [
                $product->sku,
                $product->name,
                $product->brand->name ?? '',
                $product->category->name ?? '',
                $product->supplier->name ?? '',
                $product->quantity,
                $product->reorder_level,
                number_format((float) $product->unit_price, 2, '.', ''),
                $product->stock_status,
            ];
        }
    }
}
