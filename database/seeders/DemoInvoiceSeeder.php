<?php

namespace Database\Seeders;

use App\Enums\InvoiceItemType;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Services\InvoiceCalculationService;
use App\Services\InvoiceNumberGenerator;
use Illuminate\Database\Seeder;

class DemoInvoiceSeeder extends Seeder
{
    /**
     * Seed a demo Service, a demo Product, and one demo invoice (with
     * both a SERVICE and a PRODUCT line item, partially paid) for the
     * demo customer created by DemoProjectsSeeder. Skipped outside the
     * local environment.
     */
    public function run(): void
    {
        if (! app()->environment('local')) {
            return;
        }

        $service = Service::firstOrCreate(
            ['name' => 'Website Design (hourly)'],
            ['description' => 'Design and UX work, billed hourly.', 'unit_price' => 125.00, 'tax_rate' => 0]
        );

        $product = Product::firstOrCreate(
            ['name' => 'Hosting Package (annual)'],
            ['sku' => 'HOST-ANNUAL', 'description' => 'Managed hosting, billed annually.', 'unit_price' => 480.00, 'tax_rate' => 8.5]
        );

        $customer = Customer::where('name', 'Acme Demo Co')->first();
        $project = Project::where('code', 'DEMO-1')->first();

        if (! $customer || $customer->invoices()->exists()) {
            return;
        }

        $invoice = $customer->invoices()->create([
            'project_id' => $project?->id,
            'number' => app(InvoiceNumberGenerator::class)->generate((int) now()->year),
            'issue_date' => now()->subDays(10)->format('Y-m-d'),
            'due_date' => now()->addDays(20)->format('Y-m-d'),
            'currency' => 'USD',
            'status' => 'SENT',
            'discount_type' => 'PERCENT',
            'discount_value' => 5,
            'notes' => 'Thank you for your business!',
        ]);

        $calculator = app(InvoiceCalculationService::class);

        $items = [
            [
                'item_type' => InvoiceItemType::SERVICE,
                'ref_id' => (string) $service->id,
                'description' => $service->name,
                'quantity' => 8,
                'unit_price' => $service->unit_price,
                'tax_rate' => $service->tax_rate,
            ],
            [
                'item_type' => InvoiceItemType::PRODUCT,
                'ref_id' => (string) $product->id,
                'description' => $product->name,
                'quantity' => 1,
                'unit_price' => $product->unit_price,
                'tax_rate' => $product->tax_rate,
            ],
        ];

        foreach ($items as $attributes) {
            $item = $invoice->items()->make($attributes);
            $calculator->recalculateItem($item);
            $item->save();
        }

        $invoice->amount_paid = 500;
        $calculator->recalculateInvoice($invoice);
    }
}
