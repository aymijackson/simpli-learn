<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $exam->title }} — Certificate</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #0f172a; }
        .frame { border: 6px solid {{ $isVerified ? '#059669' : '#94a3b8' }}; padding: 40px; text-align: center; }
        .eyebrow { font-size: 12px; letter-spacing: 2px; text-transform: uppercase; color: #64748b; }
        h1 { font-size: 30px; margin: 10px 0 4px; }
        .name { font-size: 24px; font-weight: bold; margin: 24px 0 4px; }
        .exam { font-size: 16px; color: #334155; margin-bottom: 4px; }
        .meta { font-size: 11px; color: #64748b; margin-top: 24px; }
        .signatures { margin-top: 40px; width: 100%; }
        .signatures td { width: 50%; text-align: center; font-size: 11px; color: #334155; padding-top: 6px; border-top: 1px solid #94a3b8; }
        .qr { margin-top: 30px; }
        .qr img { width: 100px; height: 100px; }
        .unverified-watermark { color: #94a3b8; font-size: 13px; margin-top: 16px; font-style: italic; }
    </style>
</head>
<body>
    <div class="frame">
        <p class="eyebrow">{{ $issuerName }}</p>
        <h1>Certificate of Achievement</h1>

        <p class="meta">This certifies that</p>
        <p class="name">{{ $certificate->user->name }}</p>
        <p class="meta">has successfully passed</p>
        <p class="exam">{{ $exam->title }}</p>
        <p class="meta">
            Score: {{ $certificate->attempt->score }}% &middot; Issued {{ $certificate->issued_at->format('F j, Y') }}
            &middot; Certificate #{{ $certificate->id }}
        </p>

        @if (! $isVerified)
            <p class="unverified-watermark">UNVERIFIED — upgrade to a verified certificate for full authenticity details.</p>
        @endif

        <table class="signatures">
            <tr>
                <td>{{ $signatoryName }}<br>{{ $signatoryTitle }}</td>
                <td>{{ $certificate->verification_token }}<br>Verification code</td>
            </tr>
        </table>

        @if ($isVerified && $qrDataUri)
            <div class="qr">
                <img src="{{ $qrDataUri }}" alt="Verification QR code">
                <p class="meta">Scan to verify at {{ $verificationUrl }}</p>
            </div>
        @endif
    </div>
</body>
</html>
