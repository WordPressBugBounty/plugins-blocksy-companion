<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- view scope, variables come from blocksy_companion_render_view()

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$view_type = blocksy_companion_akg( 'viewType', $attributes, 'default' );

if ( 'cover' === $view_type ) {
	if (
		! $attachment_id
		&&
		empty( $content )
	) {
		return;
	}

	blocksy_companion_render_view_e(
		__DIR__ . '/cover-field.php',
		array(
			'attributes'    => $attributes,
			'field'         => $field,
			'content'       => $content,
			'attachment_id' => $attachment_id,
		)
	);

	return;
}

if ( ! $attachment_id ) {
	return;
}

$aspect_ratio = blocksy_companion_sanitize_aspect_ratio(
	blocksy_companion_akg( 'aspectRatio', $attributes, 'auto' )
);

$image_fit = blocksy_companion_akg( 'imageFit', $attributes, 'cover' );

if ( ! in_array( $image_fit, array( 'cover', 'contain', 'fill', 'none', 'scale-down' ), true ) ) {
	$image_fit = 'cover';
}

$width = blocksy_companion_sanitize_css_length(
	blocksy_companion_akg( 'width', $attributes, '' )
);

$height = blocksy_companion_sanitize_css_length(
	blocksy_companion_akg( 'height', $attributes, '' )
);

$lightbox           = blocksy_companion_akg( 'lightbox', $attributes, '' );
$video_thumbnail    = blocksy_companion_akg( 'videoThumbnail', $attributes, '' );
$has_image_caption  = blocksy_companion_akg( 'has_image_caption', $attributes, 'no' );
$image_hover_effect = blocksy_companion_akg( 'image_hover_effect', $attributes, '' );

$size_slug = blocksy_companion_akg( 'sizeSlug', $attributes, 'full' );
$alt_text  = blocksy_companion_akg( 'alt_text', $attributes, '' );

if ( empty( $alt ) ) {
	$alt_text = get_post_meta( $attachment_id, '_wp_attachment_image_alt', true );
}

$has_field_link         = blocksy_companion_akg( 'has_field_link', $attributes, 'no' );
$has_field_link_new_tab = blocksy_companion_akg( 'has_field_link_new_tab', $attributes, 'no' );
$has_field_link_rel     = blocksy_companion_akg( 'has_field_link_rel', $attributes, '' );

if ( empty( $url ) ) {
	$has_field_link = 'no';
}

$img_attr = array(
	'style' => '',
);

$aria_label = get_the_title();

if (
	'wp:term_image' === $attributes['field']
	||
	'wp:archive_image' === $attributes['field']
) {
	global $blocksy_term_obj;

	if ( ! empty( $blocksy_term_obj ) ) {
		$aria_label = $blocksy_term_obj->name;
	}

	$maybe_term_obj = get_queried_object();

	if (
		! empty( $maybe_term_obj )
		&&
		! empty( $maybe_term_obj->name )
	) {
		$aria_label = $maybe_term_obj->name;
	}
}

$wrapper_attr = array(
	'class' => 'ct-dynamic-media',
);

$link_attr = array();

$classes = array();
$styles  = array();

$maybe_video = null;

$border_result = get_block_core_post_featured_image_border_attributes(
	$attributes
);

// Aspect aspectRatio with a height set needs to override the default width/height.
if ( ! empty( $aspect_ratio ) ) {
	$img_attr['style'] .= 'width:100%;height:100%;';
} elseif ( ! empty( $height ) ) {
	$img_attr['style'] .= "height:{$height};";
}

$img_attr['style'] .= "object-fit: {$image_fit};";

if ( ! empty( $alt_text ) ) {
	$img_attr['alt'] = $alt_text;
}

if ( 'yes' === $video_thumbnail ) {
	$maybe_video = blocksy_has_video_element(
		array(
			'display_video' => true,
			'attachment_id' => $attachment_id,
		)
	);
}

if (
	! empty( $aspect_ratio )
	&&
	'auto' !== $aspect_ratio
) {
	$img_attr['style'] .= 'aspect-ratio: ' . $aspect_ratio . ';';
}

if (
	'none' === $image_hover_effect
	&&
	! $maybe_video
) {
	if ( ! empty( $border_result['class'] ) ) {
		$img_attr['class'] = $border_result['class'];
	}

	if ( ! empty( $border_result['style'] ) ) {
		$img_attr['style'] .= $border_result['style'];
	}
}

$value = wp_get_attachment_image(
	$attachment_id,
	$size_slug,
	false,
	$img_attr
);

