<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- view scope, variables come from blocksy_companion_render_view()

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$value_fallback = blocksy_companion_akg( 'fallback', $attributes, '' );

$value = '';

$has_fallback = false;

$has_field_link              = blocksy_companion_akg( 'has_field_link', $attributes, 'no' ) === 'yes';
$has_field_link_wrap_content = blocksy_companion_akg( 'has_field_link_wrap_content', $attributes, 'no' );

$has_archive_prefix = blocksy_companion_akg( 'has_archive_prefix', $attributes, 'no' ) === 'yes';

if ( 'wp:archive_title' === $field ) {
	$value = '';

	$special_post_id = blocksy_companion_theme_functions()->blocksy_get_special_post_id();

	if ( $special_post_id ) {
		$value = get_the_title( $special_post_id );
	} elseif ( is_archive() ) {
		$archive_title_renderer = new \Blocksy\ArchiveTitleRenderer(
			array(
				'has_label' => $has_archive_prefix,
			)
		);

		$value = get_the_archive_title();

		if ( method_exists( $archive_title_renderer, 'get_the_archive_title' ) ) {
			$value = $archive_title_renderer->get_the_archive_title();
		}
	}

	if ( is_search() ) {
		$value = blocksy_companion_safe_sprintf(
			// translators: 1: the search query
			__(
				'Search Results for %1$s',
				'blocksy-companion'
			),
			get_search_query()
		);
	}
}

if ( 'wp:archive_description' === $field ) {
	$value = get_the_archive_description();

	$special_post_id = blocksy_companion_theme_functions()->blocksy_get_special_post_id();

	if ( $special_post_id ) {
		$value = blocksy_entry_excerpt(
			array(
				'length'  => 'original',
				'post_id' => $special_post_id,
			)
		);
	}

	if ( is_search() ) {
		$value = '';
	}
}

$link_attr = array();

if ( 'wp:title' === $field ) {
	$value = get_the_title();

	if ( $has_field_link ) {
		$link_attr = array(
			'href' => get_permalink(),
		);

		if ( blocksy_companion_akg( 'has_field_link_new_tab', $attributes, 'no' ) === 'yes' ) {
			$link_attr['target'] = '_blank';
		}

		if ( ! empty( blocksy_companion_akg( 'has_field_link_rel', $attributes, '' ) ) ) {
			$link_attr['rel'] = blocksy_companion_akg(
				'has_field_link_rel',
				$attributes,
				''
			);
		}

		if ( 'no' === $has_field_link_wrap_content ) {
			$value = blocksy_companion_html_tag( 'a', $link_attr, $value );
		}
	}
}

if ( 'wp:term_title' === $field ) {
	global $blocksy_term_obj;

	if ( ! empty( $blocksy_term_obj ) ) {
		$value = $blocksy_term_obj->name;

		if ( $has_field_link ) {
			$link_attr = array(
				'href' => get_term_link( $blocksy_term_obj ),
			);

			if ( blocksy_companion_akg( 'has_field_link_new_tab', $attributes, 'no' ) === 'yes' ) {
				$link_attr['target'] = '_blank';
			}

			if ( ! empty( blocksy_companion_akg( 'has_field_link_rel', $attributes, '' ) ) ) {
				$link_attr['rel'] = blocksy_companion_akg(
					'has_field_link_rel',
					$attributes,
					''
				);
			}

			if ( 'no' === $has_field_link_wrap_content ) {
				$value = blocksy_companion_html_tag( 'a', $link_attr, $value );
			}
		}
	}
}

if ( 'wp:term_count' === $field ) {
	global $blocksy_term_obj; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.VariableRedeclaration -- each field branch binds the global it reads

	if ( ! empty( $blocksy_term_obj ) ) {
		$value = "{$blocksy_term_obj->count}";

		if ( $has_field_link ) {
			$link_attr = array(
				'href' => get_term_link( $blocksy_term_obj ),
			);

			if ( blocksy_companion_akg( 'has_field_link_new_tab', $attributes, 'no' ) === 'yes' ) {
				$link_attr['target'] = '_blank';
			}

			if ( ! empty( blocksy_companion_akg( 'has_field_link_rel', $attributes, '' ) ) ) {
				$link_attr['rel'] = blocksy_companion_akg(
					'has_field_link_rel',
					$attributes,
					''
				);
			}

			if ( 'no' === $has_field_link_wrap_content ) {
				$value = blocksy_companion_html_tag( 'a', $link_attr, $value );
			}
		}
	}
}

if ( 'wp:term_description' === $field ) {
	global $blocksy_term_obj; // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.VariableRedeclaration -- each field branch binds the global it reads

	if ( ! empty( $blocksy_term_obj ) ) {
		$value = $blocksy_term_obj->description;
	}
}

