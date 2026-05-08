<!DOCTYPE html>
<html>

<head>
    <title>Appointment Letter - {{ $applicant->applicant_name }}</title>
    <style>
        @page {
            size: A4;
            margin: 20mm;
        }

        body {
            font-family: 'Times New Roman', Times, serif;
            font-size: 13px;
            line-height: 1.5;
            color: #333;
            margin: 0;
            padding: 0;
        }

        .page-container {
            width: 100%;
            max-width: 800px;
            margin: auto;
        }

        /* Force a new page for Annexure */
        .page-break {
            page-break-after: always;
        }

        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }

        .company-logo {
            width: 150px;
            height: auto;
        }

        .company-details {
            text-align: right;
            font-size: 11px;
            line-height: 1.3;
        }

        .company-name-header {
            font-size: 16px;
            font-weight: bold;
            margin-bottom: 5px;
            text-transform: uppercase;
        }

        .document-title {
            text-align: center;
            text-decoration: underline;
            font-weight: bold;
            font-size: 16px;
            margin: 40px 0;
            text-transform: uppercase;
        }

        .content {
            text-align: justify;
        }

        .terms-list {
            margin-top: 20px;
            padding-left: 20px;
        }

        .terms-list li {
            margin-bottom: 10px;
            text-align: justify;
        }

        .footer-section {
            margin-top: 40px;
        }

        .signature-table {
            width: 100%;
            margin-top: 50px;
        }

        .acceptance-section {
            margin-top: 60px;
            border-top: 1px solid #ccc;
            padding-top: 20px;
        }

        .acceptance-title {
            color: #2c3e50;
            font-weight: bold;
            font-size: 14px;
            margin-bottom: 15px;
        }

        .sig-line {
            display: inline-block;
            width: 200px;
            border-bottom: 1px solid #000;
            margin-left: 10px;
        }


        /* Annexure Table Styles */
        .annexure-title {
            text-align: center;
            font-weight: bold;
            font-size: 16px;
            margin: 30px 0;
        }

        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
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
            text-align: center;
        }

        .text-right {
            text-align: right !important;
        }

        .font-bold {
            font-weight: bold;
        }
    </style>
</head>

