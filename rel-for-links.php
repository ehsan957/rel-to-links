<?php
/**
 * Plugin Name:       Rel for Links
 * Description:       Add rel attributes (nofollow, sponsored, ugc, noopener, noreferrer, ...) to menu items, per item or automatically for external links.
 * Version:           1.0.0
 * Requires at least: 5.4
 * Requires PHP:      7.0
 * Plugin URI:        https://github.com/ehsan957/rel-to-links
 * Author:            Ehsan Shariaty
 * Author URI:        https://grizzly.ir
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       rel-for-links
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Rel_For_Links {

	const META_KEY   = '_rel_for_links_menu_item_rel';
	const OPTION_KEY = 'rel_for_links_settings';

	public static function init() {
		add_action( 'wp_nav_menu_item_custom_fields', array( __CLASS__, 'render_fields' ), 10, 2 );
		add_action( 'wp_update_nav_menu_item', array( __CLASS__, 'save_fields' ), 10, 2 );
		add_filter( 'nav_menu_link_attributes', array( __CLASS__, 'apply_rel' ), 10, 2 );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'admin_styles' ) );
		add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
		add_action( 'admin_menu', array( __CLASS__, 'add_settings_page' ) );
		add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( __CLASS__, 'action_links' ) );
	}

	/**
	 * @return array{external_enabled: bool, external_rel: string[]}
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION_KEY, array() );

		return wp_parse_args( is_array( $saved ) ? $saved : array(), self::default_settings() );
	}

	public static function default_settings() {
		return array(
			'external_enabled' => false,
			'external_rel'     => array( 'nofollow', 'noopener', 'noreferrer' ),
		);
	}

	/**
	 * Available rel values, filterable so themes/plugins can add more.
	 *
	 * @return array value => label
	 */
	public static function options() {
		return apply_filters(
			'rel_for_links_options',
			array(
				'nofollow'   => __( 'nofollow', 'rel-for-links' ),
				'sponsored'  => __( 'sponsored', 'rel-for-links' ),
				'ugc'        => __( 'ugc', 'rel-for-links' ),
				'noopener'   => __( 'noopener', 'rel-for-links' ),
				'noreferrer' => __( 'noreferrer', 'rel-for-links' ),
				'external'   => __( 'external', 'rel-for-links' ),
				'noindex'    => __( 'noindex', 'rel-for-links' ),
			)
		);
	}

	/**
	 * @param int $item_id
	 * @return string[]
	 */
	public static function get_item_rel( $item_id ) {
		$saved = get_post_meta( $item_id, self::META_KEY, true );
		return is_array( $saved ) ? array_values( array_intersect( $saved, array_keys( self::options() ) ) ) : array();
	}

	public static function render_fields( $item_id, $item ) {
		$selected = self::get_item_rel( $item_id );
		wp_nonce_field( 'rel_for_links_save_' . $item_id, 'rel_for_links_nonce_' . $item_id );
		?>
		<fieldset class="field-rel-for-links-rel description description-wide">
			<legend><?php esc_html_e( 'Link rel attributes', 'rel-for-links' ); ?></legend>
			<?php foreach ( self::options() as $value => $label ) : ?>
				<label for="rel-for-links-rel-<?php echo esc_attr( $value . '-' . $item_id ); ?>">
					<input type="checkbox"
						id="rel-for-links-rel-<?php echo esc_attr( $value . '-' . $item_id ); ?>"
						name="rel_for_links_rel[<?php echo esc_attr( $item_id ); ?>][]"
						value="<?php echo esc_attr( $value ); ?>"
						<?php checked( in_array( $value, $selected, true ) ); ?> />
					<?php echo esc_html( $label ); ?>
				</label>
			<?php endforeach; ?>
		</fieldset>
		<?php
	}

	public static function save_fields( $menu_id, $item_id ) {
		$nonce = isset( $_POST[ 'rel_for_links_nonce_' . $item_id ] ) ? sanitize_text_field( wp_unslash( $_POST[ 'rel_for_links_nonce_' . $item_id ] ) ) : '';

		// Only act on full menu-screen saves that rendered our fields.
		if ( ! $nonce || ! wp_verify_nonce( $nonce, 'rel_for_links_save_' . $item_id ) || ! current_user_can( 'edit_theme_options' ) ) {
			return;
		}

		$submitted = isset( $_POST['rel_for_links_rel'][ $item_id ] ) ? (array) wp_unslash( $_POST['rel_for_links_rel'][ $item_id ] ) : array();
		$values    = array_values( array_intersect( array_map( 'sanitize_key', $submitted ), array_keys( self::options() ) ) );

		if ( $values ) {
			update_post_meta( $item_id, self::META_KEY, $values );
		} else {
			delete_post_meta( $item_id, self::META_KEY );
		}
	}

	/**
	 * Whether a URL points to a different host than this site.
	 *
	 * @param string $url
	 * @return bool
	 */
	public static function is_external( $url ) {
		$url = (string) $url;
		if ( '' === $url || 0 === strpos( $url, '#' ) ) {
			return false;
		}

		$scheme = wp_parse_url( $url, PHP_URL_SCHEME );
		if ( $scheme && ! in_array( strtolower( $scheme ), array( 'http', 'https' ), true ) ) {
			return false; // mailto:, tel:, javascript:, etc.
		}

		$host = wp_parse_url( $url, PHP_URL_HOST );
		if ( ! $host ) {
			return false; // Relative URL.
		}

		$normalize = function ( $h ) {
			return preg_replace( '/^www\./', '', strtolower( $h ) );
		};
		$site_host = wp_parse_url( home_url(), PHP_URL_HOST );

		$external = $normalize( $host ) !== $normalize( (string) $site_host );

		return (bool) apply_filters( 'rel_for_links_is_external', $external, $url );
	}

	/**
	 * Merge chosen values with any rel already set (e.g. via core's XFN field).
	 */
	public static function apply_rel( $atts, $item ) {
		$chosen   = self::get_item_rel( $item->ID );
		$settings = self::get_settings();

		if ( $settings['external_enabled'] && ! empty( $atts['href'] ) && self::is_external( $atts['href'] ) ) {
			$chosen = array_merge( $chosen, array_intersect( (array) $settings['external_rel'], array_keys( self::options() ) ) );
		}

		if ( ! $chosen ) {
			return $atts;
		}

		$existing    = ! empty( $atts['rel'] ) ? preg_split( '/\s+/', trim( $atts['rel'] ) ) : array();
		$atts['rel'] = implode( ' ', array_unique( array_merge( $existing, $chosen ) ) );

		return $atts;
	}

	public static function register_settings() {
		register_setting(
			'rel_for_links_settings_group',
			self::OPTION_KEY,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( __CLASS__, 'sanitize_settings' ),
				'default'           => self::default_settings(),
			)
		);
	}

	public static function sanitize_settings( $input ) {
		$input = is_array( $input ) ? $input : array();
		$rel   = isset( $input['external_rel'] ) ? array_map( 'sanitize_key', (array) $input['external_rel'] ) : array();

		return array(
			'external_enabled' => ! empty( $input['external_enabled'] ),
			'external_rel'     => array_values( array_intersect( $rel, array_keys( self::options() ) ) ),
		);
	}

	public static function add_settings_page() {
		add_options_page(
			__( 'Rel for Links', 'rel-for-links' ),
			__( 'Rel for Links', 'rel-for-links' ),
			'manage_options',
			'rel-for-links',
			array( __CLASS__, 'render_settings_page' )
		);
	}

	public static function action_links( $links ) {
		$url = admin_url( 'options-general.php?page=rel-for-links' );
		array_unshift( $links, '<a href="' . esc_url( $url ) . '">' . esc_html__( 'Settings', 'rel-for-links' ) . '</a>' );
		return $links;
	}

	public static function render_settings_page() {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$settings = self::get_settings();
		?>
		<div class="wrap">
			<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
			<form action="options.php" method="post">
				<?php settings_fields( 'rel_for_links_settings_group' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'External menu links', 'rel-for-links' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[external_enabled]" value="1" <?php checked( $settings['external_enabled'] ); ?> />
								<?php esc_html_e( 'Automatically add rel attributes to menu links pointing to other domains', 'rel-for-links' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Rel values for external links', 'rel-for-links' ); ?></th>
						<td>
							<fieldset>
								<legend class="screen-reader-text"><?php esc_html_e( 'Rel values for external links', 'rel-for-links' ); ?></legend>
								<?php foreach ( self::options() as $value => $label ) : ?>
									<label style="display:block;margin-bottom:4px;">
										<input type="checkbox" name="<?php echo esc_attr( self::OPTION_KEY ); ?>[external_rel][]" value="<?php echo esc_attr( $value ); ?>" <?php checked( in_array( $value, (array) $settings['external_rel'], true ) ); ?> />
										<?php echo esc_html( $label ); ?>
									</label>
								<?php endforeach; ?>
								<p class="description"><?php esc_html_e( 'These are combined with any values chosen on individual menu items.', 'rel-for-links' ); ?></p>
							</fieldset>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	public static function admin_styles( $hook_suffix ) {
		if ( 'nav-menus.php' !== $hook_suffix ) {
			return;
		}

		wp_register_style( 'rel-for-links-admin', false, array(), '1.0.0' );
		wp_enqueue_style( 'rel-for-links-admin' );
		wp_add_inline_style(
			'rel-for-links-admin',
			'.field-rel-for-links-rel { margin: 6px 0 10px; }
			.field-rel-for-links-rel legend { margin-bottom: 4px; }
			.field-rel-for-links-rel label { display: inline-block; margin: 0 12px 4px 0; }'
		);
	}
}

Rel_For_Links::init();
