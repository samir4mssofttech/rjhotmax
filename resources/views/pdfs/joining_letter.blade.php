<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Offer of Employment - {{ $applicant->applicant_name }}</title>
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            color: #000;
            line-height: 1.5;
            margin: 0;
            padding: 0;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .logo-cell {
            width: 50%;
            text-align: left;
        }

        .company-info-cell {
            width: 50%;
            text-align: right;
            font-size: 12px;
            line-height: 1.4;
        }

        .company-name {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
        }

        .blue-line {
            border-top: 3px solid #3498db;
            margin-bottom: 30px;
            width: 100%;
        }

        .meta-data {
            margin-bottom: 30px;
        }

        .address-section {
            margin-bottom: 40px;
        }

        .subject {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 30px;
            font-size: 15px;
        }

        .content {
            text-align: justify;
            margin-bottom: 30px;
        }

        .terms-list {
            list-style-type: disc;
            margin-left: 20px;
            margin-bottom: 30px;
        }

        .terms-list li {
            margin-bottom: 8px;
        }

        .signature-section {
            margin-top: 40px;
        }

        .acceptance-block {
            margin-top: 60px;
            border-top: 1px solid #ccc;
            padding-top: 20px;
        }

        .acceptance-title {
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 15px;
        }

        .sig-line {
            display: inline-block;
            width: 200px;
            border-bottom: 1px solid #000;
        }

        .logo-img {
            width: 200px;
            height: auto;
        }

        .signature-img {
            width: 140px;
            height: auto;
            margin-bottom: -10px;
        }

        .authorized-signature {
            margin-top: 10px;
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('images/rjlogo.png') }}" class="logo-img" alt="Company Logo">
            </td>
            <td class="company-info-cell">
                <div class="company-name">RJ HOTMAX REALTY PVT LTD</div>
                CIN: U452020R2016PTC020075<br>
                Ph: +91 9439777389, 9778333433<br>
                www.rjhotmax.com | subrat@rjhotmax.com
            </td>
        </tr>
    </table>

    <div class="blue-line"></div>

    <!-- Meta Data -->
    <div class="meta-data">
        Date: {{ now()->format('d M, Y') }}<br>
        Ref No: RJH/OFFER/{{ date('Y') }}/{{ str_pad($applicant->id, 4, '0', STR_PAD_LEFT) }}
    </div>

    <!-- Recipient Address -->
    <div class="address-section">
        To,<br>
        <strong>{{ $applicant->applicant_name }}</strong><br>
        {{ $applicant->address }}<br>
        {{ $applicant->city }}, {{ $applicant->state }}, {{ $applicant->pincode ?? '' }}
    </div>

    <!-- Subject -->
    <div class="subject">
        Subject: Offer of Employment
    </div>

    <!-- Main Content -->
    <div class="content">
        <p>Dear {{ $applicant->applicant_name }},</p>

        <p>With reference to your application and subsequent interview you had with us, we are pleased to offer you the
            position of <strong>{{ ucwords(str_replace('_', ' ', $applicant->designation->value)) }}</strong> with RJ
            HOTMAX
            REALTY PVT LTD.</p>

        <p>The terms and conditions of your appointment are as follows:</p>

        <ul class="terms-list">
            <li><strong>Date of Joining:</strong> Your appointment will be effective from
                {{ $applicant->date_of_joining ? \Carbon\Carbon::parse($applicant->date_of_joining)->format('d M, Y') : '[Joining Date]' }}.
            </li>

            <li><strong>Remuneration:</strong> Your Total Fixed Cost to Company (CTC) will be
                <strong>INR {{ number_format(($applicant->salary ?? 0) * 12, 2) }}</strong> per annum.
            </li>
            <li><strong>Probation Period:</strong> You will be on probation for a period of 6 months.</li>

            <li><strong>Location:</strong> Your initial place of posting will be at our office in Bhubaneswar.</li>

            <li><strong>Notice Period:</strong> 15 days during probation / 30 days after confirmation.</li>
        </ul>

        <p>Please sign and return a duplicate copy of this letter as a token of your acceptance. We look forward to a
            mutually beneficial association.</p>
    </div>
    <br>
    <br>
    <!-- Sign Off -->
    <div class="signature-section">
        <p>Yours Sincerely,</p>
        <p>For <strong>RJ HOTMAX REALTY PVT LTD</strong></p>
        <br><br>
        <div class="authorized-signature">

            <img src="{{ public_path('images/rjsign.jpeg') }}" alt="Signature" class="signature-img">

            <br>

            __________________________<br>
            (Authorized Signatory)

        </div>
    </div>

    <!-- Acceptance Section -->
    <div class="acceptance-block">
        <div class="acceptance-title">ACCEPTANCE</div>
        <p>I accept the above offer and will join on _________________.</p>
        <br>
        <p>
            Signature: <span class="sig-line"></span>
            &nbsp;&nbsp; Date: <span class="sig-line" style="width: 120px;"></span>
        </p>
    </div>
</body>

</html>
