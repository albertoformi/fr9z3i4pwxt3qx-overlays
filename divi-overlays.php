<?php
/*
Plugin Name: Divi Overlays
Plugin URL: https://divilife.com/
Description: Create unlimited popup overlays using the Divi Builder.
Version: 3.0.1
Author: Divi Life — Tim Strifler
Author URI: https://divilife.com

// This file includes code from Main WordPress Formatting API, licensed GPLv2 - https://wordpress.org/about/gpl/
*/

// Make sure we don't expose any info if called directly or may someone integrates this plugin in a theme
if ( class_exists('DiviOverlays') || !defined('ABSPATH') || !function_exists( 'add_action' ) ) {
	
	return;
}

$all_plugins = apply_filters( 'active_plugins', get_option( 'active_plugins' ) );

$current_theme = wp_get_theme();

if ( ( $current_theme->get( 'Name' ) !== 'Divi' && $current_theme->get( 'Template' ) !== 'Divi' ) 
	&& ( $current_theme->get( 'Name' ) !== 'Extra' && $current_theme->get( 'Template' ) !== 'Extra' )
	&& apply_filters( 'divi_ghoster_ghosted_theme', get_option( 'agsdg_ghosted_theme' ) ) !== 'Divi' ) {
	
	if ( stripos( implode( $all_plugins ), 'divi-builder.php' ) === false ) {
		
		function dov_divibuilder_required() {
			
			$class = 'notice notice-error';
			$message = __( 'Divi Overlays requires plugin: Divi Builder', 'DiviOverlays' );
			
			printf( '<div class="%1$s"><p>%2$s</p></div>', esc_attr( $class ), esc_html( $message ) );
		}
		add_action( 'admin_notices', 'dov_divibuilder_required' );
		
		return;
	}
}

if ( ! defined( 'DOV_VERSION' ) ) {
	
	define( 'DOV_VERSION', '3.0.1');
}

if ( ! defined( 'DOV_SERVER_TIMEZONE' ) ) {
	
	define( 'DOV_SERVER_TIMEZONE', 'UTC');
}

if ( ! defined( 'DOV_SCHEDULING_DATETIME_FORMAT' ) ) {
	
	define( 'DOV_SCHEDULING_DATETIME_FORMAT', 'm\/d\/Y g:i A');
}

if ( ! defined( 'DOV_PLUGIN_BASENAME' ) ) {
	
	define( 'DOV_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );
}

if ( ! defined( 'DOV_PLUGIN_URL' ) ) {
	
	define( 'DOV_PLUGIN_URL', plugin_dir_url( __FILE__ ));
}

if ( ! defined( 'DOV_PLUGIN_DIR' ) ) {
	
	define( 'DOV_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
}

if ( ! defined( 'DOV_CUSTOM_POST_TYPE' ) ) {
	
	define( 'DOV_CUSTOM_POST_TYPE', 'divi_overlay' );
}


register_deactivation_hook( __FILE__, 'flush_rewrite_rules' );
register_activation_hook( __FILE__, 'divi_overlay_flush_rewrites' );
function divi_overlay_flush_rewrites() {
	
	DiviOverlays::register_cpt();
	flush_rewrite_rules();
	
	add_option( 'divi-overlays-activated', 'yes' );
}

require_once( DOV_PLUGIN_DIR . '/class.divi-overlays.php' );
add_action( 'init', array( 'DiviOverlays', 'init' ) );

if ( is_admin() ) {

	$edd_updater = DOV_PLUGIN_DIR . 'updater.php';
	$edd_updater_admin = DOV_PLUGIN_DIR . 'updater-admin.php';

	if ( file_exists( $edd_updater ) && file_exists( $edd_updater_admin ) ) {

		// Load the API Key library if it is not already loaded
		if ( ! class_exists( 'edd_divioverlays' ) ) {
			
			require_once( $edd_updater );
			require_once( $edd_updater_admin );
		}
		
		define( 'DOV_UPDATER', TRUE );
	}
	else {
		
		define( 'DOV_UPDATER', FALSE );
	}
}

function is_divioverlays_license_page() {
	
	if ( isset( $_SERVER['REQUEST_URI'] ) ) {
		
		$referer = sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) );
		
		$on_license_page = strpos( $referer, 'page=dovs-settings' );
		
		return $on_license_page;
	}
}

if ( is_admin() ) {
	
	require_once( DOV_PLUGIN_DIR . '/divi-overlays.admin.php' );
}


function dov_is_divi_builder_enabled() {
	
	// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	if ( isset( $_GET['et_fb'] ) ) {
		
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$divi_builder_enabled = sanitize_text_field( wp_unslash( $_GET['et_fb'] ) );
		
		// is divi theme builder ?
		if ( $divi_builder_enabled === '1' ) {
			
			return TRUE;
		}
	}
	
	return FALSE;
}


function dov_is_builder_editor() {
	
	if ( function_exists( 'et_fb_enqueue_bundle' ) ) {
		
		$is_builder_editor = false;

		if ( isset( $_GET['et_fb'] ) && current_user_can( 'edit_posts' ) ) {// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			
			$is_builder_editor = et_pb_is_allowed( 'use_visual_builder' );
		}
		
		return $is_builder_editor;
	}
}

