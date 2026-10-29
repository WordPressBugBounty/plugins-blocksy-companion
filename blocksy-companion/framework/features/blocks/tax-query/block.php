<?php

namespace Blocksy\Editor\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TaxQuery {
	private function maybe_enqueue_pagination_styles() {
		if ( wp_style_is( 'ct-pagination-styles', 'registered' ) ) {
			wp_enqueue_style( 'ct-pagination-styles' );
		}
	}

	public function __construct() {
		add_action(
			'wp_ajax_blocksy_get_tax_block_data',
			function () {
				if ( ! current_user_can( 'edit_posts' ) ) {
					wp_send_json_error();
				}

				$body = json_decode( file_get_contents( 'php://input' ), true );

				if ( ! isset( $body['attributes'] ) ) {
					wp_send_json_error();
				}

				$all_terms = $this->get_terms_for( $body['attributes'] );

				$pagination_output = '';

				if ( blocksy_companion_akg( 'has_pagination', $body['attributes'], 'no' ) === 'yes' ) {
					$term_query = $this->get_term_query( $body['attributes'] );

					if ( $term_query ) {
						$pagination_data = $this->get_pagination_descriptor( $body['attributes'] );
						$prefix          = self::get_prefix_for( $body['attributes'] );

						$pagination_output = blocksy_companion_theme_functions()->blocksy_display_posts_pagination(
							array(
								'query'     => $term_query,
								'prefix'    => $prefix,
								'query_var' => $pagination_data['query_var'],
							)
						);
					}
				}

				wp_send_json_success(
					array(
						'all_terms'         => $all_terms,
						'pagination_output' => $pagination_output,
					)
				);
			}
		);

		add_action(
			'wp_ajax_blocksy_get_terms_block_patterns',
			function () {
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error();
				}

				$all_patterns = \WP_Block_Patterns_Registry::get_instance()
				->get_all_registered();

				$result = array();

				foreach ( $all_patterns as $single_pattern ) {
					if (
					isset( $single_pattern['blockTypes'] )
					&&
					is_array( $single_pattern['blockTypes'] )
					&&
					in_array(
						'blocksy/tax-query',
						$single_pattern['blockTypes'],
						true
					)
					) {
						$result[] = $single_pattern;
					}
				}

				wp_send_json_success(
					array(
						'patterns' => $result,
					)
				);
			}
		);

		register_block_type(
			BLOCKSY_PATH . '/static/js/editor/blocks/tax-query/block.json',
			array(
				'render_callback' => function ( $attributes, $content, $block ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable
					$border_result = get_block_core_post_featured_image_border_attributes(
						$attributes
					);

					if ( strpos( $content, 'wp-block-blocksy-tax-query"></div>' ) !== false ) {
						return '';
					}

					$block_reader = new \WP_HTML_Tag_Processor( $content );

					if (
						$block_reader->next_tag(
							array(
								'class_name' => 'wp-block-blocksy-tax-query',
							)
						)
						&&
						! empty( $attributes['uniqueId'] )
					) {
						$block_reader->set_attribute(
							'data-id',
							\Blocksy\Editor\Blocks::sanitize_unique_id( $attributes['uniqueId'] )
						);

						if ( ! empty( $border_result['class'] ) ) {
							$block_reader->add_class( $border_result['class'] );
						}

						if ( ! empty( $border_result['style'] ) ) {
							$block_reader->set_attribute(
								'style',
								$border_result['style'] . $block_reader->get_attribute( 'style' )
							);
						}
					}

					return $block_reader->get_updated_html();
				},
			)
		);

