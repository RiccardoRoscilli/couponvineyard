<!doctype html>
<html xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml"
    xmlns:o="urn:schemas-microsoft-com:office:office">

<head>
    <title> Discount Light </title>
    <!--[if !mso]><!-->
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <!--<![endif]-->
    <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href='https://fonts.googleapis.com/css?family=Quicksand' rel='stylesheet'>
    <link href='https://fonts.googleapis.com/css?family=Nunito Sans' rel='stylesheet'>
    <style type="text/css">
        @page {
            margin: 0;
            padding: 0;
        }

        #outlook a {
            padding: 0;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Quicksand';
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
        }

        .nunito {
            font-family: 'Nunito Sans', sans-serif;
        }

        table,
        td {
            border-collapse: collapse;
            mso-table-lspace: 0pt;
            mso-table-rspace: 0pt;
        }

        img {
            border: 0;
            height: auto;
            line-height: 100%;
            outline: none;
            text-decoration: none;
            -ms-interpolation-mode: bicubic;
        }

        p {
            display: block;
            margin: 13px 0;
        }

        @media only screen and (min-width:480px) {
            .mj-column-per-100 {
                width: 100% !important;
                max-width: 100%;
            }
        }

        @media only screen and (max-width:480px) {
            table.mj-full-width-mobile {
                width: 100% !important;
            }

            td.mj-full-width-mobile {
                width: auto !important;
            }
        }
    </style>
    <style media="screen and (min-width:480px)">
        .moz-text-html .mj-column-per-100 {
            width: 100% !important;
            max-width: 100%;
        }
    </style>
</head>