<body>
    <div class="page-container">

        <!-- PAGE 1: APPOINTMENT LETTER -->
        <div class="page-break">
            <!-- Header Section -->
            <table class="header-table">
                <tr>
                    <td style="width: 50%; text-align: left;">
                        <!-- Replace with your actual logo path -->
                        <img src="{{ public_path('images/logo.png') }}" class="company-logo" alt="Logo">
                        <div style="font-weight: bold; font-size: 12px; margin-top: 5px;">{{ config('app.name') }}</div>
                    </td>
                    <td class="company-details">
                        <div class="company-name-header">{{ config('app.name') }}</div>
                        CIN-U452020R2016PTC020075<br>
                        PHONE NO-9439777389, 9778333433<br>
                        WEBSITE-WWW.RJHOTMAX.COM<br>
                        EMAIL ID-SUBRAT@RJHOTMAX.COM
                    </td>
                </tr>
            </table>

            <div class="document-title">Letter of Appointment</div>

            <div class="content">
                <p>Dear Mr. {{ $applicant->applicant_name }},</p>

                <p>As per the directives of management and exigency of services, we are pleased to appoint you as
                    <strong>{{ $applicant->position->value ?? $applicant->position }}</strong> in our Organization. You
                    will be based at our <strong>{{ $applicant->branch?->name ?? 'Head Office' }}</strong> in
                    <strong>{{ $applicant->city }}</strong>. With effect from
                    <strong>{{ \Carbon\Carbon::parse($applicant->date_of_joining)->format('d/m/Y') }}</strong> on the
                    following terms and conditions.
                </p>

                <ol class="terms-list">
                    <li>You shall be paid a consolidated remuneration package of
                        <strong>{{ $applicant->salary ?? 0 }}/-</strong> as take-home p.m as discussed and mutually
                        agreed at the time of interview. You will be eligible to other perquisites/facilities/benefits
                        as application from time to time.
                    </li>

                    <li>You shall be on probation for a period of Six months from the date of joining. Your probation
                        can be extended if your performance and effectiveness on the job is not found satisfactory. You
                        will remain on probation unless you are confirmed in writing.</li>

                    <li>After successful completion of your probation period, if your performance and effectiveness on
                        the job is found satisfactory, you will be confirmed on your post in writing and you will
                        continue to work within the framework of the organizational structure, policies and directions
                        as may be given to by the management.</li>

                    <li>You will report to the <strong>Managing Director</strong>.</li>

                    <li>Your annually increments will be applicable after completion of 12 months of employment with us.
                    </li>

                    <li>The working days will be followed by you from Monday to Saturday. The working hours for your
                        profile will be 10:00AM to 07:00 PM.</li>

                    <li>You shall diligently and satisfactorily carry out instructions given to you to the best of your
                        knowledge, skill and ability. Your ability will be judged according to the performance.</li>

                    <li>Continuation of your service with the company will be subject to the continuation of the
                        contract with client; however, the management at its own direction on availability of a position
                        at some other site may resurrect your services by way of transfer.</li>

                    <li>You will automatically retire without notice on your reaching the age of 60 years or earlier if
                        found unfit.</li>

                    <li>Either party may terminate this employment by providing 30 (thirty) days' written notice or
                        salary in lieu of the notice period, subject to company policies.</li>

                    <li>You are expected to adhere to the company's rules, policies, and code of conduct.</li>

                    <li>You shall maintain confidentiality of all company-related information during and after your
                        employment.</li>

                    <li>Any violation of company policies may lead to disciplinary action, including termination.</li>
                </ol>

                <p style="margin-top: 20px;">Please return the duplicate copy of this letter of appointment after having
                    signed the statement at the bottom that you agreed to and accepted the terms stated above. We
                    congratulate you on your appointment and wish you a long career with us.</p>
            </div>

            <div class="footer-section">
                <p>With best wishes,</p>
                <p>For <strong>{{ config('app.name') }}</strong></p>
                <br><br>
                <p><strong>Subrat Ranjan Jena</strong><br>
                    Managing Director</p>
            </div>

            <div class="acceptance-section">
                <div class="acceptance-title">Acceptance of Appointment</div>
                <p>I, {{ $applicant->applicant_name }}, hereby accept the terms and conditions of this appointment
                    letter.</p>
                <p>
                    Signature: <span class="sig-line"></span>
                    {{-- &nbsp;&nbsp; Date: {{ \Carbon\Carbon::parse($applicant->date_of_joining)->format('d/m/Y') }} --}}
                    &nbsp;&nbsp; Date: <span class="sig-line"></span>

                </p>
            </div>
        </div>

        <!-- PAGE 2: ANNEXURE 1 (SALARY BREAKUP) -->
        <div class="page-container">
            <table class="header-table">
                <tr>
                    <td style="width: 50%; text-align: left;">
                        <img src="{{ public_path('images/logo.png') }}" class="company-logo" alt="Logo">
                        <div style="font-weight: bold; font-size: 12px; margin-top: 5px;">{{ config('app.name') }}
                        </div>
                    </td>
                    <td class="company-details">
                        <div class="company-name-header">{{ config('app.name') }}</div>
                        CIN-U452020R2016PTC020075<br>
                        PHONE NO-9439777389, 9778333433<br>
                        WEBSITE-WWW.RJHOTMAX.COM<br>
                        EMAIL ID-SUBRAT@RJHOTMAX.COM
                    </td>
                </tr>
            </table>

            <div class="annexure-title">Annexure 1</div>

            <div style="margin-bottom: 20px; line-height: 1.8;">
                <strong>NAME :</strong> {{ $applicant->applicant_name }}<br>
                <strong>DESIGNATION :</strong> {{ $applicant->position->value ?? $applicant->position }}<br>
                <strong>DATE OF JOINING :</strong>
                {{ \Carbon\Carbon::parse($applicant->date_of_joining)->format('d/m/Y') }}
            </div>

            <p><strong>Salary Details:</strong></p>

            <table class="salary-table">
                <thead>
                    <tr>
                        <th>Particulars</th>
                        <th>Monthly Salary Break Up</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Earnings -->
                    <tr>
                        <td>Basic</td>
                        <td class="text-right">{{ number_format($applicant->salary * 0.4, 0) }}</td>
                    </tr>
                    <tr>
                        <td>HRA</td>
                        <td class="text-right">{{ number_format($applicant->salary * 0.2, 0) }}</td>
                    </tr>
                    <tr>
                        <td>Conveyance</td>
                        <td class="text-right">{{ number_format($applicant->salary * 0.08, 0) }}</td>
                    </tr>
                    <tr>
                        <td>Medical Allowance</td>
                        <td class="text-right">{{ number_format($applicant->salary * 0.2, 0) }}</td>
                    </tr>
                    <tr>
                        <td>Other Allowance</td>
                        <td class="text-right">{{ number_format($applicant->salary * 0.12, 0) }}</td>
                    </tr>
                    <tr class="font-bold">
                        <td>Gross Salary</td>
                        <td class="text-right">{{ number_format($applicant->salary, 0) }}</td>
                    </tr>
                    <!-- Additions -->
                    <tr>
                        <td>Add: ESIC</td>
                        <td class="text-right">0</td>
                    </tr>
                    <tr>
                        <td>Add: PF</td>
                        <td class="text-right">0</td>
                    </tr>
                    <tr>
                        <td>Add: Accidental Insurance</td>
                        <td class="text-right">0</td>
                    </tr>
                    <tr>
                        <td>Add: Mediclaim</td>
                        <td class="text-right">0</td>
                    </tr>
                    <tr class="font-bold">
                        <td>CTC per Month</td>
                        <td class="text-right">{{ number_format($applicant->salary, 0) }}</td>
                    </tr>
                    <!-- Deductions (Employee Contribution) -->
                    <tr>
                        <td>Less: ESIC (0.75% of Gross)</td>
                        <td class="text-right">{{ number_format($applicant->salary * 0.0075, 0) }}</td>
                    </tr>
                    <tr>
                        <td>Less: TDS</td>
                        <td class="text-right">0</td>
                    </tr>
                    <tr>
                        <td>Less: PF (12% of Basic)</td>
                        <td class="text-right">{{ number_format($applicant->salary * 0.4 * 0.12, 0) }}</td>
                    </tr>
                    <tr>
                        <td>Less: Bonus</td>
                        <td class="text-right">0</td>
                    </tr>
                    <tr>
                        <td>Less: PT</td>
                        <td class="text-right">0</td>
                    </tr>
                    {{-- <tr class="font-bold" style="background-color: #f9f9f9;">
                        <td>In Hand Salary Per Month</td>
                        <td class="text-right">{{ number_format($applicant->salary - 0, 0) }}/-</td>
                    </tr> --}}

                    <!-- Final Calculation -->
            <tr class="font-bold" style="background-color: #f9f9f9;">
                <td>In Hand Salary Per Month</td>
                <td class="text-right">
                    {{-- Gross - PF - ESI --}}
                    {{ number_format($applicant->salary - (($applicant->salary * 0.4) * 0.12) - ($applicant->salary * 0.0075), 0) }}/-
                </td>
            </tr>
                </tbody>
            </table>
        </div>
    </div>
</body>

</html>
