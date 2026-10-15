<?php

namespace Blocksy;

class DemoInstallOptionsInstaller {
	protected $demo_name = null;

	protected $sideloaded_images = array();
	protected $is_ajax_request   = true;

	public function __construct( $args = array() ) {
		$args = wp_parse_args(
			$args,
			array(
				'demo_name'       => null,
				'is_ajax_request' => true,
			)
		);

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$request_demo_name = isset( $_REQUEST['demo_name'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['demo_name'] ) ) : '';

		if (
			! $args['demo_name']
			&&
			'' !== $request_demo_name
		) {
			$args['demo_name'] = $request_demo_name;
		}

		$this->demo_name       = $args['demo_name'];
		$this->is_ajax_request = $args['is_ajax_request'];
	}

	public function import() {
		if (
			! current_user_can( 'edit_theme_options' )
			&&
			$this->is_ajax_request
		) {
			wp_send_json_error(
				array(
					'message' => __( "Sorry, you don't have permission to install options.", 'blocksy-companion' ),
				)
			);
		}

		if ( ! $this->demo_name ) {
			if ( $this->is_ajax_request ) {
				wp_send_json_error(
					array(
						'message' => __( 'No demo to install', 'blocksy-companion' ),
					)
				);
			} else {
				return new \WP_Error(
					'blocksy_demo_install_options_no_demo',
					__( 'No demo to install', 'blocksy-companion' )
				);
			}
		}

		$demo_name = explode( ':', $this->demo_name );

		if ( ! isset( $demo_name[1] ) ) {
			$demo_name[1] = '';
		}

		$demo_to_install = Plugin::instance()->demo->get_currently_installing_demo();

		if (
			empty( $demo_to_install )
			||
			! isset( $demo_to_install['demo'] )
			||
			! isset( $demo_to_install['demo']['options'] )
		) {
			if ( $this->is_ajax_request ) {
				wp_send_json_error(
					array(
						'message' => __( 'No demo to install', 'blocksy-companion' ),
					)
				);
			} else {
				return new \WP_Error(
					'blocksy_demo_install_options_no_demo',
					__( 'No demo to install', 'blocksy-companion' )
				);
			}
		}

		$options = $demo_to_install['demo']['options'];

		$this->import_options( $options, $demo_to_install['demo'] );

		if ( $this->is_ajax_request ) {
			wp_send_json_success();
		}
	}

