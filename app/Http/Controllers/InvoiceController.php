<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceDiscountType;
use App\Enums\InvoiceItemType;
use App\Enums\InvoiceStatus;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Requests\UpdateInvoiceRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Product;
use App\Models\Project;
use App\Models\Service;
use App\Policies\InvoicePolicy;
use App\Services\InvoiceCalculationService;
use App\Services\InvoiceNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Enum;
use Illuminate\View\View;

class InvoiceController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Invoice::class, 'invoice');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $invoices = app(InvoicePolicy::class)
            ->scopeForUser(Invoice::query(), $request->user())
            ->with('customer')
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')->toString()))
            ->when($request->filled('customer_id'), fn ($query) => $query->where('customer_id', $request->integer('customer_id')))
            ->orderByDesc('issue_date')
            ->paginate(15)
            ->withQueryString();

        return view('invoices.index', [
            'invoices' => $invoices,
            'statuses' => InvoiceStatus::cases(),
            'customers' => Customer::orderBy('name')->get(),
            'status' => $request->string('status')->toString(),
            'customerId' => $request->integer('customer_id') ?: null,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        return view('invoices.create', [
            'invoice' => new Invoice(),
            'customers' => Customer::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'discountTypes' => InvoiceDiscountType::cases(),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreInvoiceRequest $request, InvoiceNumberGenerator $numbers): RedirectResponse
    {
        $data = $request->validated();
        $data['number'] = $numbers->generate((int) date('Y', strtotime($data['issue_date'])));
        $data['status'] = InvoiceStatus::DRAFT;

        $invoice = Invoice::create($data);

        return redirect()->route('invoices.show', $invoice)
            ->with('status', 'Invoice created.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Invoice $invoice): View
    {
        $invoice->load(['customer', 'project', 'items', 'payments']);

        return view('invoices.show', [
            'invoice' => $invoice,
            'itemTypes' => InvoiceItemType::cases(),
            'services' => Service::orderBy('name')->get(),
            'products' => Product::orderBy('name')->get(),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Invoice $invoice): View
    {
        return view('invoices.edit', [
            'invoice' => $invoice,
            'customers' => Customer::orderBy('name')->get(),
            'projects' => Project::orderBy('name')->get(),
            'discountTypes' => InvoiceDiscountType::cases(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateInvoiceRequest $request, Invoice $invoice, InvoiceCalculationService $calculator): RedirectResponse
    {
        $invoice->update($request->validated());
        $calculator->recalculateInvoice($invoice);

        return redirect()->route('invoices.show', $invoice)
            ->with('status', 'Invoice updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Invoice $invoice): RedirectResponse
    {
        $invoice->delete();

        return redirect()->route('invoices.index')
            ->with('status', 'Invoice deleted.');
    }

    /**
     * Add a line item to the invoice.
     */
    public function addItem(Request $request, Invoice $invoice, InvoiceCalculationService $calculator): RedirectResponse
    {
        $this->authorize('manageItems', $invoice);

        $data = $request->validate([
            'item_type' => ['required', new Enum(InvoiceItemType::class)],
            'ref_id' => ['nullable', 'string'],
            'description' => ['required', 'string'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $item = $invoice->items()->make($data);
        $calculator->recalculateItem($item);
        $item->save();

        $calculator->recalculateInvoice($invoice);

        return back()->with('status', 'Line item added.');
    }

    /**
     * Update a line item on the invoice.
     */
    public function updateItem(Request $request, Invoice $invoice, InvoiceItem $item, InvoiceCalculationService $calculator): RedirectResponse
    {
        $this->authorize('manageItems', $invoice);
        abort_unless($item->invoice_id === $invoice->id, 404);

        $data = $request->validate([
            'item_type' => ['required', new Enum(InvoiceItemType::class)],
            'ref_id' => ['nullable', 'string'],
            'description' => ['required', 'string'],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit_price' => ['required', 'numeric', 'min:0'],
            'tax_rate' => ['nullable', 'numeric', 'min:0', 'max:100'],
        ]);

        $item->fill($data);
        $calculator->recalculateItem($item);
        $item->save();

        $calculator->recalculateInvoice($invoice);

        return back()->with('status', 'Line item updated.');
    }

    /**
     * Remove a line item from the invoice.
     */
    public function removeItem(Invoice $invoice, InvoiceItem $item, InvoiceCalculationService $calculator): RedirectResponse
    {
        $this->authorize('manageItems', $invoice);
        abort_unless($item->invoice_id === $invoice->id, 404);

        $item->delete();

        $calculator->recalculateInvoice($invoice);

        return back()->with('status', 'Line item removed.');
    }

    /**
     * Mark a DRAFT invoice as SENT.
     */
    public function markSent(Invoice $invoice): RedirectResponse
    {
        $this->authorize('markSent', $invoice);

        $invoice->update(['status' => InvoiceStatus::SENT]);

        return back()->with('status', 'Invoice marked as sent.');
    }

    /**
     * Void the invoice.
     */
    public function markVoid(Invoice $invoice): RedirectResponse
    {
        $this->authorize('markVoid', $invoice);

        $invoice->update(['status' => InvoiceStatus::VOID]);

        return back()->with('status', 'Invoice voided.');
    }

    /**
     * A print-friendly view of the invoice.
     */
    public function print(Invoice $invoice): View
    {
        $this->authorize('view', $invoice);

        $invoice->load(['customer', 'project', 'items']);

        return view('invoices.print', ['invoice' => $invoice]);
    }
}
