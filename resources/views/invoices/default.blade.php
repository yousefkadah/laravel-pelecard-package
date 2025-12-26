<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoice #{{ $invoice->number() }}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: #f5f5f5;
            padding: 20px;
        }

        .invoice-container {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
        }

        .invoice-header {
            display: flex;
            justify-content: space-between;
            align-items: start;
            margin-bottom: 40px;
            padding-bottom: 20px;
            border-bottom: 3px solid #4F46E5;
        }

        .company-info h1 {
            color: #4F46E5;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .company-info p {
            color: #666;
            font-size: 14px;
        }

        .invoice-meta {
            text-align: right;
        }

        .invoice-meta h2 {
            font-size: 32px;
            color: #333;
            margin-bottom: 10px;
        }

        .invoice-meta p {
            color: #666;
            font-size: 14px;
        }

        .invoice-details {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 40px;
        }

        .detail-section h3 {
            color: #4F46E5;
            font-size: 14px;
            text-transform: uppercase;
            margin-bottom: 10px;
            letter-spacing: 1px;
        }

        .detail-section p {
            color: #666;
            font-size: 14px;
            margin-bottom: 5px;
        }

        .invoice-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .invoice-table thead {
            background: #4F46E5;
            color: white;
        }

        .invoice-table th {
            padding: 12px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        .invoice-table td {
            padding: 12px;
            border-bottom: 1px solid #eee;
            font-size: 14px;
        }

        .invoice-table tbody tr:hover {
            background: #f9fafb;
        }

        .text-right {
            text-align: right;
        }

        .invoice-summary {
            margin-left: auto;
            width: 300px;
        }

        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            font-size: 14px;
        }

        .summary-row.total {
            border-top: 2px solid #4F46E5;
            margin-top: 10px;
            padding-top: 15px;
            font-size: 18px;
            font-weight: bold;
            color: #4F46E5;
        }

        .invoice-notes {
            margin-top: 40px;
            padding: 20px;
            background: #f9fafb;
            border-left: 4px solid #4F46E5;
        }

        .invoice-notes h3 {
            color: #4F46E5;
            font-size: 14px;
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .invoice-notes p {
            color: #666;
            font-size: 14px;
            line-height: 1.6;
        }

        .invoice-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
            text-align: center;
            color: #999;
            font-size: 12px;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .invoice-container {
                box-shadow: none;
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="invoice-container">
        <!-- Header -->
        <div class="invoice-header">
            <div class="company-info">
                <h1>{{ $invoice->vendor()['name'] ?? 'Company Name' }}</h1>
                @if(isset($invoice->vendor()['address']))
                    <p>{{ $invoice->vendor()['address'] }}</p>
                @endif
                @if(isset($invoice->vendor()['phone']))
                    <p>Phone: {{ $invoice->vendor()['phone'] }}</p>
                @endif
                @if(isset($invoice->vendor()['email']))
                    <p>Email: {{ $invoice->vendor()['email'] }}</p>
                @endif
            </div>
            <div class="invoice-meta">
                <h2>INVOICE</h2>
                <p><strong>#{{ $invoice->number() }}</strong></p>
                <p>Date: {{ $invoice->date() }}</p>
                @if($invoice->dueDate())
                    <p>Due: {{ $invoice->dueDate() }}</p>
                @endif
            </div>
        </div>

        <!-- Bill To -->
        <div class="invoice-details">
            <div class="detail-section">
                <h3>Bill To</h3>
                <p><strong>{{ $invoice->customer()['name'] ?? 'Customer Name' }}</strong></p>
                @if(isset($invoice->customer()['address']))
                    <p>{{ $invoice->customer()['address'] }}</p>
                @endif
                @if(isset($invoice->customer()['phone']))
                    <p>{{ $invoice->customer()['phone'] }}</p>
                @endif
                @if(isset($invoice->customer()['email']))
                    <p>{{ $invoice->customer()['email'] }}</p>
                @endif
            </div>
        </div>

        <!-- Items Table -->
        <table class="invoice-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th class="text-right">Qty</th>
                    <th class="text-right">Unit Price</th>
                    <th class="text-right">Total</th>
                </tr>
            </thead>
            <tbody>
                @foreach($invoice->items() as $item)
                    <tr>
                        <td>{{ $item['description'] }}</td>
                        <td class="text-right">{{ $item['quantity'] }}</td>
                        <td class="text-right">{{ $invoice->formatAmount($item['unit_price']) }}</td>
                        <td class="text-right">{{ $invoice->formatAmount($item['total']) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <!-- Summary -->
        <div class="invoice-summary">
            <div class="summary-row">
                <span>Subtotal:</span>
                <span>{{ $invoice->formatAmount($invoice->subtotal()) }}</span>
            </div>
            @if($invoice->tax() > 0)
                <div class="summary-row">
                    <span>Tax:</span>
                    <span>{{ $invoice->formatAmount($invoice->tax()) }}</span>
                </div>
            @endif
            <div class="summary-row total">
                <span>Total:</span>
                <span>{{ $invoice->formatAmount($invoice->total()) }}</span>
            </div>
        </div>

        <!-- Notes -->
        @if($invoice->notes())
            <div class="invoice-notes">
                <h3>Notes</h3>
                <p>{{ $invoice->notes() }}</p>
            </div>
        @endif

        <!-- Terms -->
        @if($invoice->terms())
            <div class="invoice-notes">
                <h3>Payment Terms</h3>
                <p>{{ $invoice->terms() }}</p>
            </div>
        @endif

        <!-- Footer -->
        <div class="invoice-footer">
            <p>Thank you for your business!</p>
        </div>
    </div>
</body>
</html>