	public function import_options( $options, $demo_content = null ) { // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- keep the public signature
		global $wp_customize;

		if ( ! $wp_customize ) {
			$_REQUEST['wp_customize'] = 'on';

			_wp_customize_include();

			$wp_customize->wp_loaded();
		}

		do_action( 'customize_save', $wp_customize ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core / third-party hook

		foreach ( $options['mods'] as $key => $val ) {
			if ( 'sidebars_widgets' === $key ) {
				continue;
			}
			if ( 'custom_css_post_id' === $key ) {
				continue;
			}

			if (
				'header_placements' === $key
				||
				'footer_placements' === $key
			) {
				$sanitized = blocksy_companion_theme_functions()->blocksy_sanitize_builder_value( array(), $val );

				if ( ThemeFunctions::NON_EXISTING_FUNCTION !== $sanitized ) {
					$val = $sanitized;
				}
			}

			do_action( 'customize_save_' . $key, $wp_customize ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core / third-party hook
			set_theme_mod( $key, $val );
		}

		if (
			isset( $options['custom_palettes'] )
			&&
			is_array( $options['custom_palettes'] )
			&&
			! empty( $options['custom_palettes'] )
		) {
			update_option( 'blocksy_custom_palettes', $options['custom_palettes'] );
		}

		do_action( 'customize_save_after', $wp_customize ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core / third-party hook

		foreach ( $options['options'] as $key => $val ) {
			if ( 'blocksy_active_extensions' === $key ) {
				if ( $val && is_array( $val ) ) {
					foreach ( $val as $single_extension ) {
						Plugin::instance()->extensions->activate_extension(
							$single_extension
						);
					}
				}
			} else {
				if (
					strpos( $key, 'woocommerce' ) !== false
					&&
					'woocommerce_thumbnail_cropping' !== $key
				) {
					add_option( $key, $val );
					update_option( $key, $val );
				} else {
					update_option( $key, $val );
				}
			}
		}

		/*
		$all = get_option('sidebars_widgets');
		$all['sidebar-1'] = [];
		update_option('sidebars_widgets', $all);

		$all = blocksy_companion_theme_functions()->blocksy_get_theme_mod('sidebars_widgets');

		if ($all) {
			$all['data']['sidebar-1'] = [];
			set_theme_mod('sidebars_widgets', $all);
		}
		 */

		if (
			class_exists( '\FluentBooking\App\Services\ImportService' )
			&&
			isset( $options['fluent_booking_data'] )
		) {
			\FluentBooking\Database\DBMigrator::run( false );
			\FluentBooking\Database\DBSeeder::run();

			try {
				$import_service = new \FluentBooking\App\Services\ImportService();
				$calendar       = $import_service->importHostJson( $options['fluent_booking_data'] );

				if ( isset( $calendar['calendar'] ) ) {
					$current_demo                            = get_option( 'blocksy_ext_demos_current_demo', array() );
					$current_demo['fluent_booking_calendar'] = $calendar['calendar']->id;
					update_option( 'blocksy_ext_demos_current_demo', $current_demo );
				}
			} catch ( \Throwable $e ) {
			}
		}

		if (
			class_exists( '\FluentForm\App\Hooks\Handlers\ActivationHandler' )
			&&
			isset( $options['fluent_form_forms'] )
		) {
			$fluent_form_activation = new \FluentForm\App\Hooks\Handlers\ActivationHandler();
			$fluent_form_activation->migrate();

			$forms = $options['fluent_form_forms'];

			$inserted_forms = array();

			if ( $forms && is_array( $forms ) ) {
				foreach ( $forms as $form_item ) {
					$form_fields = json_encode( array() );

					$fields = \FluentForm\Framework\Support\Arr::get( $form_item, 'form', '' );

					if ( ! $fields ) {
						$fields = \FluentForm\Framework\Support\Arr::get( $form_item, 'form_fields', '' );
					}

					if ( $fields ) {
						$form_fields = json_encode( $fields );
					}

					$form = array(
						'title'       => \FluentForm\Framework\Support\Arr::get( $form_item, 'title' ),
						'form_fields' => $form_fields,
						'status'      => \FluentForm\Framework\Support\Arr::get( $form_item, 'status', 'published' ),
						'has_payment' => \FluentForm\Framework\Support\Arr::get( $form_item, 'has_payment', 0 ),
						'type'        => \FluentForm\Framework\Support\Arr::get( $form_item, 'type', 'form' ),
						'created_by'  => get_current_user_id(),
					);

					if ( \FluentForm\Framework\Support\Arr::get( $form_item, 'conditions' ) ) {
						$form['conditions'] = \FluentForm\Framework\Support\Arr::get( $form_item, 'conditions' );
					}

					if ( isset( $form_item['appearance_settings'] ) ) {
						$form['appearance_settings'] = \FluentForm\Framework\Support\Arr::get( $form_item, 'appearance_settings' );
					}

					$form_id                    = \FluentForm\App\Models\Form::insertGetId( $form );
					$inserted_forms[ $form_id ] = array(
						'title'    => $form['title'],
						'edit_url' => admin_url( 'admin.php?page=fluent_forms&route=editor&form_id=' . $form_id ),
					);

					if ( isset( $form_item['metas'] ) ) {
						foreach ( $form_item['metas'] as $meta_data ) {
							$meta_key   = \FluentForm\Framework\Support\Arr::get( $meta_data, 'meta_key' );
							$meta_value = \FluentForm\Framework\Support\Arr::get( $meta_data, 'value' );
							if ( 'ffc_form_settings_generated_css' === $meta_key || 'ffc_form_settings_meta' === $meta_key ) {
								$meta_value = str_replace( 'ff_conv_app_' . \FluentForm\Framework\Support\Arr::get( $form_item, 'id' ), 'ff_conv_app_' . $form_id, $meta_value );
							}
							$settings = array(
								'form_id'  => $form_id,
								// phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
								'meta_key' => $meta_key,
								'value'    => $meta_value,
							);
							\FluentForm\App\Models\FormMeta::insert( $settings );
						}
					} else {
						$old_keys = array(
							'formSettings',
							'notifications',
							'mailchimp_feeds',
							'slack',
						);
						foreach ( $old_keys as $key ) {
							if ( isset( $form_item[ $key ] ) ) {
								\FluentForm\App\Models\FormMeta::persist( $form_id, $key, json_encode( \FluentForm\Framework\Support\Arr::get( $form_item, $key ) ) );
							}
						}
					}
					do_action_deprecated(
						'fluentform_form_imported',
						array(
							$form_id,
						),
						FLUENTFORM_FRAMEWORK_UPGRADE,
						'fluentform/form_imported',
						'Use fluentform/form_imported instead of fluentform_form_imported.'
					);
					do_action( 'fluentform/form_imported', $form_id ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- core / third-party hook
				}
			}
		}

		if (
			function_exists( 'wc_get_attribute_taxonomies' )
			&&
			isset( $options['woocommerce_attribute_taxonomies'] )
		) {
			$current = wc_get_attribute_taxonomies();

			foreach ( $options['woocommerce_attribute_taxonomies'] as $attr ) {
				$found = false;

				foreach ( array_values( $current ) as $current_attr ) {
					if ( $current_attr->attribute_name === $attr['attribute_name'] ) {
						$found = true;
						break;
					}
				}

				if ( ! $found ) {
					wc_create_attribute(
						array(
							'name'         => $attr['attribute_label'],
							'slug'         => $attr['attribute_name'],
							'type'         => $attr['attribute_type'],
							'order_by'     => $attr['attribute_orderby'],
							'has_archives' => ! ! $attr['attribute_public'],
						)
					);
				}
			}
		}

		if (
			class_exists( '\FluentSnippets\App\Model\Snippet' )
			&&
			isset( $options['fluent_snippets'] )
			&&
			is_array( $options['fluent_snippets'] )
		) {
			$snippet_model     = new \FluentSnippets\App\Model\Snippet();
			$existing_snippets = $snippet_model->get();

			$existing_code_hashes = array();
			foreach ( $existing_snippets as $existing_snippet ) {
				$existing_code_hashes[] = md5( $existing_snippet['code'] );
			}

			foreach ( $options['fluent_snippets'] as $snippet ) {
				if ( empty( $snippet['code'] ) ) {
					continue;
				}

				$name = $snippet['info']['name'] ?? '';
				if ( ! $name ) {
					continue;
				}

				$code      = base64_decode( $snippet['code'] );
				$code_hash = md5( $code );

				// Verify code hash
				if ( $code_hash !== $snippet['code_hash'] ) {
					continue;
				}

				// Skip if snippet already exists
				if ( in_array( $code_hash, $existing_code_hashes, true ) ) {
					continue;
				}

				$meta = $snippet['info'];

				$snippet_model->createSnippet( $code, $meta );
			}

			// Rebuild snippet index cache
			if ( class_exists( '\FluentSnippets\App\Helpers\Helper' ) ) {
				\FluentSnippets\App\Helpers\Helper::cacheSnippetIndex();
			}
		}

		if (
			function_exists( 'wp_update_custom_css_post' )
			&&
			isset( $options['wp_css'] )
			&&
			$options['wp_css']
		) {
			wp_update_custom_css_post( $options['wp_css'] );
		}

		/**
		 * Temporary work around until Elementor comes up with something better
		 */
		if ( class_exists( '\Elementor\Plugin' ) ) {
			$default_post_id = \Elementor\Plugin::$instance->kits_manager->get_active_id();

			if (
				! empty( $default_post_id )
				&&
				isset( $options['elementor_active_kit_settings'] )
				&&
				! empty( $options['elementor_active_kit_settings'] )
			) {
				update_post_meta(
					$default_post_id,
					'_elementor_page_settings',
					$options['elementor_active_kit_settings']
				);
			}
		}
	}
}
