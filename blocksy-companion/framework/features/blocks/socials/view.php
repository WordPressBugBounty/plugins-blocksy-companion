<?php
// phpcs:disable WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound, VariableAnalysis.CodeAnalysis.VariableAnalysis.UndefinedVariable -- view scope, variables come from blocksy_companion_render_view()

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Socials Widget
 *
 * @copyright 2019-present Creative Themes
 * @license   http://www.gnu.org/copyleft/gpl.html GNU General Public License
 * @package Blocksy
 */

$classes = blocksy_companion_akg( 'className', $atts, '' );
$color   = blocksy_companion_akg( 'social_icons_color', $atts, 'default' );
$type    = blocksy_companion_akg( 'social_type', $atts, 'simple' );
$fill    = blocksy_companion_akg( 'social_icons_fill', $atts, 'outline' );

$link_rel = '';

$link_target = blocksy_companion_akg( 'link_target', $atts, 'no' );

if ( 'yes' === $link_target ) {
	$link_target = '_blank';
	$link_rel    = 'noopener noreferrer';
} else {
	$link_target = false;
}

$link_nofollow = blocksy_companion_akg( 'link_nofollow', $atts, 'no' );

if ( 'yes' === $link_nofollow ) {
	$link_rel .= ' nofollow';
}

$colors = array(
	'--theme-icon-color'       => blocksy_companion_akg( 'customInitialColor', $atts, '' ),
	'--theme-icon-hover-color' => blocksy_companion_akg( 'customHoverColor', $atts, '' ),
);

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

$wrapper_attr = array(
	'class' => trim( 'ct-socials-block ' . $classes ),
	'style' => '',
);

$wrapper_attr['style'] .= blocksy_companion_colors_to_css( $colors );

$icons_size = (float) blocksy_companion_akg( 'social_icons_size', $atts, '' );

if ( ! empty( $icons_size ) ) {
	$wrapper_attr['style'] .= '--theme-icon-size:' . $icons_size . 'px;';
}

$items_spacing = (float) blocksy_companion_akg( 'items_spacing', $atts, '' );

if ( ! empty( $items_spacing ) ) {
	$wrapper_attr['style'] .= '--items-spacing:' . $items_spacing . 'px;';
}

$justify_content_map = array(
	'left'          => 'flex-start',
	'center'        => 'center',
	'right'         => 'flex-end',
	'space-between' => 'space-between',
);

$justify_content    = blocksy_companion_akg( 'justifyContent', $atts, 'left' );
$root_attr['style'] = '';
if ( isset( $justify_content_map[ $justify_content ] ) && 'left' !== $justify_content ) {
	$root_attr['style'] .= 'justify-content: ' . $justify_content_map[ $justify_content ] . ';';
}

if ( empty( $wrapper_attr['style'] ) ) {
	unset( $wrapper_attr['style'] );
}

blocksy_companion_html_tag_e( 'div', $wrapper_attr, false );

// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
echo blocksy_social_icons(
	blocksy_companion_akg(
		'socials',
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
				'id'      => 'instagram',
				'enabled' => true,
			),
		)
	),
	array(
		'size'         => '',
		'icons-color'  => $color,
		'fill'         => $fill,
		'type'         => $type,
		'links_target' => $link_target,
		'links_rel'    => $link_rel,
		'root_attr'    => $root_attr,
	)
);

echo '</div>';
