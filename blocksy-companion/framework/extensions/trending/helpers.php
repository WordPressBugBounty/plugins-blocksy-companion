<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'wp_ajax_blocksy_get_trending_posts', 'blocksy_companion_get_trending_posts' );
add_action( 'wp_ajax_nopriv_blocksy_get_trending_posts', 'blocksy_companion_get_trending_posts' );

if ( ! function_exists( 'blocksy_companion_get_trending_posts' ) ) {
	function blocksy_companion_get_trending_posts() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_REQUEST['page'] ) ) {
			wp_send_json_error();
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$page = intval( sanitize_text_field( wp_unslash( $_REQUEST['page'] ) ) );

		if ( ! $page ) {
			wp_send_json_error();
		}

		wp_send_json_success(
			array(
				'posts' => blocksy_companion_get_trending_posts_value(
					array(
						'paged' => $page,
					)
				),
			)
		);
	}
}

if ( ! function_exists( 'blocksy_companion_get_trending_posts_value' ) ) {
	function blocksy_companion_get_trending_posts_value( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'paged' => 1,
			)
		);

		$date_query = array();

		$date_filter = blocksy_companion_theme_functions()->blocksy_get_theme_mod( 'trending_block_filter', 'all_time' );

		if ( $date_filter && 'all_time' !== $date_filter ) {
			$days = array(
				'last_24_hours' => 1,
				'last_7_days'   => 7,
				'last_month'    => 30,
			)[ $date_filter ];

			if ( ! $days ) {
				$days = 30;
			}

			$time = time() - ( intval( $days ) * 24 * 60 * 60 );

			$date_query = array(
				'after'     => array(
					'year'  => gmdate( 'Y', $time ),
					'month' => gmdate( 'n', $time ),
					'days'  => gmdate( 'j', $time ),
				),
				'before'    => array(
					'year'  => gmdate( 'Y' ),
					'month' => gmdate( 'n' ),
					'days'  => gmdate( 'j' ),
				),
				'inclusive' => true,
			);
		}

		$post_type = blocksy_companion_theme_functions()->blocksy_get_theme_mod( 'trending_block_post_type', 'post' );

		if (
			(
				'product' === $post_type
				&&
				! class_exists( 'WooCommerce' )
			)
			||
			! post_type_exists( $post_type )
		) {
			$post_type = 'post';
		}

		$source = blocksy_companion_theme_functions()->blocksy_get_theme_mod( 'trending_block_post_source', 'categories' );

		$query_args = array(
			'post_type'           => $post_type,
			'order'               => 'DESC',
			'posts_per_page'      => 4,
			'orderby'             => 'comment_count',
			'paged'               => $args['paged'],
			'ignore_sticky_posts' => true,
			'post_status'         => 'publish',
		);

		if ( 'product' === $post_type ) {
			if ( get_option( 'woocommerce_hide_out_of_stock_items' ) === 'yes' ) {
				// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
				$query_args['meta_query'] = array(
					array(
						'key'   => '_stock_status',
						'value' => 'instock',
					),
					array(
						'key'   => '_backorders',
						'value' => 'no',
					),
				);
			}

			$trending_product_type = blocksy_companion_theme_functions()->blocksy_get_theme_mod( 'trending_block_product_type', 'defualt' );

			if ( 'sale' === $trending_product_type ) {
				$query_args['post__in'] = blocksy_companion_get_product_ids_on_sale();
				$date_query             = array();
			}

			if ( 'best' === $trending_product_type ) {
				if ( $date_filter && 'all_time' !== $date_filter ) {
					global $wpdb;

					$values = array();

					if ( isset( $date_query['after'] ) ) {
						$date_after = gmdate(
							'Y-m-d',
							strtotime(
								$date_query['after']['year'] . '-' .
								$date_query['after']['month'] . '-' .
								$date_query['after']['days']
							)
						);

						$date_after = gmdate( 'Y-m-d H:i:s', strtotime( $date_after ) );
						$values[]   = $date_after;
					}

					$sql = "
						SELECT
							product_id,
						SUM(qty) AS total_qty
						FROM (
							SELECT
								o.ID AS order_id,
								MAX(CASE WHEN lmeta.meta_key = '_product_id' THEN lmeta.meta_value END) AS product_id,
								MAX(CASE WHEN lmeta.meta_key = '_qty' THEN lmeta.meta_value END) AS qty
							FROM {$wpdb->prefix}wc_orders o
							JOIN {$wpdb->prefix}woocommerce_order_items l ON o.ID = l.order_id
							JOIN {$wpdb->prefix}woocommerce_order_itemmeta lmeta ON l.order_item_id = lmeta.order_item_id
							WHERE
								o.date_created_gmt >= %s
							GROUP BY l.order_item_id
						) AS product_data
						WHERE product_id IS NOT NULL AND qty IS NOT NULL
						GROUP BY product_id
						ORDER BY total_qty DESC
					";

					// phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared
					$sql_prepared = $wpdb->prepare( $sql, ...$values );
					// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
					$results = $wpdb->get_results( $sql_prepared, ARRAY_A );

					$identifiers = array();
					foreach ( $results as $row ) {
						$product_id                 = (int) $row['product_id'];
						$total_qty                  = (int) $row['total_qty'];
						$identifiers[ $product_id ] = $total_qty;
					}

					arsort( $identifiers );

					$query_args['post__in'] = array_keys( $identifiers );
					$query_args['orderby']  = 'post__in';
					$date_query             = array();
				} else {
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
					$query_args['meta_key'] = 'total_sales';
					$query_args['orderby']  = 'meta_value_num';
					$query_args['order']    = 'DESC';
				}
			}

			if ( 'rating' === $trending_product_type ) {
				$reviews_args = array(
					'status'      => 'approve',
					'post_status' => 'publish',
					'post_type'   => 'product',
					'date_query'  => $date_query,
				);

				$reviews     = get_comments( $reviews_args );
				$identifiers = array();

				foreach ( $reviews as $review ) {

					if ( ! isset( $identifiers[ $review->comment_post_ID ] ) ) {
						$identifiers[ $review->comment_post_ID ] = array(
							'count'  => 0,
							'rating' => 0,
						);
					}

					$rating = get_comment_meta( $review->comment_ID, 'rating', true );
					++$identifiers[ $review->comment_post_ID ]['count'];
					$identifiers[ $review->comment_post_ID ]['rating'] += intval( $rating );
				}

				$identifiers = array_filter(
					$identifiers,
					function ( $item ) {
						return $item['count'] > 0;
					}
				);
				$identifiers = array_map(
					function ( $item ) {
						return $item['rating'] / $item['count'];
					},
					$identifiers
				);
				arsort( $identifiers );

				$query_args['post__in'] = array_keys( $identifiers );
				$date_query             = array();
			}
		}

		if ( 'categories' === $source ) {
			$query_args['date_query'] = $date_query;
			$cat_option_id            = 'trending_block_category';

			if ( 'post' !== $post_type ) {
				$cat_option_id = 'trending_block_' . $post_type . '_taxonomy';
			}

			$cat_id = blocksy_companion_theme_functions()->blocksy_get_theme_mod( $cat_option_id, 'all_categories' );
			$cat_id = ( empty( $cat_id ) || 'all_categories' === $cat_id ) ? '' : $cat_id;

			if ( ! empty( $cat_id ) ) {
				$terms = get_terms( array( 'include' => $cat_id ) );

				if ( ! empty( $terms ) ) {
					// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
					$query_args['tax_query'] = array(
						array(
							'taxonomy' => $terms[0]->taxonomy,
							'field'    => 'term_id',
							'terms'    => array( $cat_id ),
						),
					);
				}
			}
		}

		if ( 'custom' === $source ) {
			$post_id = blocksy_companion_theme_functions()->blocksy_get_theme_mod( 'trending_block_post_id', '' );

			$query_args['orderby']  = 'post__in';
			$query_args['post__in'] = array( '__INEXISTING__' );

			if ( ! empty( trim( $post_id ) ) ) {
				$query_args['post__in'] = explode(
					',',
					str_replace(
						' ',
						'',
						trim(
							$post_id
						)
					)
				);
			}
		}

		/**
		 * Filters the query arguments used to fetch the trending posts.
		 *
		 * @since 1.8.0
		 *
		 * @param array $query_args Arguments passed to `WP_Query`.
		 */
		$filtered_query_args = apply_filters(
			'blocksy:trending-posts:query-args',
			$query_args
		);

		$query = new WP_Query( $filtered_query_args );

		if (
			! $query->have_posts()
			||
			(
				isset( $query_args['post__in'] )
				&&
				empty( $query_args['post__in'] )
			)
		) {
			return array(
				'posts'        => array(),
				'is_last_page' => false,
			);
		}

		$result = array();

		while ( $query->have_posts() ) {
			$query->the_post();

			$individual_entry = array(
				'id'            => get_the_ID(),
				'attachment_id' => get_post_thumbnail_id(),
				'title'         => get_the_title(),
				'url'           => get_permalink(),
				'image'         => '',
				'taxonomy'      => '',
				'price'         => '',
			);

			if ( get_post_thumbnail_id() ) {
				$individual_entry['image'] = blocksy_companion_theme_functions()->blocksy_media(
					array(
						'attachment_id' => get_post_thumbnail_id(),
						'size'          => blocksy_companion_theme_functions()->blocksy_get_theme_mod(
							'trending_block_thumbnails_size',
							'thumbnail'
						),
						'ratio'         => '1/1',
						'tag_name'      => 'a',
						'html_atts'     => array(
							'href'       => esc_url( get_permalink() ),
							'aria-label' => wp_strip_all_tags( get_the_title() ),
						),
					)
				);
			}

			$show_taxonomy  = blocksy_companion_theme_functions()->blocksy_get_theme_mod( 'trending_block_show_taxonomy', 'no' ) === 'yes';
			$taxonomy_style = blocksy_companion_theme_functions()->blocksy_get_theme_mod( 'trending_block_taxonomy_style', 'simple' );
			$taxonomy_opt   = blocksy_companion_theme_functions()->blocksy_get_taxonomies_for_cpt(
				$post_type,
				array( 'return_empty' => true )
			);

			$taxonomies_to_render = array();

			if (
				$show_taxonomy
				&&
				! empty( $taxonomy_opt )
			) {
				$taxonomy_option_id = 'trending_block_show_' . $post_type . '_taxonomy';

				$taxonomy_to_show = blocksy_companion_theme_functions()->blocksy_get_theme_mod(
					$taxonomy_option_id,
					'product' === $post_type ? 'product_cat' : array_keys( $taxonomy_opt )[0]
				);

				$taxonomy_values = get_the_terms( get_the_ID(), $taxonomy_to_show );

				if (
					$taxonomy_values
					&&
					is_array( $taxonomy_values )
				) {
					foreach ( $taxonomy_values as $tax ) {
						$taxonomies_to_render[] = blocksy_companion_html_tag(
							'a',
							array(
								'href'  => get_term_link( $tax ),
								'class' => 'ct-post-taxonomy',
							),
							$tax->name
						);
					}

					if ( ! empty( $taxonomies_to_render ) ) {

						$divider = '';

						if ( 'simple' === $taxonomy_style ) {
							$divider = ', ';
						}

						if ( 'underline' === $taxonomy_style ) {
							$divider = ' / ';
						}

						$individual_entry['taxonomy'] = blocksy_companion_html_tag(
							'ul',
							array(
								'class' => 'entry-meta',
							),
							blocksy_companion_html_tag(
								'li',
								array(
									'class'     => 'meta-categories',
									'data-type' => $taxonomy_style,
								),
								implode( $divider, $taxonomies_to_render )
							)
						);
					}
				}
			}

			$trending_block_show_price = blocksy_companion_theme_functions()->blocksy_get_theme_mod( 'trending_block_show_price', 'no' ) === 'yes';

			if (
				$trending_block_show_price
				&&
				'product' === $post_type
			) {
				$product = wc_get_product( get_the_ID() );

				$individual_entry['price'] = blocksy_companion_html_tag(
					'span',
					array(
						'class' => 'price',
					),
					$product->get_price_html()
				);
			}

			$result[] = $individual_entry;
		}

		$is_last = intval( $query->max_num_pages ) === intval( $args['paged'] );

		wp_reset_postdata();

		return array(
			'posts'        => $result,
			'is_last_page' => $is_last,
		);
	}
}

