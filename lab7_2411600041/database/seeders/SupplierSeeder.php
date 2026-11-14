<?php

namespace Database\Seeders;

use App\Models\Supplier;
use Illuminate\Database\Seeder;

class SupplierSeeder extends Seeder
{
    /**
     * Sample suppliers. updateOrCreate on the email, so running it twice does not duplicate them.
     */
    public function run(): void
    {
        $suppliers = [
            ['Manila Paper Mart', 'Ramon Dela Cruz', 'sales@manilapapermart.example.com', '0917 123 4567', 'Recto Avenue, Manila', 'Net 30', 5, 1000, true],
            ['ScholarPoint Trading', 'Liza Mendoza', 'orders@scholarpoint.example.com', '0918 234 5678', 'Cubao, Quezon City', 'Net 15', 7, 1500, true],
            ['Bright Craft Supply', 'Joel Santos', 'hello@brightcraft.example.com', '0919 345 6789', 'Divisoria, Manila', 'Net 30', 10, 800, true],
            ['TechMart Supplies', 'Grace Reyes', 'sales@techmart.example.com', '0920 456 7890', 'Greenhills, San Juan', 'COD', 3, 2000, true],
            ['Campus Bags Wholesale', 'Mark Villanueva', 'orders@campusbags.example.com', '0921 567 8901', 'Marikina City', 'Net 45', 14, 3000, true],
            ['Old Stationery Depot', 'Nena Garcia', 'info@oldstationery.example.com', '0922 678 9012', 'Binondo, Manila', 'Net 30', 21, 500, false], // inactive on purpose
        ];

        foreach ($suppliers as [$name, $contact, $email, $phone, $address, $terms, $lead, $minimum, $active]) {
            Supplier::updateOrCreate(['email' => $email], [
                'name' => $name,
                'contact_person' => $contact,
                'phone' => $phone,
                'address' => $address,
                'payment_terms' => $terms,
                'lead_time_days' => $lead,
                'minimum_order_amount' => $minimum,
                'is_active' => $active,
            ]);
        }
    }
}
