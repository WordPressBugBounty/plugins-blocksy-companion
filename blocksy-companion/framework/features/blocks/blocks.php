<?php

namespace Blocksy\Editor;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Blocks {
	private $blocks = array();

	public $query = null;

	public static function sanitize_unique_id( $raw ) {
		return is_string( $raw ) ? substr( preg_replace( '/[^a-zA-Z0-9_-]/', '', $raw ), 0, 40 ) : '';
	}

	public function __construct() {
		// Mount at `after_setup_theme` to make sure the theme is loaded
		add_action(
			'after_setup_theme',
			array( $this, 'mount' ),
			50
		);
	}

	public function mount() {
		add_action(
			'enqueue_block_editor_assets',
			function () {
				if ( ! function_exists( 'get_plugin_data' ) ) {
					require_once ABSPATH . 'wp-admin/includes/plugin.php';
				}

				$data = get_plugin_data( BLOCKSY__FILE__ );

				$deps = array(
					'wp-blocks',
					'wp-editor',
					'wp-core-data',
					'wp-element',
					'wp-block-editor',
					'wp-server-side-render',
				);

				global $wp_customize;

				if ( $wp_customize ) {
					$deps[] = 'ct-customizer-controls';
				} else {
					$deps[] = 'ct-options-scripts';
				}

				$theme = blocksy_companion_theme_functions()->blocksy_get_wp_parent_theme();

				wp_register_style(
					'blocksy-theme-blocks-editor-styles',
					get_template_directory_uri() . '/static/bundle/theme-blocks-editor-styles.min.css',
					array(),
					$theme->get( 'Version' )
				);

				wp_enqueue_script(
					'blocksy/gutenberg-blocks',
					BLOCKSY_URL . '/static/bundle/blocks/blocks.js',
					$deps,
					$data['Version'],
					false
				);
			}
		);

		$blocks = array(
			'about-me',
			'contact-info',
			'quote',
			'socials',
			'search',
			'share-box',
		);

		foreach ( $blocks as $block ) {
			$this->blocks[ $block ] = new \Blocksy\Editor\GutenbergBlock(
				$block,
				array(
					'static' => false,
				)
			);
		}

		add_action(
			'wp_ajax_blocksy_get_dynamic_block_view',
			function () {
				if (
				! current_user_can( 'edit_posts' )
				||
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				! isset( $_POST['block'] )
				||
				// phpcs:ignore WordPress.Security.NonceVerification.Missing
				! isset( $_POST['attributes'] )
				) {
					wp_send_json_error();
				}

			// phpcs:ignore WordPress.Security.NonceVerification.Missing
				$block_name = sanitize_key( $_POST['block'] );

				if ( ! isset( $this->blocks[ $block_name ] ) ) {
					wp_send_json_error();
				}

				$gutenberg_block = $this->blocks[ $block_name ];

			// phpcs:ignore WordPress.Security.NonceVerification.Missing, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				$attributes = json_decode( wp_unslash( $_POST['attributes'] ), true );

				if ( ! is_array( $attributes ) ) {
					$attributes = array();
				}

				wp_send_json_success(
					array(
						'content' => $gutenberg_block->render( $attributes ),
					)
				);
			}
		);

		$this->init_blocks();
	}

	public function init_blocks() {
		// Root Block
		new \Blocksy\Editor\Blocks\BlockWrapper();

		new \Blocksy\Editor\Blocks\BreadCrumbs();
		$this->query = new \Blocksy\Editor\Blocks\Query();
		new \Blocksy\Editor\Blocks\TaxQuery();
		new \Blocksy\Editor\Blocks\DynamicData();
	}
}