		add_filter(
			'render_block',
			function ( $block_content, $block, $instance ) {
				if ( 'blocksy/tax-template' !== $block['blockName'] ) {
					return $block_content;
				}

				if ( ! class_exists( 'Blocksy_Css_Injector' ) ) {
					return $block_content;
				}

				$processor = new \WP_HTML_Tag_Processor( $block_content );

				$context = $instance->context;

				$is_slideshow_layout = 'yes' === $context['has_slideshow'];
				$has_item_link       = blocksy_companion_akg( 'has_item_link', $block['attrs'], 'no' ) === 'yes';
				$layout              = blocksy_companion_akg( 'layout/type', $block['attrs'], 'default' );
				$is_grid_layout      = 'grid' === $layout;

				$processor->next_tag( 'div' );

				$class = array_merge(
					$is_slideshow_layout ? array( 'ct-query-template', 'is-layout-slider' ) : array( 'ct-query-template-' . $layout ),
				);

				if ( $is_slideshow_layout ) {
					wp_enqueue_style( 'ct-flexy-styles' );
				}

				if (
					! $is_slideshow_layout
					&&
					'grid' !== $layout
				) {
					$class[] = 'is-layout-flow';
				}

				if ( $has_item_link ) {
					$class [] = 'ct-has-link-overlay';
				}

				$processor->set_attribute( 'class', implode( ' ', $class ) );
				$block_content = $processor->get_updated_html();

				$css        = new \Blocksy_Css_Injector();
				$tablet_css = new \Blocksy_Css_Injector();
				$mobile_css = new \Blocksy_Css_Injector();

				$unique_id = \Blocksy\Editor\Blocks::sanitize_unique_id(
					blocksy_companion_akg( 'uniqueId', $context, '' )
				);

				$root_selector = array( "[data-id='{$unique_id}']" );

				if (
					isset( $block['attrs']['verticalAlignment'] )
					&&
					$is_grid_layout
				) {
					$align_items = 'center';

					if ( 'top' === $block['attrs']['verticalAlignment'] ) {
						$align_items = 'flex-start';
					}

					if ( 'bottom' === $block['attrs']['verticalAlignment'] ) {
						$align_items = 'flex-end';
					}

					$css->put(
						blocksy_companion_theme_functions()->blocksy_assemble_selector( $root_selector ),
						'align-items: ' . $align_items
					);
				}

				$columns = array(
					'desktop' => 1,
					'tablet'  => 1,
					'mobile'  => 1,
				);

				if ( $is_grid_layout ) {
					$columns = array(
						'desktop' => blocksy_companion_akg(
							'columnCount',
							$block['attrs']['layout'],
							'3'
						),
						'tablet'  => blocksy_companion_akg(
							'tabletColumns',
							$block['attrs'],
							'2'
						),
						'mobile'  => blocksy_companion_akg(
							'mobileColumns',
							$block['attrs'],
							'1'
						),
					);

					if ( ! $columns['desktop'] ) {
						$columns['desktop'] = 3;
					}

					if ( ! $columns['tablet'] ) {
						$columns['tablet'] = 2;
					}

					if ( ! $columns['mobile'] ) {
						$columns['mobile'] = 1;
					}
				}

				$gap = self::get_gap_value( $block['attrs'] );

				if ( $is_grid_layout && ! empty( $gap ) ) {
					$css->put(
						blocksy_companion_theme_functions()->blocksy_assemble_selector( $root_selector ),
						'--grid-columns-gap: ' . $gap
					);
				}

				if (
					! $is_grid_layout
					&&
					! empty( $gap )
					&&
					! $is_slideshow_layout
				) {
					// DO WE REALLY NEED GAP HERE???

					$css->put(
						blocksy_companion_theme_functions()->blocksy_assemble_selector(
							blocksy_companion_theme_functions()->blocksy_mutate_selector(
								array(
									'selector'  => $root_selector,
									'operation' => 'suffix',
									'to_add'    => ':where(.wp-block-term)',
								)
							)
						),
						'margin-block-end: ' . $gap
					);
				}

				$this->get_grid_styles(
					array(
						'css'           => $css,
						'tablet_css'    => $tablet_css,
						'mobile_css'    => $mobile_css,

						'root_selector' => $root_selector,

						'is_slider'     => $is_slideshow_layout,
						'layout_type'   => $is_grid_layout ? 'grid' : 'default',

						'columns'       => $columns,
					)
				);

				$block_content .= \Blocksy\Plugin::instance()->inline_styles_collector->get_style_tag(
					array(
						'css'        => $css,
						'tablet_css' => $tablet_css,
						'mobile_css' => $mobile_css,
					)
				);

				return $block_content;
			},
			11,
			3
		);

