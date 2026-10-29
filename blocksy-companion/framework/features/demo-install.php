<?php

namespace Blocksy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DemoInstall {
	public function __construct() {
		$demo_export = new DemoInstallExport();

		$ajax_actions = array(
			array(
				'id'      => 'blocksy_demo_export',
				'handler' => array( $demo_export, 'request' ),
			),

			array(
				'id'      => 'blocksy_demo_get_export_data',
				'handler' => array( $demo_export, 'get_export_data' ),
			),

			array(
				'id'      => 'blocksy_demo_get_content_preliminary_data',
				'handler' => array( $this, 'demo_get_content_preliminary_data' ),
			),

			array(
				'id'      => 'blocksy_demo_install_child_theme',
				'handler' => array( new DemoInstallChildThemeInstaller(), 'import' ),
			),

			array(
				'id'      => 'blocksy_demo_activate_plugins',
				'handler' => array( new DemoInstallPluginsInstaller(), 'import' ),
			),

			array(
				'id'      => 'blocksy_demo_erase_content',
				'handler' => array( new DemoInstallContentEraser(), 'import' ),
			),

			array(
				'id'      => 'blocksy_demo_install_widgets',
				'handler' => array( new DemoInstallWidgetsInstaller(), 'import' ),
			),

			array(
				'id'      => 'blocksy_demo_install_options',
				'handler' => array( new DemoInstallOptionsInstaller(), 'import' ),
			),

			array(
				'id'      => 'blocksy_demo_install_content',
				'handler' => array( new DemoInstallContentInstaller(), 'import' ),
			),

			array(
				'id'      => 'blocksy_demo_register_current_demo',
				'handler' => array( new DemoInstallRegisterDemo(), 'register' ),
			),

			array(
				'id'      => 'blocksy_demo_deregister_current_demo',
				'handler' => array( new DemoInstallRegisterDemo(), 'deregister' ),
			),

			array(
				'id'      => 'blocksy_demo_deactivate_plugins',
				'handler' => array( new DemoInstallPluginsUninstaller(), 'import' ),
			),

			array(
				'id'      => 'blocksy_demo_install_finish',
				'handler' => array( new DemoInstallFinalActions(), 'import' ),
			),
		);

		foreach ( $ajax_actions as $action ) {
			add_action(
				'wp_ajax_' . $action['id'],
				function () use ( $action ) {
					$capability = blocksy_companion_get_capabilities()->get_wp_capability_by( 'dashboard' );

					if ( ! current_user_can( $capability ) ) {
						wp_send_json_error();
					}

					$this->check_nonce();

					call_user_func( $action['handler'] );
				}
			);
		}

		add_filter(
			'blocksy_dashboard_localizations',
			function ( $d ) {
				/**
				 * Filters whether the starter sites installer is available in the dashboard.
				 *
				 * @since 1.7.4
				 *
				 * @param string $enabled Either 'yes' or 'no'. Default 'yes'.
				 */
				$d['has_demo_install'] = apply_filters(
					'blocksy_ext_demo_install_enabled',
					'yes'
				);

				$d['demo_install_error'] = null;

				if ( ! extension_loaded( 'xml' ) && ! extension_loaded( 'simplexml' ) ) {
					$d['demo_install_error'] = __( "Your PHP installation doesn't have support for XML. Please install the <i>xml</i> or <i>simplexml</i> PHP extension in order to be able to install starter sites. You might need to contact your hosting provider to assist you in doing so.", 'blocksy-companion' );
				}

				$d['current_installed_demo'] = $this->get_current_demo();

				$plugins = array(
					'gutenberg'                           => false,
					'stackable-ultimate-gutenberg-blocks' => false,
					'wpforms-lite'                        => false,
					'woocommerce'                         => false,
					'elementor'                           => false,
					'brizy'                               => false,
					'getwid'                              => false,
					'simply-gallery-block'                => false,
					'recipe-card-blocks-by-wpzoom'        => false,
					'map-block-gutenberg'                 => false,
					'mb-custom-post-type'                 => false,
					'leadin'                              => false,
					'block-slider'                        => false,
					'ht-slider-for-elementor'             => false,
					'modula-best-grid-gallery'            => false,
					'advanced-custom-fields'              => false,
					'greenshift-animation-and-page-builder-blocks' => false,
					'fluentform'                          => false,
					'translatepress-multilingual'         => false,
					'fluent-booking'                      => false,
				);

				foreach ( $plugins as $plugin_name => $status ) {
					$plugins_manager = $this->get_plugins_manager();

					$path = $plugins_manager->is_plugin_installed( $plugin_name );

					if ( $path ) {
						if ( $plugins_manager->is_plugin_active( $path ) ) {
							$plugins[ $plugin_name ] = true;
						}
					}
				}

				$d['active_plugins'] = $plugins;

				return $d;
			}
		);
	}