<body style="word-spacing:normal;background:#ffffff;background-color:#ffffff">
    <div style="background:#FFFFFF;background-color:#FFFFFF">
        <div class="body-section"
            style="-webkit-box-shadow: 1px 4px 11px 0px rgba(0, 0, 0, 0.15); -moz-box-shadow: 1px 4px 11px 0px rgba(0, 0, 0, 0.15); box-shadow: 1px 4px 11px 0px rgba(0, 0, 0, 0.15); margin: 0px auto;">
            <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
                style="width:100%;">
                <tbody>
                    <tr>
                        <td
                            style="direction:ltr;font-size:0px;padding:20px 0;padding-bottom:0;padding-top:0;text-align:center;">
                            <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" width="600px" ><table align="center" border="0" cellpadding="0" cellspacing="0" class="" style="width:600px;" width="600" bgcolor="#F7F3F1" ><tr><td style="line-height:0px;font-size:0px;mso-line-height-rule:exactly;"><![endif]-->
                            <div style="background:#FFFFFF;background-color:#FFFFFF;margin:0px auto;">
                                <table align="center" border="0" cellpadding="0" cellspacing="0" role="presentation"
                                    style="background:#FFFFFF;background-color:#FFFFFF;width:100%;">
                                    <tbody>
                                        <tr>
                                            <td
                                                style="direction:ltr;font-size:0px;padding:20px 0;padding-left:15px;padding-right:15px;text-align:center;">
                                                <!--[if mso | IE]><table role="presentation" border="0" cellpadding="0" cellspacing="0"><tr><td class="" style="vertical-align:top;width:570px;" ><![endif]-->
                                                <div class="mj-column-per-100 mj-outlook-group-fix"
                                                    style="font-size:0px;text-align:left;direction:ltr;display:inline-block;vertical-align:top;margin-left:20%;">
                                                    <table border="0" cellpadding="0" cellspacing="0"
                                                        role="presentation" style="vertical-align:top;" width="60%"
                                                        style="margin-left:10%">
                                                        <tbody>
                                                            <tr>
                                                                <td align="center"
                                                                    style="font-size:0px; padding:10px 25px; padding-bottom: 35px;word-break:break-word;">
                                                                    <img src="{{ public_path('logos/' . $logo) }}"
                                                                        style="height:135px" alt="top logo">
                                                                </td>
                                                            </tr>
                                                            <tr>
                                                                <td align="center"
                                                                    style="font-size:0px; padding:10px;padding-top: 25px word-break:break-word;">
                                                                    <div
                                                                        style="font-size:23px;text-align:center;color:#595959;">
                                                                        GIFT CARD</div>
                                                                </td>
                                                            </tr>

                                                            <tr>
                                                                <td align="center" class="nunito"
                                                                    style="font-size:0px;padding:10px;padding-top: 25px; word-break:break-word;">
                                                                    <div
                                                                        style="font-size:13px;text-align:center;color:#595959;">
                                                                        Dear <span
                                                                            style="font-weight: bold">{{ $beneficiarioName }}
                                                                            {{ $beneficiarioSurname }}</span><br>
                                                                    </div>
                                                                    {{-- If description is set --}}
                                                                    @if ($description)
                                                                        <div
                                                                            style="font-size:13px;text-align:center;color:#595959;">
                                                                            {!! str_replace('\n', '<br>', $description) !!}.<br>
                                                                        </div @endif
                                                                        <br>
                                                                        {{-- If details are set --}}
                                                                        @if ($details)
                                                                            <div
                                                                                style="font-size:13px;text-align:center;color:#595959; margin-top: 1%">
                                                                                Details: {!! str_replace('\n', '<br>', $details) !!}.
                                                                            </div>
                                                                        @endif
                                                                </td>
                                                            </tr>
                                                            {{-- If note is set --}}
                                                            @if ($note)
                                                                <tr>
                                                                    <td align="center"
                                                                        style="font-size:0px;padding:10px;padding-top: 15px;word-break:break-word;">
                                                                        <div
                                                                            style="font-size:18px;text-align:center;color:#595959;">
                                                                            Important notes:
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align="center" class="nunito"
                                                                        style="font-size:0px;padding:10px;padding-top: 0px;word-break:break-word;">
                                                                        <div
                                                                            style="font-size:13px;font-weight:400;text-align:center;color:#595959;">
                                                                            {!! str_replace('\n', '<br>', $note) !!}
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                            <tr>
                                                                <td align="center" class="nunito"
                                                                    style="font-size:0px;padding:10px;padding-top: 0px; padding-bottom: 0px;word-break:break-word;">
                                                                    <div
                                                                        style="font-weight:bold;font-size:13px;font-weight:400;text-align:center;color:black;">
                                                                        Gift card number: {{ $coupon_code }}
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            @php
                                                                use Carbon\Carbon;

                                                                // Se $expiration_date è una stringa, converti in Carbon e formatta
                                                                $formattedExpirationDate = Carbon::parse(
                                                                    $expiration_date,
                                                                )->format('d/m/Y');
                                                            @endphp
                                                            <tr>
                                                                <td align="center" class="nunito"
                                                                    style="font-size:0px;padding:10px; padding-top: 0px;word-break:break-word;">
                                                                    <div
                                                                        style="font-size:13px;font-weight:400;text-align:center;color:#595959;">
                                                                        Valid until {{ $formattedExpirationDate }}
                                                                    </div>
                                                                </td>
                                                            </tr>
                                                            {{-- If prenotare is set --}}
                                                            @if ($prenotare)
                                                                <tr>
                                                                    <td align="center"
                                                                        style="font-size:0px;padding:10px;padding-top: 35px;word-break:break-word;">
                                                                        <div
                                                                            style="font-size:21px;font-weight:400;text-align:center;color:#595959;">
                                                                            How to book:
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                                <tr>
                                                                    <td align="center"
                                                                        style="font-size:0px;padding:10px;padding-top: 5px;word-break:break-word;">
                                                                        <div
                                                                            style="font-size:13px;font-weight:400;text-align:center;color:#595959;">
                                                                            {!! str_replace(["\r\n", "\r", "\n"], '<br>', $prenotare) !!}
                                                                        </div>
                                                                    </td>
                                                                </tr>
                                                            @endif
                                                            @if ($location_name == 'Villa Crespi')
                                                                <tr>
                                                                    <td align="center"
                                                                        style="font-size:0px;padding:10px 25px;word-break:break-word;">
                                                                        <img src="{{ public_path('logos/logo_relaischateau.png') }}"
                                                                            style="width: 90px; height: 90px"
                                                                            alt="bottom logo">
                                                                    </td>
                                                                </tr>
                                                            @endif

                                                        </tbody>
                                                    </table>
                                                </div>
                                                <!--[if mso | IE]></td></tr></table><![endif]-->
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                            <!--[if mso | IE]></td></tr></table></td></tr></table><![endif]-->
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
        <!--[if mso | IE]></td></tr></table><![endif]-->
    </div>
</body>

</html>
