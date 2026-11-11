<?php

namespace Database\Seeders;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    /**
     * Sample school supplies, with brands and categories.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->first() ?? User::first();

        $categories = [
            'Paper Products' => 'Notebooks, bond paper, pads and sticky notes.',
            'Writing Instruments' => 'Pens, pencils, markers and highlighters.',
            'Art & Craft' => 'Coloring, drawing and craft materials.',
            'Filing & Organization' => 'Folders, envelopes, binders and organizers.',
            'Desk Accessories' => 'Scissors, tape, glue, staplers and other desk tools.',
            'Math & Measuring Tools' => 'Rulers, calculators and geometry tools.',
            'School Bags' => 'Backpacks, pencil cases and bags for students.',
            'Computer & Printing Supplies' => 'Printer ink, USB drives and printing supplies.',
        ];

        $brands = [
            'Pilot' => 'Pens and writing instruments.',
            'Faber-Castell' => 'Art, drawing and school supplies.',
            'Staedtler' => 'Pencils, markers and drawing tools.',
            'Pentel' => 'Pens and writing tools.',
            'Mongol' => 'Pencils and school writing tools.',
            'HBW' => 'Notebooks and paper products.',
            'Casio' => 'Calculators and electronics.',
            'Crayola' => 'Crayons and coloring supplies.',
            '3M' => 'Tapes, sticky notes and office supplies.',
            'Epson' => 'Printers and printer ink.',
            'Generic' => 'Unbranded school supplies.',
        ];

        $categoryIds = [];
        foreach ($categories as $name => $description) {
            $categoryIds[$name] = Category::firstOrCreate(['name' => $name], ['description' => $description])->id;
        }

        $brandIds = [];
        foreach ($brands as $name => $description) {
            $brandIds[$name] = Brand::firstOrCreate(['name' => $name], ['description' => $description])->id;
        }

        // [name, sku, brand, category, quantity, reorder_level, unit_price, supplier, description]
        $products = [
            ['Notebook 80 leaves (Spiral)', 'PP-001', 'HBW', 'Paper Products', 120, 40, 38.00, 'Manila Paper Mart', 'Spiral notebook, 80 leaves'],
            ['Pad Paper Yellow 80 leaves', 'PP-002', 'HBW', 'Paper Products', 60, 20, 42.00, 'Manila Paper Mart', 'Yellow pad paper for writing'],
            ['Bond Paper A4 (500 sheets)', 'PP-003', 'Generic', 'Paper Products', 35, 15, 285.00, 'Manila Paper Mart', 'A4 bond paper ream'],
            ['Sticky Notes 3x3 in (100 sheets)', 'PP-004', '3M', 'Paper Products', 8, 12, 65.00, 'Manila Paper Mart', 'Repositionable sticky notes'],
            ['Ballpoint Pen Black 0.7 mm (box of 12)', 'WI-001', 'Pilot', 'Writing Instruments', 45, 15, 144.00, 'ScholarPoint Trading', 'Black ballpoint pens'],
            ['Gel Pen Blue 0.5 mm', 'WI-002', 'Pentel', 'Writing Instruments', 80, 30, 28.00, 'ScholarPoint Trading', 'Blue gel ink pen'],
            ['Pencil No. 2 (box of 12)', 'WI-003', 'Mongol', 'Writing Instruments', 100, 30, 96.00, 'ScholarPoint Trading', 'No. 2 pencils for school'],
            ['Permanent Marker Black', 'WI-004', 'Staedtler', 'Writing Instruments', 5, 12, 55.00, 'ScholarPoint Trading', 'Black permanent marker'],
            ['Highlighter Set (4 colors)', 'WI-005', 'Faber-Castell', 'Writing Instruments', 22, 10, 120.00, 'ScholarPoint Trading', 'Highlighters in 4 colors'],
            ['Crayons 24 colors', 'AC-001', 'Crayola', 'Art & Craft', 40, 15, 135.00, 'Bright Craft Supply', 'Box of 24 crayons'],
            ['Watercolor Set 12 colors', 'AC-002', 'Faber-Castell', 'Art & Craft', 0, 8, 165.00, 'Bright Craft Supply', 'Watercolor paint set'],
            ['Colored Pencils 12 colors', 'AC-003', 'Staedtler', 'Art & Craft', 18, 10, 185.00, 'Bright Craft Supply', 'Box of 12 colored pencils'],
            ['Long Folder (pack of 10)', 'FO-001', 'Generic', 'Filing & Organization', 70, 25, 48.00, 'Manila Paper Mart', 'Long size folders'],
            ['Clear Book 20 pockets', 'FO-002', 'HBW', 'Filing & Organization', 15, 15, 79.00, 'Manila Paper Mart', 'Clear book for documents'],
            ['Brown Envelope Long (pack of 50)', 'FO-003', 'Generic', 'Filing & Organization', 30, 10, 120.00, 'Manila Paper Mart', 'Long brown envelopes'],
            ['Scissors 7 in', 'DA-001', 'Faber-Castell', 'Desk Accessories', 25, 10, 75.00, 'ScholarPoint Trading', 'Student scissors'],
            ['Glue Stick 21 g', 'DA-002', 'Generic', 'Desk Accessories', 90, 30, 22.00, 'ScholarPoint Trading', 'Non-toxic glue stick'],
            ['Masking Tape 1 in', 'DA-003', '3M', 'Desk Accessories', 4, 10, 38.00, 'ScholarPoint Trading', 'Masking tape roll'],
            ['Stapler No. 35 with Staples', 'DA-004', 'Generic', 'Desk Accessories', 30, 8, 95.00, 'ScholarPoint Trading', 'Desk stapler with staples'],
            ['Scientific Calculator', 'MM-001', 'Casio', 'Math & Measuring Tools', 12, 5, 695.00, 'TechMart Supplies', 'Scientific calculator'],
            ['Ruler 12 in (30 cm)', 'MM-002', 'Staedtler', 'Math & Measuring Tools', 60, 20, 25.00, 'ScholarPoint Trading', 'Plastic ruler'],
            ['Student Backpack 18 in', 'SB-001', 'Generic', 'School Bags', 14, 6, 850.00, 'Campus Bags Wholesale', 'Backpack for students'],
            ['Pencil Case (Zipper)', 'SB-002', 'Generic', 'School Bags', 0, 10, 75.00, 'Campus Bags Wholesale', 'Zipper pencil case'],
            ['Printer Ink Black 70 ml', 'CP-001', 'Epson', 'Computer & Printing Supplies', 9, 10, 285.00, 'TechMart Supplies', 'Black ink bottle'],
            ['USB Flash Drive 32 GB', 'CP-002', 'Generic', 'Computer & Printing Supplies', 38, 10, 320.00, 'TechMart Supplies', '32 GB flash drive'],
        ];

        foreach ($products as [$name, $sku, $brand, $category, $quantity, $reorder, $price, $supplier, $description]) {
            $product = Product::updateOrCreate(['sku' => $sku], [
                'name' => $name,
                'brand_id' => $brandIds[$brand],
                'category_id' => $categoryIds[$category],
                'description' => $description,
                'quantity' => $quantity,
                'reorder_level' => $reorder,
                'unit_price' => $price,
                'supplier' => $supplier,
            ]);

            // Opening stock transaction, so every product starts with a stock movement record
            if ($user && $product->quantity > 0 && $product->transactions()->count() === 0) {
                $product->transactions()->create([
                    'transaction_type' => 'in',
                    'quantity' => $product->quantity,
                    'reference_number' => 'OPENING-STOCK',
                    'user_id' => $user->id,
                ]);
            }
        }
    }
}
