<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Bienvenido a MANTIS</title>
</head>
<body style="margin:0; padding:0; background-color:#0c0f0f; color:#e2e2e2; font-family:Arial, Helvetica, sans-serif;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#0c0f0f;">
        <tr>
            <td align="center" style="padding:40px 16px;">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#121414; border:1px solid #333535;">
                    <tr>
                        <td style="padding:32px 40px; border-bottom:1px solid #333535;">
                            <span style="font-family:monospace; font-size:22px; letter-spacing:2px; color:#9cfb2b; font-weight:bold;">MANTIS</span>
                            <span style="font-family:monospace; font-size:11px; letter-spacing:2px; color:#c0caaf; text-transform:uppercase; margin-left:8px;">:: Industrial</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 40px;">
                            <h1 style="margin:0 0 16px; color:#e2e2e2; font-size:22px;">Su empresa ha sido registrada</h1>
                            <p style="margin:0 0 24px; color:#c0caaf; font-size:14px; line-height:22px;">
                                {{ $companyName }} ha sido dada de alta en la plataforma MANTIS. A continuación encontrará el dominio de acceso y las credenciales del administrador.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#1e2020; border:1px solid #333535; margin-bottom:24px;">
                                <tr>
                                    <td style="padding:16px 20px;">
                                        <span style="display:block; font-family:monospace; font-size:11px; letter-spacing:1px; text-transform:uppercase; color:#c0caaf; margin-bottom:4px;">Dominio de acceso</span>
                                        <span style="display:block; color:#e2e2e2; font-size:15px;">{{ $domain }}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px; border-top:1px solid #333535;">
                                        <span style="display:block; font-family:monospace; font-size:11px; letter-spacing:1px; text-transform:uppercase; color:#c0caaf; margin-bottom:4px;">URL de acceso</span>
                                        <a href="{{ $loginUrl }}" style="color:#9cfb2b; font-size:15px; text-decoration:none;">{{ $loginUrl }}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 20px; border-top:1px solid #333535;">
                                        <span style="display:block; font-family:monospace; font-size:11px; letter-spacing:1px; text-transform:uppercase; color:#c0caaf; margin-bottom:8px;">Credenciales por defecto del administrador</span>
                                        <span style="display:block; color:#e2e2e2; font-size:14px;">Usuario: <strong style="color:#e2e2e2;">{{ $username }}</strong></span>
                                        <span style="display:block; color:#e2e2e2; font-size:14px; margin-top:4px;">Contraseña: <strong style="color:#e2e2e2;">{{ $password }}</strong></span>
                                    </td>
                                </tr>
                            </table>

                            @if ($requiresActivation)
                                <p style="margin:0 0 24px; color:#c0caaf; font-size:13px; line-height:20px;">
                                    Su solicitud está <strong style="color:#e2e2e2;">pendiente de activación</strong>. No podrá acceder a la plataforma hasta que su empresa sea activada por nuestro equipo.
                                </p>
                            @endif

                            <p style="margin:0 0 8px; color:#c0caaf; font-size:13px; line-height:20px;">
                                Le recomendamos cambiar la contraseña tras el primer acceso.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:20px 40px; border-top:1px solid #333535; background-color:#0c0f0f; color:#c0caaf; font-size:12px;">
                            MANTIS Industrial · Gestión de mantenimiento
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>