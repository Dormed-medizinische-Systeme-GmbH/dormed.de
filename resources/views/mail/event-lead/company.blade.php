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
                            @include('mail.event-lead._lead-details')
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