function showOverlay( $overlay_id = NULL ) {
	
	if ( dov_is_divi_builder_enabled() ) {
		
		return;
	}
	
	global $wp_embed;
	
    if ( !is_numeric( $overlay_id ) )
        return NULL;
	
	$overlay_id = (int) $overlay_id;
    
	$post_data = get_post( $overlay_id );
	
	$post_content = $post_data->post_content;
	
	$at_type = get_post_meta( $overlay_id, 'overlay_automatictrigger', true );
	
	/* Scheduling */
	if ( $at_type !== '' && $at_type !== '0' ) {
			
		$enable_scheduling = get_post_meta( $post_data->ID, 'do_enable_scheduling' );
		
		if( !isset( $enable_scheduling[0] ) ) {
			
			$enable_scheduling[0] = 0;
		}
		
		$enable_scheduling = (int) $enable_scheduling[0];
		
		if ( $enable_scheduling ) {
			
			$timezone = DOV_SERVER_TIMEZONE;
			
			$timezone = new DateTimeZone( $timezone );
			
			$wp_timezone = wp_timezone();
			
			if ( $wp_timezone !== false ) {
				
				$timezone = $wp_timezone;
			}
			
			$date_now = current_datetime();
			
			// Start & End Time
			if ( $enable_scheduling == 1 ) {
				
				$date_start = get_post_meta( $post_data->ID, 'do_date_start', true );
				$date_end = get_post_meta( $post_data->ID, 'do_date_end', true );
				
				$date_start = doConvertDateToUserTimezone( $date_start );
				$date_start = new DateTimeImmutable( $date_start, $timezone );
				
				if ( $date_start >= $date_now ) {
					
					return;
				}
				
				if ( $date_end != '' ) {
				
					$date_end = doConvertDateToUserTimezone( $date_end );
					$date_end = new DateTimeImmutable( $date_end, $timezone );
					
					if ( $date_end <= $date_now ) {
						
						return;
					}
				}
			}
			
			
			// Recurring Scheduling
			if ( $enable_scheduling == 2 ) {
				
				$wNum = $date_now->format( 'N' );
				
				$is_today = get_post_meta( $post_data->ID, 'divioverlays_scheduling_daysofweek_' . $wNum );
				
				if ( isset( $is_today[0] ) && $is_today[0] == 1 ) {
					
					$is_today = $is_today[0];
					
					$time_start = get_post_meta( $post_data->ID, 'do_time_start', true );
					$time_end = get_post_meta( $post_data->ID, 'do_time_end', true );
					$schedule_start = null;
					$schedule_end = null;
					
					if ( $time_start != '' ) {
						
						$time_start_24 = gmdate( 'H:i', strtotime( $time_start ) );
						$time_start_24 = explode( ':', $time_start_24 );
						$time_start_now = new DateTimeImmutable( 'now', $timezone );
						$schedule_start = $time_start_now->setTime( $time_start_24[0], $time_start_24[1], 0 );
					}
					
					if ( $time_end != '' ) {
						
						$time_end_24 = gmdate( 'H:i', strtotime( $time_end ) );
						$time_end_24 = explode( ':', $time_end_24 );
						$time_end_now = new DateTimeImmutable( 'now', $timezone );
						$schedule_end = $time_end_now->setTime( $time_end_24[0], $time_end_24[1], 0 );
					}
					
					if ( ( $time_start != '' && $time_end != '' && $schedule_start >= $date_now && $schedule_end > $date_now )
						|| ( $time_start != '' && $time_end != '' && $schedule_start <= $date_now && $schedule_end < $date_now )
						|| ( $time_start != '' && $time_end == '' && $schedule_start <= $date_now )
						|| ( $time_start == '' && $time_end != '' && $schedule_end < $date_now )
						) {
						
						return;
					}
				} else {
					
					return;
				}
			}
		}
	}
	/* End Scheduling */
	
	$et_pb_divioverlay_effect_entrance = get_post_meta( $post_data->ID, 'et_pb_divioverlay_effect_entrance', true );
	$et_pb_divioverlay_effect_exit = get_post_meta( $post_data->ID, 'et_pb_divioverlay_effect_exit', true );
	$dov_effect_entrance_speed = get_post_meta( $post_data->ID, 'dov_effect_entrance_speed', true );
	$dov_effect_exit_speed = get_post_meta( $post_data->ID, 'dov_effect_exit_speed', true );
	
	if ( !empty( $dov_effect_entrance_speed ) ) {
		
		$dov_effect_entrance_speed = $dov_effect_entrance_speed;
		
	} else {
		
		$dov_effect_entrance_speed = 1;
	}
	
	if ( !empty( $dov_effect_exit_speed ) ) {
		
		$dov_effect_exit_speed = $dov_effect_exit_speed;
		
	} else {
		
		$dov_effect_exit_speed = 1;
	}
	
	$bgcolor = get_post_meta( $post_data->ID, 'post_overlay_bg_color', true );
	
	$do_enablebgblur = get_post_meta( $post_data->ID, 'do_enablebgblur' );
	if ( isset( $do_enablebgblur[0] ) ) {
		
		$do_enablebgblur = $do_enablebgblur[0];
		
	} else {
		
		$do_enablebgblur = 0;
	}
	
	$preventscroll = get_post_meta( $post_data->ID, 'post_do_preventscroll' );
	if ( isset( $preventscroll[0] ) ) {
		
		$preventscroll = $preventscroll[0];
		
	} else {
		
		$preventscroll = 0;
	}
	
	
	$enableesckey = get_post_meta( $post_data->ID, 'do_enableesckey' );
	if ( isset( $enableesckey[0] ) ) {
		
		$enableesckey = $enableesckey[0];
		
	} else {
		
		$enableesckey = 1;
	}
	
	
	$closebtninoverlay = get_post_meta( $post_data->ID, 'closebtninoverlay' );
	if ( isset( $closebtninoverlay[0] ) ) {
		
		$closebtninoverlay = $closebtninoverlay[0];
		
	} else {
		
		$closebtninoverlay = 0;
	}
	
	$closebtn_pointoforigin = get_post_meta( $post_data->ID, 'dov_closebtn_pointoforigin', true );
	$closebtn_pointoforigin_tablet = get_post_meta( $post_data->ID, 'dov_closebtn_pointoforigin_tablet', true );
	$closebtn_pointoforigin_phone = get_post_meta( $post_data->ID, 'dov_closebtn_pointoforigin_phone', true );
	
	if ( !isset( $closebtn_pointoforigin ) || $closebtn_pointoforigin === '' || $closebtn_pointoforigin === 0 ) { 
	
		$closebtn_pointoforigin = 'top_right';
	}
	
	if ( !isset( $closebtn_pointoforigin_tablet ) || $closebtn_pointoforigin_tablet === '' || $closebtn_pointoforigin_tablet === 0 ) { 
	
		$closebtn_pointoforigin_tablet = 'top_right';
	}
	
	if ( !isset( $closebtn_pointoforigin_phone ) || $closebtn_pointoforigin_phone === '' || $closebtn_pointoforigin_phone === 0 ) { 
	
		$closebtn_pointoforigin_phone = 'top_right';
	}
	
	$closebtnclickingoutside = get_post_meta( $post_data->ID, 'post_do_closebtnclickingoutside' );
	if ( isset( $closebtnclickingoutside[0] ) ) {
		
		$closebtnclickingoutside = $closebtnclickingoutside[0];
		
	} else {
		
		$closebtnclickingoutside = 0;
	}
	
	$hideclosebtn = get_post_meta( $post_data->ID, 'post_do_hideclosebtn' );
	if ( isset( $hideclosebtn[0] ) ) {
		
		$hideclosebtn = $hideclosebtn[0];
		
	} else {
		
		$hideclosebtn = 0;
	}
	
		$customizeclosebtn = get_post_meta( $post_data->ID, 'post_do_customizeclosebtn' );
		if( !isset( $customizeclosebtn[0] ) ) {
			
			$customizeclosebtn[0] = '0';
		}
		
		$close_cookie = get_post_meta( $post_data->ID, 'dov_closebtn_cookie', true );
		if( !isset( $close_cookie ) ) {
			
			$close_cookie = 1;
		}
		
		$enableajax = (int) get_post_meta( $post_data->ID, 'do_enableajax', true );
		if( !isset( $enableajax ) ) {
			
			$enableajax = 0;
		}
		
		if ( $enableajax === 1 ) {
			
			$output = '';
			
		} else {
			
			if ( !et_pb_is_pagebuilder_used( get_the_ID() ) ) {
				
				$post_content = do_shortcode( $post_content );
				
			} else {
			
				$post_content = apply_filters( 'the_content', $post_content );
			}
			
			$post_content = et_builder_get_builder_content_opening_wrapper() . $post_content . et_builder_get_builder_content_closing_wrapper();
			
			$output = $post_content;
			// Monarch fix: Remove Divi Builder main section class and add it later with JS
			$output = str_replace( 'et_pb_section ', 'dov_dv_section ', $output );
		}
		
		$pointoforigin = get_post_meta( $post_data->ID, 'dov_pointoforigin', true );
		$pointoforigin_tablet = get_post_meta( $post_data->ID, 'dov_pointoforigin_tablet', true );
		$pointoforigin_phone = get_post_meta( $post_data->ID, 'dov_pointoforigin_phone', true );
		
		$pointoforigin_tags = ' data-pointoforigin="' . $closebtn_pointoforigin . '" data-pointoforigin_tablet="' . $closebtn_pointoforigin_tablet . '" data-pointoforigin_phone="' . $closebtn_pointoforigin_phone . '"';
		
		print et_core_esc_previously( getOverlayDesignStyles( $post_data->ID ) );
		
		print et_core_esc_previously( getCustomizeCloseButtonStyles( $post_data->ID ) );
	?>
	<style class="divi-overlay-styles-<?php print et_core_esc_previously( $overlay_id ) ?>"></style>
	<div id="divi-overlay-container-<?php print et_core_esc_previously( $overlay_id ) ?>" class="overlay-container" aria-hidden="true">
	<div class="divioverlay-bg animate__animated"></div>
		<div id="overlay-<?php print et_core_esc_previously( $post_data->ID ) ?>" class="divioverlay" style="display:none;"
		data-bgcolor="<?php print et_core_esc_previously( $bgcolor ) ?>" data-enablebgblur="<?php print et_core_esc_previously( $do_enablebgblur ) ?>" data-preventscroll="<?php print et_core_esc_previously( $preventscroll ) ?>" data-enableesckey="<?php print et_core_esc_previously( $enableesckey ) ?>"	
		data-scrolltop="" data-cookie="<?php print et_core_esc_previously( $close_cookie ) ?>" data-enableajax="<?php print et_core_esc_previously( $enableajax ) ?>" data-contentloaded="0" data-animationin="<?php print et_core_esc_previously( $et_pb_divioverlay_effect_entrance ) ?>" data-animationout="<?php print et_core_esc_previously( $et_pb_divioverlay_effect_exit ) ?>" data-animationspeedin="<?php print et_core_esc_previously( $dov_effect_entrance_speed ) ?>" data-animationspeedout="<?php print et_core_esc_previously( $dov_effect_exit_speed ) ?>" aria-modal="true" role="dialog" aria-labelledby="overlay-labelledby-<?php print et_core_esc_previously( $post_data->ID ) ?>" aria-describedby="overlay-describedby-<?php print et_core_esc_previously( $post_data->ID ) ?>" data-pointoforigin="<?php print et_core_esc_previously( $pointoforigin ) ?>" data-pointoforigin_tablet="<?php print et_core_esc_previously( $pointoforigin_tablet ) ?>" data-pointoforigin_phone="<?php print et_core_esc_previously( $pointoforigin_phone ) ?>" data-id="<?php print et_core_esc_previously( $overlay_id ) ?>" data-closeclickingoutside="<?php print et_core_esc_previously( $closebtnclickingoutside ) ?>">
			<h1 class="screen-reader-text" id="overlay-labelledby-<?php print et_core_esc_previously( $post_data->ID ) ?>"><?php esc_html_e( 'Dialog window', 'DiviOverlays' ) ?></h1>
			
			<?php if ( $closebtninoverlay == 0 && $hideclosebtn == 0 ) { ?>
			<div class="overlay-close-container"<?php print et_core_esc_previously( $pointoforigin_tags ) ?>>
				<a href="javascript:;" class="overlay-close overlay-customclose-btn-<?php print et_core_esc_previously( $overlay_id ) ?>" aria-label="Close Overlay modal" title="Close dialog window" role="button"><span class="<?php if ( $customizeclosebtn[0] == 1 ) { ?>custom_btn<?php } ?>">&times;</span></a>
			</div>
			<?php } ?>
			
			<div class="overlay-entry-content">
				<div id="overlay-describedby-<?php print et_core_esc_previously( $post_data->ID ) ?>">
				
					<div class="post-content-wrapper">
					
						<div class="post-content-animation">
						
						<?php if ( $closebtninoverlay == 1 && $hideclosebtn == 0 ) { ?>
						<div class="overlay-close-container"<?php print et_core_esc_previously( $pointoforigin_tags ) ?>>
							<a href="javascript:;" class="overlay-close overlay-customclose-btn-<?php print et_core_esc_previously( $overlay_id ) ?>" aria-label="Close Overlay modal" title="Close dialog window" role="button"><span class="<?php if ( $customizeclosebtn[0] == 1 ) { ?>custom_btn<?php } ?>">&times;</span></a>
						</div>
						<?php } ?>
						
						<?php 
							
							print et_core_esc_previously( $output );
							
						?>
						</div>
					</div>
				</div>
			</div>
			
		</div>
	</div>
	<?php 
}