		register_block_type(
			BLOCKSY_PATH . '/static/js/editor/blocks/tax-template/block.json',
			array(
				'render_callback' => function ( $attributes, $content, $block ) {
					$all_terms = $this->get_terms_for( $block->context );

					if ( empty( $all_terms ) ) {
						return '';
					}

					$attributes = wp_parse_args(
						$attributes,
						array(
							'desktopColumns' => '3',
							'tabletColumns'  => '2',
							'mobileColumns'  => '1',

							'has_item_link'  => 'no',
						)
					);

					$context = wp_parse_args(
						$block->context,
						array(
							'has_slideshow'                => 'no',
							'has_slideshow_arrows'         => 'yes',
							'has_slideshow_pills'          => 'no',
							'has_slideshow_autoplay'       => 'no',
							'has_slideshow_autoplay_speed' => 3,
							'hide_empty'                   => 'yes',
							'has_pagination'               => 'no',
						)
					);

					$is_slideshow_layout = 'yes' === $context['has_slideshow'];

					$content     = '';
					$pills_count = 0;

					$wrapper_attributes = get_block_wrapper_attributes();

					foreach ( $all_terms as $key => $term ) {
						$term_obj = get_term( $term['term_id'] );

						if ( ! $term_obj ) {
							continue;
						}

						$all_terms = get_terms(
							array(
								'taxonomy'   => $term_obj->taxonomy,
								'hide_empty' => 'yes' === $context['hide_empty'],
								'include'    => array( $term_obj->term_id ),
							)
						);

						if (
							! is_wp_error( $all_terms )
							&&
							! empty( $all_terms[0] )
						) {
							$term_obj->count = $all_terms[0]->count;
						}

						$block_instance = $block->parsed_block;

						// Set the block name to one that does not correspond to an existing registered block.
						// This ensures that for the inner instances of the Post Template block, we do not render any block supports.
						$block_instance['blockName'] = 'core/null';

						global $blocksy_term_obj;
						$blocksy_term_obj = $term_obj;

						// Render the inner blocks of the Post Template block with `dynamic` set to `false` to prevent calling
						// `render_callback` and ensure that no wrapper markup is included.
						$block_content = ( new \WP_Block( $block_instance ) )->render(
							array( 'dynamic' => false )
						);

						$link_html = '';

						if ( 'yes' === $attributes['has_item_link'] ) {
							$link_attributes = array(
								'href'  => get_term_link( $term_obj ),
								'class' => 'ct-link-overlay',
							);

							$link_html = blocksy_companion_html_tag(
								'a',
								$link_attributes,
								''
							);
						}

						$single_item = blocksy_companion_html_tag(
							'div',
							array(
								'class' => implode(
									' ',
									array(
										'wp-block-term',
										'is-layout-flow',
									// 'ct-term-' . $term_obj->term_id
									)
								),
							),
							$link_html .
							$block_content
						);

						if ( $is_slideshow_layout ) {
							$single_item = blocksy_companion_html_tag(
								'div',
								array_merge(
									array(
										'class' => 'flexy-item',
									),
									0 === $key ? array( 'data-item' => 'initial' ) : array()
								),
								$single_item
							);
						}

						$content .= $single_item;
						$pills_count++;

						$blocksy_term_obj = null;
					}

					if ( $is_slideshow_layout ) {
						$arrows = '';
						$pills  = '';

						if ( 'yes' === $context['has_slideshow_arrows'] ) {
							/**
							 * Filters the SVG icons used for Flexy slideshow navigation arrows.
							 *
							 * @since 2.0.98
							 *
							 * @param array $arrow_icons {
							 *     SVG markup for slideshow navigation arrows.
							 *
							 *     @type string $prev Previous arrow SVG markup.
							 *     @type string $next Next arrow SVG markup.
							 * }
							 */
							$arrow_icons = apply_filters(
								'blocksy:flexy:arrows',
								array(
									'prev' => '<svg width="16" height="10" fill="currentColor" viewBox="0 0 16 10"><path d="M15.3 4.3h-13l2.8-3c.3-.3.3-.7 0-1-.3-.3-.6-.3-.9 0l-4 4.2-.2.2v.6c0 .1.1.2.2.2l4 4.2c.3.4.6.4.9 0 .3-.3.3-.7 0-1l-2.8-3h13c.2 0 .4-.1.5-.2s.2-.3.2-.5-.1-.4-.2-.5c-.1-.1-.3-.2-.5-.2z"></path></svg>',
									'next' => '<svg width="16" height="10" fill="currentColor" viewBox="0 0 16 10"><path d="M.2 4.5c-.1.1-.2.3-.2.5s.1.4.2.5c.1.1.3.2.5.2h13l-2.8 3c-.3.3-.3.7 0 1 .3.3.6.3.9 0l4-4.2.2-.2V5v-.3c0-.1-.1-.2-.2-.2l-4-4.2c-.3-.4-.6-.4-.9 0-.3.3-.3.7 0 1l2.8 3H.7c-.2 0-.4.1-.5.2z"></path></svg>',
								)
							);

							$arrows = '<span class="flexy-arrow-prev" data-position="outside">' . $arrow_icons['prev'] . '</span>
								<span class="flexy-arrow-next" data-position="outside">' . $arrow_icons['next'] . '</span>';
						}

						if ( 'yes' === $context['has_slideshow_pills'] ) {
							ob_start();
							blocksy_companion_theme_functions()->blocksy_flexy_pills(
								array(
									'pills_count'          => $pills_count,
									'pills_container_attr' => array(
										'data-flexy' => $pills_count <= 5
											? 'no:paused'
											: 'no',
									),
								)
							);
							$pills = ob_get_clean();
						}

						$content = blocksy_companion_html_tag(
							'div',
							array_merge(
								array(
									'class'      => 'flexy-container',
									'data-flexy' => 'no',
								),
								'yes' === $context['has_slideshow_autoplay'] ? array(
									'data-autoplay' => $context['has_slideshow_autoplay_speed'],
								) : array()
							),
							blocksy_companion_html_tag(
								'div',
								array(
									'class' => 'flexy',
								),
								blocksy_companion_html_tag(
									'div',
									array(
										'class'           => 'flexy-view',
										'data-flexy-view' => 'boxed',
									),
									blocksy_companion_html_tag(
										'div',
										array(
											'class' => 'flexy-items',
										),
										$content
									)
								) .
								$arrows
							) . $pills
						);
					}

					$result = blocksy_companion_safe_sprintf(
						'<div %1$s>%2$s</div>',
						$wrapper_attributes,
						$content
					);

					if (
						blocksy_companion_akg( 'has_pagination', $context, 'no' ) === 'yes'
						&&
						! $is_slideshow_layout
					) {
						$this->maybe_enqueue_pagination_styles();

						$term_query = $this->get_term_query( $block->context );

						if ( $term_query ) {
							$pagination_data = $this->get_pagination_descriptor( $block->context );
							$prefix          = self::get_prefix_for( $block->context );

							$result .= blocksy_companion_theme_functions()->blocksy_display_posts_pagination(
								array(
									'query'     => $term_query,
									'prefix'    => $prefix,
									'query_var' => $pagination_data['query_var'],
								)
							);
						}
					}

					return $result;
				},
			)
		);

