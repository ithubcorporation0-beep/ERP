<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $invoice->number }}</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #1f2937; margin: 2rem; }
        h1 { font-size: 1.5rem; margin-bottom: 0; }
        .muted { color: #6b7280; }
        table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        th, td { text-align: left; padding: 0.5rem; border-bottom: 1px solid #e5e7eb; }
        th { font-size: 0.75rem; text-transform: uppercase; color: #6b7280; }
        .text-right { text-align: right; }
        .totals { width: 260px; margin-left: auto; margin-top: 1rem; }
        .totals div { display: flex; justify-content: space-between; padding: 0.25rem 0; }
        .totals .grand { font-weight: bold; border-top: 1px solid #e5e7eb; padding-top: 0.5rem; }
        .header-grid { display: flex; justify-content: space-between; margin-top: 1.5rem; }
        @media print {
            a { color: inherit; text-decoration: none; }
        }
    </style>
</head>
<body>
    <h1>{{ __('Invoice') }} {{ $invoice->number }}</h1>
    <p class="muted">{{ $invoice->status->label() }}</p>

    <div class="header-grid">
        <div>
            <strong>{{ __('Bill To') }}</strong><br>
            {{ $invoice->customer->name }}<br>
            {{ $invoice->customer->email }}
        </div>
        <div>
            <strong>{{ __('Issue Date') }}:</strong> {{ $invoice->issue_date->format('Y-m-d') }}<br>
            <strong>{{ __('Due Date') }}:</strong> {{ optional($invoice->due_date)->format('Y-m-d') ?? '—' }}<br>
            @if ($invoice->project)
                <strong>{{ __('Project') }}:</strong> {{ $invoice->project->name }}<br>
            @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>{{ __('Description') }}</th>
                <th>{{ __('Qty') }}</th>
                <th>{{ __('Unit Price') }}</th>
                <th>{{ __('Tax %') }}</th>
                <th class="text-right">{{ __('Line Total') }}</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($invoice->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ number_format($item->unit_price, 2) }}</td>
                    <td>{{ $item->tax_rate !== null ? number_format($item->tax_rate, 2).'%' : '—' }}</td>
                    <td class="text-right">{{ number_format($item->line_total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="totals">
        <div><span>{{ __('Subtotal') }}</span><span>{{ $invoice->currency }} {{ number_format($invoice->subtotal, 2) }}</span></div>
        <div><span>{{ __('Tax') }}</span><span>{{ $invoice->currency }} {{ number_format($invoice->tax_total, 2) }}</span></div>
        <div class="grand"><span>{{ __('Total') }}</span><span>{{ $invoice->currency }} {{ number_format($invoice->total, 2) }}</span></div>
        <div><span>{{ __('Amount Paid') }}</span><span>{{ $invoice->currency }} {{ number_format($invoice->amount_paid, 2) }}</span></div>
        <div><span>{{ __('Balance Due') }}</span><span>{{ $invoice->currency }} {{ number_format($invoice->balance_due, 2) }}</span></div>
    </div>

    @if ($invoice->notes)
        <p class="muted" style="margin-top: 2rem;"><strong>{{ __('Notes') }}:</strong> {{ $invoice->notes }}</p>
    @endif

    <script>window.print();</script>
</body>
</html>