function getOverlayDesignStyles( $post_id ) {
	
	$output = '';
	
	$responsive_maximum_width = DOV_RESPONSIVE_MAX_WIDTH;
	$responsive_tablet_width = DOV_RESPONSIVE_TABLET_WIDTH;
	$responsive_phone_width = DOV_RESPONSIVE_PHONE_WIDTH;
	$responsive_min_width = DOV_RESPONSIVE_MIN_WIDTH;
	
	$output .= '<style>';
	
	$minwidth = get_post_meta( $post_id, 'dov_minwidth', true );
	$width = get_post_meta( $post_id, 'dov_width', true );
	$maxwidth = get_post_meta( $post_id, 'dov_maxwidth', true );
	$minheight = get_post_meta( $post_id, 'dov_minheight', true );
	$height = get_post_meta( $post_id, 'dov_height', true );
	$maxheight = get_post_meta( $post_id, 'dov_maxheight', true );
	
	$pointoforigin = get_post_meta( $post_id, 'dov_pointoforigin', true );
	
	$horizontal_offset = get_post_meta( $post_id, 'dov_horizontal_offset', true );
	$vertical_offset = get_post_meta( $post_id, 'dov_vertical_offset', true );
	$horizontal_offset = get_post_meta( $post_id, 'dov_horizontal_offset', true );
	
	$output .= '@media (min-width: ' . ( $responsive_tablet_width + 1 ) . 'px){';
	
		$output .= '#divi-overlay-container-' . et_core_esc_previously( $post_id ) . ' .post-content-animation > .et-boc {';
		
			if ( ( isset( $vertical_offset ) && $vertical_offset !== '' )
				|| ( isset( $horizontal_offset ) && $horizontal_offset !== '' ) ) {
				
				$output .= 'position:relative !important; ';
			}
		
			if ( isset( $vertical_offset ) && $vertical_offset !== '' ) {
				
				$output .= 'top:' . esc_attr( $vertical_offset ) . ' !important; ';
			}
			
			if ( isset( $horizontal_offset ) && $horizontal_offset !== '' ) {
				
				$output .= 'left:' . esc_attr( $horizontal_offset ) . ' !important; ';
			}
		
		$output .= '}';
	
		$output .= '#divi-overlay-container-' . et_core_esc_previously( $post_id ) . ' .overlay-entry-content > div {';
		
			if ( isset( $pointoforigin ) 
				&& $pointoforigin !== 'center_center'
				&& $pointoforigin !== 'top_center'
				&& $pointoforigin !== 'bottom_center' ) {
		
				if ( isset( $minwidth ) && $minwidth !== '' ) {
					
					$output .= 'min-width:' . esc_attr( $minwidth ) . ' !important; ';
				}
				
				if ( isset( $width ) && $width !== '' ) {
					
					$output .= 'width:' . esc_attr( $width ) . ' !important; ';
				}
				
				if ( isset( $maxwidth ) && $maxwidth !== '' ) {
					
					$output .= 'max-width:' . esc_attr( $maxwidth ) . ' !important; ';
				}
			}
			
			if ( isset( $pointoforigin ) && $pointoforigin !== '' ) {
				
				$output .= 'position:absolute !important; ';
			}
			
			if ( isset( $minheight ) && $minheight !== '' ) {
				
				$output .= 'min-height:' . esc_attr( $minheight ) . ' !important; ';
			}
			
			if ( isset( $height ) && $height !== '' ) {
				
				$output .= 'height:' . esc_attr( $height ) . ' !important; ';
			}
			
			if ( isset( $maxheight ) && $maxheight !== '' ) {
				
				$output .= 'max-height:' . esc_attr( $maxheight ) . ' !important; ';
			}
		
		$output .= '}';
		
		$output .= '#divi-overlay-container-' . et_core_esc_previously( $post_id ) . ' .overlay-entry-content {';
		
			if ( isset( $pointoforigin ) && (
				$pointoforigin === 'center_center'
				|| $pointoforigin === 'center_left'
				|| $pointoforigin === 'center_right' ) ) {
					
				$output .= 'align-items: center;';
			
			}
			
			if ( isset( $pointoforigin ) && (
				$pointoforigin === 'center_center'
				|| $pointoforigin === 'top_center'
				|| $pointoforigin === 'bottom_center' ) ) {
			
				$output .= 'margin-right:auto;margin-left:auto;';
		
				if ( isset( $minwidth ) && $minwidth !== '' ) {
					
					$output .= 'min-width:' . esc_attr( $minwidth ) . ' !important; ';
				}
				
				if ( isset( $width ) && $width !== '' ) {
					
					$output .= 'width:' . esc_attr( $width ) . ' !important; ';
				}
				
				if ( isset( $maxwidth ) && $maxwidth !== '' ) {
					
					$output .= 'max-width:' . esc_attr( $maxwidth ) . ' !important; ';
				}
			}
			
		$output .= '}';
		
	
	$output .= '}';
	
	
	$minwidth_tablet = get_post_meta( $post_id, 'dov_minwidth_tablet', true );
	$width_tablet = get_post_meta( $post_id, 'dov_width_tablet', true );
	$maxwidth_tablet = get_post_meta( $post_id, 'dov_maxwidth_tablet', true );
	$minheight_tablet = get_post_meta( $post_id, 'dov_minheight_tablet', true );
	$height_tablet = get_post_meta( $post_id, 'dov_height_tablet', true );
	$maxheight_tablet = get_post_meta( $post_id, 'dov_maxheight_tablet', true );
	
	$pointoforigin_tablet = get_post_meta( $post_id, 'dov_pointoforigin_tablet', true );
	
	$vertical_offset_tablet = get_post_meta( $post_id, 'dov_vertical_offset_tablet', true );
	$horizontal_offset_tablet = get_post_meta( $post_id, 'dov_horizontal_offset_tablet', true );
	
	if ( $vertical_offset_tablet === '' ) {
		
		$vertical_offset_tablet = 0;
	}
	
	if ( $horizontal_offset_tablet === '' ) {
		
		$horizontal_offset_tablet = 0;
	}
	
	$output .= '@media (max-width: ' . $responsive_tablet_width . 'px) and (min-width: ' . ($responsive_phone_width + 1) . 'px){';
	
		$output .= '#divi-overlay-container-' . et_core_esc_previously( $post_id ) . ' .post-content-animation > div {';
		
			$output .= 'padding: 0 20px; ';
		
		$output .= '}';
	
		$output .= '#divi-overlay-container-' . et_core_esc_previously( $post_id ) . ' .post-content-animation > .et-boc {';
		
			$output .= 'position:relative !important; ';
			
			$output .= 'top:' . esc_attr( $vertical_offset_tablet ) . ' !important; ';
			
			$output .= 'left:' . esc_attr( $horizontal_offset_tablet ) . ' !important; ';
		
		$output .= '}';
	
		$output .= '#divi-overlay-container-' . et_core_esc_previously( $post_id ) . ' .overlay-entry-content > div {';
		
			if ( isset( $pointoforigin_tablet ) 
				&& $pointoforigin_tablet !== 'center_center'
				&& $pointoforigin_tablet !== 'top_center'
				&& $pointoforigin_tablet !== 'bottom_center' ) {
		
				if ( isset( $minwidth_tablet ) && $minwidth_tablet !== '' ) {
					
					$output .= 'min-width:' . esc_attr( $minwidth_tablet ) . ' !important; ';
				}
				
				if ( isset( $width_tablet ) && $width_tablet !== '' ) {
					
					$output .= 'width:' . esc_attr( $width_tablet ) . ' !important; ';
				}
				
				if ( isset( $maxwidth_tablet ) && $maxwidth_tablet !== '' ) {
					
					$output .= 'max-width:' . esc_attr( $maxwidth_tablet ) . ' !important; ';
				}
			}
			
			if ( isset( $pointoforigin_tablet ) && $pointoforigin_tablet !== '' ) {
				
				$output .= 'position:absolute !important; ';
			}
			
			if ( isset( $minheight_tablet ) && $minheight_tablet !== '' ) {
				
				$output .= 'min-height:' . esc_attr( $minheight_tablet ) . ' !important; ';
			}
			
			if ( isset( $height_tablet ) && $height_tablet !== '' ) {
				
				$output .= 'height:' . esc_attr( $height_tablet ) . ' !important; ';
			}
			
			if ( isset( $maxheight_tablet ) && $maxheight_tablet !== '' ) {
				
				$output .= 'max-height:' . esc_attr( $maxheight_tablet ) . ' !important; ';
			}
		
		$output .= '}';
		
		$output .= '#divi-overlay-container-' . et_core_esc_previously( $post_id ) . ' .overlay-entry-content {';
		
			if ( isset( $pointoforigin_tablet ) && (
				$pointoforigin_tablet === 'center_center'
				|| $pointoforigin_tablet === 'center_left'
				|| $pointoforigin_tablet === 'center_right' ) ) {
					
				$output .= 'align-items: center;';
			
			}
			
			if ( isset( $pointoforigin_tablet ) && (
				$pointoforigin_tablet === 'center_center'
				|| $pointoforigin_tablet === 'top_center'
				|| $pointoforigin_tablet === 'bottom_center' ) ) {
			
				$output .= 'margin-right:auto;margin-left:auto;';
				
				if ( isset( $minwidth_tablet ) && $minwidth_tablet !== '' ) {
					
					$output .= 'min-width:' . esc_attr( $minwidth_tablet ) . ' !important; ';
				}
				
				if ( isset( $width_tablet ) && $width_tablet !== '' ) {
					
					$output .= 'width:' . esc_attr( $width_tablet ) . ' !important; ';
				}
				
				if ( isset( $maxwidth_tablet ) && $maxwidth_tablet !== '' ) {
					
					$output .= 'max-width:' . esc_attr( $maxwidth_tablet ) . ' !important; ';
				}
			}
			
		$output .= '}';
	
	$output .= '}';
	
	
	$minwidth_phone = get_post_meta( $post_id, 'dov_minwidth_phone', true );
	$width_phone = get_post_meta( $post_id, 'dov_width_phone', true );
	$maxwidth_phone = get_post_meta( $post_id, 'dov_maxwidth_phone', true );
	$minheight_phone = get_post_meta( $post_id, 'dov_minheight_phone', true );
	$height_phone = get_post_meta( $post_id, 'dov_height_phone', true );
	$maxheight_phone = get_post_meta( $post_id, 'dov_maxheight_phone', true );
	
	$pointoforigin_phone = get_post_meta( $post_id, 'dov_pointoforigin_phone', true );
	
	$vertical_offset_phone = get_post_meta( $post_id, 'dov_vertical_offset_phone', true );
	$horizontal_offset_phone = get_post_meta( $post_id, 'dov_horizontal_offset_phone', true );
	
	if ( $vertical_offset_phone === '' ) {
		
		$vertical_offset_phone = 0;
	}
	
	if ( $horizontal_offset_phone === '' ) {
		
		$horizontal_offset_phone = 0;
	}
	
	$output .= '@media (max-width: ' . $responsive_phone_width . 'px){';
	
		$output .= '#divi-overlay-container-' . et_core_esc_previously( $post_id ) . ' .post-content-animation > div {';
		
			$output .= 'padding: 0 20px; ';
		
		$output .= '}';
	
		$output .= '#divi-overlay-container-' . et_core_esc_previously( $post_id ) . ' .post-content-animation > .et-boc {';
		
			$output .= 'position:relative !important; ';
		
			$output .= 'top:' . esc_attr( $vertical_offset_phone ) . ' !important; ';
			
			$output .= 'left:' . esc_attr( $horizontal_offset_phone ) . ' !important; ';
		
		$output .= '}';
	
		$output .= '#sidebar-overlay #divi-overlay-container-' . et_core_esc_previously( $post_id ) . ' .overlay-entry-content > div {';
		
			if ( isset( $pointoforigin_phone ) 
				&& $pointoforigin_phone !== 'center_center'
				&& $pointoforigin_phone !== 'top_center'
				&& $pointoforigin_phone !== 'bottom_center' ) {
		
				if ( isset( $minwidth_phone ) && $minwidth_phone !== '' ) {
					
					$output .= 'min-width:' . esc_attr( $minwidth_phone ) . ' !important; ';
				}
				
				if ( isset( $width_phone ) && $width_phone !== '' ) {
					
					$output .= 'width:' . esc_attr( $width_phone ) . ' !important; ';
				}
				
				if ( isset( $maxwidth_phone ) && $maxwidth_phone !== '' ) {
					
					$output .= 'max-width:' . esc_attr( $maxwidth_phone ) . ' !important; ';
				}
			}
			
			if ( isset( $pointoforigin_phone ) && $pointoforigin_phone !== '' ) {
				
				$output .= 'position:absolute !important; ';
			}
			
			if ( isset( $minheight_phone ) && $minheight_phone !== '' ) {
				
				$output .= 'min-height:' . esc_attr( $minheight_phone ) . ' !important; ';
			}
			
			if ( isset( $height_phone ) && $height_phone !== '' ) {
				
				$output .= 'height:' . esc_attr( $height_phone ) . ' !important; ';
			}
			
			if ( isset( $maxheight_phone ) && $maxheight_phone !== '' ) {
				
				$output .= 'max-height:' . esc_attr( $maxheight_phone ) . ' !important; ';
			}
		
		$output .= '}';
		
		$output .= '#divi-overlay-container-' . et_core_esc_previously( $post_id ) . ' .overlay-entry-content {';
		
			if ( isset( $pointoforigin_phone ) && (
				$pointoforigin_phone === 'center_center'
				|| $pointoforigin_phone === 'center_left'
				|| $pointoforigin_phone === 'center_right' ) ) {
					
				$output .= 'align-items: center;';
			
			}
			
			if ( isset( $pointoforigin_phone ) && (
				$pointoforigin_phone === 'center_center'
				|| $pointoforigin_phone === 'top_center'
				|| $pointoforigin_phone === 'bottom_center' ) ) {
			
				$output .= 'margin-right:auto;margin-left:auto;';
				
				if ( isset( $minwidth_phone ) && $minwidth_phone !== '' ) {
					
					$output .= 'min-width:' . esc_attr( $minwidth_phone ) . ' !important; ';
				}
				
				if ( isset( $width_phone ) && $width_phone !== '' ) {
					
					$output .= 'width:' . esc_attr( $width_phone ) . ' !important; ';
				}
				
				if ( isset( $maxwidth_phone ) && $maxwidth_phone !== '' ) {
					
					$output .= 'max-width:' . esc_attr( $maxwidth_phone ) . ' !important; ';
				}
			}
			
		$output .= '}';
	
	$output .= '}';
	
	$output .= '</style>';
	
	return $output;
}