		add_action(
			'init',
			function () {
				$tax_block_patterns = array(
					'tax-layout-1',
					'tax-layout-2',
					'tax-layout-3',
					'tax-layout-4',
					'tax-layout-5',
				);

				foreach ( $tax_block_patterns as $tax_block_pattern ) {
					$pattern_data = blocksy_companion_get_variables_from_file(
						__DIR__ . '/block-patterns/' . $tax_block_pattern . '.php',
						array( 'pattern' => array() )
					);

					register_block_pattern(
						'blocksy/' . $tax_block_pattern,
						$pattern_data['pattern']
					);
				}
			}
		);
	}

	private static function get_gap_value( $attributes ) {
		if ( ! isset( $attributes['style'] ) ) {
			return '';
		}

		$gap_value = '';

		$gap_value = blocksy_companion_akg( 'spacing', $attributes['style'], array() );

		if ( ! isset( $gap_value['blockGap'] ) ) {
			return '';
		}

		$gap_value = $gap_value['blockGap'];

		$combined_gap_value = '';
		$gap_sides          = is_array( $gap_value ) ? array( 'top', 'left' ) : array( 'top' );

		foreach ( $gap_sides as $gap_side ) {
			$process_value = $gap_value;

			if ( is_array( $gap_value ) ) {
				$process_value = isset( $gap_value[ $gap_side ] ) ? $gap_value[ $gap_side ] : '';
			}

			if (
				is_string( $process_value )
				&&
				strpos( $process_value, 'var:preset|spacing|' ) !== false
			) {
				$index_to_splice = strrpos( $process_value, '|' ) + 1;
				$slug            = _wp_to_kebab_case( substr( $process_value, $index_to_splice ) );
				$process_value   = "var(--wp--preset--spacing--$slug)";
			}

			$combined_gap_value .= "$process_value ";
		}

		return trim( $combined_gap_value );
	}

	private static function get_attributes( $attributes ) {
		// migrate to native brands
		if ( 'product_brands' === $attributes['taxonomy'] ) {
			$attributes['taxonomy'] = 'product_brand';
		}

		$attributes = wp_parse_args(
			$attributes,
			array(
				'uniqueId'                     => '',

				'taxonomy'                     => 'category',
				// all | parent | relevant
				'level'                        => 'all',
				'limit'                        => 5,
				'hide_empty'                   => 'yes',
				'offset'                       => 0,
				'orderby'                      => 'none',
				'order'                        => 'desc',
				'include_term_ids'             => array(),
				'exclude_term_ids'             => array(),
				'class'                        => '',

				// yes | no
				'has_slideshow'                => 'no',
				'has_slideshow_arrows'         => 'yes',
				'has_slideshow_pills'          => 'no',
				'has_slideshow_autoplay'       => 'no',
				'has_slideshow_autoplay_speed' => 3,

				// yes | no
				'has_pagination'               => 'no',
			),
		);

		$attributes['uniqueId'] = \Blocksy\Editor\Blocks::sanitize_unique_id(
			$attributes['uniqueId']
		);

		return $attributes;
	}

	private static function get_prefix_for( $attributes ) {
		$attributes = self::get_attributes( $attributes );

		$prefix = 'blog';

		$taxonomy = $attributes['taxonomy'];

		if ( ! taxonomy_exists( $taxonomy ) ) {
			return $prefix;
		}

		$taxonomy_object = get_taxonomy( $taxonomy );

		if ( ! $taxonomy_object || empty( $taxonomy_object->object_type ) ) {
			return $prefix;
		}

		// Get the first post type this taxonomy is assigned to
		$post_type = $taxonomy_object->object_type[0];

		$custom_post_types = array();

		if ( blocksy_companion_theme_functions()->blocksy_manager() ) {
			$custom_post_types = blocksy_companion_theme_functions()->blocksy_manager()->post_types->get_supported_post_types();
		}

		foreach ( $custom_post_types as $cpt ) {
			if ( $cpt === $post_type ) {
				$prefix = $cpt . '_archive';
			}
		}

		if ( 'product' === $post_type ) {
			$prefix = 'woo_categories';
		}

		return $prefix;
	}

	// ?tax-query-{uniqueId}=2
	public function get_pagination_descriptor( $attributes ) {
		$attributes = self::get_attributes( $attributes );

		$query_var = 'tax-query-' . $attributes['uniqueId'];

		$current_page = 1;

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET[ $query_var ] ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$current_page = intval( sanitize_text_field( wp_unslash( $_GET[ $query_var ] ) ) );
		}

		return array(
			'query_var' => $query_var,
			'value'     => max( 1, $current_page ),
		);
	}

	/**
	 * Build term query args from block attributes.
	 */
	public function get_term_query_args( $attributes ) {
		$attributes = self::get_attributes( $attributes );

		if ( ! taxonomy_exists( $attributes['taxonomy'] ) ) {
			return null;
		}

		$filtered_include = array();
		$filtered_exclude = array();

		if (
			! empty( $attributes['include_term_ids'] )
			&&
			isset( $attributes['include_term_ids'][ $attributes['taxonomy'] ] )
			&&
			isset( $attributes['include_term_ids'][ $attributes['taxonomy'] ]['strategy'] )
			&&
			'specific' === $attributes['include_term_ids'][ $attributes['taxonomy'] ]['strategy']
		) {
			foreach ( $attributes['include_term_ids'][ $attributes['taxonomy'] ]['terms'] as $value ) {
				$term = get_term_by( 'slug', $value, $attributes['taxonomy'] );

				if ( $term ) {
					$filtered_include[] = $term->term_id;
				}
			}
		}

		if (
			! empty( $attributes['exclude_term_ids'] )
			&&
			isset( $attributes['exclude_term_ids'][ $attributes['taxonomy'] ] )
			&&
			isset( $attributes['exclude_term_ids'][ $attributes['taxonomy'] ]['strategy'] )
			&&
			'specific' === $attributes['exclude_term_ids'][ $attributes['taxonomy'] ]['strategy']
		) {
			foreach ( $attributes['exclude_term_ids'][ $attributes['taxonomy'] ]['terms'] as $value ) {
				$term = get_term_by( 'slug', $value, $attributes['taxonomy'] );

				if ( $term ) {
					$filtered_exclude[] = $term->term_id;
				}
			}
		}

		$terms_query_args = array(
			'taxonomy'   => $attributes['taxonomy'],
			'orderby'    => $attributes['orderby'],
			'order'      => $attributes['order'],
			'offset'     => $attributes['offset'],
			'number'     => $attributes['limit'],
			'hide_empty' => 'yes' === $attributes['hide_empty'],
		);

		// for product categories, if orderby is none, we want to let
		// WooCommerce handle the ordering so we get the same order as
		// set in the admin UI.
		if (
			'none' === $terms_query_args['orderby']
			&&
			'product_cat' === $terms_query_args['taxonomy']
		) {
			unset( $terms_query_args['orderby'] );
		}

		if (
			'parent' === $attributes['level']
			||
			'relevant' === $attributes['level']
		) {
			$terms_query_args['parent'] = 0;
		}

		if ( 'relevant' === $attributes['level'] ) {
			$current_term = get_queried_object();

			if (
				$current_term
				&&
				$current_term instanceof \WP_Term
			) {
				$terms_query_args['parent'] = $current_term->term_id;
			}
		}

		if ( ! empty( $filtered_exclude ) ) {
			// phpcs:ignore WordPressVIPMinimum.Performance.WPQueryParams.PostNotIn_exclude
			$terms_query_args['exclude'] = $filtered_exclude;
		}

		if ( ! empty( $filtered_include ) ) {
			$terms_query_args['include'] = $filtered_include;
		}

		// Handle pagination offset
		if ( 'yes' === $attributes['has_pagination'] ) {
			$pagination_data = $this->get_pagination_descriptor( $attributes );
			$current_page    = $pagination_data['value'];
			$per_page        = $attributes['limit'];
			$base_offset     = $attributes['offset'];

			$terms_query_args['offset'] = ( $current_page - 1 ) * $per_page + $base_offset;
		}

		/**
		 * Filters the term query arguments used by the Advanced Taxonomies block.
		 *
		 * @since 2.1.11
		 *
		 * @param array $terms_query_args Term query arguments prepared by the block.
		 * @param array $attributes       Block attributes.
		 */
		return apply_filters(
			'blocksy:general:blocks:tax-query:args',
			$terms_query_args,
			$attributes
		);
	}

	/**
	 * Get term query with pagination adapter.
	 * Returns a WP_Query-like object for use with pagination.
	 */
	public function get_term_query( $attributes ) {
		$attributes       = self::get_attributes( $attributes );
		$terms_query_args = $this->get_term_query_args( $attributes );

		if ( null === $terms_query_args ) {
			return null;
		}

		$pagination_data = $this->get_pagination_descriptor( $attributes );

		return new TermQueryPaginationAdapter(
			$terms_query_args,
			$pagination_data['value']
		);
	}

	public function get_terms_for( $attributes ) {
		$attributes       = self::get_attributes( $attributes );
		$terms_query_args = $this->get_term_query_args( $attributes );

		if ( null === $terms_query_args ) {
			return array();
		}

		add_filter(
			'get_terms_orderby',
			array( $this, 'allow_random_order_by_in_term_query' ),
			10,
			2
		);

		$terms = get_terms( $terms_query_args );

		remove_filter(
			'get_terms_orderby',
			array( $this, 'allow_random_order_by_in_term_query' ),
			10,
			2
		);

		if (
			is_wp_error( $terms )
			||
			empty( $terms )
		) {
			return array();
		}

		$terms_descriptors = array();

		foreach ( $terms as $term ) {
			$attachment = array();

			$id = get_term_meta( $term->term_id, 'thumbnail_id' );

			if ( isset( $id[0] ) ) {
				$attachment = array(
					'attachment_id' => $id[0],
					'url'           => wp_get_attachment_image_url( $id[0], 'full' ),
				);
			}

			$term_atts = blocksy_companion_theme_functions()->blocksy_get_taxonomy_options( $term->term_id );

			$maybe_image_id = isset( $term->term_id ) ? get_term_meta( $term->term_id, 'thumbnail_id', true ) : '';

			if ( ! empty( $maybe_image_id ) ) {
				$term_atts['icon_image'] = array(
					'attachment_id' => $maybe_image_id,
					'url'           => wp_get_attachment_image_url( $maybe_image_id, 'full' ),
				);
			}

			$maybe_icon  = blocksy_companion_akg( 'icon_image', $term_atts, '' );
			$maybe_image = blocksy_companion_akg( 'image', $term_atts, $attachment );

			$terms_descriptors[] = array(
				'term_id' => $term->term_id,
				'icon'    => $maybe_icon,
				'image'   => $maybe_image,
			);
		}

		return $terms_descriptors;
	}

	public function allow_random_order_by_in_term_query( $orderby, $query ) {
		if ( 'rand' === $query['orderby'] ) {
			return 'RAND()';
		}

		return $orderby;
	}

	private function get_grid_styles( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'css'           => null,
				'tablet_css'    => null,
				'mobile_css'    => null,

				'root_selector' => '',

				// default | grid
				'layout_type'   => 'default',

				'columns'       => array(
					'desktop' => 1,
					'tablet'  => 1,
					'mobile'  => 1,
				),

				'is_slider'     => false,
			)
		);

		$css        = $args['css'];
		$tablet_css = $args['tablet_css'];
		$mobile_css = $args['mobile_css'];

		if ( 'grid' === $args['layout_type'] ) {
			$grid_columns_width = array(
				'desktop' => $args['columns']['desktop'],
				'tablet'  => $args['columns']['tablet'],
				'mobile'  => $args['columns']['mobile'],
			);

			if ( $args['is_slider'] ) {
				$grid_columns_width['desktop'] = round(
					100 / $grid_columns_width['desktop'],
					2
				) . '%';

				$grid_columns_width['tablet'] = round(
					100 / $grid_columns_width['tablet'],
					2
				) . '%';

				$grid_columns_width['mobile'] = round(
					100 / $grid_columns_width['mobile'],
					2
				) . '%';
			}

			blocksy_companion_theme_functions()->blocksy_output_responsive(
				array(
					'css'          => $css,
					'tablet_css'   => $tablet_css,
					'mobile_css'   => $mobile_css,
					'selector'     => blocksy_companion_theme_functions()->blocksy_assemble_selector( $args['root_selector'] ),
					'variableName' => 'grid-columns-width',
					'value'        => $grid_columns_width,
					'unit'         => '',
				)
			);
		}

		if ( $args['is_slider'] ) {
			$selectors = array(
				'desktop' => '',
				'tablet'  => '',
				'mobile'  => '',
			);

			foreach ( $selectors as $device => $selector ) {
				$selectors[ $device ] = blocksy_companion_theme_functions()->blocksy_assemble_selector(
					blocksy_companion_theme_functions()->blocksy_mutate_selector(
						array(
							'selector'  => blocksy_companion_theme_functions()->blocksy_mutate_selector(
								array(
									'selector'  => $args['root_selector'],
									'operation' => 'suffix',
									'to_add'    => '[data-flexy*="no"]',
								)
							),
							'operation' => 'suffix',
							'to_add'    => '.flexy-item:nth-child(n + ' . ( intval( $args['columns'][ $device ] ) + 1 ) . ')',
						)
					)
				);
			}

			blocksy_companion_theme_functions()->blocksy_output_responsive(
				array(
					'css'          => $css,
					'tablet_css'   => $tablet_css,
					'mobile_css'   => $mobile_css,
					'selector'     => $selectors,
					'variableName' => 'height',
					'variableType' => 'property',
					'value'        => '1',
				)
			);
		}
	}
}
