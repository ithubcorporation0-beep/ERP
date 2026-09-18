<?php

namespace App\Http\Controllers;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Http\Requests\StorePaymentRequest;
use App\Http\Requests\UpdatePaymentRequest;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Payment;
use App\Policies\PaymentPolicy;
use App\Services\InvoiceCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PaymentController extends Controller
{
    public function __construct()
    {
        $this->authorizeResource(Payment::class, 'payment');
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $payments = app(PaymentPolicy::class)
            ->scopeForUser(Payment::query(), $request->user())
            ->with(['invoice', 'customer'])
            ->when($request->filled('invoice_id'), fn ($query) => $query->where('invoice_id', $request->integer('invoice_id')))
            ->when($request->filled('customer_id'), fn ($query) => $query->where('customer_id', $request->integer('customer_id')))
            ->orderByDesc('received_date')
            ->paginate(15)
            ->withQueryString();

        return view('payments.index', [
            'payments' => $payments,
            'customers' => Customer::orderBy('name')->get(),
            'customerId' => $request->integer('customer_id') ?: null,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request): View
    {
        $eligibleInvoices = Invoice::query()
            ->whereIn('status', [InvoiceStatus::SENT, InvoiceStatus::PARTIALLY_PAID])
            ->where('balance_due', '>', 0)
            ->with('customer')
            ->orderBy('number')
            ->get();

        return view('payments.create', [
            'invoices' => $eligibleInvoices,
            'methods' => PaymentMethod::cases(),
            'selectedInvoiceId' => $request->integer('invoice_id') ?: null,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StorePaymentRequest $request, InvoiceCalculationService $calculator): RedirectResponse
    {
        $data = $request->validated();
        $invoice = Invoice::findOrFail($data['invoice_id']);
        $data['customer_id'] = $invoice->customer_id;

        $payment = DB::transaction(function () use ($data, $invoice, $calculator) {
            $payment = Payment::create($data);
            $calculator->recalculateInvoice($invoice);

            return $payment;
        });

        return redirect()->route('payments.show', $payment)
            ->with('status', 'Payment recorded.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment): View
    {
        $payment->load(['invoice.customer']);

        return view('payments.show', [
            'payment' => $payment,
            'methods' => PaymentMethod::cases(),
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdatePaymentRequest $request, Payment $payment, InvoiceCalculationService $calculator): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $payment, $calculator) {
            $payment->update($data);
            $calculator->recalculateInvoice($payment->invoice);
        });

        return redirect()->route('payments.show', $payment)
            ->with('status', 'Payment updated.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment, InvoiceCalculationService $calculator): RedirectResponse
    {
        $invoice = $payment->invoice;

        DB::transaction(function () use ($payment, $invoice, $calculator) {
            $payment->delete();
            $calculator->recalculateInvoice($invoice);
        });

        return redirect()->route('payments.index')
            ->with('status', 'Payment deleted.');
    }
}