function getCustomizeCloseButtonStyles( $post_id ) {
	
	$output = '';

	$customizeclosebtn = get_post_meta( $post_id, 'post_do_customizeclosebtn' );
	if ( isset( $customizeclosebtn[0] ) ) {
		
		$customizeclosebtn = $customizeclosebtn[0];
		
	} else {
		
		$customizeclosebtn = false;
	}
	
	$responsive_maximum_width = DOV_RESPONSIVE_MAX_WIDTH;
	$responsive_tablet_width = DOV_RESPONSIVE_TABLET_WIDTH;
	$responsive_phone_width = DOV_RESPONSIVE_PHONE_WIDTH;
	$responsive_min_width = DOV_RESPONSIVE_MIN_WIDTH;
	
	$pointoforigin = get_post_meta( $post_id, 'dov_closebtn_pointoforigin', true );
	
	if ( isset( $pointoforigin ) ) {
		
		$pointoforigin = $pointoforigin;
		
	} else {
		
		$pointoforigin = 'top_right';
	}
	
	$pointoforigin_tablet = get_post_meta( $post_id, 'dov_closebtn_pointoforigin_tablet', true );
	
	if ( isset( $pointoforigin_tablet ) ) {
		
		$pointoforigin_tablet = $pointoforigin_tablet;
		
	} else {
		
		$pointoforigin_tablet = 'top_right';
	}
	
	$pointoforigin_phone = get_post_meta( $post_id, 'dov_closebtn_pointoforigin_phone', true );
	
	if ( isset( $pointoforigin_phone ) ) {
		
		$pointoforigin_phone = $pointoforigin_phone;
		
	} else {
		
		$pointoforigin_phone = 'top_right';
	}
		
	$output .= '<style>';
	
	if ( $customizeclosebtn ) {
	
		$color = get_post_meta( $post_id, 'post_doclosebtn_text_color', true );
		$color_tablet = get_post_meta( $post_id, 'post_doclosebtn_text_color_tablet', true );
		$color_phone = get_post_meta( $post_id, 'post_doclosebtn_text_color_phone', true );
		
		$bgcolor = get_post_meta( $post_id, 'post_doclosebtn_bg_color', true );
		$bgcolor_tablet = get_post_meta( $post_id, 'post_doclosebtn_bg_color_tablet', true );
		$bgcolor_phone = get_post_meta( $post_id, 'post_doclosebtn_bg_color_phone', true );
		
		$fontsize = get_post_meta( $post_id, 'post_doclosebtn_fontsize', true );
		$fontsize_tablet = get_post_meta( $post_id, 'post_doclosebtn_fontsize_tablet', true );
		$fontsize_phone = get_post_meta( $post_id, 'post_doclosebtn_fontsize_phone', true );
		
		$borderradius = get_post_meta( $post_id, 'post_doclosebtn_borderradius', true );
		$borderradius_tablet = get_post_meta( $post_id, 'post_doclosebtn_borderradius_tablet', true );
		$borderradius_phone = get_post_meta( $post_id, 'post_doclosebtn_borderradius_phone', true );
		
		$padding = get_post_meta( $post_id, 'post_doclosebtn_padding', true );
		$padding_tablet = get_post_meta( $post_id, 'post_doclosebtn_padding_tablet', true );
		$padding_phone = get_post_meta( $post_id, 'post_doclosebtn_padding_phone', true );
	}
	
	$vertical_offset = get_post_meta( $post_id, 'dov_closebtn_pointoforigindov_vertical_offset', true );
	$vertical_offset_tablet = get_post_meta( $post_id, 'dov_closebtn_pointoforigindov_vertical_offset_tablet', true );
	$vertical_offset_phone = get_post_meta( $post_id, 'dov_closebtn_pointoforigindov_vertical_offset_phone', true );
	
	$horizontal_offset = get_post_meta( $post_id, 'dov_closebtn_pointoforigindov_horizontal_offset', true );
	$horizontal_offset_tablet = get_post_meta( $post_id, 'dov_closebtn_pointoforigindov_horizontal_offset_tablet', true );
	$horizontal_offset_phone = get_post_meta( $post_id, 'dov_closebtn_pointoforigindov_horizontal_offset_phone', true );
	
	$output .= '@media (min-width: ' . ( $responsive_tablet_width + 1 ) . 'px){';
	
		$output .= '.overlay-customclose-btn-' . et_core_esc_previously( $post_id ) . ' {';
			
			if ( $customizeclosebtn ) {
				
				if ( !isset( $padding ) || $padding === '' || $padding == 0 ) { $padding = '0px'; }
				$output .= 'padding:' . esc_attr( $padding ) . ' !important; ';
				
				if ( !isset( $borderradius ) || $borderradius === '' || $borderradius == 0 ) { $borderradius = '50%'; }
				$output .= '-moz-border-radius:' . esc_attr( $borderradius ) . ' !important; ';
				$output .= '-webkit-border-radius:' . esc_attr( $borderradius ) . ' !important; ';
				$output .= '-khtml-border-radius:' . esc_attr( $borderradius ) . ' !important; ';
				$output .= 'border-radius:' . esc_attr( $borderradius ) . ' !important; ';
				
				if ( !isset( $fontsize ) || $fontsize === '' ) { $fontsize = '25px'; }
				$output .= 'font-size:' . esc_attr( $fontsize ) . ' !important; ';
				
				if ( !isset( $bgcolor ) || $bgcolor === '' ) { $bgcolor = 'transparent'; }
				$output .= 'background-color:' . esc_attr( $bgcolor ) . ' !important; ';
				
				$output .= 'color:' . esc_attr( $color ) . ' !important; ';
			}
			
			
			if ( isset( $horizontal_offset ) && $horizontal_offset !== '' ) {
			
				$output .= 'left:' . esc_attr( $horizontal_offset ) . ' !important; ';
			}
			
			if ( isset( $vertical_offset ) && $vertical_offset !== '' ) {
				
				$output .= 'top:' . esc_attr( $vertical_offset ) . ' !important; ';
			}
		
		$output .= '}';
		
		$output .= getCloseButtonOriginPositionStyles( '', $pointoforigin );
	
	$output .= '}';
	
	
	$output .= '@media (max-width: ' . $responsive_tablet_width . 'px) and (min-width: ' . ($responsive_phone_width + 1) . 'px){';
	
		$output .= '.overlay-customclose-btn-' . et_core_esc_previously( $post_id ) . ' {';
	
			if ( $customizeclosebtn ) {
				
				if ( !isset( $padding_tablet ) || $padding_tablet === '' || $padding_tablet == 0 ) { $padding_tablet = '0px'; }
				$output .= 'padding:' . esc_attr( $padding_tablet ) . ' !important; ';
				
				if ( !isset( $borderradius_tablet ) || $borderradius_tablet === '' || $borderradius_tablet == 0 ) { $borderradius_tablet = '50%'; }
				$output .= '-moz-border-radius:' . esc_attr( $borderradius_tablet ) . ' !important; ';
				$output .= '-webkit-border-radius:' . esc_attr( $borderradius_tablet ) . ' !important; ';
				$output .= '-khtml-border-radius:' . esc_attr( $borderradius_tablet ) . ' !important; ';
				$output .= 'border-radius:' . esc_attr( $borderradius_tablet ) . ' !important; ';
				
				if ( !isset( $fontsize_tablet ) || $fontsize_tablet === '' ) { $fontsize_tablet = '25px'; }
				$output .= 'font-size:' . esc_attr( $fontsize_tablet ) . ' !important; ';
				
				if ( !isset( $bgcolor_tablet ) || $bgcolor_tablet === '' ) { $bgcolor_tablet = 'transparent'; }
				$output .= 'background-color:' . esc_attr( $bgcolor_tablet ) . ' !important; ';
				
				$output .= 'color:' . esc_attr( $color_tablet ) . ' !important; ';
			}
			
			if ( isset( $horizontal_offset_tablet ) && $horizontal_offset_tablet !== '' ) {
			
				$output .= 'left:' . esc_attr( $horizontal_offset_tablet ) . ' !important; ';
			}
			
			if ( isset( $vertical_offset_tablet ) && $vertical_offset_tablet !== '' ) {
			
				$output .= 'top:' . esc_attr( $vertical_offset_tablet ) . ' !important; ';
			}
		
		$output .= '}';
		
		$output .= getCloseButtonOriginPositionStyles( '_tablet', $pointoforigin_tablet );
		
	$output .= '}';
	
	
	$output .= '@media (max-width: ' . $responsive_phone_width . 'px){';
	
		$output .= '.overlay-customclose-btn-' . et_core_esc_previously( $post_id ) . ' {';
		
			if ( $customizeclosebtn ) {
				
				if ( !isset( $padding_phone ) || $padding_phone === '' || $padding_phone == 0 ) { $padding_phone = '0px'; }
				$output .= 'padding:' . esc_attr( $padding_phone ) . ' !important; ';
				
				if ( !isset( $borderradius_phone ) || $borderradius_phone === '' || $borderradius_phone == 0 ) { $borderradius_phone = '50%'; }
				$output .= '-moz-border-radius:' . esc_attr( $borderradius_phone ) . ' !important; ';
				$output .= '-webkit-border-radius:' . esc_attr( $borderradius_phone ) . ' !important; ';
				$output .= '-khtml-border-radius:' . esc_attr( $borderradius_phone ) . ' !important; ';
				$output .= 'border-radius:' . esc_attr( $borderradius_phone ) . ' !important; ';
				
				if ( !isset( $fontsize_phone ) || $fontsize_phone === '' ) { $fontsize_phone = '25px'; }
				$output .= 'font-size:' . esc_attr( $fontsize_phone ) . ' !important; ';
				
				if ( !isset( $bgcolor_phone ) || $bgcolor_phone === '' ) { $bgcolor_phone = 'transparent'; }
				$output .= 'background-color:' . esc_attr( $bgcolor_phone ) . ' !important; ';
				
				$output .= 'color:' . esc_attr( $color_phone ) . ' !important; ';
			}
			
			if ( isset( $horizontal_offset_phone ) && $horizontal_offset_phone !== '' ) {
			
				$output .= 'left:' . esc_attr( $horizontal_offset_phone ) . ' !important; ';
			}
			
			if ( isset( $vertical_offset_phone ) && $vertical_offset_phone !== '' ) {
			
				$output .= 'top:' . esc_attr( $vertical_offset_phone ) . ' !important; ';
			}
		
		$output .= '}';
		
		$output .= getCloseButtonOriginPositionStyles( '_phone', $pointoforigin_phone );
		
	$output .= '}';
	
	$output .= '</style>';
	
	return $output;
}

function getCloseButtonOriginPositionStyles( $device, $pointoforigin_ref ) {
	
	$css[ 'top_left' ] = 'body .divioverlay .overlay-close-container[data-pointoforigin' . $device . '="top_left"] {
		left:0;
		top:0;
		-webkit-transform: none;
		   -moz-transform: none;
			-ms-transform: none;
			 -o-transform: none;
				transform: none;
	}';

	$css[ 'top_center' ] = 'body .divioverlay .overlay-close-container[data-pointoforigin' . $device . '="top_center"] {
		right: auto;
		left: 50%;
		top: 0;
		-moz-transform: translate(-50%, -50%);
		-webkit-transform: translate(-50%, -50%);
		-o-transform: translate(-50%, -50%);
		transform: translate(-50%, 0%);
	}';

	$css[ 'top_right' ] = 'body .divioverlay .overlay-close-container[data-pointoforigin' . $device . '="top_right"] {
		left:auto;
		right:0;
		top:0;
		-webkit-transform: none;
		   -moz-transform: none;
			-ms-transform: none;
			 -o-transform: none;
				transform: none;
	}';

	$css[ 'bottom_left' ] = 'body .divioverlay .overlay-close-container[data-pointoforigin' . $device . '="bottom_left"] {
		left:0;
		top:auto;
		bottom:0;
		-webkit-transform: none;
		   -moz-transform: none;
			-ms-transform: none;
			 -o-transform: none;
				transform: none;
	}';
	
	$css[ 'bottom_center' ] = 'body .divioverlay .overlay-close-container[data-pointoforigin' . $device . '="bottom_center"] {
		right: auto;
		left: 50%;
		top: auto;
		bottom:0;
		-moz-transform: translate(-50%, -50%);
		-webkit-transform: translate(-50%, -50%);
		-o-transform: translate(-50%, -50%);
		transform: translate(-50%, 0%);
	}';
	
	$css[ 'bottom_right' ] = 'body .divioverlay .overlay-close-container[data-pointoforigin' . $device . '="bottom_right"] {
		left:auto;
		right:0;
		top:auto;
		bottom:0;
		-webkit-transform: none;
		   -moz-transform: none;
			-ms-transform: none;
			 -o-transform: none;
				transform: none;
	}';
	
	if ( !isset( $css[ $pointoforigin_ref ] ) ) {
		
		return;
	}
	
	$css = $css[ $pointoforigin_ref ];
	
	return $css;
}


function setHeightWidthSrc($s, $width, $height)
{
  return preg_replace(
    '@^<iframe\s*title="(.*)"\s*width="(.*)"\s*height="(.*)"\s*src="(.*?)"\s*(.*?)</iframe>$@s',
    '<iframe title="\1" width="' . $width . '" height="' . $height . '" src="\4?wmode=transparent" \5</iframe>',
    $s
  );
}


class DiviOverlaysCore {
	
	/**
	 * @var \WP_Filesystem_Base|null
	 */
	public static $wpfs;
	
	/**
	 * @var ET_Core_Data_Utils
	 */
	public static $data_utils;
	
	private static $slug = 'divioverlays-divi-custom-styles';
	
	private static $post_id;
	
