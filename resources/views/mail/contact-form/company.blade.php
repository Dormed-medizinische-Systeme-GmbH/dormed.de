<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
</head>
<body style="margin:0;padding:0;background-color:#f4f5f7;font-family:Arial,Helvetica,sans-serif;color:#12141c;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f4f5f7;padding:32px 16px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" style="max-width:560px;background-color:#ffffff;border-radius:8px;overflow:hidden;">
                    @if ($spamScore !== null)
                    @php
                        $spamPercent = (int) round($spamScore * 100);
                        [$spamColor, $spamBackground] = match (true) {
                            $spamScore >= 0.7 => ['#B91C1C', '#FEE2E2'],
                            $spamScore >= 0.4 => ['#B45309', '#FEF3C7'],
                            default => ['#047857', '#ECFDF5'],
                        };
                    @endphp
                    <tr>
                        <td style="background-color:{{ $spamBackground }};padding:12px 32px;">
                            <span style="color:{{ $spamColor }};font-size:13px;font-weight:bold;letter-spacing:0.3px;">Spam-Score: {{ $spamPercent }} %</span>
                        </td>
                    </tr>
                    @endif
                    <tr>
                        <td style="background-color:#0c162c;padding:24px 32px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">Neue Anfrage über das Kontaktformular</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <table role="presentation" width="100%" style="font-size:15px;line-height:1.6;">
                                <tr>
                                    <td style="padding:6px 0;color:#4c5770;width:140px;">Name</td>
                                    <td style="padding:6px 0;">{{ $name }}</td>
                                </tr>
                                <tr>
                                    <td style="padding:6px 0;color:#4c5770;">E-Mail</td>
                                    <td style="padding:6px 0;"><a href="mailto:{{ $email }}" style="color:#0c162c;">{{ $email }}</a></td>
                                </tr>
                                @if ($telefon)
                                <tr>
                                    <td style="padding:6px 0;color:#4c5770;">Telefon</td>
                                    <td style="padding:6px 0;">{{ $telefon }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding:6px 0;color:#4c5770;">PLZ</td>
                                    <td style="padding:6px 0;">{{ $plz }}</td>
                                </tr>
                                @if ($praxis)
                                <tr>
                                    <td style="padding:6px 0;color:#4c5770;">Praxis</td>
                                    <td style="padding:6px 0;">{{ $praxis }}</td>
                                </tr>
                                @endif
                                @if ($fachgebiet)
                                <tr>
                                    <td style="padding:6px 0;color:#4c5770;">Fachgebiet</td>
                                    <td style="padding:6px 0;">{{ $fachgebiet }}</td>
                                </tr>
                                @endif
                                <tr>
                                    <td style="padding:6px 0;color:#4c5770;">Rückruf gewünscht</td>
                                    <td style="padding:6px 0;">{{ $wantsCallback ? 'Ja' : 'Nein' }}{{ $wantsCallback && $rueckrufDatum ? ' — '.$rueckrufDatum : '' }}</td>
                                </tr>
                            </table>

                            @if ($nachricht)
                            <div style="margin-top:24px;padding-top:20px;border-top:1px solid #e5e7eb;">
                                <p style="margin:0 0 8px;color:#4c5770;font-size:14px;">Nachricht</p>
                                <p style="margin:0;font-size:15px;line-height:1.6;white-space:pre-line;">{{ $nachricht }}</p>
                            </div>
                            @endif
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
