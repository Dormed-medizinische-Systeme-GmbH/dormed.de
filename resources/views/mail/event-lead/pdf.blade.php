<!DOCTYPE html>
<html lang="de">
<head>
<meta charset="utf-8">
<title>Lead {{ $eventName }}: {{ $praxisName }}</title>
<style>
    @page { margin: 0; }
    body { margin: 0; font-family: 'DejaVu Sans', sans-serif; color: #12141c; }
</style>
</head>
<body>
    <table width="100%" cellpadding="0" cellspacing="0">
        <tr>
            <td style="background-color:#0B5FB7;padding:12px 40px;">
                <span style="color:#ffffff;font-size:11px;font-weight:bold;">Veranstaltung: {{ $eventName }}</span>
            </td>
        </tr>
        <tr>
            <td style="background-color:#0c162c;padding:24px 40px;">
                <span style="color:#ffffff;font-size:16px;font-weight:bold;">Neuer Lead über das Veranstaltungsformular</span>
            </td>
        </tr>
        <tr>
            <td style="padding:32px 40px;">
                @include('mail.event-lead._lead-details')
                <p style="margin:32px 0 0;color:#4c5770;font-size:10px;">Eingegangen am {{ $receivedAt->format('d.m.Y H:i') }} Uhr</p>
            </td>
        </tr>
    </table>
</body>
</html>
