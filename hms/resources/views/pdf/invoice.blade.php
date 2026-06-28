<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice #{{ $bill->id }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333;
            margin: 0;
            padding: 10px;
            font-size: 14px;
            line-height: 1.5;
        }
        .invoice-header {
            width: 100%;
            margin-bottom: 30px;
            border-bottom: 2px solid #3b82f6;
            padding-bottom: 15px;
        }
        .invoice-header td {
            vertical-align: top;
        }
        .title {
            font-size: 28px;
            font-weight: bold;
            color: #1e3a8a;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        .hospital-details {
            text-align: right;
            font-size: 12px;
            color: #4b5563;
        }
        .info-section {
            width: 100%;
            margin-bottom: 30px;
        }
        .info-section td {
            width: 50%;
            vertical-align: top;
        }
        .info-box {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin-right: 10px;
        }
        .info-box-right {
            background-color: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 6px;
            padding: 15px;
            margin-left: 10px;
        }
        .info-title {
            font-weight: bold;
            color: #1e3a8a;
            margin-top: 0;
            margin-bottom: 8px;
            border-bottom: 1px solid #e2e8f0;
            padding-bottom: 5px;
            font-size: 13px;
            text-transform: uppercase;
        }
        .info-row {
            margin-bottom: 4px;
            font-size: 12px;
        }
        .info-label {
            font-weight: bold;
            color: #4b5563;
            display: inline-block;
            width: 100px;
        }
        .items-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 35px;
        }
        .items-table th {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            text-align: left;
            padding: 10px;
            font-size: 12px;
            text-transform: uppercase;
        }
        .items-table td {
            padding: 10px;
            border-bottom: 1px solid #e2e8f0;
            font-size: 13px;
        }
        .items-table tr:nth-child(even) {
            background-color: #f8fafc;
        }
        .totals-section {
            width: 100%;
            margin-bottom: 30px;
        }
        .totals-table {
            float: right;
            width: 280px;
            border-collapse: collapse;
        }
        .totals-table td {
            padding: 8px 12px;
            font-size: 13px;
        }
        .totals-table .label {
            text-align: right;
            font-weight: bold;
            color: #4b5563;
        }
        .totals-table .val {
            text-align: right;
        }
        .totals-table .grand-total {
            background-color: #1e3a8a;
            color: #ffffff;
            font-weight: bold;
            font-size: 15px;
        }
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 11px;
            font-weight: bold;
            text-transform: uppercase;
        }
        .badge-paid { background-color: #d1fae5; color: #065f46; }
        .badge-partial { background-color: #fef3c7; color: #92400e; }
        .badge-issued { background-color: #dbeafe; color: #1e40af; }
        .badge-draft { background-color: #e5e7eb; color: #374151; }
        .footer {
            margin-top: 50px;
            border-top: 1px solid #e2e8f0;
            padding-top: 15px;
            text-align: center;
            font-size: 11px;
            color: #6b7280;
        }
    </style>
</head>
<body>

    <table class="invoice-header">
        <tr>
            <td>
                <div class="title">HMS INVOICE</div>
                <div style="font-size: 12px; color: #4b5563; margin-top: 5px;">
                    Invoice #{{ $bill->id }}
                </div>
            </td>
            <td class="hospital-details">
                <strong>Hospital Management System Inc.</strong><br>
                123 Healthcare Boulevard<br>
                Medical City, MC 90210<br>
                billing@hms.com
            </td>
        </tr>
    </table>

    <table class="info-section">
        <tr>
            <td>
                <div class="info-box">
                    <div class="info-title">Patient Details</div>
                    <div class="info-row"><span class="info-label">Name:</span> {{ $bill->patient->user->name }}</div>
                    <div class="info-row"><span class="info-label">Code:</span> {{ $bill->patient->patient_code }}</div>
                    <div class="info-row"><span class="info-label">Email:</span> {{ $bill->patient->user->email }}</div>
                    <div class="info-row"><span class="info-label">Gender:</span> {{ ucfirst($bill->patient->gender) }}</div>
                    <div class="info-row"><span class="info-label">DOB:</span> {{ $bill->patient->dob }}</div>
                </div>
            </td>
            <td>
                <div class="info-box-right">
                    <div class="info-title">Invoice Details</div>
                    <div class="info-row">
                        <span class="info-label">Status:</span> 
                        <span class="badge badge-{{ $bill->status->value }}">
                            {{ $bill->status->value }}
                        </span>
                    </div>
                    <div class="info-row">
                        <span class="info-label">Issued At:</span> 
                        {{ $bill->issued_at ? $bill->issued_at->format('Y-m-d H:i') : 'N/A' }}
                    </div>
                    <div class="info-row">
                        <span class="info-label">Due Date:</span> 
                        {{ $bill->due_date ? $bill->due_date->format('Y-m-d') : 'N/A' }}
                    </div>
                    @if($bill->appointment && $bill->appointment->doctor)
                    <div class="info-row">
                        <span class="info-label">Primary Doctor:</span> 
                        Dr. {{ $bill->appointment->doctor->user->name }}
                    </div>
                    @endif
                </div>
            </td>
        </tr>
    </table>

    <table class="items-table">
        <thead>
            <tr>
                <th style="width: 15%;">Type</th>
                <th style="width: 50%;">Description</th>
                <th style="width: 10%; text-align: center;">Qty</th>
                <th style="width: 12%; text-align: right;">Unit Price</th>
                <th style="width: 13%; text-align: right;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bill->items as $item)
            <tr>
                <td>{{ ucfirst($item->item_type) }}</td>
                <td>{{ $item->description }}</td>
                <td style="text-align: center;">{{ $item->quantity }}</td>
                <td style="text-align: right;">${{ number_format($item->unit_price, 2) }}</td>
                <td style="text-align: right;">${{ number_format($item->total, 2) }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <div style="width: 100%; clear: both;">
        <table class="totals-table">
            <tr>
                <td class="label">Total Amount:</td>
                <td class="val">${{ number_format($bill->total_amount, 2) }}</td>
            </tr>
            <tr>
                <td class="label">Paid Amount:</td>
                <td class="val">${{ number_format($bill->paid_amount, 2) }}</td>
            </tr>
            <tr class="grand-total">
                <td class="label" style="color: #ffffff;">Balance Due:</td>
                <td class="val">${{ number_format($bill->total_amount - $bill->paid_amount, 2) }}</td>
            </tr>
        </table>
    </div>

    <div style="clear: both;"></div>

    <div class="footer">
        <p>Thank you for choosing Hospital Management System. If you have questions about this invoice, contact our billing office.</p>
        <p>&copy; {{ date('Y') }} Hospital Management System. All rights reserved.</p>
    </div>

</body>
</html>
