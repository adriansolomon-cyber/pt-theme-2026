<?php
/**
 * Email Footer - Project Timber
 */
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
</td>
</tr>
<!-- Footer -->
<tr>
    <td width="100%" align="center"
        style="padding: 0 24px 24px; background-color: #ffffff; font-family: 'Work Sans', Arial, sans-serif;"
        bgcolor="#ffffff">

        <table align="center" cellpadding="0" cellspacing="0" border="0" width="560"
            style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
            <tr>
                <td align="center"
                    style="background-color: #ffff00; border-radius: 16px; padding: 30px 30px 24px;"
                    bgcolor="#ffff00">

                    <!-- Logo -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%"
                        style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>
                            <td align="center" style="padding: 0 0 16px 0;">
                                <img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/social-logos/v2/logo-email-footer.svg"
                                    alt="Project Timber"
                                    style="display: block; height: auto; max-width: 100%; border: 0; outline: none;"
                                    border="0">
                            </td>
                        </tr>
                    </table>

                    <!-- Social icons -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%"
                        style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>
                            <td align="center" style="padding: 0 0 20px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"
                                    style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                                    <tr>
                                        <td style="padding: 0 6px;">
                                            <a href="https://www.facebook.com/projecttimber" target="_blank" style="text-decoration: none;">
                                                <img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/social-logos/v2/fb-v2.svg"
                                                    alt="Facebook" width="24" height="24"
                                                    style="display: block; border: 0; width: 24px; height: 24px;"
                                                    border="0">
                                            </a>
                                        </td>
                                        <td style="padding: 0 6px;">
                                            <a href="https://twitter.com/project_timber" target="_blank" style="text-decoration: none;">
                                                <img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/social-logos/v2/x-v2-icon.svg"
                                                    alt="X / Twitter" width="24" height="24"
                                                    style="display: block; border: 0; width: 24px; height: 24px;"
                                                    border="0">
                                            </a>
                                        </td>
                                        <td style="padding: 0 6px;">
                                            <a href="https://www.pinterest.co.uk/projecttimberltd/" target="_blank" style="text-decoration: none;">
                                                <img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/social-logos/v2/pinterest-v2.svg"
                                                    alt="Pinterest" width="24" height="24"
                                                    style="display: block; border: 0; width: 24px; height: 24px;"
                                                    border="0">
                                            </a>
                                        </td>
                                        <td style="padding: 0 6px;">
                                            <a href="https://www.instagram.com/projecttimber/" target="_blank" style="text-decoration: none;">
                                                <img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/social-logos/v2/insta-v2.svg"
                                                    alt="Instagram" width="24" height="24"
                                                    style="display: block; border: 0; width: 24px; height: 24px;"
                                                    border="0">
                                            </a>
                                        </td>
                                        <td style="padding: 0 6px;">
                                            <a href="https://www.youtube.com/@projecttimber" target="_blank" style="text-decoration: none;">
                                                <img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/social-logos/v2/youtube-v2.svg"
                                                    alt="YouTube" width="24" height="24"
                                                    style="display: block; border: 0; width: 24px; height: 24px;"
                                                    border="0">
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>
                        </tr>
                    </table>

                    <!-- Support message -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%"
                        style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>
                            <td align="center"
                                style="padding: 0 10px 20px; font-family: 'Work Sans', Arial, sans-serif; font-size: 14px; color: #333333; line-height: 1.6; text-align: center;">
                                If you have any questions, feedback, or concerns regarding your purchase or anything else, please don't hesitate to reach out to us. We're here to assist you every step of the way.
                            </td>
                        </tr>
                    </table>

                    <!--
                        Contact info row.
                        FIXED: removed display:flex from <tr> and display:inline-flex + gap
                        from <td> — both are ignored by Outlook and Gmail web, causing the
                        icon and text to stack vertically.
                        Each contact item is now a nested two-cell table (icon | text) so
                        they sit side by side reliably in every client.
                    -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%"
                        style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>

                            <!-- Email contact -->
                            <td align="center" valign="middle" width="50%"
                                style="padding: 0 8px 16px 0;">
                                <table cellpadding="0" cellspacing="0" border="0"
                                    style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; margin: 0 auto;">
                                    <tr>
                                        <td valign="middle" style="padding: 0;">
                                            <img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/social-logos/v2/envelope.svg"
                                                alt="" width="12" height="12"
                                                style="display: block; border: 0; width: 12px; height: 12px;"
                                                border="0">
                                        </td>
                                        <td valign="middle"
                                            style="font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; color: #333333; white-space: nowrap;">
                                            <a href="mailto:care@projecttimber.co.uk"
                                                style="color: #333333; text-decoration: none; font-weight: bold;">
                                                care@projecttimber.co.uk
                                            </a>
                                        </td>
                                    </tr>
                                </table>
                            </td>

                            <!-- Phone contact -->
                            <td align="center" valign="middle" width="50%"
                                style="padding: 0 0 16px 8px;">
                                <table cellpadding="0" cellspacing="0" border="0"
                                    style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; margin: 0 auto;">
                                    <tr>
                                        <td valign="middle" style="padding: 0;">
                                            <img src="<?php echo esc_url( get_stylesheet_directory_uri() ); ?>/assets/images/social-logos/v2/phone.svg"
                                                alt="" width="12" height="12"
                                                style="display: block; border: 0; width: 12px; height: 12px;"
                                                border="0">
                                        </td>
                                        <td valign="middle"
                                            style="font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; color: #333333; font-weight: bold; white-space: nowrap;">
                                            01777 801215
                                        </td>
                                    </tr>
                                </table>
                            </td>

                        </tr>
                    </table>

                    <!-- Terms & copyright -->
                    <table cellpadding="0" cellspacing="0" border="0" width="100%"
                        style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
                        <tr>
                            <td align="center"
                                style="font-family: 'Work Sans', Arial, sans-serif; font-size: 13px; color: #333333; line-height: 1.6; text-align: center;">
                                <p style="margin: 0 0 6px;">
                                    Please review our
                                    <a href="https://www.projecttimber.com/terms/" target="_blank"
                                        style="color: #333333; text-decoration: underline; font-weight: normal;">
                                        Terms and Conditions
                                    </a>
                                    for more details about your purchase.
                                </p>
                                <p style="margin: 0; font-size: 13px; color: #333333;">
                                    Project Timber &copy; <?php echo esc_html( date( 'Y' ) ); ?>
                                </p>
                            </td>
                        </tr>
                    </table>

                </td>
            </tr>
        </table>

    </td>
</tr>
<!-- / Footer -->