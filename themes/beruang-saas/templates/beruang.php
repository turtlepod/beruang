<?php
/**
 * Template Name: Beruang SaaS
 * Template Post Type: page
 *
 * @package BeruangSaaS
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header();

$tabs = array();

if ( is_user_logged_in() ) {
	$tabs = array(
		array(
			'id'        => 'form',
			'label'     => __( 'Form', 'beruang-saas' ),
			'shortcode' => '[beruang-form]',
		),
		array(
			'id'        => 'list',
			'label'     => __( 'List', 'beruang-saas' ),
			'shortcode' => '[beruang-list]',
		),
		array(
			'id'        => 'budget',
			'label'     => __( 'Budget', 'beruang-saas' ),
			'shortcode' => '[beruang-budget]',
		),
		array(
			'id'        => 'graph',
			'label'     => __( 'Graph', 'beruang-saas' ),
			'shortcode' => '[beruang-graph]',
		),
		array(
			'id'        => 'wallet',
			'label'     => __( 'Wallet', 'beruang-saas' ),
			'shortcode' => '[beruang-wallet]',
		),
	);
}
?>

<?php if ( is_user_logged_in() ) : ?>
	<nav class="site-nav beruang-tabs-nav" data-tabs-nav="beruang" aria-label="<?php esc_attr_e( 'Beruang sections', 'beruang-saas' ); ?>">
		<ul role="tablist" aria-label="<?php esc_attr_e( 'Beruang tabs', 'beruang-saas' ); ?>">
			<?php foreach ( $tabs as $index => $tab ) : ?>
				<li>
					<button
						type="button"
						class="beruang-tab<?php echo 0 === $index ? ' is-active' : ''; ?>"
						id="beruang-tab-<?php echo esc_attr( $tab['id'] ); ?>"
						data-tab="<?php echo esc_attr( $tab['id'] ); ?>"
						role="tab"
						aria-controls="beruang-panel-<?php echo esc_attr( $tab['id'] ); ?>"
						aria-selected="<?php echo 0 === $index ? 'true' : 'false'; ?>"
						tabindex="<?php echo 0 === $index ? '0' : '-1'; ?>"
					>
						<?php echo esc_html( $tab['label'] ); ?>
					</button>
				</li>
			<?php endforeach; ?>
			<span class="beruang-tab-indicator" aria-hidden="true"></span>
		</ul>
	</nav>
<?php endif; ?>

<main id="primary" class="site-main">
	<?php if ( is_user_logged_in() ) : ?>
		<div class="beruang-tabs-content" data-tabs-content="beruang" data-swipe-tabs="true">
			<div class="beruang-tabs-strip">
			<?php foreach ( $tabs as $index => $tab ) : ?>
				<section
					class="beruang-tab-panel<?php echo 0 === $index ? ' is-active' : ''; ?>"
					id="beruang-panel-<?php echo esc_attr( $tab['id'] ); ?>"
					data-panel="<?php echo esc_attr( $tab['id'] ); ?>"
					role="tabpanel"
					aria-labelledby="beruang-tab-<?php echo esc_attr( $tab['id'] ); ?>"
					aria-hidden="<?php echo 0 === $index ? 'false' : 'true'; ?>"
				>
					<?php echo do_shortcode( $tab['shortcode'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</section>
			<?php endforeach; ?>
			</div>
		</div>
		<script>
		( function () {
			var hash = window.location.hash ? window.location.hash.slice( 1 ) : '';
			if ( ! hash ) return;
			var navEl = document.querySelector( '[data-tabs-nav="beruang"]' );
			if ( ! navEl ) return;
			var tabs  = Array.from( navEl.querySelectorAll( '.beruang-tab' ) );
			var valid = tabs.some( function ( t ) { return t.dataset.tab === hash; } );
			if ( ! valid ) return;
			var contentEl = document.querySelector( '[data-tabs-content="beruang"]' );
			var panels    = contentEl ? Array.from( contentEl.querySelectorAll( '.beruang-tab-panel' ) ) : [];
			tabs.forEach( function ( t ) {
				var active = t.dataset.tab === hash;
				t.classList.toggle( 'is-active', active );
				t.setAttribute( 'aria-selected', active ? 'true' : 'false' );
				t.setAttribute( 'tabindex', active ? '0' : '-1' );
			} );
			panels.forEach( function ( p ) {
				var active = p.dataset.panel === hash;
				p.classList.toggle( 'is-active', active );
				p.setAttribute( 'aria-hidden', active ? 'false' : 'true' );
			} );
		}() );
		</script>
	<?php else : ?>
		<?php
		while ( have_posts() ) :
			the_post();
			the_content();
		endwhile;
		?>
	<?php endif; ?>
</main>

<?php
get_footer();
