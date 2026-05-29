<!DOCTYPE html>
<html>

<head>
    <meta charset="utf-8">
    <title>Offer Letter - {{ $applicant->applicant_name }}</title>
    <style>
        @page {
            size: A4;
            margin: 0;
            /* Margin 0 to allow the footer to touch the bottom */
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 14px;
            color: #000;
            line-height: 1.6;
            margin: 0;
            padding: 20mm;
            /* Adding padding here instead of @page margin */
        }

        /* Header Styles */
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 50px;
        }

        .logo-cell {
            width: 30%;
            text-align: left;
            vertical-align: middle;
        }

        .company-info-cell {
            width: 70%;
            text-align: left;
            font-size: 13px;
            line-height: 1.3;
            vertical-align: middle;
        }

        .company-name {
            font-size: 22px;
            font-weight: bold;
            color: #000080;
            /* Dark Blue */
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .company-details {
            font-size: 11px;
            font-weight: bold;
        }

        /* Title */
        .document-title {
            text-align: center;
            font-weight: bold;
            text-decoration: underline;
            font-size: 18px;
            margin-bottom: 40px;
            text-transform: uppercase;
        }

        /* Content Styles */
        .meta-data {
            margin-bottom: 20px;
        }

        .salutation {
            margin-bottom: 20px;
        }

        .content {
            text-align: justify;
            margin-bottom: 20px;
        }

        .highlight {
            font-weight: bold;
        }

        /* Signature Section */
        .signature-container {
            margin-top: 50px;
            width: 100%;
        }

        .sig-table {
            width: 100%;
            border-collapse: collapse;
        }

        .sig-box {
            width: 50%;
            vertical-align: top;
            text-align: left;
        }

        .sig-box-right {
            text-align: right;
        }

        .sig-line {
            margin-top: 60px;
            font-weight: bold;
            font-size: 12px;
        }

        /* Footer Address Bar */
        .footer-bar {
            position: fixed;
            bottom: 0;
            left: 0;
            width: 100%;
            background-color: #ffcc00;
            /* Yellowish color from image */
            color: #000;
            text-align: center;
            font-size: 12px;
            font-weight: bold;
            padding: 10px 0;
            border-top: 2px solid #000;
        }

        .logo-img {
            width: 120px;
            height: auto;
        }

        .signature-img {
            width: 140px;
            height: auto;
            margin-bottom: -10px;
        }
    </style>
</head>

<body>
    <!-- Header Section -->
    <table class="header-table">
        <tr>
            <td class="logo-cell">
                <img src="{{ public_path('images/rjlogo.png') }}" class="logo-img" alt="RJ Logo">
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

    <!-- Document Title -->
    <div class="document-title">
        OFFER LETTER
    </div>

    <!-- Date -->
    <div class="meta-data">
        Offer Date: {{ now()->format('d/m/Y') }}
    </div>

    <!-- Salutation -->
    <div class="salutation">
        Dear {{ $applicant->applicant_name }},
    </div>

    <!-- Main Content -->
    <div class="content">
        <p>
            Congratulations! We are pleased to confirm that you have been selected to work for
            <span class="highlight">RJ HOTMAX REALTY PVT LTD</span>. We are delighted to make you the following job
            offer.
            The position we are offering you is that of <span
                class="highlight">{{ ucwords(str_replace('_', ' ', $applicant->designation->value)) }}</span>
            with a monthly cost to company <span class="highlight">INR
                {{ number_format($applicant->salary, 2) }}/-</span>.
        </p>

        <p>
            We would like you to start
            {{ $applicant->date_of_joining ? \Carbon\Carbon::parse($applicant->date_of_joining)->format('d/m/Y') : '[Date]' }}
            reporting at 10.00 AM. Please report to the managing director, for documentation and orientation.
            Please sign the enclosed copy of this letter and return it to me by
            {{ $applicant->date_of_joining ? \Carbon\Carbon::parse($applicant->date_of_joining)->format('d/m/Y') : '[Date]' }}
            to indicate your acceptance of this offer.
        </p>

        <p>
            We are confident you will be able to make a significant contribution to the success of our
            RJ HOTMAX REALTY PVT LTD and look forward to working.
        </p>
    </div>

    <!-- Sign Off -->
    <div class="signature-container">
        <p>Sincerely,</p>
        <p>RJ HOTMAX REALTY PVT LTD</p>

        <table class="sig-table">
            <tr>
                <td class="sig-box">
                    <div class="sig-line">

                        <img src="{{ public_path('images/rjsign.jpeg') }}" alt="Signature" class="signature-img">

                        <br>

                        __________________________<br>
                        SUBRAT RANJAN JENA<br>
                        AUTHORISED SIGNATORY
                    </div>
                </td>
                <td class="sig-box sig-box-right">
                    <div class="sig-line">
                        __________________________<br>
                        SIGNATURE OF THE EMPLOYEE
                    </div>
                </td>
            </tr>
        </table>
    </div>

    <!-- Fixed Footer Address Bar -->
    <div class="footer-bar">
        Plot No: 146, Corner Stone, 3rd floor, Flat -302, Niladri Vihar, Chandrasekharpur Bhubaneswar-751021, Odisha
    </div>
</body>

</html>