if (
	'yes' === $has_field_link
	&&
	(
		! $maybe_video
		||
		'yes' !== $video_thumbnail
	)
) {
	$link_attr = array(
		'href'       => $url,
		'aria-label' => wp_strip_all_tags( $aria_label ),
	);

	if ( 'no' !== $has_field_link_new_tab ) {
		$link_attr['target'] = '_blank';
	}

	if ( ! empty( $has_field_link_rel ) ) {
		$link_attr['rel'] = $has_field_link_rel;
	}
}

if ( empty( $value ) ) {
	return;
}

if ( ! empty( $width ) ) {
	$styles[] = 'width: ' . $width . ';';
}

if ( ! empty( $height ) ) {
	$styles[] = 'height: ' . $height . ';';
}

if ( ! empty( $attributes['imageAlign'] ) ) {
	$classes[] = 'align' . $attributes['imageAlign'];
}

if (
	'yes' === $video_thumbnail
	&&
	$maybe_video
) {
	$wrapper_attr['data-media-id'] = $attachment_id;

	$value .= $maybe_video['icon'];

	if ( blocksy_companion_akg( 'media_video_player', $maybe_video, 'no' ) === 'yes' ) {
		$classes[] = 'ct-simplified-player';
	}

	$new_default_based_on_old_value = blocksy_companion_akg(
		'media_video_autoplay',
		$maybe_video,
		'no'
	) === 'yes' ? 'autoplay' : 'click';

	if (
		blocksy_companion_akg(
			'media_video_event',
			$maybe_video,
			$new_default_based_on_old_value
		) === 'autoplay'
	) {
		$wrapper_attr['data-state'] = 'autoplay';
	}
}

$wrapper_attr['class'] .= ' ' . implode( ' ', $classes );

$wrapper_attr['class'] = trim( $wrapper_attr['class'] );

$wrapper_attr['style'] = implode( ' ', $styles );

if (
	'none' !== $image_hover_effect
	||
	$maybe_video
) {
	$span_styles  = array();
	$span_classes = array( 'ct-dynamic-media-inner' );

	if ( ! empty( $border_result['style'] ) ) {
		$span_styles[] = $border_result['style'];
	}

	if ( ! empty( $border_result['class'] ) ) {
		$span_classes[] = $border_result['class'];
	}

	$value = blocksy_companion_html_tag(
		'span',
		array(
			'data-hover' => $image_hover_effect,
			'class'      => implode( ' ', $span_classes ),
			'style'      => implode( ' ', $span_styles ),
		),
		$value
	);
}

$caption_html = '';

if ( 'yes' === $has_image_caption ) {
	$caption = wp_get_attachment_caption( $attachment_id );

	if ( ! empty( $caption ) ) {
		$caption_html = blocksy_companion_html_tag(
			'figcaption',
			array(
				'class' => 'wp-element-caption',
			),
			wp_kses_post( $caption )
		);
	}
}

$tag_name = 'figure';

if ( ! empty( $link_attr ) ) {
	if ( ! empty( $caption_html ) ) {
		$value = blocksy_companion_html_tag( 'a', $link_attr, $value ) . $caption_html;
	} else {
		$tag_name     = 'a';
		$wrapper_attr = array_merge(
			$wrapper_attr,
			$link_attr
		);
	}
} else {
	$value .= $caption_html;
}

$wrapper_attr = get_block_wrapper_attributes( $wrapper_attr );

if (
	'yes' === $lightbox
	&&
	function_exists( 'block_core_image_render_lightbox' )
	&&
	'yes' !== $has_field_link
	&&
	'yes' !== $video_thumbnail
	&&
	! $maybe_video
) {
	$lightbox_block = array(
		'blockName'    => 'core/image',
		'attrs'        => array(
			'id'              => $attachment_id,
			'linkDestination' => 'none',
		),
		'innerBlocks'  => array(),
		'innerHTML'    => '',
		'innerContent' => array(),
	);

	$lightbox_block_instance = null;

	if ( class_exists( 'WP_Block' ) ) {
		$lightbox_block_instance = new WP_Block( $lightbox_block );
	}

	// Match core/image asset loading for lightbox behavior.
	if ( function_exists( 'wp_enqueue_script_module' ) ) {
		wp_enqueue_script_module( '@wordpress/block-library/image/view' );
	}

	wp_enqueue_style( 'wp-block-image' );

	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	echo block_core_image_render_lightbox(
		blocksy_companion_html_tag( $tag_name, $wrapper_attr, $value ),
		$lightbox_block,
		$lightbox_block_instance
	);

	return;
}

blocksy_companion_html_tag_e( $tag_name, $wrapper_attr, $value );