	private static $ID = 0;
	
	private static $module_index = - 1;
	
	private static $filename;
	
	private static $file_extension;
	
	private static $cache_dir;
	
	public function __construct( $post_id = 0 ) {
		
		self::$ID = $post_id;
	}
	
	
	public static function start_module_index_override() {
		if ( ! class_exists( 'ET_Builder_Element' ) ) {
			return;
		}
		
		ET_Builder_Element::begin_theme_builder_layout( self::$ID );

		add_filter(
			'et_pb_module_shortcode_attributes',
			array( 'DiviOverlaysCore', 'do_module_index_override' ),
			10
		);
	}
	
	public static function end_module_index_override() {
		if ( ! class_exists( 'ET_Builder_Element' ) ) {
			return;
		}
		
		ET_Builder_Element::end_theme_builder_layout();
		
		remove_filter( 'et_pb_module_shortcode_attributes', array( 'DiviOverlaysCore', 'do_module_index_override' ), 11 );

		global $et_pb_predefined_module_index;

		unset( $et_pb_predefined_module_index );
	}
	
	public static function do_module_index_override( $value = '' ) {
		global $et_pb_predefined_module_index;

		self::$module_index ++;
		$et_pb_predefined_module_index = sprintf(
			'dov_%1$s_%2$s',
			self::$ID,
			self::$module_index
		);

		return $value;
	}

	public static function getRender( $post_id = NULL ) {
		
		try {
			
			if ( !is_numeric( $post_id ) ) {
				
				throw new InvalidArgumentException( 'DiviOverlaysCore::getRender > $post_id is not numeric');
			}
			
		} catch (Exception $e) {
		
			DiviOverlays::log( $e );
		}
		
		$post_data = get_post( $post_id );
		
		$content = $post_data->post_content;
		
		$render['post_data'] = $post_data;
		$render['output'] = $content;
		
		return $render;
	}

	public static function divi_display_options() {
		
		// Strip 'validate' key from settings as it is used server-side only.
		$default_settings = et_theme_builder_get_template_settings_options();
		foreach ( $default_settings as $group_key => $group ) {
			foreach ( $group['settings'] as $setting_key => $setting ) {
				unset( $default_settings[ $group_key ]['settings'][ $setting_key ]['validate'] );
			}
		}
		
		$groups = array( 
				'displayLocations' => $default_settings,
		);
		
		return $groups;
	}
}


if ( dov_is_divi_builder_enabled() && is_divioverlays_license_page() === false ) {
	
	add_action( 'wp_head', 'diviOverlaysFBassets', 1 );
}

function diviOverlaysFBassets() {
	
	if ( get_post_type() === DOV_CUSTOM_POST_TYPE
		|| ( isset( $_GET['custom_page_id'] ) && get_post_type( (int) $_GET['custom_page_id'] ) == DOV_CUSTOM_POST_TYPE ) ) {// phpcs:ignore WordPress.Security.NonceVerification.Recommended
	
		$version = DOV_VERSION;
		
		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
			
			$version .= '-' . time();
		}
		
		wp_register_style( 'divi-overlays-fb-css', DOV_PLUGIN_URL . 'assets/css/admin/admin-fb.css', array(), $version, 'all' );
		wp_enqueue_style( 'divi-overlays-fb-css' );
	}
}

// This is not required on post edit or license page
if ( !is_admin() && !dov_is_divi_builder_enabled() && is_divioverlays_license_page() === false ) {
	
	// phpcs:ignore WordPress.Security.NonceVerification
	if ( !isset( $_GET['divioverlays_id'] ) && !isset( $_GET['action'] ) ) {
		
		// Add WooCommerce class names on Divi Overlays posts which uses WooCommerce modules
		add_action( 'wp_head', 'checkAllDiviOverlays', 7 );
	}
	
	add_action( 'wp_head', 'addActionFiltersDiviOverlays', 11 );
}

function addActionFiltersDiviOverlays() {
	
	add_action( 'et_before_main_content', 'getAllDiviOverlays', 10 );
}


function checkAllDiviOverlays() {
	
	if ( class_exists( 'WooCommerce' ) ) {
		
		$classes = get_body_class();
		
		if ( !in_array( 'woocommerce', $classes ) 
			&& !in_array( 'woocommerce-page', $classes ) 
			&& function_exists( 'et_builder_has_woocommerce_module' ) ) {
			
			$overlays_in_current = getAllDiviOverlays( false );
			
			if ( is_array( $overlays_in_current ) && count( $overlays_in_current ) > 0 ) {
				
				foreach( $overlays_in_current as $overlay_id ) {
				
					$overlay_id = (int) $overlay_id;
					
					$post = get_post( $overlay_id );
					
					$has_wc_module = et_builder_has_woocommerce_module( $post->post_content );
					
					if ( $has_wc_module === true ) {
						
						add_filter( 'body_class', function( $classes ) {
							
							$classes[] = 'woocommerce';
							$classes[] = 'woocommerce-page';
							
							return $classes;
						} );
						
						// Load WooCommerce related scripts
						divi_overlays_load_wc_scripts();
						
						return;
					}
				}
			}
		}
	}
	
	// Support Slider Revolution by ThemePunch
	// Reset global vars to prevent any conflicts
	global $rs_material_icons_css, $rs_material_icons_css_parsed;
	
	$rs_material_icons_css = false;
	$rs_material_icons_css_parsed = false;
	
	
	// Support Gravity Forms Styles Pro
	// Restore dequeue Gravity Forms styles
	wp_enqueue_style( 'gforms_css' );
	wp_enqueue_style( 'gforms_reset_css' );
	wp_enqueue_style( 'gforms_formsmain_css' );
	wp_enqueue_style( 'gforms_ready_class_css' );
	wp_enqueue_style( 'gforms_browsers_css' );
	
	
	// Support Caldera Forms
	if ( class_exists( 'Caldera_Forms_Render_Assets' ) ) {
		
		Caldera_Forms_Render_Assets::register();
		
		wp_enqueue_script( 'cf-baldrick' );
		wp_enqueue_script( 'cf-ajax' );
	}
	
	add_filter( 'et_pb_set_style_selector', 'divi_overlays_et_pb_set_style_selector', 10, 2 );
}

