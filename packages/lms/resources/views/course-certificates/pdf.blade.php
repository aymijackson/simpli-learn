<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>{{ $course->title }} — Certificate</title>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; color: #0f172a; }
        .frame { border: 6px solid #059669; padding: 40px; text-align: center; }
        .eyebrow { font-size: 12px; letter-spacing: 2px; text-transform: uppercase; color: #64748b; }
        h1 { font-size: 30px; margin: 10px 0 4px; }
        .name { font-size: 24px; font-weight: bold; margin: 24px 0 4px; }
        .course { font-size: 16px; color: #334155; margin-bottom: 4px; }
        .meta { font-size: 11px; color: #64748b; margin-top: 24px; }
        .qr { margin-top: 30px; }
        .qr img { width: 100px; height: 100px; }
    </style>
</head>
<body>
    <div class="frame">
        <p class="eyebrow">{{ $course->tenant->name }}</p>
        <h1>Certificate of Completion</h1>

        <p class="meta">This certifies that</p>
        <p class="name">{{ $certificate->user->name }}</p>
        <p class="meta">has successfully completed</p>
        <p class="course">{{ $course->title }}</p>
        <p class="meta">
            Issued {{ $certificate->issued_at->format('F j, Y') }} &middot; Certificate #{{ $certificate->id }}
        </p>

        <div class="qr">
            <img src="{{ $qrDataUri }}" alt="Verification QR code">
            <p class="meta">Scan to verify at {{ $verificationUrl }}</p>
        </div>
    </div>
</body>
</html>