if ( 'wp:excerpt' === $field ) {
	$excerpt_args = array(
		'length'         => intval( blocksy_companion_akg( 'excerpt_length', $attributes, 40 ) ),
		'skip_container' => true,
	);

	if ( blocksy_companion_akg( 'tagName', $attributes, 'div' ) === 'p' ) {
		remove_filter( 'the_excerpt', 'wpautop' );
	}

	$value = blocksy_entry_excerpt( $excerpt_args );

	if ( empty( $value ) && ! empty( $value_fallback ) ) {
		$has_fallback = true;
		$value        = do_shortcode( blocksy_companion_sanitize_html_for_display( $value_fallback ) );
	}
	if ( blocksy_companion_akg( 'tagName', $attributes, 'div' ) === 'p' ) {
		add_filter( 'the_excerpt', 'wpautop' );
	}
}

if ( 'wp:date' === $field ) {
	$date_format = get_option( 'date_format', 'F j, Y' );

	if ( blocksy_companion_akg( 'default_format', $attributes, 'published' ) === 'no' ) {
		$date_format = blocksy_companion_akg( 'date_format', $attributes, 'F j, Y' );

		if ( 'custom' === $date_format ) {
			$date_format = blocksy_companion_akg( 'custom_date_format', $attributes, 'F j, Y' );
		}
	}

	$value = blocksy_companion_html_tag(
		'time',
		array(
			'datetime' => get_the_date( 'c' ),
			'itemprop' => 'datePublished',
		),
		blocksy_companion_sanitize_html_for_display( get_the_date( $date_format ) )
	);

	if ( blocksy_companion_akg( 'date_type', $attributes, 'published' ) === 'modified' ) {
		$value = blocksy_companion_html_tag(
			'time',
			array(
				'datetime' => get_the_modified_date( 'c' ),
				'itemprop' => 'dateModified',
			),
			blocksy_companion_sanitize_html_for_display( get_the_modified_date( $date_format ) )
		);
	}

	if ( $has_field_link ) {
		$link_attr = array(
			'href' => get_permalink(),
		);

		if ( blocksy_companion_akg( 'has_field_link_new_tab', $attributes, 'no' ) === 'yes' ) {
			$link_attr['target'] = '_blank';
		}

		if ( ! empty( blocksy_companion_akg( 'has_field_link_rel', $attributes, '' ) ) ) {
			$link_attr['rel'] = blocksy_companion_akg(
				'has_field_link_rel',
				$attributes,
				''
			);
		}

		if ( 'no' === $has_field_link_wrap_content ) {
			$value = blocksy_companion_html_tag( 'a', $link_attr, $value );
		}
	}
}

if ( 'wp:comments' === $field ) {
	$value = blocksy_companion_sanitize_html_for_display(
		get_comments_number_text(
			blocksy_companion_akg( 'zero_text', $attributes, __( 'No comments', 'blocksy-companion' ) ),
			blocksy_companion_akg( 'single_text', $attributes, __( 'One comment', 'blocksy-companion' ) ),
			blocksy_companion_akg( 'multiple_text', $attributes, __( '% comments', 'blocksy-companion' ) )
		)
	);

	if ( $has_field_link ) {
		$value = blocksy_companion_html_tag(
			'a',
			array_merge(
				array(
					'href' => get_comments_link(),
				),
				blocksy_companion_akg( 'has_field_link_new_tab', $attributes, 'no' ) === 'yes' ?
				array(
					'target' => '_blank',
				) : array(),
				! empty( blocksy_companion_akg( 'has_field_link_rel', $attributes, '' ) ) ? array(
					'rel' => blocksy_companion_akg( 'has_field_link_rel', $attributes, '' ),
				) : array()
			),
			$value
		);
	}
}

if ( 'wp:author' === $field ) {
	$author_id    = blocksy_get_author_id();
	$author_field = blocksy_companion_akg( 'author_field', $attributes, 'display_name' );

	$overide_link = '';

	if ( 'email' === $author_field ) {
		$value = blocksy_get_the_author_meta( 'user_email', $author_id );

		if ( ! empty( $value ) ) {
			$overide_link = 'mailto:' . $value;
		}
	}

	if ( 'user_url' === $author_field ) {
		$value = blocksy_get_the_author_meta( 'user_url', $author_id );

		if ( ! empty( $value ) ) {
			$overide_link = $value;
		}
	}

	if ( 'nicename' === $author_field ) {
		$value = blocksy_get_the_author_meta( 'nickname', $author_id );
	}

	if ( 'display_name' === $author_field ) {
		$value = blocksy_get_the_author_meta( 'display_name', $author_id );
	}

	if ( 'first_name' === $author_field ) {
		$value = blocksy_get_the_author_meta( 'first_name', $author_id );
	}

	if ( 'last_name' === $author_field ) {
		$value = blocksy_get_the_author_meta( 'last_name', $author_id );
	}

	if ( 'description' === $author_field ) {
		$value = blocksy_get_the_author_meta( 'description', $author_id );
	}

	if ( empty( $value ) && ! empty( $value_fallback ) ) {
		$has_fallback = true;
		$value        = do_shortcode( blocksy_companion_sanitize_html_for_display( $value_fallback ) );
	}

	if (
		! empty( $value )
		&&
		$has_field_link
	) {
		$value = blocksy_companion_html_tag(
			'a',
			array_merge(
				array(
					'href' => ! empty( $overide_link ) ? $overide_link : get_author_posts_url( $author_id ),
				),
				blocksy_companion_akg( 'has_field_link_new_tab', $attributes, 'no' ) === 'yes' ? array(
					'target' => '_blank',
				) : array(),
				! empty( blocksy_companion_akg( 'has_field_link_rel', $attributes, '' ) ) ? array(
					'rel' => blocksy_companion_akg( 'has_field_link_rel', $attributes, '' ),
				) : array()
			),
			$value
		);
	}
}