function divi_overlays_et_pb_set_style_selector( $selector, $function_name ) {
	
	// Extra theme support
	if ( function_exists( 'extra_layout_used' ) ) {
	
		// List of module slugs that need to be prefixed
		$prefixed_modules = apply_filters( 'extra_layout_prefixed_selectors', array(
			'et_pb_section',
			'et_pb_row',
			'et_pb_row_inner',
			'et_pb_column',
		));
						
		// Prefixing selectors in Extra layout
		if ( extra_layout_used() || ( is_et_pb_preview() && isset( $_GET['is_extra_layout'] ) ) && in_array( $function_name, $prefixed_modules ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( 'default' === ET_Builder_Element::get_theme_builder_layout_type() ) {
				
				$extra_extra_db = '.et_extra_layout .et_pb_extra_column_main .et-db ';
				$extra_extra_without_db = '.et_extra_layout .et_pb_extra_column_main '; 
				
				if ( $extra_extra_db === substr( $selector, 0, 49 ) ) {
					
					$selector = str_replace( $extra_extra_db, $extra_extra_without_db, $selector );
				}
				
				$extra_extra_bodydb = '.et_extra_layout .et_pb_extra_column_main body.et-db ';
				$extra_extra_without_bodydb = '.et_extra_layout ';
				
				if ( $extra_extra_bodydb === substr( $selector, 0, 53 ) ) {
					
					$selector = str_replace( $extra_extra_bodydb, $extra_extra_without_bodydb, $selector );
				}
			}
		}
	}
	
	return $selector;
}
		

function divi_overlays_load_wc_scripts() {
	
	if ( ! class_exists( 'WC_Frontend_Scripts' ) && function_exists( 'et_core_is_fb_enabled' ) && ! et_core_is_fb_enabled() ) {
		return;
	}

	// Simply enqueue the scripts; All of them have been registered
	if ( 'yes' === get_option( 'woocommerce_enable_ajax_add_to_cart' ) ) {
		wp_enqueue_script( 'wc-add-to-cart' );
	}

	if ( current_theme_supports( 'wc-product-gallery-zoom' ) ) {
		wp_enqueue_script( 'zoom' );
	}
	if ( current_theme_supports( 'wc-product-gallery-slider' ) ) {
		wp_enqueue_script( 'flexslider' );
	}
	if ( current_theme_supports( 'wc-product-gallery-lightbox' ) ) {
		wp_enqueue_script( 'photoswipe-ui-default' );
		wp_enqueue_style( 'photoswipe-default-skin' );

		add_action( 'wp_footer', 'woocommerce_photoswipe' );
	}
	wp_enqueue_script( 'wc-single-product' );

	if ( 'geolocation_ajax' === get_option( 'woocommerce_default_customer_address' ) ) {
		$ua = strtolower( wc_get_user_agent() ); // Exclude common bots from geolocation by user agent.

		if ( ! strstr( $ua, 'bot' ) && ! strstr( $ua, 'spider' ) && ! strstr( $ua, 'crawl' ) ) {
			wp_enqueue_script( 'wc-geolocation' );
		}
	}

	wp_enqueue_script( 'woocommerce' );
	wp_enqueue_script( 'wc-cart-fragments' );

	// Enqueue style
	$wc_styles = WC_Frontend_Scripts::get_styles();

	foreach ( $wc_styles as $style_handle => $wc_style ) {
		if ( ! isset( $wc_style['has_rtl'] ) ) {
			$wc_style['has_rtl'] = false;
		}

		wp_enqueue_style( $style_handle, $wc_style['src'], $wc_style['deps'], $wc_style['version'], $wc_style['media'], $wc_style['has_rtl'] );
	}
}


function _do_avoidRenderTags( $content = NULL, $restore = false ) {
	
	if ( !$content ) {
		
		return '';
	}
	
	try {
		
		if ( !$restore ) {
			
			$content = str_replace( '[et_pb_video', '[et_pb_video_divioverlaystemp', $content );
			$content = str_replace( '[/et_pb_video]', '[/et_pb_video_divioverlaystemp]', $content );
			
			$content = str_replace( '[et_pb_contact_form', '[et_pb_contact_form_divioverlaystemp', $content );
			$content = str_replace( '[/et_pb_contact_form]', '[/et_pb_contact_form_divioverlaystemp]', $content );
			
			$content = str_replace( '[woocommerce_checkout]', '[woocommerce_checkout_divioverlaystemp]', $content );
			$content = str_replace( '[et_pb_wc_add_to_cart', '[et_pb_wc_add_to_cart_divioverlaystemp]', $content );
			
			$content = str_replace( '[ultimatemember', '[ultimatemember_divioverlaystemp]', $content );
		
		} else {
			
			$content = str_replace( '[et_pb_video_divioverlaystemp', '[et_pb_video', $content );
			$content = str_replace( '[/et_pb_video_divioverlaystemp]', '[/et_pb_video]', $content );
			
			$content = str_replace( '[et_pb_contact_form_divioverlaystemp', '[et_pb_contact_form', $content );
			$content = str_replace( '[/et_pb_contact_form_divioverlaystemp]', '[/et_pb_contact_form]', $content );
			
			$content = str_replace( '[woocommerce_checkout_divioverlaystemp]', '[woocommerce_checkout]', $content );
			$content = str_replace( '[et_pb_wc_add_to_cart_divioverlaystemp', '[et_pb_wc_add_to_cart]', $content );
			
			$content = str_replace( '[ultimatemember_divioverlaystemp', '[ultimatemember]', $content );
		}
	
	} catch ( Exception $e ) {
	
		DiviOverlays::log( $e );
	}
	
	return $content;
}


function searchForDiviOverlays( $content = NULL ) {
	
	$divioverlays_in_content = array();
		
	if ( !$content ) {
		
		return $divioverlays_in_content;
	}
	
	// Old patterns
	$matches = array();
	$pattern = '/id="(.*?overlay_[0-9]+)"/';
	preg_match_all($pattern, $content, $matches);
	
	$overlays_overlay_ = $matches[1];
	
	$matches = array();
	$pattern = '/id="(.*?overlay_unique_id_[0-9]+)"/';
	preg_match_all($pattern, $content, $matches);
	
	$overlays_overlay_unique_id_ = $matches[1];
	
	$matches = array();
	$pattern = '/class="(.*?divioverlay\-[0-9]+)"/';
	preg_match_all($pattern, $content, $matches);
	
	$overlays_class_overlay = $matches[1];
	
	// New patterns
	$matches = array();
	$pattern = '/.*class\s*=\s*["\'].*divioverlay\-[0-9]+/';
	preg_match_all($pattern, $content, $matches);
	
	$found_overlays_byclass_2 = $matches[0];
	
	$matches = array();
	$pattern = '/href="\#(.*?overlay\-[0-9]+)"/';
	preg_match_all($pattern, $content, $matches);
	
	$found_overlays_byhrefhash = $matches[1];
	
	$matches = array();
	$pattern = '/url="#(.*?overlay\-[0-9]+)"/';
	preg_match_all($pattern, $content, $matches);
	
	$found_overlays_byurlhash = $matches[1];
	
	$matches = array();
	$pattern = '/(?=<[^>]+(?=[\s+\"\']overlay\-[0-9]+[\s+\"\']).+)([^>]+>)/';
	preg_match_all($pattern, $content, $matches);
	
	$found_overlays_onanyattr = $matches[0];
	
	$matches = array();
	$pattern = '/module_id="(.*?overlay_unique_id_[0-9]+)"/';
	preg_match_all($pattern, $content, $matches);
	
	$found_overlays_divimoduleid = $matches[1];
	
	$matches = array();
	$pattern = '/.*module_class\s*=\s*["\'].*divioverlay\-[0-9]+/';
	preg_match_all($pattern, $content, $matches);
	
	$found_overlays_divimoduleclass = $matches[0];
	
	$divioverlays_found = array_merge( $overlays_overlay_, $overlays_overlay_unique_id_, $overlays_class_overlay, $found_overlays_byclass_2, $found_overlays_byhrefhash, $found_overlays_byurlhash, $found_overlays_onanyattr, $found_overlays_divimoduleid, $found_overlays_divimoduleclass);
	
	if ( is_array( $divioverlays_found ) && count( $divioverlays_found ) > 0 ) {
		
		$divioverlays_in_content = array_flip( array_filter( array_map( 'prepareOverlays', $divioverlays_found ) ) );
	}
		
	return $divioverlays_in_content;
}


function getAllDiviOverlays( $render = true ) {
	
	if ( !class_exists( 'DiviExtension' ) ) {

		return;
	}
	
	$render = ( $render === '' ) ? true : false;
	
	if ( $render === true ) {
		
		if ( ! defined( 'DOV_DIVI_RESPONSIVE_PREFS' ) ) {
			
			define( 'DOV_DIVI_RESPONSIVE_PREFS', et_fb_app_preferences() );
		}
		
		$divi_prefs = DOV_DIVI_RESPONSIVE_PREFS;
		
		$responsive_maximum_width = $divi_prefs['responsive_maximum_width']['value'];
		$responsive_tablet_width = $divi_prefs['responsive_tablet_width']['value'];
		$responsive_phone_width = $divi_prefs['responsive_phone_width']['value'];
		
		if ( $responsive_maximum_width == '' ) { $responsive_maximum_width = $divi_prefs['responsive_maximum_width']['default']; }
		if ( $responsive_tablet_width == '' ) { $responsive_tablet_width = $divi_prefs['responsive_tablet_width']['default']; }
		if ( $responsive_phone_width == '' ) { $responsive_phone_width = $divi_prefs['responsive_phone_width']['default']; }
		
		$responsive_min_width = floatval( $responsive_maximum_width ) + 1;
		
		if ( ! defined( 'DOV_RESPONSIVE_MIN_WIDTH' ) ) {
			
			define( 'DOV_RESPONSIVE_MIN_WIDTH', $responsive_min_width );
		}
		
		if ( ! defined( 'DOV_RESPONSIVE_MAX_WIDTH' ) ) {
			
			define( 'DOV_RESPONSIVE_MAX_WIDTH', $responsive_maximum_width );
		}
		
		if ( ! defined( 'DOV_RESPONSIVE_TABLET_WIDTH' ) ) {
			
			define( 'DOV_RESPONSIVE_TABLET_WIDTH', $responsive_tablet_width );
		}
		
		if ( ! defined( 'DOV_RESPONSIVE_PHONE_WIDTH' ) ) {
			
			define( 'DOV_RESPONSIVE_PHONE_WIDTH', $responsive_phone_width );
		}
		
		print '<script class="divioverlays-globalresponsivevalues">';
		print et_core_esc_previously( 'var dov_globalresponsivevalues = { min_width:' . $responsive_min_width . ', max_width:' . $responsive_maximum_width . ', tablet_width:' . $responsive_tablet_width . ', phone_width:' . $responsive_phone_width . ' };' );
		print '</script>';
	}
	
	/* Search Divi Overlay in current post */
	global $post;
	
	$overlays_in_post = array();
	
	if ( is_object( $post ) ) {
		
		try {
			
			if ( !$post ) {
				
				throw new InvalidArgumentException( 'getAllDiviOverlays() > Required var $post');
			}
			
			if ( ! isset( $post->ID ) ) {
				
				throw new InvalidArgumentException( 'getAllDiviOverlays() > Couldn\'t find property $post->ID');
			}
			
			$content = DiviOverlaysCore::getRender( $post->ID );
			
			$content = $content['output'];
		
		} catch (Exception $e) {
		
			DiviOverlays::log( $e );
			
			$content = '';
		}
		
		$overlays_in_post = searchForDiviOverlays( $content );
	}
	
	/* Search Divi Overlay in active menus */
	$theme_locations = get_nav_menu_locations();
	
	$overlays_in_menus = array();
	
	if ( is_array( $theme_locations ) && count( $theme_locations ) > 0 ) {
		
		$overlays_in_menus = array();
		
		foreach( $theme_locations as $theme_location => $theme_location_value ) {
			
			$menu = get_term( $theme_locations[$theme_location], 'nav_menu' );
			
			// menu exists?
			if( !is_wp_error($menu) && NULL !== $menu ) {
				
				$menu_term_id = $menu->term_id;
				
				// Support WPML for menus
				if ( function_exists( 'icl_object_id' ) ) {
					$menu_term_id = icl_object_id( $menu_term_id, 'nav_menu' );
				}
				
				$menu_items = wp_get_nav_menu_items( $menu_term_id );
				
				foreach ( (array) $menu_items as $key => $menu_item ) {
					
					$url = $menu_item->url;
					
					$extract_id = prepareOverlays( $url );
					
					if ( $extract_id ) {
						
						$overlays_in_menus[ $extract_id ] = 1;
					}
					
					/* Search Divi Overlay in menu classes */
					if ( isset( $menu_item->classes[0] ) && $menu_item->classes[0] != '' && count( $menu_item->classes ) > 0 ) {
						
						foreach ( $menu_item->classes as $key => $class ) {
							
							if ( $class != '' ) {
								
								$extract_id = prepareOverlays( $class );
								
								if ( $extract_id ) {
								
									$overlays_in_menus[ $extract_id ] = 1;
								}
							}
						}
					}
					
					/* Search Divi Overlay in Link Relationship (XFN) */
					if ( !empty( $menu_item->xfn ) ) {
						
						$extract_id = prepareOverlays( $menu_item->xfn );
						
						if ( $extract_id ) {
						
							$overlays_in_menus[ $extract_id ] = 1;
						}
					}
				}
			}
		}
	}
	
	$overlays_in_menus = array_filter( $overlays_in_menus );
	
	
	/* Search CSS Triggers in all Divi Overlays */
	global $wp_query;
	
	$args = array(
		'meta_key'   => 'post_css_selector',
		'meta_value' => '',
		'meta_compare' => '!=',
		'post_type' => DOV_CUSTOM_POST_TYPE,
		'cache_results'  => false,
		'posts_per_page' => -1
	);
	$query = new WP_Query( $args );
	
	$posts = $query->get_posts();
	
	$overlays_with_css_trigger = array();
	
	if ( isset( $posts[0] ) ) {
		
		if ( $render ) {
		
			print '<script type="text/javascript">var overlays_with_css_trigger = {';
		}
		
		foreach( $posts as $dv_post ) {
			
			$post_id = $dv_post->ID;
			
			$get_css_selector = get_post_meta( $post_id, 'post_css_selector' );
				
			$css_selector = $get_css_selector[0];
			
			if ( $css_selector != '' ) {
				
				if ( $render ) {
					
					print '\'' . et_core_esc_previously( $post_id ) . '\': \'' . et_core_esc_previously( $css_selector ) . '\',';
				}
				
				$overlays_with_css_trigger[ $post_id ] = $css_selector;
			}
		}
		
		if ( $render ) {
			
			print '};</script>';
		}
	}
	
	
	/* Search URL Triggers in all Divi Overlays */
	$args = array(
		'meta_key'   => 'post_enableurltrigger',
		'meta_value' => '1',
		'meta_compare' => '=',
		'post_type' => DOV_CUSTOM_POST_TYPE,
		'cache_results'  => false,
		'posts_per_page' => -1
	);
	$query = new WP_Query( $args );
	
	$posts = $query->get_posts();
	
	$overlays_with_url_trigger = array();
	
	if ( isset( $posts[0] ) ) {
		
		$display_in_current = false;
		
		foreach( $posts as $dv_post ) {
			
			$post_id = $dv_post->ID;
			
			$overlays_with_url_trigger[ $post_id ] = 1;
		}
	}
	$overlays_with_url_trigger = array_filter( $overlays_with_url_trigger );
	
	
	/* Add Overlays with Display Locations: Force render */
	$args = array(
		'meta_key'   => 'do_forcerender',
		'meta_value' => '1',
		'meta_compare' => '=',
		'post_type' => 'divi_overlay',
		'cache_results'  => false,
		'posts_per_page' => -1
	);
	$posts = get_posts( $args );
	
	$overlays_forcerender = array();
	
	if ( isset( $posts[0] ) ) {
		
		$display_in_current = false;
		
		foreach( $posts as $dv_post ) {
			
			$post_id = $dv_post->ID;
			
			$overlays_forcerender[ $post_id ] = 1;
		}
	}
	$overlays_forcerender = array_filter( $overlays_forcerender );
	
	
	/* Search Automatic Triggers in all Divi Overlays */
	
	// Server-Side Device Detection with Browscap
	require_once( plugin_dir_path( __FILE__ ) . 'php-libraries/Browscap/Browscap.php' );
	$browscap = new Browscap( plugin_dir_path( __FILE__ ) . '/php-libraries/Browscap/Cache/' );
	$browscap->doAutoUpdate = false;
	$current_browser = $browscap->getBrowser();
	
	$isMobileDevice = $current_browser->isMobileDevice;
	$isTabletDevice = $current_browser->isTablet;
	
	$overlays_with_automatic_trigger = array();
	
	$args = array(
		'meta_key'   => 'overlay_automatictrigger',
		'meta_value' => '',
		'meta_compare' => '!=',
		'post_type' => DOV_CUSTOM_POST_TYPE,
		'cache_results'  => false,
		'posts_per_page' => -1
	);
	$query = new WP_Query( $args );
	
	$posts = $query->get_posts();
	
	if ( isset( $posts[0] ) ) {
		
		if ( $render ) {
			
			print '<script type="text/javascript">var overlays_with_automatic_trigger = {';
		}
		
		foreach( $posts as $dv_post ) {
			
			$post_id = $dv_post->ID;
			
			$at_disablemobile = get_post_meta( $post_id, 'overlay_automatictrigger_disablemobile' );
			$at_disabletablet = get_post_meta( $post_id, 'overlay_automatictrigger_disabletablet' );
			$at_disabledesktop = get_post_meta( $post_id, 'overlay_automatictrigger_disabledesktop' );
			$onceperload = get_post_meta( $post_id, 'overlay_automatictrigger_onceperload', true );
			
			if ( isset( $onceperload[0] ) ) {
				
				$onceperload = $onceperload[0];
				
			} else {
				
				$onceperload = 1;
			}
			
			if ( isset( $at_disablemobile[0] ) ) {
				
				$at_disablemobile = $at_disablemobile[0];
				
			} else {
				
				$at_disablemobile = 1;
			}
			
			if ( isset( $at_disabletablet[0] ) ) {
				
				$at_disabletablet = $at_disabletablet[0];
				
			} else {
				
				$at_disabletablet = 0;
			}
			
			if ( isset( $at_disabledesktop[0] ) ) {
				
				$at_disabledesktop = $at_disabledesktop[0];
				
			} else {
				
				$at_disabledesktop = 0;
			}
			
			$printSettings = 1;
			if ( $at_disablemobile && $isMobileDevice ) {
				
				$printSettings = 0;
			}
			
			if ( $at_disablemobile && $isMobileDevice && $isTabletDevice ) {
				
				$printSettings = 1;
			}
			
			if ( $at_disabletablet && $isTabletDevice ) {
				
				$printSettings = 0;
			}
			
			if ( $at_disabledesktop && !$isMobileDevice && !$isTabletDevice ) {
				
				$printSettings = 0;
			}
			
			if ( $printSettings ) {
				
				$at_type = get_post_meta( $post_id, 'overlay_automatictrigger', true );
				$at_timed = get_post_meta( $post_id, 'overlay_automatictrigger_timed_value', true );
				$at_scroll_from = get_post_meta( $post_id, 'overlay_automatictrigger_scroll_from_value', true );
				$at_scroll_to = get_post_meta( $post_id, 'overlay_automatictrigger_scroll_to_value', true );
				
				if ( $at_type != '' ) {
					
					switch ( $at_type ) {
						
						case 'overlay-timed':
							$at_value = $at_timed;
						break;
						
						case 'overlay-scroll':
							$at_value = $at_scroll_from . ':' . $at_scroll_to;
						break;
						
						default:
							$at_value = $at_type;
					}
					
					$at_settings = wp_json_encode( array( 'at_type' => $at_type, 'at_value' => $at_value, 'at_onceperload' => $onceperload ) );
					
					if ( $render ) {
						
						print '\'' . et_core_esc_previously( $post_id ) . '\': \'' . et_core_esc_previously( $at_settings ) . '\',';
					}
					
					$overlays_with_automatic_trigger[ $post_id ] = $at_type;
				}
			}
		}
		
		if ( $render ) {
			
			print '};</script>';
		}
	}
	$overlays_with_automatic_trigger = array_filter( $overlays_with_automatic_trigger );
	
	
	/* Search in all Divi Layouts */
	$divioverlays_in_layouts = array();
	
	if ( function_exists( 'et_theme_builder_frontend_render_layout' ) ) {
		
		$layouts = et_theme_builder_get_template_layouts();
		
		$layout = '';
		
		$content = '';
		
		if ( is_array( $layouts ) && array_filter( $layouts ) ) {
			
			foreach( $layouts as $layout_type => $layout_ ) {
				
				if ( isset( $layout_['id'] ) && $layout_['enabled'] === true && $layout_['id'] !== 0 ) {
					
					$layout_id = $layout_['id'];
			
					$layout = get_post( $layout_id );
					
					if ( null !== $layout || $layout->post_type === $layout_type ) {
						
						$layout = _do_avoidRenderTags( $layout->post_content );
						
						$content .= $layout;
					}
				}
			}
			
			$content = stripStr( $content, '<iframe', '</iframe>' );
			$content = stripStr( $content, '<script', '</script>' );
			$content = stripStr( $content, '<style', '</style>' );
			
			$divioverlays_in_layouts = searchForDiviOverlays( $content );
		}
	}
	
	
	/* Ignore repeated ids and print overlays */
	$overlays = $overlays_in_post + $overlays_in_menus + $overlays_with_css_trigger + $overlays_with_url_trigger + $overlays_forcerender + $overlays_with_automatic_trigger + $divioverlays_in_layouts;
	
	// Do not render others overlays when current post is an overlay
	if ( is_object( $post ) && $post->post_type === 'divi_overlay' ) {
		
		$overlays = [ $post->ID ];
	}
	
	$total_overlays = count( $overlays );
	
	if ( $render && $total_overlays > 0 ) {
		
		print '<style id="divioverlay-styles"></style>';
		print '<div id="divioverlay-links"></div>';
		print '<div id="sidebar-overlay" class="hiddenMainContainer">';
	}
	
	$overlays_in_current = renderDiviOverlays( $overlays, $render );
	
	if ( $render && $total_overlays > 0 ) {
		
		print '</div>';
	}
	
	if ( $render && $total_overlays > 0 ) {
			
		?>
		<script type="text/javascript">
		var divioverlays_ajaxurl = "<?php print esc_url( home_url( '/' ) ); ?>"
		, divioverlays_us = "<?php print wp_create_nonce( 'divilife_divioverlays' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
		, divioverlays_loadingimg = "<?php print et_core_intentionally_unescaped( plugins_url( '/', __FILE__ ) . 'assets/img/divilife-loader.svg', 'fixed_string' ) ?>";
		</script>
		<?php
		
		$gdpr = get_option( 'divilife_divioverlays_gdpr' );
		
		if ( isset( $gdpr ) ) {
			
			if ( $gdpr === 'on' ) {
				
				$gdpr = true;
				
			} else if ( $gdpr === '' ) {
				
				$gdpr = false;
			}
		}
		else {
			
			$gdpr = false;
		}
		
		$dov_url_animate = DOV_PLUGIN_URL . 'assets/css/animate.min.css';
		
		wp_register_style( 'divi-overlays-animate-style', $dov_url_animate, array(), '4.1.1', 'all' );
		wp_enqueue_style('divi-overlays-animate-style');
		
		wp_register_style( 'divi-overlays-customanimations', DOV_PLUGIN_URL . 'assets/css/custom_animations.css', array(), DOV_VERSION, 'all' );
		wp_enqueue_style('divi-overlays-customanimations');
		
		wp_register_style('divi-overlays-custom_style_css', DOV_PLUGIN_URL . 'assets/css/style.css', array(), DOV_VERSION, 'all' );
		wp_enqueue_style('divi-overlays-custom_style_css');
		
		wp_register_script('divi-overlays-exit-intent', DOV_PLUGIN_URL . 'assets/js/jquery.exitintent.js', array("jquery"), DOV_VERSION );
		wp_enqueue_script('divi-overlays-exit-intent');
		
		wp_register_script('divi-overlays-custom-js', DOV_PLUGIN_URL . 'assets/js/custom.js', array("jquery"), DOV_VERSION, true);
		wp_enqueue_script('divi-overlays-custom-js');
		
		remove_action( 'et_before_main_content', 'getAllDiviOverlays', 1 );
	}
	else {
		
		return $overlays_in_current;
	}
}


function stripStr($str, $start, $end) {
	
	if ( function_exists( 'mb_stripos' ) ) {
		
		while( ( $pos = mb_stripos( $str, $start ) ) !== false ) {
			
			$aux = mb_substr($str, $pos + mb_strlen( $start ) );
			$str = mb_substr($str, 0, $pos).mb_substr( $aux, mb_stripos( $aux, $end ) + mb_strlen( $end ) );
		}
	}
	else {
		
		while( ( $pos = stripos( $str, $start ) ) !== false ) {
			
			$aux = substr( $str, $pos + strlen( $start ) );
			$str = substr( $str, 0, $pos ).substr( $aux, stripos( $aux, $end ) + strlen( $end ) );
		}
	}

    return $str;
}


function renderDiviOverlays( $overlays, $render ) {
	
	$overlays_in_current = array();
	
	if ( is_array( $overlays ) && count( $overlays ) > 0 ) {
		
		global $post;
		
		$ref_id = 0;
		
		if ( function_exists( 'get_queried_object_id' ) && get_queried_object_id() > 0 ) {
			
			$current_post_id = get_queried_object_id();
		
		} else {
			
			$current_post_id = 0;
		
			$current_home_post_id = (int) get_option( 'page_on_front' );
			
			$is_home = is_home();
			
			if ( $current_home_post_id == 0 && !$is_home ) {
				
				$current_post_id = get_the_ID();
			}
		}
		
		if ( is_category() ) {
			
			$current_category_id = (int) get_queried_object_id();
		}
		else {
			
			$current_category_id = 0;
		}
		
		if ( is_tag() ) {
			
			$current_tag_id = (int) get_queried_object_id();
		}
		else {
			
			$current_tag_id = 0;
		}
		
		$post_id = $current_post_id;
		$is_preview          = is_preview() || is_et_pb_preview();
		$forced_in_footer    = $post_id && et_builder_setting_is_on( 'et_pb_css_in_footer', $post_id );
		$forced_inline       = ! $post_id || $is_preview || $forced_in_footer || et_builder_setting_is_off( 'et_pb_static_css_file', $post_id ) || et_core_is_safe_mode_active() || ET_GB_Block_Layout::is_layout_block_preview();
		
		if ( isset( $_GET['action'] ) && $_GET['action'] == 'divioverlays_getcontent' ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		
			$forced_in_footer = $forced_inline = false;
		}
		
		// Get reference for overlay Divi styles
		if ( $current_post_id > 0 ) {
			
			$ref_id = $current_post_id;
		}
		else if ( $current_category_id > 0 ) {
			
			$ref_id = $current_category_id;
		}
		else if ( $current_tag_id > 0 ) {
			
			$ref_id = $current_tag_id;
		}
		
		
		$request = ET_Theme_Builder_Request::from_current();
		$type = $request->get_type();
		$subtype = $request->get_subtype();
		$id = $request->get_id();
		
		foreach( $overlays as $overlay_id => $idx ) {
			
			$applicable_templates = array();
			
			if ( get_post_status ( $overlay_id ) == 'publish' && is_numeric( $overlay_id ) ) {
				
				$display_in_current = false;
				
				$templates = et_theme_builder_get_theme_builder_templates( true );
				$settings  = et_theme_builder_get_flat_template_settings_options();
				
				$useon = get_post_meta( $overlay_id, 'displaylocations_useon', false );
				$useon = isset( $useon[0] ) ? $useon[0] : array();
				$useon = is_array( $useon ) ? $useon : array();
				$useon = array_filter( $useon );
				
				$excludefrom = get_post_meta( $overlay_id, 'displaylocations_excludefrom', false );
				$excludefrom = isset( $excludefrom[0] ) ? $excludefrom[0] : array();
				$excludefrom = is_array( $excludefrom ) ? $excludefrom : array();
				$excludefrom = array_filter( $excludefrom );
				
				foreach ( $excludefrom as $setting_id ) {
					
					if ( divioverlays_fulfills_template_setting( $settings, $setting_id, $type, $subtype, $id ) ) {
						
						continue 2;
					}
				}
				
				$highest_priority = '';
				
				foreach ( $useon as $setting_id ) {
					if ( divioverlays_fulfills_template_setting( $settings, $setting_id, $type, $subtype, $id ) ) {
						$highest_priority = divioverlays_get_higher_priority_template_setting( $settings, $highest_priority, $setting_id );
					}
				}
				
				if ( '' !== $highest_priority ) {
					
					$applicable_templates[] = array(
						'template'       => array(),
						'top_setting_id' => $highest_priority,
					);
				}
				
				$applicable_template = array_reduce( $applicable_templates, array( $request, 'reduce_get_template' ), array() );
				
				if ( ! empty( $applicable_template ) ) {
					
					$display_in_current = true;
				}
				
				// Conditions
				$displayConditions = get_post_meta( $overlay_id, 'dov_displayConditionsJSON', true );
				$is_displayable                        = true;
				$is_display_conditions_set             = ! empty( $displayConditions );
				$is_display_conditions_as_base64_empty = 'W10=' === $displayConditions;
				$has_display_conditions                = $is_display_conditions_set && ! $is_display_conditions_as_base64_empty;

				// Check if display_conditions attribute is defined, Decode the data and check if it is displayable.
				if ( $has_display_conditions ) {
					$display_conditions_json = base64_decode( $displayConditions ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.obfuscation_base64_decode  -- The returned data is an array and necessary validation checks are performed.
				}
				
				if ( $has_display_conditions && false !== $display_conditions_json ) {
					
					$display_conditions = json_decode( $display_conditions_json, true );
					$is_displayable     = \ET_Builder_Module_Fields_Factory::get( 'DisplayConditions' )->is_displayable( $display_conditions );
					
					$display_in_current = ( $is_displayable ) ? true : false;
				}
				
				if ( $display_in_current ) {
					
					$overlays_in_current[ $overlay_id ] = $overlay_id;
					
					if ( $render ) {
						
						showOverlay( $overlay_id );
					}
				}
			}
		}
	}
	
	return $overlays_in_current;
}


/**
 * Get $a or $b depending on which template setting has a higher priority.
 * Handles cases such as category settings with equal priority but in a ancestor-child relationship.
 * Returns an empty string if neither setting is found.
 *
 * @since 4.0
 *
 * @param array  $flat_settings Flat settings.
 * @param string $a             First template setting.
 * @param string $b             Second template setting.
 *
 * @return string
 */
function divioverlays_get_higher_priority_template_setting( $flat_settings, $a, $b ) {
	$map        = array_flip( array_keys( $flat_settings ) );
	$a_ancestor = divioverlays_get_setting_ancestor( $flat_settings, $a );
	$b_ancestor = divioverlays_get_setting_ancestor( $flat_settings, $b );
	$a_found    = ! empty( $a_ancestor );
	$b_found    = ! empty( $b_ancestor );

	if ( ! $a_found || ! $b_found ) {
		if ( $a_found ) {
			return $a;
		}

		if ( $b_found ) {
			return $b;
		}

		return '';
	}

	if ( $a_ancestor['priority'] !== $b_ancestor['priority'] ) {
		// Priorities are not equal - use a simple comparison.
		return $a_ancestor['priority'] >= $b_ancestor['priority'] ? $a : $b;
	}

	if ( $a_ancestor['id'] !== $b_ancestor['id'] ) {
		// Equal priorities, but the ancestors are not the same - use the order in $flat_settings
		// so we have a deterministic result even if $a and $b are swapped.
		return $map[ $a_ancestor['id'] ] <= $map[ $b_ancestor['id'] ] ? $a : $b;
	}

	// Equal priorities, same ancestor.
	$ancestor  = $a_ancestor;
	$a_pieces  = explode( ET_THEME_BUILDER_SETTING_SEPARATOR, $a );
	$b_pieces  = explode( ET_THEME_BUILDER_SETTING_SEPARATOR, $b );
	$separator = preg_quote( ET_THEME_BUILDER_SETTING_SEPARATOR, '/' );

	// Hierarchical post types are a special case by spec since we have to take hierarchy into account.
	// Test if the ancestor matches "singular:post_type:<post_type>:children:id:".
	$id_pieces  = array( 'singular', 'post_type', '[^' . $separator . ']+', 'children', 'id', '' );
	$term_regex = '/^' . implode( $separator, $id_pieces ) . '$/';

	if ( preg_match( $term_regex, $ancestor['id'] ) && is_post_type_hierarchical( $a_pieces[2] ) ) {
		$a_post_id = (int) $a_pieces[5];
		$b_post_id = (int) $b_pieces[5];

		$a_post_ancestors = get_post_ancestors( $a_post_id );
		$b_post_ancestors = get_post_ancestors( $b_post_id );

		if ( in_array( $a_post_id, $b_post_ancestors, true ) ) {
			// $b is a child of $a so it should take priority.
			return $b;
		}

		if ( in_array( $b_post_id, $a_post_ancestors, true ) ) {
			// $a is a child of $b so it should take priority.
			return $a;
		}

		// neither $a nor $b is an ancestor to the other - continue the comparisons.
	}

	// Term archive listings are a special case by spec since we have to take hierarchy into account.
	// Test if the ancestor matches "archive:taxonomy:<taxonomy>:term:id:".
	$id_pieces  = array( 'archive', 'taxonomy', '[^' . $separator . ']+', 'term', 'id', '' );
	$term_regex = '/^' . implode( $separator, $id_pieces ) . '$/';

	if ( preg_match( $term_regex, $ancestor['id'] ) && is_taxonomy_hierarchical( $a_pieces[2] ) ) {
		$a_term_id = $a_pieces[5];
		$b_term_id = $b_pieces[5];

		if ( term_is_ancestor_of( $a_term_id, $b_term_id, $a_pieces[2] ) ) {
			// $b is a child of $a so it should take priority.
			return $b;
		}

		if ( term_is_ancestor_of( $b_term_id, $a_term_id, $a_pieces[2] ) ) {
			// $a is a child of $b so it should take priority.
			return $a;
		}

		// neither $a nor $b is an ancestor to the other - continue the comparisons.
	}

	// Find the first difference in the settings and compare it.
	// The difference should be representing an id or a slug.
	foreach ( $a_pieces as $index => $a_piece ) {
		$b_piece = $b_pieces[ $index ];

		if ( $b_piece === $a_piece ) {
			continue;
		}

		if ( is_numeric( $a_piece ) ) {
			$prioritized = (float) $a_piece <= (float) $b_piece ? $a : $b;
		} else {
			$prioritized = strcmp( $a, $b ) <= 0 ? $a : $b;
		}

		/**
		 * Filters the higher prioritized setting in a given pair that
		 * has equal built-in priority.
		 *
		 * @since 4.2
		 *
		 * @param string $prioritized_setting
		 * @param string $setting_a
		 * @param string $setting_b
		 * @param ET_Theme_Builder_Request $request
		 */
		return apply_filters( 'et_theme_builder_prioritized_template_setting', $prioritized, $a, $b, $this );
	}

	// We should only reach this point if $a and $b are equal so it doesn't
	// matter which we return.
	return $a;
}


function divioverlays_fulfills_template_setting( $flat_settings, $setting_id, $type, $subtype, $id ) {
	
	$ancestor  = divioverlays_get_setting_ancestor( $flat_settings, $setting_id );
	$fulfilled = false;

	if ( ! empty( $ancestor ) && isset( $ancestor['validate'] ) && is_callable( $ancestor['validate'] ) ) {
		// @phpcs:ignore Generic.PHP.ForbiddenFunctions.Found
		$fulfilled = call_user_func(
			$ancestor['validate'],
			$type,
			$subtype,
			$id,
			explode( ET_THEME_BUILDER_SETTING_SEPARATOR, $setting_id )
		);
	}

	return $fulfilled;
}

function divioverlays_get_setting_ancestor( $flat_settings, $setting_id ) {
	
	$id = $setting_id;

	if ( ! isset( $flat_settings[ $id ] ) ) {
		// If the setting is not found, check if a valid parent exists.
		$parent_id = explode( ET_THEME_BUILDER_SETTING_SEPARATOR, $id );
		array_pop( $parent_id );
		$parent_id[] = '';
		$parent_id   = implode( ET_THEME_BUILDER_SETTING_SEPARATOR, $parent_id );
		$id          = $parent_id;
	}

	if ( ! isset( $flat_settings[ $id ] ) ) {
		// The setting is still not found - bail.
		return array();
	}

	return $flat_settings[ $id ];
}


add_action( 'wp_footer', 'divioverlays_getcontent' );
function divioverlays_getcontent() {
	
	if ( isset( $_GET['divioverlays_id'] ) && isset( $_GET['action'] ) && $_GET['action'] == 'divioverlays_getcontent' ) {
		
		global $wp_embed;
		
		check_ajax_referer( 'divilife_divioverlays', 'security' );
		
		$overlay_id = intval( sanitize_text_field( wp_unslash( $_GET['divioverlays_id'] ) ) );
		
		$post_data = get_post( $overlay_id );
		
		$post_content = $post_data->post_content;
		
		$DiviOverlaysCore = new DiviOverlaysCore( $post_data->ID );
		
		$DiviOverlaysCore->start_module_index_override();
		
		$wp_embed->post_ID = $post_data->ID;
		
		// Process the [embed] shortcodes
		$wp_embed->run_shortcode( $post_content );
		
		// Passes any unlinked URLs that are on their own line
		$wp_embed->autoembed( $post_content );
		
		// Search content for shortcodes and filter shortcodes through their hooks
		$output = do_shortcode( $post_content );
		
		$output = et_builder_get_layout_opening_wrapper() . $output . et_builder_get_layout_closing_wrapper();
		
		// Monarch fix: Remove Divi Builder main section class and add it later with JS
		$output = str_replace( 'et_pb_section ', 'dov_dv_section ', $output );
		
		$DiviOverlaysCore->end_module_index_override();
		
		print '<div id="divioverlay-content-ajax">' . et_core_esc_previously( $output ) . '</div>';
	}
}


function get_all_wordpress_menus(){
    return get_terms( 'nav_menu', array( 'hide_empty' => true ) ); 
}


function prepareOverlays( $key = NULL )
{
    if ( !$key ) {
        return NULL;
	}
	
    if ( is_array( $key ) ) {
        return NULL;
	}
	
	$overlay_id = '';
	
	// it is an url with hash overlay?
	if ( strpos( $key, "#overlay-" ) !== false ) {
		
		$exploded_url = explode( "#", $key );
		
		if ( isset( $exploded_url[1] ) ) {
			
			$key = str_replace( 'overlay-', '', $exploded_url[1] );
			
			$overlay_id = $key;
		}
	}
	
	if ( $overlay_id === '' || $overlay_id === null ) {
		
		$pos = 0;
		$pos1 = strpos( $key, 'unique_overlay_menu_id_' );
		$pos2 = strpos( $key, 'overlay_' );
		$pos3 = strpos( $key, 'unique_id_' );
		$pos4 = strpos( $key, 'divioverlay-' );
		$pos5 = strpos( $key, 'overlay_unique_id_' );
		
		if ( $pos1 !== false || $pos2 !== false || $pos3 !== false || $pos4 !== false || $pos5 !== false ) {
			
			if ( $pos1 > 0 ) {
				
				$pos = $pos1;
			}
			
			if ( $pos2 > 0 ) {
				
				$pos = $pos2;
			}
			
			if ( $pos3 > 0 ) {
				
				$pos = $pos3;
			}
			
			if ( $pos4 > 0 ) {
				
				$pos = $pos4;
			}
			
			if ( $pos5 > 0 ) {
				
				$pos = $pos5;
			}
			
			$key = substr( $key, $pos );
			$overlay_id = preg_replace( '/[^0-9.]/', '', $key );
		}
		else {
			
			return NULL;
		}
	}
	
    if ( $overlay_id === '' ) {
		
        return NULL;
	}
	
	if ( !overlayIsPublished( $overlay_id ) ) {
		
		return NULL;
	}
	
	return $overlay_id;
}

function overlayIsPublished( $key ) {
	
	$post = get_post_status( $key );
	
	if ( $post !== 'publish' ) {
		
		return FALSE;
	}
	
	return TRUE;
}
