<?php

namespace Blocksy;

class DemoInstallRegisterDemo {
	public function register() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if (! isset($_REQUEST['demo_name']) || ! $_REQUEST['demo_name']) {
			wp_send_json_error([
				'message' => __("No demo name provided.", 'blocksy-companion')
			]);
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$demo_name = explode(':', sanitize_text_field(wp_unslash($_REQUEST['demo_name'])));

		if (! isset($demo_name[1])) {
			$demo_name[1] = '';
		}

		$demo = $demo_name[0];
		$builder = $demo_name[1];

		$this->set_current_demo($demo . ':' . $builder);

		wp_send_json_success();
	}

	public function deregister() {
		update_option('blocksy_ext_demos_current_demo', null);

		wp_send_json_success();
	}

	public function set_current_demo($demo) {
		update_option('blocksy_ext_demos_current_demo', [
			'demo' => $demo
		]);
	}
}