if ( ! function_exists( 'blocksy_companion_get_trending_block' ) ) {
	function blocksy_companion_get_trending_block( $result = null ) {
		if ( ! $result ) {
			$result = blocksy_companion_get_trending_posts_value();
		}

		if ( empty( $result['posts'] ) ) {
			return '';
		}

		ob_start();

		$container_attr = array(
			'class'     => 'ct-container',
			'data-page' => '1',
		);

		if ( $result['is_last_page'] ) {
			unset( $container_attr['data-page'] );
		}

		$class = 'ct-trending-block';

		$class .= ' ' . blocksy_companion_theme_functions()->blocksy_visibility_classes(
			blocksy_companion_theme_functions()->blocksy_get_theme_mod(
				'trending_block_visibility',
				array(
					'desktop' => true,
					'tablet'  => true,
					'mobile'  => false,
				)
			)
		);

		$attr = array(
			'class' => $class,
		);

		if ( is_customize_preview() ) {
			$attr['data-shortcut']          = 'border';
			$attr['data-shortcut-location'] = 'trending_posts_ext';
		}

		$label_tag = blocksy_companion_theme_functions()->blocksy_validate_html_tag(
			array(
				'tag'     => blocksy_companion_theme_functions()->blocksy_get_theme_mod( 'trending_block_label_tag' ),
				'allowed' => 'headings_text',
				'default' => 'h3',
			)
		);

		$trending_label = blocksy_companion_sanitize_html_for_display(
			blocksy_companion_theme_functions()->blocksy_get_theme_mod(
				'trending_block_label',
				__( 'Trending now', 'blocksy-companion' )
			)
		);

		$icon = '<svg width="13" height="13" viewBox="0 0 13 13" fill="currentColor"><path d="M13 5.8V9c0 .4-.2.6-.5.6s-.5-.2-.5-.5V7.2l-4.3 4.2c-.2.2-.6.2-.8 0L4.6 9.1.9 12.8c-.1.1-.2.2-.4.2s-.3-.1-.4-.2c-.2-.2-.2-.6 0-.8l4.1-4.1c.2-.2.6-.2.8 0l2.3 2.3 3.8-3.8H9.2c-.3 0-.5-.2-.5-.5s.2-.5.5-.5h3.4c.2 0 .3.1.4.2v.2z"/></svg>';

		if ( function_exists( 'blocksy_companion_get_icon' ) ) {
			$icon_source = blocksy_companion_theme_functions()->blocksy_get_theme_mod( 'trending_block_icon_source', 'default' );

			if ( 'custom' === $icon_source ) {
				$icon = blocksy_companion_get_icon(
					array(
						'icon_descriptor' => blocksy_companion_theme_functions()->blocksy_get_theme_mod(
							'trending_block_custom_icon',
							array(
								'icon' => 'fas fa-fire',
							)
						),
						'icon_container'  => false,
						'icon_html_atts'  => array(
							'width'  => '13',
							'height' => '13',
							'fill'   => 'currentColor',
						),
					)
				);
			}
		}

		?>

	<section <?php blocksy_companion_attr_to_html_e( $attr ); ?>>
		<div <?php blocksy_companion_attr_to_html_e( $container_attr ); ?>>

			<<?php echo esc_html( $label_tag ); ?> class="ct-module-title">
				<?php
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $trending_label;

					/**
					 * Note to code reviewers: This line doesn't need to be escaped.
					 * The value used here escapes the value properly.
					 * It contains an inline SVG, which is safe.
					 */
					// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo $icon;
				?>

				<?php if ( ! $result['is_last_page'] ) { ?>
					<span class="ct-slider-arrows">
						<span class="ct-arrow-prev">
							<svg width="8" height="8" fill="currentColor" viewBox="0 0 8 8">
								<path d="M5.05555,8L1.05555,4,5.05555,0l.58667,1.12-2.88,2.88,2.88,2.88-.58667,1.12Z"/>
							</svg>
						</span>

						<span class="ct-arrow-next">
							<svg width="8" height="8" fill="currentColor" viewBox="0 0 8 8">
								<path d="M2.35778,6.88l2.88-2.88L2.35778,1.12,2.94445,0l4,4-4,4-.58667-1.12Z"/>
							</svg>
						</span>
					</span>
				<?php } ?>
			</<?php echo esc_html( $label_tag ); ?>>

			<?php
			foreach ( $result['posts'] as $post ) {
				blocksy_companion_html_tag_e(
					'div',
					array(
						'class' => 'ct-trending-block-item',
					),
					$post['image'] .
					blocksy_companion_html_tag(
						'div',
						array(
							'class' => 'ct-trending-block-item-content',
						),
						$post['taxonomy'] .
						blocksy_companion_html_tag(
							'a',
							array(
								'href'  => $post['url'],
								'class' => 'ct-post-title',
							),
							$post['title']
						) .
						$post['price']
					)
				);
			}
			?>

		</div>
	</section>

		<?php

		return ob_get_clean();
	}
}
