<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- view scope, variables come from blocksy_companion_render_view()

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$position_classnames = array(
	'top left'      => 'is-position-top-left',
	'top center'    => 'is-position-top-center',
	'top right'     => 'is-position-top-right',
	'center left'   => 'is-position-center-left',
	'center center' => 'is-position-center-center',
	'center right'  => 'is-position-center-right',
	'bottom left'   => 'is-position-bottom-left',
	'bottom center' => 'is-position-bottom-center',
	'bottom right'  => 'is-position-bottom-right',
);

$wrapper_attr = array(
	'class' => 'wp-block-cover ct-dynamic-cover',
	'style' => '',
);

$aspect_ratio = blocksy_companion_sanitize_aspect_ratio(
	blocksy_companion_akg( 'aspectRatio', $attributes, 'auto' )
);

if ( ! $aspect_ratio ) {
	$aspect_ratio = 'auto';
}

$minimum_height = blocksy_companion_sanitize_css_length(
	blocksy_companion_akg( 'minimumHeight', $attributes, '' )
);
$size_slug      = blocksy_companion_akg( 'sizeSlug', $attributes, 'full' );

$has_parallax                       = blocksy_companion_akg( 'hasParallax', $attributes, false );
$is_repeated                        = blocksy_companion_akg( 'isRepeated', $attributes, false );
$allow_custom_content_and_wide_size = blocksy_companion_akg( 'allowCustomContentAndWideSize', $attributes, true );

$focal_point      = blocksy_companion_akg( 'focalPoint', $attributes, array() );
$content_position = blocksy_companion_akg( 'contentPosition', $attributes, 'center center' );

if (
	! empty( $position_classnames[ $content_position ] )
	&&
	'center center' !== $content_position
) {
	$wrapper_attr['class'] .= ' has-custom-content-position ' . $position_classnames[ $content_position ];
}

$focal_point_result = '';

if ( ! empty( $focal_point ) ) {
	$focal_point_result = (float) $focal_point['x'] * 100 . '% ' . (float) $focal_point['y'] * 100 . '%;';
}

if ( $has_parallax ) {
	$wrapper_attr['class'] .= ' has-parallax';
}

if ( $is_repeated ) {
	$wrapper_attr['class'] .= ' is-repeated';
}

if ( ! empty( $attributes['imageAlign'] ) ) {
	$wrapper_attr['class'] .= ' align' . $attributes['imageAlign'];
}

$border_result = get_block_core_post_featured_image_border_attributes(
	$attributes
);

if ( ! empty( $border_result['class'] ) ) {
	$wrapper_attr['class'] .= ' ' . $border_result['class'];
}

if ( ! empty( $border_result['style'] ) ) {
	$wrapper_attr['style'] .= $border_result['style'];
}

if ( 'auto' !== $aspect_ratio ) {
	$wrapper_attr['style'] .= 'aspect-ratio: ' . $aspect_ratio . ';';
	$minimum_height         = 'unset';
}

if ( ! empty( $minimum_height ) ) {
	$wrapper_attr['style'] .= 'min-height: ' . $minimum_height . ';';
}

$wrapper_attr = get_block_wrapper_attributes( $wrapper_attr );

$img_attr = array(
	'class' => 'wp-block-cover__image-background wp-post-image',
);

$image_result = '';

if (
	! $has_parallax
	&&
	! $is_repeated
) {
	if ( ! empty( $focal_point_result ) ) {
		$img_attr['style'] = 'object-position: ' . $focal_point_result;
	}

	$image_result = wp_get_attachment_image(
		$attachment_id,
		$size_slug,
		false,
		$img_attr
	);
}

if (
	$has_parallax
	||
	$is_repeated
) {
	$attachment = wp_get_attachment_image_src(
		$attachment_id,
		$size_slug
	);

	$image_result = blocksy_companion_html_tag(
		'div',
		array(
			'class' => "wp-block-cover__image-background wp-image-{$attachment_id} has-parallax",
			'style' => 'background-image: url(' . $attachment[0] . ');background-position: 50% 50%;',
		),
		''
	);
}

$overlay_classes = array(
	'wp-block-cover__background',
);

$overlay_atts = array(
	'aria-hidden' => 'true',
);

$overlay_content = blocksy_companion_html_tag(
	'span',
	array_merge(
		array(
			'class' => join( ' ', $overlay_classes ),
		),
		$overlay_atts
	),
	''
);

$inner_classes = array(
	'wp-block-cover__inner-container',
	wp_unique_id( 'ct-dynamic-cover__inner-' ),
);

if ( $allow_custom_content_and_wide_size ) {
	$inner_classes[] = 'is-layout-constrained';
	$inner_classes[] = 'wp-block-cover-is-layout-constrained';
} else {
	$inner_classes[] = 'is-layout-flow';
	$inner_classes[] = 'wp-block-cover-is-layout-flow';
}

$content_size = blocksy_companion_akg( 'contentSize', $attributes, 0 );
$wide_size    = blocksy_companion_akg( 'wideSize', $attributes, 0 );

if ( ! empty( $content_size ) ) {
	$css      = new \Blocksy_Css_Injector();
	$selector = '.' . join( '.', $inner_classes ) . ' > :where(:not(.alignleft):not(.alignright):not(.alignfull))';

	$css->put(
		$selector,
		'max-width: ' . $content_size
	);

	$css->put(
		$selector,
		'margin-left: auto !important'
	);

	$css->put(
		$selector,
		'margin-right: auto !important'
	);

	\Blocksy\Plugin::instance()->inline_styles_collector->add(
		array(
			'css' => $css,
		)
	);
}

if ( ! empty( $wide_size ) ) {
	$css = new \Blocksy_Css_Injector();

	$css->put(
		'.' . join( '.', $inner_classes ) . ' > .alignwide',
		'max-width: ' . $wide_size
	);

	\Blocksy\Plugin::instance()->inline_styles_collector->add(
		array(
			'css' => $css,
		)
	);
}

$content = blocksy_companion_html_tag(
	'div',
	$wrapper_attr,
	$overlay_content .
	$image_result .
	blocksy_companion_html_tag(
		'div',
		array(
			'class' => join( ' ', $inner_classes ),
		),
		$content
	)
);

wp_enqueue_style( 'wp-block-cover' );
// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo $content;
