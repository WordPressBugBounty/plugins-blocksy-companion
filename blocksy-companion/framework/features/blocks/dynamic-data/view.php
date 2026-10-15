<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- view scope, variables come from blocksy_companion_render_view()

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$field = blocksy_companion_akg( 'field', $attributes, 'wp:title' );

if ( strpos( $field, 'woo:' ) === 0 ) {
	blocksy_companion_render_view_e(
		__DIR__ . '/views/woo-field.php',
		array(
			'attributes' => $attributes,
			'field'      => $field,
		)
	);

	return;
}

if ( strpos( $field, 'wp:' ) === 0 ) {
	if (
		'wp:featured_image' !== $field
		&&
		'wp:author_avatar' !== $field
		&&
		'wp:archive_image' !== $field
	) {
		blocksy_companion_render_view_e(
			__DIR__ . '/views/wp-field.php',
			array(
				'attributes' => $attributes,
				'field'      => $field,
				'block'      => $block,
			)
		);
	}

	if ( 'wp:term_image' === $field ) {
		global $blocksy_term_obj;

		if ( isset( $blocksy_term_obj ) ) {
			blocksy_companion_render_view_e(
				__DIR__ . '/views/archive-image-field.php',
				array(
					'attributes' => $attributes,
					'field'      => $field,
					'content'    => $content,
					'term_id'    => $blocksy_term_obj->term_id,
				)
			);
		}
	}

	if ( 'wp:archive_image' === $field ) {
		blocksy_companion_render_view_e(
			__DIR__ . '/views/archive-image-field.php',
			array(
				'attributes' => $attributes,
				'field'      => $field,
				'content'    => $content,
			)
		);
	}

	if ( 'wp:featured_image' === $field ) {
		blocksy_companion_render_view_e(
			__DIR__ . '/views/image-field.php',
			array(
				'attributes'    => $attributes,
				'field'         => $field,
				'content'       => $content,
				'attachment_id' => get_post_thumbnail_id(),
				'url'           => get_permalink(),
			)
		);
	}

	if ( 'wp:author_avatar' === $field ) {
		blocksy_companion_render_view_e(
			__DIR__ . '/views/avatar-field.php',
			array(
				'attributes' => $attributes,
				'field'      => $field,
			)
		);
	}

	return;
}

if ( ! function_exists( 'blocksy_companion_get_ext' ) ) {
	return;
}

if (
	! blocksy_companion_get_ext( 'post-types-extra' )
	||
	! blocksy_companion_get_ext( 'post-types-extra' )->dynamic_data
) {
	return;
}

$field_descriptor = explode( ':', $field );

if ( count( $field_descriptor ) < 2 ) {
	return;
}

$field_render = blocksy_companion_get_ext( 'post-types-extra' )
	->dynamic_data
	->custom_fields_manager
	->render_field(
		$field_descriptor[1],
		array(
			'provider'     => $field_descriptor[0],
			'allow_images' => true,
		)
	);

if ( ! $field_render ) {
	return;
}

if ( \Blocksy\Extensions\PostTypesExtra\CustomField::$TYPE_IMAGE === $field_render['type'] ) { // phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase -- public CustomField API
	if ( isset( $field_render['value']['id'] ) ) {
		blocksy_companion_render_view_e(
			__DIR__ . '/views/image-field.php',
			array(
				'attributes'    => $attributes,
				'field'         => $field,
				'attachment_id' => $field_render['value']['id'],
			)
		);
	}

	return;
}

blocksy_companion_render_view_e(
	__DIR__ . '/views/custom-text-field.php',
	array(
		'attributes'   => $attributes,
		'value'        => $field_render['value'],
		'trusted_html' => ! empty( $field_render['trusted_html'] ),
	)
);