	public function get_demo_remote_url( $args = array() ) {
		$endpoint = 'https://startersites.io/';
		// $endpoint = 'https://demo.creativethemes.com/';
		// $endpoint = 'http://localhost:3008/';
		return $endpoint . '?' . http_build_query( $args );
	}

	public function demo_get_content_preliminary_data() {
		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_send_json_error();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$raw_demo_name = isset( $_REQUEST['demo_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['demo_name'] ) ) : '';

		if ( '' === $raw_demo_name ) {
			wp_send_json_error(
				array(
					'message' => __( 'No demo name provided.', 'blocksy-companion' ),
				)
			);
		}

		$demo_name = explode( ':', $raw_demo_name );

		if ( ! isset( $demo_name[1] ) ) {
			$demo_name[1] = '';
		}

		$demo    = $demo_name[0];
		$builder = $demo_name[1];

		$demo_content = Plugin::instance()->demo->fetch_single_demo(
			array(
				'demo'    => $demo,
				'builder' => $builder,
				'field'   => 'all',
			)
		);

		update_option(
			'blocksy_ext_demos_currently_installing_demo',
			array(
				'demo' => json_encode( $demo_content ),
			)
		);

		wp_send_json_success( $demo_content );
	}

	public function get_current_demo() {
		return get_option( 'blocksy_ext_demos_current_demo', null );
	}

	public function blocksy_demo_get_export_data() {
		$this->check_nonce();

		if ( ! current_user_can( 'edit_theme_options' ) ) {
			wp_send_json_error();
		}

		$data = get_option(
			'blocksy_ext_demos_exported_demo_data',
			array()
		);

		wp_send_json_success(
			array(
				'data' => $data,
			)
		);
	}

	public function get_plugins_manager() {
		if ( ! class_exists( 'Blocksy_Plugin_Manager' ) ) {
			require_once get_template_directory() . '/admin/dashboard/plugins/ct-plugin-manager.php';
		}

		return new \Blocksy_Plugin_Manager();
	}

	public function fetch_single_demo( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'demo'    => $args['demo'],
				'builder' => '',
				'field'   => '',
			)
		);

		$body = blocksy_companion_request_remote_url(
			$this->get_demo_remote_url(
				array(
					'route' => 'get_single',
					'demo'  => $args['demo'] . ':' . $args['builder'],
					'field' => $args['field'],
				)
			),
			array(
				'user_agent_type' => 'wp',
			)
		);

		$body = json_decode( $body, true );

		if ( ! $body ) {
			return false;
		}

		return $body;
	}

	public function fetch_all_demos() {
		$body = blocksy_companion_request_remote_url(
			$this->get_demo_remote_url(
				array(
					'route' => 'get_all',
				)
			),
			array(
				'user_agent_type' => 'wp',
			)
		);

		if ( is_wp_error( $body ) ) {
			return $body;
		}

		if ( ! $body ) {
			return new \WP_Error( 'demo_fetch_failed', 'Failed to fetch demos.' );
		}

		$body = json_decode( $body, true );

		if ( ! $body ) {
			return false;
		}

		$data = get_plugin_data( BLOCKSY__FILE__ );

		$result = array();

		foreach ( $body as $single_demo ) {
			if ( ! isset( $single_demo['required_companion_version'] ) ) {
				$result[] = $single_demo;
				continue;
			}

			if ( version_compare(
				$data['Version'],
				$single_demo['required_companion_version'],
				'>='
			) ) {
				$result[] = $single_demo;
			}
		}

		return $result;
	}

	public function check_nonce() {
		if ( ! check_ajax_referer( 'ct-dashboard', 'nonce', false ) ) {
			wp_send_json_error( 'nonce' );
		}
	}

	public function get_currently_installing_demo() {
		$demo_to_install = get_option(
			'blocksy_ext_demos_currently_installing_demo',
			array()
		);

		if ( ! empty( $demo_to_install ) && ! empty( $demo_to_install['demo'] ) ) {
			$demo_to_install['demo'] = json_decode(
				$demo_to_install['demo'],
				true
			);
		}

		return $demo_to_install;
	}
}
