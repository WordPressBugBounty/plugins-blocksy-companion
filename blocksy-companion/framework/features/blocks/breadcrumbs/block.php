<?php

namespace Blocksy\Editor\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BreadCrumbs {
	public function __construct() {
		add_action( 'init', array( $this, 'blocksy_breadcrumbs_block' ) );

		add_action(
			'wp_ajax_blocksy_blocks_retrieve_breadcrumbs_data_descriptor',
			function () {
				$blocksy_manager = blocksy_companion_theme_functions()->blocksy_manager();

				if (
					! current_user_can( 'manage_options' )
					||
					! $blocksy_manager
				) {
					wp_send_json_error();
				}

				$data = json_decode( file_get_contents( 'php://input' ), true );

				if ( ! isset( $data['post_id'] ) ) {
					wp_send_json_success(
						/**
						 * Filters the breadcrumbs block preview data sent to the block editor.
						 *
						 * @since 2.1.0
						 *
						 * @param array $data {
						 *     Breadcrumbs preview data.
						 *
						 *     @type int|null $post_id   The previewed post ID. Default null.
						 *     @type string   $post_type The previewed post type. Default 'post'.
						 *     @type string   $render    Rendered breadcrumbs markup. Omitted when no post is selected.
						 * }
						 */
						apply_filters(
							'blocksy:general:blocks:breadcrumbs:data',
							array(
								'post_id'   => null,
								'post_type' => 'post',
							)
						)
					);
				}

				if ( ! $data || ! isset( $data['post_id'] ) ) {
					wp_send_json_error();
				}

				global $post, $wp_query;
				$post_id = intval( $data['post_id'] );
				$post    = $post_id ? get_post( $post_id ) : null;

				if (
					isset( $data['taxonomyTerms'] )
					&& is_array( $data['taxonomyTerms'] )
					&& ! empty( $data['taxonomyTerms'] )
				) {
					add_filter(
						'wp_get_object_terms',
						function ( $terms, $object_ids, $taxonomies ) use ( $data ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
							$terms = array();

							foreach ( $data['taxonomyTerms'] as $slug => $values ) {
								foreach ( $values as $category_id ) {
									$term = get_term( $category_id, $slug );

									if ( ! $term || is_wp_error( $term ) ) {
										continue;
									}

									$terms[] = $term;
								}
							}

							return $terms;
						},
						10,
						3
					);
				}

				if ( ! $post || is_wp_error( $post ) ) {
					wp_send_json_error();
				}

				$wp_query->is_single   = true;
				$wp_query->is_singular = true;

				if ( 'product' === $post->post_type ) {
					$wp_query->is_product = true;
					add_filter( 'is_woocommerce', '__return_true' );

					global $product;

					$product = wc_get_product( $post_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- WooCommerce global
				}

				$post->post_title = '__BLOCKSY_BREADCRUMBS_POST_TITLE__';
				add_filter(
					'the_title',
					function ( $post_title, $post_id ) {
						if ( $GLOBALS['post']->ID !== $post_id ) {
							return $post_title;
						}

						return $GLOBALS['post']->post_title;
					},
					10,
					2
				);

				if ( ! class_exists( '\Blocksy\BreadcrumbsBuilder' ) ) {
					wp_send_json_error();
				}

				$breadcrumbs_builder = new \Blocksy\BreadcrumbsBuilder(
					array(
						'is_frontend' => false,
					)
				);

				wp_send_json_success(
					/**
					 * Filters the breadcrumbs block preview data sent to the block editor.
					 *
					 * @since 2.1.0
					 *
					 * @param array $data {
					 *     Breadcrumbs preview data.
					 *
					 *     @type int|null $post_id   The previewed post ID. Default null.
					 *     @type string   $post_type The previewed post type. Default 'post'.
					 *     @type string   $render    Rendered breadcrumbs markup. Omitted when no post is selected.
					 * }
					 */
					apply_filters(
						'blocksy:general:blocks:breadcrumbs:data',
						array(
							'post_id'   => $post_id,
							'post_type' => $post->post_type,
							'render'    => $breadcrumbs_builder->render(
								array(
									'class' => 'ct-breadcrumbs-block',
								)
							),
						)
					)
				);
			}
		);
	}

	public function blocksy_breadcrumbs_block() {
		$block_data = array(
			'render_callback' => function ( $attributes, $content ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
				$attributes = wp_parse_args(
					$attributes,
					array(
						'className' => '',
						'style'     => array(),
					)
				);

				$colors = array();

				if ( isset( $attributes['linkColor'] ) ) {
					$var                                  = sanitize_key( $attributes['linkColor'] );
					$colors['--theme-link-initial-color'] = "var(--wp--preset--color--$var)";
				}

				if (
					isset( $attributes['customLinkColor'] )
					&&
					blocksy_companion_theme_functions()->blocksy_validate_color( $attributes['customLinkColor'] )
				) {
					$colors['--theme-link-initial-color'] = $attributes['customLinkColor'];
				}

				if ( isset( $attributes['textColor'] ) ) {
					$var                          = sanitize_key( $attributes['textColor'] );
					$colors['--theme-text-color'] = "var(--wp--preset--color--$var)";
				}

				if (
					isset( $attributes['customTextColor'] )
					&&
					blocksy_companion_theme_functions()->blocksy_validate_color( $attributes['customTextColor'] )
				) {
					$colors['--theme-text-color'] = $attributes['customTextColor'];
				}

				if ( isset( $attributes['linkHoverColor'] ) ) {
					$var                                = sanitize_key( $attributes['linkHoverColor'] );
					$colors['--theme-link-hover-color'] = "var(--wp--preset--color--$var)";
				}

				if (
					isset( $attributes['customLinkHoverColor'] )
					&&
					blocksy_companion_theme_functions()->blocksy_validate_color( $attributes['customLinkHoverColor'] )
				) {
					$colors['--theme-link-hover-color'] = $attributes['customLinkHoverColor'];
				}

				$colors_css = '';

				foreach ( $colors as $key => $value ) {
					if ( empty( $value ) ) {
						continue;
					}

					$colors_css .= $key . ':' . $value . ';';
				}

				if ( ! class_exists( '\Blocksy\BreadcrumbsBuilder' ) ) {
					return '';
				}

				$breadcrumbs_builder = new \Blocksy\BreadcrumbsBuilder();

				$wp_styles = wp_style_engine_get_styles(
					$attributes['style']
				);

				$wp_styles_css = isset( $wp_styles['css'] ) ? $wp_styles['css'] : '';

				$class = '';

				if ( is_string( $attributes['className'] ) ) {
					$class = implode(
						' ',
						array_map(
							'sanitize_html_class',
							explode( ' ', $attributes['className'] )
						)
					);
				}

				return $breadcrumbs_builder->render(
					array_merge(
						array(
							'class' => $class,
						),
						! empty( $wp_styles_css ) || ! empty( $colors_css ) ? array(
							'style' => $wp_styles_css . $colors_css,
						) : array()
					)
				);
			},
		);

		register_block_type(
			BLOCKSY_PATH . '/static/js/editor/blocks/breadcrumbs/block.json',
			$block_data
		);
	}
}
