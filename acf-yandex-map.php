<?php

/*
Plugin Name: Yandex Map Field for ACF
Plugin URI: https://github.com/constlab/acf-yandex-map
Description: Editing map on page, add geopoints and circles
Version: 1.3
Author: Const Lab
Author URI: https://constlab.ru
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html
*/

defined( 'YA_MAP_LANG_DOMAIN' ) or define( 'YA_MAP_LANG_DOMAIN', 'acf-yandex-map' );
defined( 'ACF_YA_MAP_VERSION' ) or define( 'ACF_YA_MAP_VERSION', '1.3.0' );

load_plugin_textdomain( YA_MAP_LANG_DOMAIN, false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );

function include_field_types_yandex_map( $version = false ) {
	if ( ! $version ) {
		$version = 4;
	}

	include_once __DIR__ . '/acf-yandex-map-v' . $version . '.php';
}

add_action( 'acf/include_field_types', 'include_field_types_yandex_map' );
add_action( 'acf/register_fields', 'include_field_types_yandex_map' );


/**
 *  Page for options
 */
add_action('admin_menu', 'add_plugin_page');
function add_plugin_page() {
	add_options_page( YA_MAP_LANG_DOMAIN, YA_MAP_LANG_DOMAIN, 'manage_options', 'acf_yandex_map_slug', 'acf_yandex_map_slug_page_output' );
}

function acf_yandex_map_slug_page_output() { ?>
	<div class="wrap">
		<h3><?php echo get_admin_page_title() ?></h3>

		<form action="options.php" method="POST">
			<?php
				settings_fields( 'option_group' );     // скрытые защитные поля
				do_settings_sections( 'acf_yandex_map_page' ); // секции с настройками (опциями). У нас она всего одна 'section_id'
				submit_button();
			?>
		</form>
	</div><?php
}

/**
 * Регистрируем настройки.
 * Настройки будут храниться в массиве, а не одна настройка = одна опция.
 */
add_action('admin_init', 'plugin_settings');
function plugin_settings() {
	// параметры: $option_group, $option_name, $sanitize_callback
	register_setting( 'option_group', 'acf_yandex_map', 'sanitize_callback' );

	// параметры: $id, $title, $callback, $page
	add_settings_section( 'section_id', 'Options', '', 'acf_yandex_map_page' ); 

	// параметры: $id, $title, $callback, $page, $section, $args
	add_settings_field('acf_yandex_map', 'API-key', 'fill_acf_yandex_map', 'acf_yandex_map_page', 'section_id' );
}

function fill_acf_yandex_map() {
	$val = get_option('acf_yandex_map');
	$val = $val ? $val['api_key'] : null; ?>
	
	<label>
		(for //api-maps.yandex.ru/2.1)&nbsp; 
		<input type="text" name="acf_yandex_map[api_key]" style="min-width: 300px;" value="<?php echo esc_attr( $val ) ?>" />
	</label>
	
	<?php
}

## Очистка данных
function sanitize_callback($options) { 
	// очищаем
	foreach ($options as $name => &$val) {
		if ($name == 'api_key' ) {
			$val = strip_tags($val);
		}
	}

	return $options;
}


/// Function for frontend

if ( ! function_exists( 'the_yandex_map' ) ) {

	/**
	 * @param string $selector
	 * @param int|bool $post_id
	 * @param null $data
	 */
	function the_yandex_map( $selector, $post_id = false, $data = null ) {

		$post_id = function_exists( 'acf_get_valid_post_id' ) ? acf_get_valid_post_id( $post_id ) :  $post_id;

		$value = ( $data !== null ) ? $data : get_field( $selector, $post_id, false );

		if ( ! $value ) {
			return;
		}

		$dir = plugin_dir_url( __FILE__ );

		$val = get_option('acf_yandex_map');
		$val = $val ? $val['api_key'] : null;

		wp_register_script( 'yandex-map-api', '//api-maps.yandex.ru/2.1/?lang=' . get_bloginfo( 'language' ) . ($val ? '&api_key=' . $val : ''), array( 'jquery' ), null );
		wp_register_script( 'yandex-map-frontend', "{$dir}js/yandex-map.min.js", array( 'yandex-map-api' ), ACF_YA_MAP_VERSION );
		wp_enqueue_script( 'yandex-map-frontend' );

		$map_id = uniqid( 'map_' );

		wp_localize_script( 'yandex-map-frontend', $map_id, array(
			'params' => $value
		) );

		/**
		 * Filter the map height for frontend.
		 *
		 * @since 1.2.0
		 *
		 * @param string $selector Field name
		 * @param int $post_id Current page id
		 * @param array $value Map field value
		 */
		$field        = get_field_object( $selector, $post_id );
		$field_height = $field ? $field['height'] : 200;
		$height_map   = apply_filters( 'acf-yandex-map/height', $field_height, $selector, $post_id, $value );

		echo sprintf( '<div class="yandex-map" id="%s" style="width:auto;height:%dpx"></div>', $map_id, $height_map );
	}

}