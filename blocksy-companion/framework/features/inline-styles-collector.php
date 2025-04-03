<?php

namespace Blocksy;

class InlineStylesCollector {
	private $css = '';

	public function __construct() {
		add_action('wp_footer', [$this, 'output_css'], 5);
	}

	public function output_css() {
		if (empty($this->css)) {
			return;
		}

		echo '<style id="ct-main-styles-footer-inline-css">' . $this->css . '</style>';
	}

	public function add($args = []) {
		$args = wp_parse_args($args, [
			'css' => null,
			'tablet_css' => null,
			'mobile_css' => null
		]);

		$strategy = 'core-block-supports';

		if ($strategy === 'core-block-supports') {
			$this->process_core_block_supports($args);
		}

		if ($strategy === 'top_of_footer') {
			$this->process_top_of_footer($args);
		}
	}

	private function process_core_block_supports($args) {
		$styles = [];

		if ($args['css']) {
			$styles[] = $args['css']->get_wp_style_engine_rules([
				'device' => 'desktop'
			]);
		}

		if ($args['tablet_css']) {
			$styles[] = $args['tablet_css']->get_wp_style_engine_rules([
				'device' => 'tablet'
			]);
		}

		if ($args['mobile_css']) {
			$styles[] = $args['mobile_css']->get_wp_style_engine_rules([
				'device' => 'mobile'
			]);
		}

		blc_call_gutenberg_function(
			'wp_style_engine_get_stylesheet_from_css_rules',
			[
				$styles,
				[
					'context'  => 'block-supports',
					'prettify' => false,
					'optimize' => true
				]
			]
		);
	}

	private function process_top_of_footer($args) {
		$styles = [
			'desktop' => '',
			'tablet' => '',
			'mobile' => ''
		];

		if ($args['css']) {
			$styles['desktop'] .= $args['css']->build_css_structure();
		}

		if ($args['tablet_css']) {
			$styles['tablet'] .= $args['tablet_css']->build_css_structure();
		}

		if ($args['mobile_css']) {
			$styles['mobile'] .= $args['mobile_css']->build_css_structure();
		}

		// TODO: maybe extract media queries
		$final_css = $styles['desktop'];

		if (! empty(trim($styles['tablet']))) {
			$final_css .= '@media (max-width: 999.98px) {' . $styles['tablet'] . '}';
		}

		if (! empty(trim($styles['mobile']))) {
			$final_css .= '@media (max-width: 689.98px) {' . $styles['mobile'] . '}';
		}

		if (! empty($final_css)) {
			$this->css .= $final_css;
		}
	}
}
