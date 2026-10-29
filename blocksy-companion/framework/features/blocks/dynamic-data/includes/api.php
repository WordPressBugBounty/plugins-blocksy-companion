<?php

namespace Blocksy\Editor\Blocks;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class DynamicDataAPI {
	public function __construct() {
		add_action(
			'rest_api_init',
			function () {
				register_rest_field(
					'attachment',
					'has_video',
					array(
						'get_callback' => function ( $post ) {
							if ( 'attachment' !== $post['type'] ) {
								return null;
							}

							$maybe_new_video = blocksy_companion_theme_functions()->blocksy_get_post_options( $post['id'] );

							$media_video_source = blocksy_companion_akg(
								'media_video_source',
								$maybe_new_video,
								'upload'
							);

							$video_url = '';

							if ( 'upload' === $media_video_source ) {
								$video_url = blocksy_companion_akg(
									'media_video_upload',
									$maybe_new_video,
									''
								);
							}

							if ( 'youtube' === $media_video_source ) {
								$video_url = blocksy_companion_akg(
									'media_video_youtube_url',
									$maybe_new_video,
									''
								);
							}

							if ( 'vimeo' === $media_video_source ) {
								$video_url = blocksy_companion_akg(
									'media_video_vimeo_url',
									$maybe_new_video,
									''
								);
							}

							return ! empty( $video_url );
						},
					)
				);
			}
		);

		add_action(
			'wp_ajax_blocksy_blocks_retrieve_dynamic_data_descriptor',
			function () {
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error();
				}

				$data = json_decode( file_get_contents( 'php://input' ), true );

				if ( ! array_key_exists( 'context', $data ) ) {
					wp_send_json_error();
				}

				wp_send_json_success(
					/**
					 * Filters the dynamic data descriptor sent to the block editor.
					 *
					 * @since 2.0.49
					 *
					 * @param array $data {
					 *     Dynamic data descriptor.
					 *
					 *     @type array $fields Custom fields available for the requested context.
					 * }
					 */
					apply_filters(
						'blocksy:general:blocks:dynamic-data:data',
						array(
							'fields' => $this->get_custom_fields_response( $data ),
						)
					)
				);
			}
		);

