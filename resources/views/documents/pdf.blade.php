@php
    /** @var \App\Models\Document $document */
    /** @var \App\Models\User $issuer */
    $money = fn (int $cents): string => $document->currency.' '.number_format($cents / 100, 2);
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $document->number }}</title>

    {{-- Dompdf only understands CSS 2.1, so this sheet stays on tables, borders and spacing. --}}
    <style>
        @page { margin: 40px 44px; }

        body {
            font-family: "DejaVu Sans", sans-serif;
            font-size: 10.5px;
            line-height: 1.5;
            color: #1f2937;
        }

        table { width: 100%; border-collapse: collapse; }
        td, th { vertical-align: top; }

        .muted { color: #6b7280; }
        .right { text-align: right; }
        .bold { font-weight: bold; }

        .masthead { border-bottom: 1px solid #e5e7eb; padding-bottom: 18px; }
        .masthead h1 {
            margin: 0;
            font-size: 22px;
            letter-spacing: 1.5px;
            text-transform: uppercase;
        }
        .masthead .number { margin: 4px 0 0; font-size: 11px; color: #6b7280; }
        .masthead .status {
            margin: 10px 0 0;
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #6b7280;
        }

        .parties { padding: 22px 0; }
        .parties h2 {
            margin: 0 0 4px;
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #6b7280;
        }
        .parties p { margin: 0; }

        .dates td { padding: 1px 0; }
        .dates .label { color: #6b7280; padding-right: 14px; }

        .lines th {
            border-top: 1px solid #e5e7eb;
            border-bottom: 1px solid #e5e7eb;
            padding: 7px 0;
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #6b7280;
            text-align: left;
        }
        .lines td { border-bottom: 1px solid #f3f4f6; padding: 8px 0; }
        .lines tr { page-break-inside: avoid; }
        .lines .amount { width: 90px; text-align: right; }
        .lines .qty { width: 60px; text-align: right; }
        .lines .price { width: 100px; text-align: right; }

        .totals { margin-top: 16px; page-break-inside: avoid; }
        .totals td { padding: 3px 0; }
        .totals .label { color: #6b7280; }
        .totals .grand td {
            border-top: 1px solid #e5e7eb;
            padding-top: 8px;
            font-size: 12px;
            font-weight: bold;
        }

        .notes {
            margin-top: 34px;
            border-top: 1px solid #e5e7eb;
            padding-top: 14px;
            page-break-inside: avoid;
        }
        .notes h2 {
            margin: 0 0 4px;
            font-size: 9px;
            letter-spacing: 1px;
            text-transform: uppercase;
            color: #6b7280;
        }
        .notes p { margin: 0; color: #374151; }
    </style>
</head>
<body>
    <table class="masthead">
        <tr>
            <td>
                <h1>{{ $document->type->label() }}</h1>
                <p class="number">{{ $document->number }}</p>
                <p class="status">{{ $document->status->label() }}</p>
            </td>
            <td class="right">
                <p class="bold" style="margin: 0;">{{ $issuer->name }}</p>
                <p class="muted" style="margin: 0;">{{ $issuer->email }}</p>
            </td>
        </tr>
    </table>

    <table class="parties">
        <tr>
            <td style="width: 55%;">
                <h2>Billed to</h2>
                <p class="bold">{{ $document->client_name }}</p>
                @if ($document->client_email)
                    <p class="muted">{{ $document->client_email }}</p>
                @endif
                @if ($document->client_address)
                    @foreach (preg_split('/\R/', $document->client_address) as $line)
                        <p class="muted">{{ $line }}</p>
                    @endforeach
                @endif
            </td>
            <td>
                <table class="dates right">
                    <tr>
                        <td class="label right">Issue date</td>
                        <td class="right">{{ $document->issue_date->format('M j, Y') }}</td>
                    </tr>
                    @if ($document->due_date)
                        <tr>
                            <td class="label right">{{ $document->type->dueDateLabel() }}</td>
                            <td class="right">{{ $document->due_date->format('M j, Y') }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label right">Currency</td>
                        <td class="right">{{ $document->currency }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="lines">
        <thead>
            <tr>
                <th>Description</th>
                <th class="qty">Qty</th>
                <th class="price">Unit price</th>
                <th class="amount">Amount</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($document->items as $item)
                <tr>
                    <td>{{ $item->description }}</td>
                    <td class="qty">{{ (float) $item->quantity }}</td>
                    <td class="price">{{ $money($item->unit_price_cents) }}</td>
                    <td class="amount">{{ $money($item->total_cents) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table class="totals">
        <tr>
            <td></td>
            <td style="width: 260px;">
                <table>
                    <tr>
                        <td class="label">Subtotal</td>
                        <td class="right">{{ $money($document->subtotal_cents) }}</td>
                    </tr>
                    @if ($document->discount_cents > 0)
                        <tr>
                            <td class="label">Discount</td>
                            <td class="right">-{{ $money($document->discount_cents) }}</td>
                        </tr>
                    @endif
                    <tr>
                        <td class="label">Tax ({{ (float) $document->tax_rate }}%)</td>
                        <td class="right">{{ $money($document->tax_cents) }}</td>
                    </tr>
                    <tr class="grand">
                        <td>{{ $document->type->totalLabel() }}</td>
                        <td class="right">{{ $money($document->total_cents) }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    @if ($document->notes)
        <div class="notes">
            <h2>Notes</h2>
            @foreach (preg_split('/\R/', $document->notes) as $line)
                <p>{{ $line }}</p>
            @endforeach
        </div>
    @endif
</body>
</html>
