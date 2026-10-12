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
                        <td style="background-color:#0B5FB7;padding:12px 32px;">
                            <span style="color:#ffffff;font-size:13px;font-weight:bold;letter-spacing:0.3px;">Veranstaltung: {{ $eventName }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#0c162c;padding:24px 32px;">
                            <span style="color:#ffffff;font-size:18px;font-weight:bold;">Neuer Lead über das Veranstaltungsformular</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px;">
                            <table role="presentation" width="100%" style="font-size:15px;line-height:1.6;">
                                <tr>
                                    <td style="padding:6px 0;color:#4c5770;width:140px;">Praxis / Name</td>
                                    <td style="padding:6px 0;">{{ $praxisName }}</td>
                                </tr>
                                @if ($strasse)
                                <tr>
                                    <td style="padding:6px 0;color:#4c5770;">Straße</td>
                                    <td style="padding:6px 0;">{{ $strasse }}</td>
                                </tr>
                                @endif
                                @if ($plz || $ort)
                                <tr>
                                    <td style="padding:6px 0;color:#4c5770;">PLZ / Ort</td>
                                    <td style="padding:6px 0;">{{ trim($plz.' '.$ort) }}</td>
                                </tr>
                                @endif
                                @if ($email)
                                <tr>
                                    <td style="padding:6px 0;color:#4c5770;">E-Mail</td>
                                    <td style="padding:6px 0;"><a href="mailto:{{ $email }}" style="color:#0c162c;">{{ $email }}</a></td>
                                </tr>
                                @endif
                                @if ($telefon)
                                <tr>
                                    <td style="padding:6px 0;color:#4c5770;">Telefon</td>
                                    <td style="padding:6px 0;">{{ $telefon }}</td>
                                </tr>
                                @endif
                                @if ($interesse)
                                <tr>
                                    <td style="padding:6px 0;color:#4c5770;">Interesse an</td>
                                    <td style="padding:6px 0;">{{ $interesse }}</td>
                                </tr>
                                @endif
                                @if ($budget)
                                <tr>
                                    <td style="padding:6px 0;color:#4c5770;">Budget</td>
                                    <td style="padding:6px 0;">{{ $budget }}</td>
                                </tr>
                                @endif
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
