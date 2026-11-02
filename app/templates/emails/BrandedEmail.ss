<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html lang="de" style="margin: 0; padding: 0; width: 100%;">
    <head>
        <meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="format-detection" content="telephone=no">
        <title>$Subject</title>
        <link href="https://fonts.googleapis.com/css?family=Roboto:400,700" rel="stylesheet">
        <style type="text/css">
            body { margin: 0; padding: 0; }
            a { color: #b983ff; text-decoration: none; }
            .content p { margin: 0 0 16px 0; }
            @media only screen and (max-width: 600px) {
                .wrapper { width: 100% !important; }
                .inner { padding-left: 16px !important; padding-right: 16px !important; }
            }
        </style>
    </head>

    <body bgcolor="#151515" link="#b983ff" text="#ffffff" style="background-color: #151515; margin: 0; padding: 0 0 32px 0;">
        <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation" style="background-color: #151515; width: 100%;">
            <tr>
                <td align="center">
                    <table class="wrapper" width="600" cellspacing="0" cellpadding="0" border="0" role="presentation" style="table-layout: fixed; width: 600px; background-color: #151515;">
                        <%-- Header: logo left, social icons right --%>
                        <tr>
                            <td class="inner" style="padding: 32px 16px 0 16px;">
                                <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation">
                                    <tr>
                                        <td valign="middle" align="left">
                                            <a href="$WebsiteLink" target="_blank"><img src="$LogoURL" width="68" alt="Ottos Halloweenhaus" border="0" style="display: block; width: 68px;"></a>
                                        </td>
                                        <td valign="middle" align="right">
                                            <% include BrandedEmailSocial %>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td align="center" style="padding-top: 40px; color: #ffffff; font-family: Roboto, arial; font-size: 24px; font-weight: 700; line-height: 1.5;">
                                Ottos Halloweenhaus
                            </td>
                        </tr>

                        <tr>
                            <td style="padding-top: 16px;">
                                <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation"><tr><td height="3" style="border-top: 3px solid #390083; font-size: 0; line-height: 0;">&nbsp;</td></tr></table>
                            </td>
                        </tr>

                        <%-- Content --%>
                        <tr>
                            <td class="inner" style="padding: 40px 32px 0 32px;">
                                <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation">
                                    <tr>
                                        <td align="center" style="padding-top: 16px; color: #ffffff; font-family: Roboto, arial; font-size: 24px; font-weight: 700; line-height: 1.5;">
                                            $Subject
                                        </td>
                                    </tr>
                                    <tr>
                                        <td class="content" align="left" style="padding-top: 16px; color: #ffffff; font-family: Roboto, arial; font-size: 16px; line-height: 1.5; word-break: break-word;">
                                            $Text
                                        </td>
                                    </tr>
                                    <% if $ButtonLink %>
                                    <tr>
                                        <td align="center" style="padding: 15px 0;">
                                            <table width="268" cellspacing="0" cellpadding="0" border="0" role="presentation" style="width: 268px;">
                                                <tr>
                                                    <td align="center" bgcolor="#390083" style="border-radius: 16px; background-color: #390083;">
                                                        <a href="$ButtonLink" target="_blank" style="display: block; padding: 12px 0; font-family: Roboto, arial; font-size: 16px; line-height: 1.15; color: #ffffff; text-decoration: none; border-radius: 16px;">$ButtonText</a>
                                                    </td>
                                                </tr>
                                            </table>
                                        </td>
                                    </tr>
                                    <% end_if %>
                                    <% if $Signature %>
                                    <tr>
                                        <td align="left" style="padding: 15px 0; color: #ffffff; font-family: Roboto, arial; font-size: 16px; line-height: 1.5;">
                                            $Signature
                                        </td>
                                    </tr>
                                    <% end_if %>
                                </table>
                            </td>
                        </tr>

                        <tr>
                            <td style="padding-top: 40px;">
                                <table width="100%" cellspacing="0" cellpadding="0" border="0" role="presentation"><tr><td height="3" style="border-top: 3px solid #390083; font-size: 0; line-height: 0;">&nbsp;</td></tr></table>
                            </td>
                        </tr>

                        <%-- Footer: social icons centered --%>
                        <tr>
                            <td align="center" style="padding: 32px 32px 20px 32px;">
                                <% include BrandedEmailSocial %>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
    </body>
</html>
