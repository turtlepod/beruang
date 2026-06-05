<?php
/**
 * Header template.
 *
 * @package BeruangSaaS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<div class="site-drawer-overlay" id="site-drawer-overlay" aria-hidden="true"></div>

<?php
$drawer_content = '';
$account_url    = '';

if ( function_exists( '\\Beruang\\get_theme_drawer_content' ) ) {
	$drawer_content = \Beruang\get_theme_drawer_content();
}

if ( function_exists( '\\Beruang\\get_theme_account_page_url' ) ) {
	$account_url = \Beruang\get_theme_account_page_url();
}
?>

<aside class="site-drawer" id="site-drawer" aria-label="<?php esc_attr_e( 'Main menu', 'beruang-saas' ); ?>" aria-hidden="true">
	<button class="site-drawer-close" id="site-drawer-close" aria-label="<?php esc_attr_e( 'Close menu', 'beruang-saas' ); ?>">
		<?php beruang_saas_icon( 'close', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?>
	</button>
	<div class="site-drawer-content">
		<?php if ( ! empty( $drawer_content ) ) : ?>
			<?php echo wp_kses_post( do_shortcode( shortcode_unautop( wpautop( $drawer_content ) ) ) ); ?>
		<?php else : ?>
			<p><?php esc_html_e( 'No drawer content set yet. Configure it in Beruang > Theme Settings.', 'beruang-saas' ); ?></p>
		<?php endif; ?>
	</div>
</aside>

<header class="site-header">
	<div class="site-header-inner">
		<button class="site-hamburger" id="site-hamburger" aria-label="<?php esc_attr_e( 'Open menu', 'beruang-saas' ); ?>" aria-expanded="false" aria-controls="site-drawer">
			<?php beruang_saas_icon( 'hamburger', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?>
		</button>

		<a class="site-logo" href="<?php echo esc_url( home_url( '/' ) ); ?>">
			<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 640 640" class="site-logo-icon" aria-hidden="true" focusable="false">
				<path fill="currentColor" d="M298.5 156.9C312.8 199.8 298.2 243.1 265.9 253.7C233.6 264.3 195.8 238.1 181.5 195.2C167.2 152.3 181.8 109 214.1 98.4C246.4 87.8 284.2 114 298.5 156.9zM164.4 262.6C183.3 295 178.7 332.7 154.2 346.7C129.7 360.7 94.5 345.8 75.7 313.4C56.9 281 61.4 243.3 85.9 229.3C110.4 215.3 145.6 230.2 164.4 262.6zM133.2 465.2C185.6 323.9 278.7 288 320 288C361.3 288 454.4 323.9 506.8 465.2C510.4 474.9 512 485.3 512 495.7L512 497.3C512 523.1 491.1 544 465.3 544C453.8 544 442.4 542.6 431.3 539.8L343.3 517.8C328 514 312 514 296.7 517.8L208.7 539.8C197.6 542.6 186.2 544 174.7 544C148.9 544 128 523.1 128 497.3L128 495.7C128 485.3 129.6 474.9 133.2 465.2zM485.8 346.7C461.3 332.7 456.7 295 475.6 262.6C494.5 230.2 529.6 215.3 554.1 229.3C578.6 243.3 583.2 281 564.3 313.4C545.4 345.8 510.3 360.7 485.8 346.7zM374.1 253.7C341.8 243.1 327.2 199.8 341.5 156.9C355.8 114 393.6 87.8 425.9 98.4C458.2 109 472.8 152.3 458.5 195.2C444.2 238.1 406.4 264.3 374.1 253.7z"/>
			</svg>
			<span class="screen-reader-text"><?php bloginfo( 'name' ); ?></span>
		</a>

		<?php if ( is_user_logged_in() ) : ?>
			<div class="site-profile">
				<?php if ( ! empty( $account_url ) ) : ?>
					<a class="site-profile-avatar" href="<?php echo esc_url( $account_url ); ?>" aria-label="<?php esc_attr_e( 'Account page', 'beruang-saas' ); ?>">
						<?php beruang_saas_icon( 'profile', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?>
					</a>
				<?php else : ?>
					<div class="site-profile-avatar" aria-hidden="true">
						<?php beruang_saas_icon( 'profile', array( 'attrs' => array( 'aria-hidden' => 'true' ) ) ); ?>
					</div>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>

</header>
