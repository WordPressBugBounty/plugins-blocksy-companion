<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- view scope, variables come from blocksy_companion_render_view()

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shares Widget
 *
 * @copyright 2019-present Creative Themes
 * @license   http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @package Blocksy
 */

$classes       = blocksy_companion_akg( 'className', $atts, '' );
$color         = blocksy_companion_akg( 'share_icons_color', $atts, 'default' );
$type          = blocksy_companion_akg( 'share_type', $atts, 'simple' );
$fill          = blocksy_companion_akg( 'share_icons_fill', $atts, 'outline' );
$icons_size    = (float) blocksy_companion_akg( 'share_icons_size', $atts, '' );
$items_spacing = (float) blocksy_companion_akg( 'items_spacing', $atts, '' );

$colors = array();

$wrapper_attr = array(
	'class' => trim( 'ct-shares-block ' . $classes ),
	'style' => '',
);

if ( 'official' !== $color ) {
	$colors['--theme-icon-color']       = blocksy_companion_akg( 'customInitialColor', $atts, '' );
	$colors['--theme-icon-hover-color'] = blocksy_companion_akg( 'customHoverColor', $atts, '' );

	if ( 'simple' !== $type ) {
		$base_color  = blocksy_companion_akg( 'customBorderColor', $atts, 'rgba(218, 222, 228, 0.5)' );
		$hover_color = blocksy_companion_akg( 'customBorderHoverColor', $atts, 'rgba(218, 222, 228, 0.7)' );

		if ( isset( $atts['borderColor'] ) ) {
			$var        = $atts['borderColor'];
			$base_color = "var(--wp--preset--color--$var)";
		}

		if ( isset( $atts['borderHoverColor'] ) ) {
			$var         = $atts['borderHoverColor'];
			$hover_color = "var(--wp--preset--color--$var)";
		}

		if ( 'solid' === $fill ) {
			$base_color  = blocksy_companion_akg( 'customBackgroundColor', $atts, 'rgba(218, 222, 228, 0.5)' );
			$hover_color = blocksy_companion_akg( 'customBackgroundHoverColor', $atts, 'rgba(218, 222, 228, 0.7)' );

			if ( isset( $atts['backgroundColor'] ) ) {
				$var        = $atts['backgroundColor'];
				$base_color = "var(--wp--preset--color--$var)";
			}

			if ( isset( $atts['backgroundHoverColor'] ) ) {
				$var         = $atts['backgroundHoverColor'];
				$hover_color = "var(--wp--preset--color--$var)";
			}
		}

		$colors = array_merge(
			$colors,
			array(
				'--background-color'       => $base_color,
				'--background-hover-color' => $hover_color,
			)
		);
	}

	if ( isset( $atts['initialColor'] ) ) {
		$var                          = $atts['initialColor'];
		$colors['--theme-icon-color'] = "var(--wp--preset--color--$var)";
	}

	if ( isset( $atts['hoverColor'] ) ) {
		$var                                = $atts['hoverColor'];
		$colors['--theme-icon-hover-color'] = "var(--wp--preset--color--$var)";
	}

	$wrapper_attr['style'] .= blocksy_companion_colors_to_css( $colors );
}

if ( ! empty( $icons_size ) ) {
	$wrapper_attr['style'] .= '--theme-icon-size:' . $icons_size . 'px;';
}

if ( ! empty( $items_spacing ) ) {
	$wrapper_attr['style'] .= '--items-spacing:' . $items_spacing . 'px;';
}

if ( empty( $wrapper_attr['style'] ) ) {
	unset( $wrapper_attr['style'] );
}

blocksy_companion_html_tag_e( 'div', $wrapper_attr, false );

/**
 * blocksy_share_icons() function is already properly escaped.
 * Escaping it again here would cause SVG icons to not be outputed
 */

$attr = array();

$data_icons_type = $type;

if ( $fill ) {
	if ( 'simple' !== $type ) {
		$data_icons_type .= ':' . $fill;
	}
}


// v.2.0.74 migration
$share_networks = blocksy_companion_akg(
	'share_networks',
	$atts,
	array(
		array(
			'id'      => 'facebook',
			'enabled' => true,
		),
		array(
			'id'      => 'twitter',
			'enabled' => true,
		),
		array(
			'id'      => 'pinterest',
			'enabled' => true,
		),
		array(
			'id'      => 'linkedin',
			'enabled' => true,
		),
	)
);

if ( empty( $share_networks ) ) {
	foreach ( $atts as $key => $value ) {
		if ( 'yes' !== $value && 'no' !== $value ) {
			continue;
		}

		if ( strpos( $key, 'share_' ) !== 0 ) {
			continue;
		}

		$share_networks[ str_replace( 'share_', '', $key ) ] = array(
			'id'      => str_replace( 'share_', '', $key ),
			'enabled' => 'yes' === $value,
		);
	}
}

$atts['share_networks'] = $share_networks;
// v.2.0.74 migration

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo blocksy_get_social_share_box(
	array(
		'type'               => 'share',
		'class'              => '',
		'html_atts'          => array_merge(
			array(
				'data-type' => 'type-3',
			),
			$attr
		),
		'strategy'           => array(
			'strategy' => array_merge(
				$atts,
				array(
					'share_box_visibility' => array(
						'desktop' => true,
						'tablet'  => true,
						'mobile'  => true,
					),
					'share_box_title'      => '',
				)
			),
		),
		'links_wrapper_attr' => array(
			'data-icons-type' => $data_icons_type,
			'data-color'      => $color,
		),
	)
);

echo '</div>';
