<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Payout Summary — {{ $month }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; margin: 30px; }
        h2 { text-align: center; margin-bottom: 5px; }
        p.sub { text-align: center; color: #555; margin-bottom: 20px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #333; padding: 8px 10px; }
        th { background: #1a1a2e; color: #fff; text-align: left; }
        td { text-align: left; }
        td.right, th.right { text-align: right; }
        tr:nth-child(even) { background: #f2f2f2; }
        .total-row td { font-weight: bold; background: #e2e8f0; }
    </style>
</head>
<body>
    <h2>Payout Summary</h2>
    <p class="sub">Month: {{ $month }}</p>

    <table>
        <thead>
            <tr>
                <th style="width: 6%;">Sl No</th>
                <th style="width: 24%;">Employee Name</th>
                <th style="width: 22%;">Account No</th>
                <th style="width: 16%;">IFSC Code</th>
                <th style="width: 17%;">Bank Name</th>
                <th class="right" style="width: 15%;">Amount (₹)</th>
            </tr>
        </thead>
        <tbody>
            @php $totalAmount = 0; @endphp
            @foreach ($rows as $row)
                @php $totalAmount += $row['amount']; @endphp
                <tr>
                    <td>{{ $row['sl'] }}</td>
                    <td>{{ $row['name'] }}</td>
                    <td>{{ $row['account_no'] }}</td>
                    <td>{{ $row['ifsc'] }}</td>
                    <td>{{ $row['bank_name'] }}</td>
                    <td class="right">{{ number_format($row['amount'], 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td colspan="5" style="text-align: right;">Total</td>
                <td class="right">{{ number_format($totalAmount, 2) }}</td>
            </tr>
        </tbody>
    </table>
</body>
</html>
