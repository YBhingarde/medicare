<!doctype html>
<html lang="en">

<body style="margin:0;padding:0;background:#f4f7fc;font-family:Arial,Helvetica,sans-serif;color:#1f2333">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fc;padding:28px 12px">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"
                    style="max-width:480px;background:#ffffff;border-radius:16px;overflow:hidden;border:1px solid #e8eaf0">
                    <tr>
                        <td style="background:#2563eb;padding:20px 28px;color:#ffffff;font-size:20px;font-weight:bold">
                            MediCare</td>
                    </tr>
                    <tr>
                        <td style="padding:28px">
                            <p style="margin:0 0 6px;font-size:16px">Hello{{ $name ? ' ' . $name : '' }},</p>
                            <p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#4a5068">
                                @if ($purpose === 'reset_password')
                                    Use this code to reset your MediCare password.
                                @else
                                    Use this code to verify your email address on MediCare.
                                @endif
                            </p>
                            <div style="text-align:center;margin:0 0 20px">
                                <span
                                    style="display:inline-block;background:#e7f0ff;color:#2563eb;font-size:34px;font-weight:bold;letter-spacing:10px;padding:14px 22px 14px 32px;border-radius:12px">{{ $code }}</span>
                            </div>
                            <p style="margin:0 0 8px;font-size:13px;color:#4a5068">This code is valid for
                                {{ $minutes }} minutes and can be used only once.</p>
                            <p style="margin:0;font-size:13px;color:#7b8194">Never share this code with anyone. MediCare
                                will never ask you for it. If you did not request it, you can safely ignore this email.
                            </p>
                        </td>
                    </tr>
                </table>
                <p style="margin:14px 0 0;font-size:12px;color:#9aa0b4">&copy; {{ date('Y') }} MediCare</p>
            </td>
        </tr>
    </table>
</body>

</html>
