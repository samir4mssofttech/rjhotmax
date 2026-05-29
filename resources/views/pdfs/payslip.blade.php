<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">

    <style>
        @page {
            size: A4;
            margin: 0;
        }

        body {
            font-family: 'Helvetica', sans-serif;
            font-size: 12px;
            color: #000;
            margin: 0;
            padding: 15mm 20mm;
        }

        .text-center {
            text-align: center;
        }

        .text-right {
            text-align: right;
        }

        .bold {
            font-weight: bold;
        }

        /* Header Styles - Matching Offer Letter */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
            border-bottom: 2px solid #000;
            padding-bottom: 10px;
        }

        .logo-cell {
            width: 30%;
            text-align: left;
            vertical-align: middle;
        }

        .company-info-cell {
            width: 70%;
            text-align: left;
            vertical-align: middle;
        }

        .company-name {
            font-size: 20px;
            font-weight: bold;
            color: #000080;
            /* Dark Blue from Offer Letter */
            text-transform: uppercase;
            margin-bottom: 5px;
        }

        .company-details {
            font-size: 10px;
            font-weight: bold;
            line-height: 1.3;
        }

        .logo-img {
            width: 120px;
            height: auto;
        }

        .document-title {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            font-size: 16px;
            margin: 20px 0;
            text-transform: uppercase;
        }

        .info-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .info-table td {
            padding: 4px 0;
            vertical-align: top;
            font-size: 12px;
        }

        .label {
            color: #555;
            font-weight: bold;
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
        }

        .salary-table th,
        .salary-table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        .salary-table th {
            background-color: #f2f2f2;
            font-weight: bold;
        }

        .amount-col {
            text-align: right !important;
            width: 25%;
        }

        .footer-table {
            width: 100%;
            border-collapse: collapse;
            border: 1px solid #000;
            margin-top: 0;
        }

        .footer-table td {
            border: 1px solid #000;
            padding: 8px;
        }

        .signature-section {
            margin-top: 40px;
            width: 100%;
        }

        .signature-box {
            width: 200px;
            text-align: center;
            float: right;
        }

        .signature-line {
            border-top: 1px solid #000;
            margin-bottom: 5px;
            font-weight: bold;
            font-size: 12px;
        }

        .signature-img {
            width: 140px;
            height: auto;
            margin-bottom: -10px;
        }

        /* Matching the Yellow Footer Bar from Offer Letter */
        .footer-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #ffcc00;
            color: #000;
            text-align: center;
            font-size: 11px;
            font-weight: bold;
            padding: 10px 0;
            border-top: 2px solid #000;
        }
    </style>
</head>

<body>

    <!-- Header Section (Updated to RJ HOTMAX) -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path($logoPath) }}" class="logo-img" alt="RJ Logo">
            </td>
            <td class="company-info-cell">
                <div class="company-name">RJ HOTMAX REALTY PVT LTD</div>
                <div class="company-details">
                    CIN-U452020R2016PTC020075<br>
                    PHONE NO-9439777389, 9778333433, WEBSITE-WWW.RJHOTMAX.COM<br>
                    EMAIL ID-SUBRAT@RJHOTMAX.COM
                </div>
            </td>
        </tr>
    </table>

    <div class="document-title">
        PAYSLIP FOR THE MONTH OF {{ $month }}
    </div>

    <!-- Employee Information -->
    <table class="info-table">
        <tr>
            <td width="15%" class="label">Emp ID</td>
            <td width="35%">{{ $employee->account_number ?? ($employee->account_no ?? 'N/A') }}</td>
            <td width="15%" class="label">Employee Name:</td>
            <td width="35%">{{ $employee->name }}</td>
        </tr>
        <tr>
            <td class="label">PF. No.</td>
            <td>{{ $employee->pf_number ?? 'N/A' }}</td>
            <td class="label">ESI No.</td>
            <td>{{ $employee->esi_number ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">NOD</td>
            <td>{{ $nod }}</td>

            <td class="label">NDP</td>
            <td>{{ $ndp }}</td>
        </tr>
        <tr>
            <td class="label">DOJ</td>
            <td>{{ $employee->join_date ? \Carbon\Carbon::parse($employee->join_date)->format('d-m-Y') : 'N/A' }}</td>
            <td class="label">Designation</td>
            <td>
                {{ ucwords(str_replace('_', ' ', $employee->designation ?? 'Accountant')) }}
            </td>
        </tr>
        <tr>
            <td class="label">A/c No</td>
            <td>{{ $employee->bank_account_number ?? ($employee->bank_account_number ?? 'N/A') }}</td>
            <td class="label">Bank Name</td>
            <td>{{ $employee->bank_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">PAN</td>
            <td>{{ $employee->pan_number ?? 'N/A' }}</td>
            <td class="label">DOB</td>
            <td>{{ $employee->dob ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">UAN</td>
            <td>{{ $employee->uan_number ?? 'N/A' }}</td>
            <td class="label">LOP</td>
            <td>{{ number_format($lop,2) }}</td>
        </tr>
        <tr>
            <td class="label">Aadhar No</td>
            <td>{{ $employee->aadhar_number ?? 'N/A' }}</td>
            <td class="label">Remarks</td>
            <td>{{ $employee->remarks ?? 'N/A' }}</td>
        </tr>
    </table>

    <!-- Earnings and Deductions -->
    <table class="salary-table">
        <thead>
            <tr>
                <th width="35%">Earnings</th>
                <th class="amount-col">Amount</th>
                <th width="35%">Deductions</th>
                <th class="amount-col">Amount</th>
            </tr>
        </thead>
        <tbody>
            @php
                $maxRows = max(count($earnings), count($deductions));
            @endphp
            @for ($i = 0; $i < $maxRows; $i++)
                <tr>
                    <td>{{ $earnings[$i]['label'] ?? '' }}</td>
                    <td class="amount-col">{{ isset($earnings[$i]) ? number_format($earnings[$i]['amount'], 2) : '' }}
                    </td>
                    <td>{{ $deductions[$i]['label'] ?? '' }}</td>
                    <td class="amount-col">
                        {{ isset($deductions[$i]) ? number_format($deductions[$i]['amount'], 2) : '' }}</td>
                </tr>
            @endfor
            <tr class="bold">
                <td>Total</td>
                <td class="amount-col">{{ number_format($total_earnings, 2) }}</td>
                <td>Total</td>
                <td class="amount-col">{{ number_format($total_deductions, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <!-- Net Pay Footer -->
    <table class="footer-table">
        <tr>
            <td class="bold" width="20%">Net Pay</td>
            <td class="bold" width="30%" style="font-size: 14px;">{{ number_format($net_pay, 2) }}</td>
            <td colspan="2" style="border: none;"></td>
        </tr>
        <tr>
            <td class="bold">In Words</td>
            <td colspan="3" style="font-style: italic;">{{ $net_pay_words }}</td>
        </tr>
    </table>

    <!-- Signature Section -->
    <div class="signature-section">
        <div class="signature-box">

            <img src="{{ public_path('images/rjsign.jpeg') }}" alt="Signature" class="signature-img">

            <div class="signature-line">
                __________________________<br>
                SUBRAT RANJAN JEN<br>
                AUTHORISED SIGNATORY
            </div>

        </div>
    </div>

    <!-- Fixed Footer Address Bar (Exactly like Offer Letter) -->
    <div class="footer-bar">
        Plot No: 146, Corner Stone, 3rd floor, Flat -302, Niladri Vihar, Chandrasekharpur Bhubaneswar-751021, Odisha
    </div>

</body>

</html>
