<?php
/**
 * Email Header
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Get Settings.
 */
$header_img_src = esc_url_raw( get_option( 'ec_vanilla_all_header_logo' ) );
if ( ! isset( $header_img_src ) || '' == $header_img_src ) {
	$header_img_src = esc_url_raw( get_option( 'woocommerce_email_header_image' ) );
}

?>
<!DOCTYPE html>
<html dir="<?php echo is_rtl() ? 'rtl' : 'ltr'?>">

	<head>

		<meta http-equiv="Content-Type" content="text/html; charset=<?php bloginfo( 'charset' ); ?>" />
		<meta name="viewport" content="width=device-width, initial-scale=1.0" />
		<title><?php echo get_bloginfo( 'name', 'display' ); ?></title>

		<!--[if mso]>
		<style type="text/css">
		body, table, td { font-family: Arial, sans-serif !important; }
		</style>
		<![endif]-->

		<style type="text/css">
			/* Reset & base — these are safe even in Gmail app on Android */
			body, #bodyTable { margin: 0 !important; padding: 0 !important; width: 100% !important; }
			img { border: 0; outline: none; text-decoration: none; display: block; }

			/* Outlook-safe font fallback */
			body { font-family: 'Work Sans', Arial, sans-serif; }
			p, td { font-family: 'Work Sans', Arial, sans-serif; font-size: 15px; }
			h1{font-size: 43px; font-weight: 600;}
			.email-rounded-btn{
				padding: 13px 24px;
				border-radius: 99px;
				background: #FF0; 
				text-decoration: none;  
			}
			.palletways-tracking{
				padding: 0 24px;
			}
			/* Mobile responsive */
			@media only screen and (max-width: 620px) {
				#template_container { width: 100% !important; }
				#template_header_table { width: 100% !important; }
				.header-logo-cell { padding: 16px 16px 0 16px !important; }
				.header-icons-cell { padding: 16px 16px 0 16px !important; text-align: left !important; }
				.social-icon { margin-right: 8px !important; }
			}
		</style>

		<!--
			NOTE: Google Fonts via <link> are stripped by most email clients including
			Gmail web. Work Sans is used here as a best-effort for clients that support
			it (Apple Mail, Outlook macOS). All other clients will fall back to Arial.
			Do NOT rely on web fonts for layout or sizing.
		-->
		<link rel="preconnect" href="https://fonts.googleapis.com">
		<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
		<link href="https://fonts.googleapis.com/css2?family=Work+Sans:ital,wght@0,100..900;1,100..900&display=swap" rel="stylesheet">

	</head>

	<body style="margin: 0; padding: 0; background-color: #f5f5f5;"
		<?php echo is_rtl() ? 'rightmargin' : 'leftmargin'; ?>="0"
		marginwidth="0" topmargin="0" marginheight="0" offset="0">

		<!-- Wrapper table — full width background -->
		<table border="0" cellpadding="0" cellspacing="0" width="100%" id="bodyTable"
			style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #f5f5f5; display: table; padding: 24px;">
			<tr>
				<td align="center" valign="top" style="padding: 24px 0;">

					<!-- Main container: 600px wide -->
					<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_container"
						style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffffff; border: none; max-width: 600px;">
					<tbody style="">
						<!-- ===== HEADER ROW ===== -->
						<tr>
							<td valign="top" style="padding: 24px 24px 0 24px;">

								<!--
									Header bar: logo left, social icons right.
									We use a nested table instead of flexbox/div layout
									because flexbox is not supported in email clients.
									background-color is a solid yellow fallback — gradients
									and border-radius are ignored by Outlook.
								-->
								<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_header_table"
									style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; background-color: #ffff00; border-radius: 12px;">
									<tr>

										<!-- Logo cell -->
										<td valign="middle" class="header-logo-cell"
											style="padding: 16px 0 16px 20px; width: auto;">
											<?php
											if ( $img = get_option( 'woocommerce_email_header_image' ) ) {
												echo '<img src="https://www.projecttimber.com/wp-content/uploads/2026/04/ProjectTimber-Logo-2-1.png"'
													. ' alt="' . esc_attr( get_bloginfo( 'name', 'display' ) ) . '"'
													. ' width="150"'
													. ' style="display: block; max-width: 150px; height: auto; border: 0; outline: none;" />';
											}
											?>
										</td>

										<!-- Social icons cell — right-aligned -->
										<td valign="middle" align="right" class="header-icons-cell"
											style="padding: 16px 20px 16px 0;">

											<!--
												Icons in a table row so spacing is consistent
												across all clients (gap/flex not supported).
											-->
											<table border="0" cellpadding="0" cellspacing="0"
												style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; display: inline-table;">
												<tr>
													<td style="padding: 0 10px 0 0;">
														<a href="https://www.facebook.com/projecttimber" style="text-decoration: none;">
                       										 <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/social-logos/v2/fb-v2.svg"
																width="24" height="24"
																class="social-icon"
																style="display: block; border: 0; outline: none; width: 24px; height: 24px;" />
														</a>
													</td>
													<td style="padding: 0 10px 0 0;">
														<a href="https://www.youtube.com/@projecttimber" style="text-decoration: none;">
															 <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/social-logos/v2/youtube-v2.svg"
																width="24" height="24"
																class="social-icon"
																style="display: block; border: 0; outline: none; width: 24px; height: 24px;" />
														</a>
													</td>
													<td style="padding: 0;">
														<a href="https://www.instagram.com/projecttimber/" style="text-decoration: none;">
															 <img src="<?php echo get_stylesheet_directory_uri(); ?>/assets/images/social-logos/v2/insta-v2.svg"
																width="24" height="24"
																class="social-icon"
																style="display: block; border: 0; outline: none; width: 24px; height: 24px;" />
														</a>
													</td>
												</tr>
											</table>

										</td>
									</tr>
								</table>

							</td>
						</tr>
						<!-- ===== END HEADER ROW ===== -->

						<!-- ===== BODY ROW ===== -->
						<tr>
							<td align="center" valign="top">
								<table border="0" cellpadding="0" cellspacing="0" width="100%" id="template_body"
									style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt; max-width: 600px;">
									<tr>
										<td valign="top" id="body_content">
											<table border="0" cellpadding="0" cellspacing="0" width="100%"
												style="border-collapse: collapse; mso-table-lspace: 0pt; mso-table-rspace: 0pt;">
												<tr>
													<td valign="top" style="padding: 24px;">
														<div id="body_content_inner"
															style="font-family: 'Work Sans', Arial, sans-serif; color: #3B333D; font-size: 16px; line-height: 1.6;">