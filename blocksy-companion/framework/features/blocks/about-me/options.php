<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound -- include scope, variables are read by the options loader

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * About me widget
 *
 * @copyright 2019-present Creative Themes
 * @license   http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @package   Blocksy
 */

$default_wp_user = '';

if ( blocksy_companion_theme_functions()->blocksy_manager() !== \Blocksy\ThemeFunctions::NON_EXISTING_FUNCTION ) {
	$default_wp_user = blocksy_companion_theme_functions()->blocksy_manager()
		->entity_id_picker
		->get_default_value(
			array(
				'entity' => 'users',
			)
		);
}


$options = array( // phpcs:ignore VariableAnalysis.CodeAnalysis.VariableAnalysis.UnusedVariable -- read by the options loader
	'title'                    => array(
		'type'                => 'hidden',
		'label'               => __( 'Title', 'blocksy-companion' ),
		'value'               => __( 'About me', 'blocksy-companion' ),
		'disableRevertButton' => true,
	),

	'about_source'             => array(
		'label'   => __( 'User Source', 'blocksy-companion' ),
		'type'    => 'ct-radio',
		'value'   => 'from_wp',
		'inline'  => true,
		'choices' => array(
			'from_wp' => __( 'Dynamic', 'blocksy-companion' ),
			'custom'  => __( 'Custom', 'blocksy-companion' ),
		),
	),

	blocksy_rand_md5()         => array(
		'type'      => 'ct-condition',
		'condition' => array( 'about_source' => 'from_wp' ),
		'options'   => array(
			'wp_user' => array(
				'type'    => 'ct-entity-picker',
				'label'   => __( 'User', 'blocksy-companion' ),
				'entity'  => 'users',
				'purpose' => 'default',
				'value'   => $default_wp_user,
			),
		),
	),

	blocksy_rand_md5()         => array(
		'type'      => 'ct-condition',
		'condition' => array( 'about_source' => 'custom' ),
		'options'   => array(
			'about_avatar' => array(
				'label'       => __( 'Image', 'blocksy-companion' ),
				'type'        => 'ct-image-uploader',
				'value'       => array( 'attachment_id' => null ),
				'attr'        => array( 'data-type' => 'no-frame' ),
				'emptyLabel'  => __( 'Select Image', 'blocksy-companion' ),
				'filledLabel' => __( 'Change Image', 'blocksy-companion' ),
			),

			'about_name'   => array(
				'label' => __( 'Name', 'blocksy-companion' ),
				'type'  => 'hidden',
				'value' => 'John Doe',
			),

			'about_text'   => array(
				'label'        => __( 'Description', 'blocksy-companion' ),
				'type'         => 'hidden',
				'value'        => 'Lorem ipsum dolor sit amet consectetur adipiscing elit sed do eiusmod tempor incididunt ut labore et dolore magna aliqua tincidunt tortor aliquam.',
				'desc'         => __(
					'You can add here some arbitrary HTML code.',
					'blocksy-companion'
				),

				'mediaButtons' => false,
				'tinymce'      => array(
					'toolbar1' => 'bold,italic,link,undo,redo',
				),
			),
		),
	),

	'about_avatar_size'        => array(
		'label'   => __( 'Image Size', 'blocksy-companion' ),
		'type'    => 'ct-select',
		'value'   => 'small',
		'purpose' => 'default',
		'choices' => array(
			'small'  => __( 'Small', 'blocksy-companion' ),
			'medium' => __( 'Medium', 'blocksy-companion' ),
			'large'  => __( 'Large', 'blocksy-companion' ),
		),
	),

	'avatar_shape'             => array(
		'label'   => __( 'Image Shape', 'blocksy-companion' ),
		'type'    => 'ct-radio',
		'value'   => 'rounded',
		'inline'  => true,
		'choices' => array(
			'rounded' => __( 'Rounded', 'blocksy-companion' ),
			'square'  => __( 'Square', 'blocksy-companion' ),
		),
	),

	'about_alignment'          => array(
		'type'    => 'ct-radio',
		'label'   => __( 'Alignment', 'blocksy-companion' ),
		'value'   => 'center',
		'divider' => 'top:full',
		'attr'    => array( 'data-type' => 'alignment' ),
		'choices' => array(
			'left'   =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M4 19.8h8.9v-1.5H4v1.5zm8.9-15.6H4v1.5h8.9V4.2zm-8.9 7v1.5h16v-1.5H4z"></path></svg>',
			'center' =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M16.4 4.2H7.6v1.5h8.9V4.2zM4 11.2v1.5h16v-1.5H4zm3.6 8.6h8.9v-1.5H7.6v1.5z"></path></svg>',
			'right'  =>
				'<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" aria-hidden="true" focusable="false"><path d="M11.1 19.8H20v-1.5h-8.9v1.5zm0-15.6v1.5H20V4.2h-8.9zM4 12.8h16v-1.5H4v1.5z"></path></svg>',
		),
	),

	'about_socials'            => array(
		'label'      => __( 'Social Channels', 'blocksy-companion' ),
		'type'       => 'ct-layers',
		'divider'    => 'top:full',
		'manageable' => true,
		'desc'       => blocksy_companion_safe_sprintf(
			// translators: %s is a link to the Customizer section.
			__( 'You can configure social URLs in %s.', 'blocksy-companion' ),
			blocksy_companion_safe_sprintf(
				'<a href="%s" target="_blank">%s</a>',
				admin_url( '/customize.php?autofocus[section]=social_accounts' ),
				__( 'Customizer', 'blocksy-companion' )
			)
		),
		'value'      => array(
			array(
				'id'      => 'facebook',
				'enabled' => true,
			),

			array(
				'id'      => 'twitter',
				'enabled' => true,
			),

			array(
				'id'      => 'instagram',
				'enabled' => true,
			),
		),

		/**
		 * Filters the social networks available in the block's networks picker.
		 *
		 * @since 2.0.49
		 *
		 * @param array $networks {
		 *     Social networks, keyed by network id.
		 *
		 *     @type array $network {
		 *         @type string $label Network label shown in the picker.
		 *     }
		 * }
		 */
		'settings'   => apply_filters(
			'blocksy:socials:options:icon',
			blocksy_get_social_networks_list()
		),
	),

	'link_target'              => array(
		'type'    => 'ct-switch',
		'label'   => __( 'Open links in new tab', 'blocksy-companion' ),
		'value'   => 'no',
		'divider' => 'top:full',
	),

	'link_nofollow'            => array(
		'type'  => 'ct-switch',
		'label' => __( 'Set links to nofollow', 'blocksy-companion' ),
		'value' => 'no',
	),

	'about_social_icons_size'  => array(
		'label'      => __( 'Icons Size', 'blocksy-companion' ),
		'type'       => 'ct-slider',
		'min'        => 5,
		'max'        => 50,
		'value'      => '',
		'responsive' => false,
		'divider'    => 'top:full',
	),

	'about_items_spacing'      => array(
		'label'      => __( 'Items Spacing', 'blocksy-companion' ),
		'type'       => 'ct-slider',
		'min'        => 5,
		'max'        => 50,
		'value'      => '',
		'responsive' => false,
	),

	'about_social_icons_color' => array(
		'label'   => __( 'Icons Color', 'blocksy-companion' ),
		'type'    => 'ct-radio',
		'value'   => 'default',
		'view'    => 'text',
		'divider' => 'top:full',
		'setting' => array( 'transport' => 'postMessage' ),
		'choices' => array(
			'default'  => __( 'Custom', 'blocksy-companion' ),
			'official' => __( 'Official', 'blocksy-companion' ),
		),
	),

	'about_social_type'        => array(
		'label'   => __( 'Icons Shape Type', 'blocksy-companion' ),
		'type'    => 'ct-radio',
		'value'   => 'rounded',
		'choices' => array(
			'simple'  => __( 'None', 'blocksy-companion' ),
			'rounded' => __( 'Rounded', 'blocksy-companion' ),
			'square'  => __( 'Square', 'blocksy-companion' ),
		),
	),

	blocksy_rand_md5()         => array(
		'type'      => 'ct-condition',
		'condition' => array( 'about_social_type' => '!simple' ),
		'options'   => array(
			'about_social_icons_fill' => array(
				'label'   => __( 'Shape Fill Type', 'blocksy-companion' ),
				'type'    => 'ct-radio',
				'value'   => 'outline',
				'choices' => array(
					'outline' => __( 'Outline', 'blocksy-companion' ),
					'solid'   => __( 'Solid', 'blocksy-companion' ),
				),
			),
		),
	),
);
