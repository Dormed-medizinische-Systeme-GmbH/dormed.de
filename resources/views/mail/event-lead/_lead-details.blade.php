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
    <p style="margin:0;font-size:15px;line-height:1.6;">{!! nl2br(e($nachricht)) !!}</p>
</div>
@endif