if ( 'wp:terms' === $field ) {
	$taxonomy = blocksy_companion_akg( 'taxonomy', $attributes, '' );

	if ( empty( $taxonomy ) ) {
		$internal_taxonomies = get_object_taxonomies(
			array(
				'post_type'         => get_post_type(),
				'public'            => true,
				'show_in_nav_menus' => true,
			)
		);

		$taxonomies = array();

		foreach ( $internal_taxonomies as $tax ) {
			$taxonomy_object = get_taxonomy( $tax );

			if ( ! $taxonomy_object->public ) {
				continue;
			}

			$taxonomies[] = $tax;
		}

		if ( ! empty( $taxonomies ) ) {
			$taxonomy = $taxonomies[0];
		}
	}

	$value = '';

	if ( ! empty( $taxonomy ) ) {
		$terms = get_the_terms( get_the_ID(), $taxonomy );

		if (
			! empty( $terms )
			&&
			! is_wp_error( $terms )
		) {
			$terms = array_map(
				function ( $term ) use ( $taxonomy, $attributes, $has_field_link ) {
					$tag_name = 'span';

					$attrs = array();

					$classes = array();

					$term_accent_color = blocksy_companion_akg( 'termAccentColor', $attributes, 'yes' );

					if ( 'yes' === $term_accent_color ) {
						$classes[] = 'ct-term-' . $term->term_id;
					}

					$term_class = blocksy_companion_akg( 'termClass', $attributes, '' );

					if ( ! empty( $term_class ) ) {
						$classes[] = $term_class;
					}

					if ( ! empty( $classes ) ) {
						$attrs['class'] = implode( ' ', $classes );
					}

					if ( $has_field_link ) {
						$tag_name = 'a';

						$attrs['href'] = get_term_link( $term, $taxonomy );

						if ( blocksy_companion_akg( 'has_field_link_new_tab', $attributes, 'no' ) === 'yes' ) {
							$attrs['target'] = '_blank';
						}

						if ( ! empty( blocksy_companion_akg( 'has_field_link_rel', $attributes, '' ) ) ) {
							$attrs['rel'] = blocksy_companion_akg( 'has_field_link_rel', $attributes, '' );
						}
					}

					return '<' . $tag_name . ' ' . trim( blocksy_companion_attr_to_html( $attrs ) ) . '>' . $term->name . '</' . $tag_name . '>';
				},
				$terms
			);

			$value = implode(
				preg_replace(
					'/ /',
					"\u{00A0}",
					blocksy_companion_sanitize_html_for_display(
						blocksy_companion_akg( 'separator', $attributes, ', ' )
					)
				),
				$terms
			);
		}
	}

	if ( empty( $value ) && ! empty( $value_fallback ) ) {
		$has_fallback = true;
		$value        = do_shortcode( blocksy_companion_sanitize_html_for_display( $value_fallback ) );
	}
}

if (
	empty( trim( $value ) )
	&&
	trim( $value ) !== '0'
) {
	return;
}

$value_before = blocksy_companion_akg( 'before', $attributes, '' );
$value_after  = blocksy_companion_akg( 'after', $attributes, '' );

if (
	! $has_fallback
	&&
	( ! empty( $value_before ) || ! empty( $value_after ) )
) {
	$value = blocksy_companion_sanitize_html_for_display(
		$value_before . $value . $value_after
	);
}

$tag_name = blocksy_companion_akg( 'tagName', $attributes, 'div' );

$classes = array(
	'ct-dynamic-data',
);

if ( ! empty( $attributes['align'] ) ) {
	$classes[] = 'has-text-align-' . $attributes['align'];
}

$wrapper_attr['class'] = implode( ' ', $classes );
$wrapper_attr['style'] = '';

$border_result = get_block_core_post_featured_image_border_attributes(
	$attributes
);

if ( ! empty( $border_result['class'] ) ) {
	$wrapper_attr['class'] .= ' ' . $border_result['class'];
}

if ( ! empty( $border_result['style'] ) ) {
	$wrapper_attr['style'] .= $border_result['style'];
}

$block_type                    = WP_Block_Type_Registry::get_instance()->get_registered( 'blocksy/dynamic-data' );
$block_type->supports['color'] = true;
wp_apply_colors_support( $block_type, $attributes );

if (
	'yes' === $has_field_link_wrap_content
	&&
	$has_field_link
) {
	$value = blocksy_companion_html_tag( 'a', $link_attr, $value );
}

$wrapper_attr = get_block_wrapper_attributes( $wrapper_attr );

blocksy_companion_html_tag_e( $tag_name, $wrapper_attr, $value );
