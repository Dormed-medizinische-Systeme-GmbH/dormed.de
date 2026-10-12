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
                    <tr>
                        <td style="background-color:#0c162c;padding:24px 32px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">SONORING DORMED</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <p style="margin:0 0 24px;font-size:16px;line-height:1.5;">Guten Tag,</p>

                            <!-- Check-Icon -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="padding-bottom:20px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" width="60" height="60" style="width:60px;height:60px;background-color:#ECFDF5;border:2px solid #A7F3D0;border-radius:50%;">
                                            <tr>
                                                <td align="center" valign="middle" style="width:60px;height:60px;font-size:26px;line-height:26px;color:#10B981;font-weight:bold;">&#10003;</td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Eyebrow / Headline / Subline -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td align="center" style="font-family:'Courier New',Courier,monospace;font-size:11px;letter-spacing:2px;text-transform:uppercase;color:#10B981;padding-bottom:14px;">
                                        {{ $eventName }}
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="font-size:24px;font-weight:bold;letter-spacing:-0.5px;line-height:1.2;color:#0B1A2E;padding-bottom:12px;">
                                        Danke für Ihren Besuch —<br>
                                        <span style="color:#0B5FB7;">wir melden uns bei Ihnen.</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td align="center" style="font-size:15px;line-height:1.6;color:#48576F;padding-bottom:28px;">
                                        Schön, dass Sie uns auf der Veranstaltung „{{ $eventName }}" besucht haben.
                                        Ihre Angaben sind bei uns eingegangen — Ihr persönlicher Ansprechpartner
                                        meldet sich nach der Veranstaltung direkt bei Ihnen.
                                    </td>
                                </tr>
                            </table>

                            <!-- CTAs -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:28px;">
                                <tr>
                                    <td align="center">
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="padding:0 6px 12px;">
                                                    <a href="{{ route('ultraschallgeraete.index') }}" style="display:inline-block;background-color:#0B5FB7;background-image:linear-gradient(90deg,#093A7E,#3EB2F0);color:#ffffff;font-size:14px;font-weight:bold;text-decoration:none;padding:12px 22px;">Geräte entdecken</a>
                                                </td>
                                                <td style="padding:0 6px 12px;">
                                                    <a href="{{ route('veranstaltungen') }}" style="display:inline-block;background-color:#ffffff;border:1px solid rgba(9,58,126,0.25);color:#0B5FB7;font-size:14px;font-weight:bold;text-decoration:none;padding:11px 22px;">Weitere Termine</a>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                            </table>

                            <!-- Direktkontakt -->
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-top:1px solid rgba(9,58,126,0.1);">
                                <tr>
                                    <td align="center" style="padding-top:20px;">
                                        <span style="font-family:'Courier New',Courier,monospace;font-size:10px;letter-spacing:1.5px;text-transform:uppercase;color:rgba(9,58,126,0.4);">Oder direkt:</span>
                                        <br><br>
                                        <a href="tel:+4923118860" style="color:#0B1A2E;font-size:14px;font-weight:bold;text-decoration:none;">📞 02301 / 188-600</a>
                                        &nbsp;&nbsp;&nbsp;
                                        <a href="mailto:mail@dormed.de" style="color:#0B1A2E;font-size:14px;font-weight:bold;text-decoration:none;">✉️ mail@dormed.de</a>
                                    </td>
                                </tr>
                            </table>

                            <p style="margin:28px 0 0;font-size:16px;line-height:1.5;">
                                Mit freundlichen Grüßen<br>
                                Ihr Team von SONORING DORMED
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
