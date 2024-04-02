<?php
/**
 * Plugin Name: Elementor S3 Uploads Support
 * Description: Provides integration between Elementor and Human Made S3 Uploads plugins
 * Author:      Web Grow Studio
 * License:     GNU General Public License v2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 */

// Basic security, prevents file from being loaded directly.
defined( 'ABSPATH' ) or die( 'Busted!' );

class WGS_Elementor_S3_Uploads {
	public function __construct()
	{
		add_action('plugins_loaded', [$this, 'bootstrap']);
	}

	/**
	 * Registers the plugin hooks after all plugins are loaded
	 *
	 * @return void
	 *
	 * @hook plugins_loaded
	 */
	public function bootstrap()
	{
		if ( is_plugin_active('elementor/elementor.php') && is_plugin_active('S3-Uploads/s3-uploads.php') ) {
			add_filter( 'get_post_metadata', [$this, 'loadElementorExternalSvg'], 10, 5);

			add_action( 'wgs_update_elementor_inline_svg', [$this, 'updateElementorInlineSvg'], 10, 2 );
		}
	}

	/**
	 * Handles svg uploads the same way Elementor does it but adds support for Amazon S3 storage links
	 *
	 * @param $value
	 * @param $object_id
	 * @param $meta_key
	 * @param $single
	 * @param $meta_type
	 *
	 * @return bool|mixed|string
	 *
	 * @hook get_post_metadata
	 */
	public function loadElementorExternalSvg( $value, $object_id, $meta_key, $single, $meta_type )
	{
		if ( $meta_key === '_elementor_inline_svg' && !doing_action('wgs_update_elementor_inline_svg') ) {
			if ( !empty($value) ) {
				return $value;
			}

			$attachment_file = get_attached_file( $object_id );

			if ( strpos($attachment_file, 's3://') === 0 ) {
				$svg = wp_remote_fopen(wp_get_attachment_url($object_id));
			} else if ( file_exists($attachment_file) ) {
				$svg = \Elementor\Utils::file_get_contents($attachment_file);
			} else {
				return '';
			}

			$valid_svg = ( new \Elementor\Core\Utils\Svg\Svg_Sanitizer())->sanitize($svg);

			if ( false === $valid_svg ) {
				return $value;
			}

			if ( ! empty( $valid_svg ) ) {
				do_action('wgs_update_elementor_inline_svg', $object_id, $valid_svg);
			}

			return $valid_svg;
		}

		return $value;
	}

	/**
	 * Updates the svg string in DB, as needed by Elementor to display the svg icons inline
	 *
	 * @param $object_id
	 * @param $valid_svg
	 *
	 * @return void
	 *
	 * @hook wgs_update_elementor_inline_svg
	 */
	public function updateElementorInlineSvg( $object_id, $valid_svg )
	{
		update_post_meta( $object_id, '_elementor_inline_svg', $valid_svg );
	}
}

$wgs_elementor_s3_uploads = new WGS_Elementor_S3_Uploads();