		add_action(
			'wp_ajax_blocksy_dynamic_data_block_custom_field_data',
			function () {
				if ( ! current_user_can( 'manage_options' ) ) {
					wp_send_json_error();
				}

				$data = json_decode( file_get_contents( 'php://input' ), true );

				if (
					! $data
					||
					! isset( $data['context'] )
					||
					! isset( $data['field_provider'] )
					||
					! isset( $data['field_id'] )
				) {
					wp_send_json_error();
				}

				$context_object = $this->compute_context_object( $data['context'] );

				if ( ! $context_object ) {
					wp_send_json_success(
						array(
							'field_data' => '',
						)
					);
				}

				if (
					'woo' === $data['field_provider']
					&&
					'brands' === $data['field_id']
				) {
					wp_send_json_success(
						array(
							'field_data' => $this->render_woo_brands_field( $context_object ),
						)
					);
				}

				if (
					'woo' === $data['field_provider']
					&&
					'attributes' === $data['field_id']
				) {
					wp_send_json_success(
						array(
							'field_data' => $this->render_woo_attributes_field(
								$context_object,
								isset( $data['attributes']['attribute'] )
									? $data['attributes']['attribute']
									: ''
							),
						)
					);
				}

				wp_send_json_success(
					array(
						'field_data' => $this->render_custom_field(
							$context_object,
							$data['field_provider'],
							$data['field_id']
						),
					)
				);
			}
		);
	}

	private function render_woo_brands_field( $context_object ) {
		if ( ! is_a( $context_object, 'WP_Post' ) ) {
			return array();
		}

		$brands = get_the_terms( $context_object->ID, 'product_brand' );

		if ( ! $brands || is_wp_error( $brands ) ) {
			return array();
		}

		$brands_result = array();

		foreach ( $brands as $term ) {
			$term_atts = blocksy_companion_theme_functions()->blocksy_get_taxonomy_options( $term->term_id );

			$maybe_image_id = get_term_meta( $term->term_id, 'thumbnail_id', true );

			if ( ! empty( $maybe_image_id ) ) {
				$maybe_attachment_url = wp_get_attachment_image_url(
					$maybe_image_id,
					'full'
				);

				if ( $maybe_attachment_url ) {
					$term_atts['icon_image'] = array(
						'attachment_id' => $maybe_image_id,
						'url'           => $maybe_attachment_url,
					);
				}
			}

			$maybe_image = blocksy_companion_akg( 'icon_image', $term_atts, '' );

			if ( is_array( $maybe_image ) ) {
				$attachment = $maybe_image;
			}

			$brands_result[] = array(
				'term_id' => $term->term_id,
				'name'    => $term->name,
				'slug'    => $term->slug,
				'image'   => $attachment,
			);
		}

		return $brands_result;
	}

	private function render_woo_attributes_field( $context_object, $attribute ) {
		if ( ! is_a( $context_object, 'WP_Post' ) ) {
			return array();
		}

		$product = wc_get_product( $context_object->ID );

		if ( ! $product ) {
			return array();
		}

		if ( empty( $attribute ) ) {
			$choices = DynamicData::get_product_attribute_choices();

			if ( count( $choices ) > 0 ) {
				$attribute = array_keys( $choices )[0];
			}
		}

		$attributes    = $product->get_attributes();
		$taxonomy_name = wc_attribute_taxonomy_name( $attribute );

		if (
			! $attributes
			||
			! isset( $attributes[ sanitize_title( $taxonomy_name ) ] )
		) {
			return array();
		}

		$attribute = $attributes[ sanitize_title( $taxonomy_name ) ];

		if ( ! $attribute ) {
			return array();
		}

		$terms = $attribute->get_terms();

		if ( ! $terms ) {
			return array();
		}

		$terms_result = array();

		foreach ( $terms as $term ) {
			$terms_result[] = array(
				'term_id' => $term->term_id,
				'name'    => $term->name,
				'slug'    => $term->slug,
			);
		}

		return $terms_result;
	}

	private function render_custom_field( $context_object, $field_provider, $field_id ) {
		if (
			! blocksy_companion_get_ext( 'post-types-extra' )
			||
			! blocksy_companion_get_ext( 'post-types-extra' )->dynamic_data
			||
			! blocksy_companion_get_ext( 'post-types-extra' )->dynamic_data->custom_fields_manager
		) {
			return '';
		}

		$field_render = blocksy_companion_get_ext( 'post-types-extra' )
			->dynamic_data
			->custom_fields_manager
			->render_field(
				$field_id,
				array(
					'provider'       => $field_provider,

					'allow_images'   => true,

					'context_object' => $context_object,
				)
			);

		if ( ! $field_render ) {
			return '';
		}

		if (
			! is_string( $field_render['value'] )
			||
			! empty( $field_render['trusted_html'] )
		) {
			return $field_render['value'];
		}

		return blocksy_companion_sanitize_html_for_display( $field_render['value'] );
	}

	private function get_custom_fields_response( $data ) {
		if (
			! blocksy_companion_get_ext( 'post-types-extra' )
			||
			! blocksy_companion_get_ext( 'post-types-extra' )->dynamic_data
			||
			! blocksy_companion_get_ext( 'post-types-extra' )->dynamic_data->custom_fields_manager
		) {
			return array();
		}

		$fields = blocksy_companion_get_ext( 'post-types-extra' )
			->dynamic_data
			->custom_fields_manager
			->get_fields(
				$data['context'],
				array(
					'provider'     => 'all',
					'allow_images' => true,
				)
			);

		$fields_response = array();

		foreach ( $fields as $fields_descriptor ) {
			$provider_data = array(
				'provider'       => $fields_descriptor['provider'],
				'provider_label' => $fields_descriptor['label'],
				'fields'         => array(),
			);

			foreach ( $fields_descriptor['fields'] as $field ) {
				$provider_data['fields'][] = array(
					'id'    => $field->get_id(),
					'label' => $field->get_label(),
					'type'  => $field->get_type(),
				);
			}

			$fields_response[] = $provider_data;
		}

		return $fields_response;
	}

	public function compute_context_object( $context ) {
		if ( ! isset( $context['type'] ) ) {
			return null;
		}

		if ( 'post' === $context['type'] ) {
			if (
				! isset( $context['post_id'] )
				||
				! isset( $context['post_type'] )
			) {
				return null;
			}

			return get_post( $context['post_id'] );
		}

		if ( 'term' === $context['type'] ) {
			if ( ! isset( $context['term_id'] ) ) {
				return null;
			}

			return get_term( $context['term_id'] );
		}

		return null;
	}
}
