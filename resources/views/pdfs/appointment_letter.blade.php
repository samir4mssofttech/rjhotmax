<!DOCTYPE html>
<html>
<head>
    <title>Appointment Letter - {{ $applicant->applicant_name }}</title>
    <style>
        @page { size: A4; margin: 20mm; }
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
    </style>
</head>
<body>
    <div class="page-container">
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

            <p>As per the directives of management and exigency of services, we are pleased to appoint you as <strong>{{ $applicant->position->value ?? $applicant->position }}</strong> in our Organization. You will be based at our <strong>{{ $applicant->branch?->name ?? 'Head Office' }}</strong> in <strong>{{ $applicant->city }}</strong>. With effect from <strong>{{ \Carbon\Carbon::parse($applicant->date_of_joining)->format('d/m/Y') }}</strong> on the following terms and conditions.</p>

            <ol class="terms-list">
                <li>You shall be paid a consolidated remuneration package of <strong>{{($applicant->salary ?? 0)}}/-</strong> as take-home p.m as discussed and mutually agreed at the time of interview. You will be eligible to other perquisites/facilities/benefits as application from time to time.</li>
                
                <li>You shall be on probation for a period of Six months from the date of joining. Your probation can be extended if your performance and effectiveness on the job is not found satisfactory. You will remain on probation unless you are confirmed in writing.</li>
                
                <li>After successful completion of your probation period, if your performance and effectiveness on the job is found satisfactory, you will be confirmed on your post in writing and you will continue to work within the framework of the organizational structure, policies and directions as may be given to by the management.</li>
                
                <li>You will report to the <strong>Managing Director</strong>.</li>
                
                <li>Your annually increments will be applicable after completion of 12 months of employment with us.</li>
                
                <li>The working days will be followed by you from Monday to Saturday. The working hours for your profile will be 10:00AM to 07:00 PM.</li>
                
                <li>You shall diligently and satisfactorily carry out instructions given to you to the best of your knowledge, skill and ability. Your ability will be judged according to the performance.</li>
                
                <li>Continuation of your service with the company will be subject to the continuation of the contract with client; however, the management at its own direction on availability of a position at some other site may resurrect your services by way of transfer.</li>
                
                <li>You will automatically retire without notice on your reaching the age of 60 years or earlier if found unfit.</li>
                
                <li>Either party may terminate this employment by providing 30 (thirty) days' written notice or salary in lieu of the notice period, subject to company policies.</li>
                
                <li>You are expected to adhere to the company's rules, policies, and code of conduct.</li>
                
                <li>You shall maintain confidentiality of all company-related information during and after your employment.</li>
                
                <li>Any violation of company policies may lead to disciplinary action, including termination.</li>
            </ol>

            <p style="margin-top: 20px;">Please return the duplicate copy of this letter of appointment after having signed the statement at the bottom that you agreed to and accepted the terms stated above. We congratulate you on your appointment and wish you a long career with us.</p>
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
            <p>I, {{ $applicant->applicant_name }}, hereby accept the terms and conditions of this appointment letter.</p>
            <p>
                Signature: <span class="sig-line"></span> 
                {{-- &nbsp;&nbsp; Date: {{ \Carbon\Carbon::parse($applicant->date_of_joining)->format('d/m/Y') }} --}}
                &nbsp;&nbsp; Date: <span class="sig-line"></span> 

            </p>
        </div>
    </div>
</body>
</html>