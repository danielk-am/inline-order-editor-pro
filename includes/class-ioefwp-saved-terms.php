<?php
/**
 * Saved terms: named terms texts kept in the settings, ready to drop into an order's terms box.
 *
 * Choosing saved terms only fills the box. Nothing is saved until the order is.
 *
 * @package Inline_Order_Editor_Pro
 */

defined( 'ABSPATH' ) || exit;

/**
 * The saved terms list, its settings field and its picker on the order.
 */
final class IOEFWP_Saved_Terms {

	const OPTION = 'ioefwp_saved_terms';

	/**
	 * Hooks.
	 */
	public static function init() {
		add_filter( 'woocommerce_general_settings', array( __CLASS__, 'add_settings' ), 20 );
		add_action( 'woocommerce_admin_field_ioefwp_saved_terms', array( __CLASS__, 'render_field' ) );
		add_filter( 'woocommerce_admin_settings_sanitize_option_' . self::OPTION, array( __CLASS__, 'sanitize_field' ), 10, 3 );
		add_action( 'ioefw_terms_box_actions', array( __CLASS__, 'render_picker' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_settings_script' ) );
	}

	/**
	 * The saved terms, cleaned: a list of name and text pairs.
	 *
	 * @return array[]
	 */
	public static function get_all() {
		return self::clean( get_option( self::OPTION, array() ) );
	}

	/**
	 * Cleans a list of saved terms. Rows without a name or without a text are dropped.
	 *
	 * @param mixed $rows The rows as stored or as posted.
	 * @return array[]
	 */
	public static function clean( $rows ) {
		$clean = array();

		foreach ( is_array( $rows ) ? $rows : array() as $row ) {
			if ( ! is_array( $row ) || ! isset( $row['name'], $row['text'] ) || ! is_scalar( $row['name'] ) || ! is_scalar( $row['text'] ) ) {
				continue;
			}

			$name = sanitize_text_field( (string) $row['name'] );
			$text = trim( sanitize_textarea_field( (string) $row['text'] ) );

			if ( '' !== $name && '' !== $text ) {
				$clean[] = array(
					'name' => $name,
					'text' => $text,
				);
			}
		}

		return $clean;
	}

	/**
	 * Adds the Saved terms section after the Order terms settings.
	 *
	 * @param array $settings WooCommerce's General settings.
	 * @return array
	 */
	public static function add_settings( $settings ) {
		$settings   = (array) $settings;
		$settings[] = array(
			'title' => __( 'Saved terms', 'inline-order-editor-pro' ),
			'type'  => 'title',
			'desc'  => __( 'Terms you use often, such as one set for weddings and one for trade customers. Pick one on an order to fill in its terms.', 'inline-order-editor-pro' ),
			'id'    => 'ioefwp_saved_terms_options',
		);
		$settings[] = array(
			'title'    => __( 'Saved terms', 'inline-order-editor-pro' ),
			'id'       => self::OPTION,
			'type'     => 'ioefwp_saved_terms',
			'default'  => array(),
			'autoload' => false,
		);
		$settings[] = array(
			'type' => 'sectionend',
			'id'   => 'ioefwp_saved_terms_options',
		);

		return $settings;
	}

	/**
	 * Draws the settings field: one row per saved terms, with a name and a text.
	 *
	 * @param array $field The field's settings.
	 */
	public static function render_field( $field ) {
		$rows   = self::get_all();
		$rows[] = array(
			'name' => '',
			'text' => '',
		);
		?>
		<tr valign="top">
			<th scope="row" class="titledesc"><?php echo esc_html( isset( $field['title'] ) ? $field['title'] : '' ); ?></th>
			<td class="forminp ioefwp-saved-terms">
				<input type="hidden" name="<?php echo esc_attr( self::OPTION ); ?>[keep]" value="1" />
				<div class="ioefwp-saved-terms__rows">
					<?php foreach ( $rows as $index => $row ) : ?>
						<div class="ioefwp-saved-terms__row" style="margin-bottom: 12px;">
							<p>
								<label>
									<span class="screen-reader-text"><?php esc_html_e( 'Name of these terms', 'inline-order-editor-pro' ); ?></span>
									<input type="text" class="regular-text" name="<?php echo esc_attr( self::OPTION . '[' . $index . '][name]' ); ?>" value="<?php echo esc_attr( $row['name'] ); ?>" placeholder="<?php esc_attr_e( 'Name, for example Wedding orders', 'inline-order-editor-pro' ); ?>" />
								</label>
								<button type="button" class="button-link ioefwp-saved-terms__remove"><?php esc_html_e( 'Remove', 'inline-order-editor-pro' ); ?></button>
							</p>
							<p>
								<label>
									<span class="screen-reader-text"><?php esc_html_e( 'The terms', 'inline-order-editor-pro' ); ?></span>
									<textarea name="<?php echo esc_attr( self::OPTION . '[' . $index . '][text]' ); ?>" rows="3" style="min-width: 50%;" placeholder="<?php esc_attr_e( 'The terms', 'inline-order-editor-pro' ); ?>"><?php echo esc_textarea( $row['text'] ); ?></textarea>
								</label>
							</p>
						</div>
					<?php endforeach; ?>
				</div>
				<p><button type="button" class="button ioefwp-saved-terms__add"><?php esc_html_e( 'Add another', 'inline-order-editor-pro' ); ?></button></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Cleans the posted field before WooCommerce stores it.
	 *
	 * WooCommerce's own cleaning would flatten the line breaks, so the raw value is cleaned here.
	 *
	 * @param mixed $value     The value after WooCommerce's default cleaning.
	 * @param array $option    The field's settings.
	 * @param mixed $raw_value The value as posted, unslashed.
	 * @return array[]
	 */
	public static function sanitize_field( $value, $option = array(), $raw_value = null ) {
		return self::clean( $raw_value );
	}

	/**
	 * Draws the picker under the order's terms box.
	 *
	 * @param mixed $order The order being edited.
	 */
	public static function render_picker( $order = null ) {
		$rows = self::get_all();

		if ( ! $rows ) {
			return;
		}
		?>
		<span aria-hidden="true">|</span>
		<label>
			<span class="screen-reader-text"><?php esc_html_e( 'Use saved terms', 'inline-order-editor-pro' ); ?></span>
			<select class="ioefwp-saved-terms-picker">
				<option value=""><?php esc_html_e( 'Use saved terms', 'inline-order-editor-pro' ); ?></option>
				<?php foreach ( $rows as $row ) : ?>
					<option value="<?php echo esc_attr( $row['text'] ); ?>"><?php echo esc_html( $row['name'] ); ?></option>
				<?php endforeach; ?>
			</select>
		</label>
		<?php
	}

	/**
	 * Loads the script for the settings field, on WooCommerce's settings screen only.
	 */
	public static function enqueue_settings_script() {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || 'woocommerce_page_wc-settings' !== $screen->id ) {
			return;
		}

		wp_enqueue_script( 'ioefwp-settings', plugins_url( 'assets/js/pro-settings.js', IOEFWP_PLUGIN_FILE ), array(), IOEFWP_VERSION, true );
	}
}
