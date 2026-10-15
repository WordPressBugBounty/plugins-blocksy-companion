<?php

namespace Blocksy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DemoInstallExport {
	public function request() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$demo_id = isset( $_REQUEST['demoId'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['demoId'] ) ) : '';

		if ( '' === $demo_id ) {
			wp_send_json_error();
		}

		global $wp_customize;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$builder = isset( $_REQUEST['builder'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['builder'] ) ) : '';

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$plugins = isset( $_REQUEST['plugins'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['plugins'] ) ) : '';

		$plugins = explode( ',', preg_replace( '/\s+/', '', $plugins ) );

		$options_data = new DemoInstallOptionsExport();

		$widgets_data = new DemoInstallWidgetsExport();
		$widgets_data = $widgets_data->export();

		add_filter(
			'export_wp_all_post_types',
			function ( $post_types ) {
				$post_types['wpforms'] = 'wpforms';
				return $post_types;
			}
		);

		$content_data = new DemoInstallContentExport();
		$content_data = $content_data->export();

		$demo_data = array(
			'options'           => $options_data->export(),
			'widgets'           => $widgets_data,
			'content'           => $content_data,

			'pages_ids_options' => $options_data->export_pages_ids_options(),
			'created_at'        => gmdate( 'd-m-Y' ),

			'builder'           => $builder,
			'plugins'           => $plugins,
		);

		update_option(
			'blocksy_ext_demos_exported_demo_data',
			array(
				'demoId'  => $demo_id,
				'builder' => $builder,
				'plugins' => $plugins,
			)
		);

		wp_send_json_success(
			array(
				'demo' => $demo_data,
			)
		);
	}

	public function get_export_data() {
		if ( ! current_user_can( 'manage_options' ) ) {
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
}
