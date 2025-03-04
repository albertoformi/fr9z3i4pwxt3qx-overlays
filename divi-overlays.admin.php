<?php 

	/* Add custom column in post type */
	add_filter( 'manage_edit-divi_overlay_columns', 'my_edit_divi_overlay_columns' ) ;

	function my_edit_divi_overlay_columns( $columns ) {

		$columns = array(
			'cb' => '<input type="checkbox" />',
			'title' => __( 'Title' ),
			'unique_class' => __( 'CSS Class' ),
			'unique_indentifier' => __( 'CSS ID' ),
			'unique_menu_id' => __( 'Menu ID' ),
			'author' => __( 'Author' ),
			'date' => __( 'Date' )
		);

		return $columns;
	}

	add_action( 'manage_divi_overlay_posts_custom_column', 'my_manage_divi_overlay_columns', 10, 2 );

	function my_manage_divi_overlay_columns( $column, $post_id ) {
		global $post;

		switch( $column ) {
			
			/* If displaying the 'unique_class' column. */
			case 'unique_class' :

				/* Get the post meta. */
				$post_slug = "divioverlay-$post->ID";

				print et_core_esc_previously( $post_slug );

				break;

			/* If displaying the 'unique-indentifier' column. */
			case 'unique_indentifier' :

				/* Get the post meta. */
				$post_slug = "overlay_unique_id_$post->ID";

				print et_core_esc_previously( $post_slug );

				break;

			case 'unique_menu_id' :

				/* Get the post meta. */
				$post_slug = "unique_overlay_menu_id_$post->ID";

				print et_core_esc_previously( $post_slug );

				break;
				
			default :
				break;
		}
	}
	/* Custom column End here */


	// Meta boxes for Divi Overlay //
	function et_add_divi_overlay_meta_box() {
		
		$screen = get_current_screen();
		
		if ( $screen->post_type == DOV_CUSTOM_POST_TYPE ) {
			
			$status = get_option( 'divilife_edd_divioverlays_license_status' );
			$last_check = get_option( 'divilife_edd_divioverlays_license_lastcheck', false, false );
			$now = time();
			
			if ( $last_check === false ) {
				
				update_option( 'divilife_edd_divioverlays_license_lastcheck', $now );
				$last_check = $now;
			}
			
			$since_last_check = $now - $last_check;
			
			// An hour passed? check for license status
			if ( $since_last_check > 3599 ) {
				
				update_option( 'divilife_edd_divioverlays_license_lastcheck', $now );
				
				$check_license = divilife_edd_divioverlays_check_license( TRUE );
				if ( ( isset( $check_license->license ) && $check_license->license !== 'valid' && 'add' === $screen->action ) 
					|| ( isset( $check_license->license ) && isset( $_GET['action'] ) && $check_license->license !== 'valid' && 'edit' === $_GET['action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					|| ( $status === false && 'add' === $screen->action ) 
					|| ( $status === false && isset( $_GET['action'] ) && 'edit' === $_GET['action'] ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					) {
					
					$message = '';
					$base_url = admin_url( 'edit.php?post_type=divi_overlay&page=dovs-settings' );
					$redirect = add_query_arg( array( 'message' => rawurlencode( $message ), 'divilife' => 'divioverlays' ), $base_url );
					
					wp_safe_redirect( $redirect );
					exit();
				}
			}
			
			add_meta_box( 'divioverlay_postsettings', esc_html__( 'Divi Overlays', 'DiviOverlays' ), 'divioverlay_settings_callback', DOV_CUSTOM_POST_TYPE, 'normal');
			
			remove_meta_box( 'postcustom', false, 'normal' ); 
		}
	}
	add_action( 'add_meta_boxes', 'et_add_divi_overlay_meta_box' );

	add_filter('is_protected_meta', 'removefields_from_customfieldsmetabox', 10, 2);
	function removefields_from_customfieldsmetabox( $protected, $meta_key ) {
		
		if ( function_exists( 'get_current_screen' ) ) {
			
			$screen = get_current_screen();
			
			$remove = $protected;
			
			if ( $screen !== null && $screen->post_type != DOV_CUSTOM_POST_TYPE ) {
			
				if ( $meta_key == 'overlay_automatictrigger'
					|| $meta_key == 'overlay_automatictrigger_disablemobile'
					|| $meta_key == 'overlay_automatictrigger_disabletablet'
					|| $meta_key == 'overlay_automatictrigger_disabledesktop'
					|| $meta_key == 'overlay_automatictrigger_onceperload' 
					|| $meta_key == 'overlay_automatictrigger_scroll_from_value'
					|| $meta_key == 'overlay_automatictrigger_scroll_to_value'
					|| $meta_key == 'do_enable_scheduling' 
					|| $meta_key == 'do_at_pages' 
					|| $meta_key == 'do_at_pages_selected' 
					|| $meta_key == 'do_at_pagesexception_selected' 
					|| $meta_key == 'post_do_customizeclosebtn'
					|| $meta_key == 'closebtninoverlay'
					|| $meta_key == 'post_do_hideclosebtn' 
					|| $meta_key == 'post_do_preventscroll' 
					|| $meta_key == 'post_enableurltrigger' 
					|| $meta_key == 'css_selector_at_pages'
					|| $meta_key == 'css_selector_at_pages_selected'
					|| $meta_key == 'do_date_start'
					|| $meta_key == 'do_date_end'
					|| $meta_key == 'dov_closebtn_cookie'
					|| $meta_key == 'do_enableajax'
					|| $meta_key == 'et_pb_divioverlay_effect_entrance'
					|| $meta_key == 'et_pb_divioverlay_effect_exit'
					|| $meta_key == 'dov_effect_entrance_speed'
					|| $meta_key == 'do_showguests'
					|| $meta_key == 'do_showusers'
					|| $meta_key == 'post_do_closebtnclickingoutside'
					|| $meta_key == 'do_enableesckey'
					) {
						
					$remove = true;
				}
			}
			
			return $remove;
		}
	}

	function do276_title_filter( $where, $wp_query )
	{
		global $wpdb;
		
		if ( $search_term = $wp_query->get( 'do_by_title_like' ) ) {
			$where .= ' AND ' . $wpdb->posts . '.post_title LIKE \'%' . $wpdb->esc_like( $search_term ) . '%\'';
		}
		
		return $where;
	}


	if ( ! function_exists( 'do_displaylocations_callback' ) ) :

		function do_displaylocations_callback() {
			
			$post_id = get_the_ID();
				
			wp_nonce_field( 'divioverlays_displaylocations', 'divioverlays_displaylocations_nonce' );
			
			$displaylocations = DiviOverlaysCore::divi_display_options();
			
			$useon = get_post_meta( $post_id, 'displaylocations_useon', true );
			$excludefrom = get_post_meta( $post_id, 'displaylocations_excludefrom', true );
			
			$newpost = false;
			if ( get_post_status() === 'auto-draft' ) {
				
				$newpost = true;
			}
			
			if ( !isset( $useon[0] ) ) {
				
				$useon = [];
				
				if ( $newpost === true ) {
					
					$useon[0] = 'singular:post_type:page:all';
					$useon[1] = 'singular:post_type:post:all';
				}
			}
			
			if ( !isset( $excludefrom[0] ) ) {
				
				$excludefrom = [];
			}
			
			$useon_check = array_flip( $useon );
			$excludefrom_check = array_flip( $excludefrom );
			
			$useon_options_check = implode( ' ', $useon );
			$excludefrom_options_check = implode( ' ', $excludefrom );
			
			?>
			<script>
			var divioverlays_get_template_settings = "<?php print wp_create_nonce( 'divioverlays_get_template_settings' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>"
			, divioverlays_ajax_url = '<?php print esc_url( admin_url('admin-ajax.php') ); ?>'
			, divilife_displaylocations = <?php print wp_json_encode( $displaylocations ) ?>
			, divilife_divioverlays_postid = '<?php print et_core_esc_previously( $post_id ) ?>';
			</script>
			
			<div class="divilife_meta_box divilife_meta_box_displaylocations">
			
				<div class="divilife_displaylocations">
					<div class="et-common-tabs-navigation">
						<button type="button" class="divilife_dl_navigation__button et-common-tabs-navigation__button et-common-tabs-navigation__button--active" role="tab" data-key="useon"><?php esc_html_e( 'Use On', 'Divi' ) ?></button>
						<button type="button" class="divilife_dl_navigation__button et-common-tabs-navigation__button" role="tab" data-key="excludefrom"><?php esc_html_e( 'Exclude From', 'Divi' ) ?></button>
					</div>
					<div class="et-tb-dropdown-modal__tabs-contents-scroll">
						<div class="et-tb-dropdown-modal__tabs-contents">
						
							<div id="dl-tab-useon" class="displaylocations-tabcontent displaylocations-useon displaylocations-useon et-tb-dropdown-modal__tab-content et-tb-dropdown-modal__tab-content--active">
							
							<?php 
								
							foreach ( $displaylocations['displayLocations'] as $displaylocations_type ) {
								
							?>
								<div class="et-tb-template-settings-group et-common-checkbox-group">
								
									<label class="et-common-checkbox-group__label"><?php print esc_html( $displaylocations_type['label'] ) ?></label>
								
									<ul class="et-common-checkbox-group__list">
									
									<?php
										
									foreach ( $displaylocations_type['settings'] as $dloptions ) {
										
										$checkoption = false;
										if ( 
											isset( $useon_check[ $dloptions['id'] ] ) 
											|| ( isset( $dloptions['options'] ) && strpos($useon_options_check, $dloptions['id']) !== false ) 
											) {
											
											$checkoption = true;
										}
									?>
										
										<li class="et-tb-template-settings-group-setting<?php if ( isset( $dloptions['options'] ) ) { ?> do-dloptions<?php } else { ?> non-dloptions<?php } ?>">
											<div class="et-tb-template-settings-group-setting__label">
												<label class="et-common-checkbox">
													<input type="checkbox" class="et-common-checkbox__input" value="useon:<?php print et_core_esc_previously( $dloptions['id'] ) ?>"<?php if ( $checkoption ) { ?> checked="checked"<?php } ?>>
													<span class="et-common-checkbox__label">
														<span class="et-common-checkbox-group__label-contents"><span class="et-common-checkbox-group__label-main"><?php print esc_html( $dloptions['label'] ) ?></span></span>
													</span>
													<?php if ( isset( $dloptions['options'] ) ) { ?>
													<div class="et-fb-icon et-fb-icon--chevron-right" style="fill: rgb(76, 88, 102); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
														<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision">
															<g><path d="M13.38 19.48l4.6-4.6a1.25 1.25 0 0 0 0-1.77l-4.6-4.6a1.25 1.25 0 1 0-1.77 1.77L15.32 14l-3.71 3.71a1.25 1.25 0 1 0 1.77 1.77z" fill-rule="evenodd"></path></g>
														</svg>
													</div>
													<?php } ?>
												</label>
											</div>
										</li>
										
									<?php 
									
									}
									
									?>
									
									</ul>
								
								</div>
								
							<?php
							
							}
							
							?>
								
							</div>
							
							<div id="dl-tab-excludefrom" class="displaylocations-tabcontent displaylocations-excludefrom et-tb-dropdown-modal__tab-content">
							
							<?php 
								
							foreach ( $displaylocations['displayLocations'] as $displaylocations_type ) {
								
							?>
								<div class="et-tb-template-settings-group et-common-checkbox-group">
								
									<label class="et-common-checkbox-group__label"><?php print esc_html( $displaylocations_type['label'] ) ?></label>
								
									<ul class="et-common-checkbox-group__list">
									
									<?php
										
									foreach ( $displaylocations_type['settings'] as $dloptions ) {
										
										$checkoption = false;
										if ( 
											isset( $excludefrom_check[ $dloptions['id'] ] ) 
											|| ( isset( $dloptions['options'] ) && strpos($excludefrom_options_check, $dloptions['id']) !== false ) 
											) {
											
											$checkoption = true;
										}
										
									?>
										
										<li class="et-tb-template-settings-group-setting<?php if ( isset( $dloptions['options'] ) ) { ?> do-dloptions<?php } else { ?> non-dloptions<?php } ?>">
											<div class="et-tb-template-settings-group-setting__label">
												<label class="et-common-checkbox">
													<input type="checkbox" class="et-common-checkbox__input et-common-checkbox__input--danger" value="excludefrom:<?php print et_core_esc_previously( $dloptions['id'] ) ?>"<?php if ( $checkoption ) { ?> checked="checked"<?php } ?>>
													<span class="et-common-checkbox__label">
														<span class="et-common-checkbox-group__label-contents"><span class="et-common-checkbox-group__label-main"><?php print esc_html( $dloptions['label'] ) ?></span></span>
													</span>
													<?php if ( isset( $dloptions['options'] ) ) { ?>
													<div class="et-fb-icon et-fb-icon--chevron-right" style="fill: rgb(76, 88, 102); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
														<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision">
															<g><path d="M13.38 19.48l4.6-4.6a1.25 1.25 0 0 0 0-1.77l-4.6-4.6a1.25 1.25 0 1 0-1.77 1.77L15.32 14l-3.71 3.71a1.25 1.25 0 1 0 1.77 1.77z" fill-rule="evenodd"></path></g>
														</svg>
													</div>
													<?php } ?>
												</label>
											</div>
										</li>
										
									<?php 
									
									}
									
									?>
									
									</ul>
								
								</div>
								
							<?php
							
							}
							
							?>
								
							</div>
							
						</div>
					</div>
				</div>
				
				<div class="divilife_displaylocations_subcontent"></div>

				<div class="divilife_displaylocations_subcontent_dlvalues">
				
					<?php
						
						if ( isset( $useon ) && is_array( $useon ) ) {
							
							foreach( $useon as $useonidx => $useonvalue ) {
								
					?>
					
					<input class="do-hide" data-inputreference="useon:<?php print et_core_esc_previously( $useonvalue ) ?>" name="divilife_useon[]" value="<?php print esc_html( $useonvalue ) ?>">
					<?php
							}
						}
					?>
					
					<?php
						
						if ( isset( $excludefrom ) && is_array( $excludefrom ) ) {
							
							foreach( $excludefrom as $excludefromidx => $excludefromvalue ) {
								
					?>
					
					<input class="do-hide" data-inputreference="excludefrom:<?php print et_core_esc_previously( $excludefromvalue ) ?>" name="divilife_excludefrom[]" value="<?php print esc_html( $excludefromvalue ) ?>">
					<?php
							}
						}
					?>
					
				</div>

			</div>
			<?php
		}
		
	endif;


	if ( ! function_exists( 'divioverlays_get_template_settings' ) ) :

		function divioverlays_get_template_settings() {
			
			et_builder_security_check( 'theme_builder', 'edit_others_posts', 'divioverlays_get_template_settings', 'nonce', '_GET' );

			$parent   = isset( $_GET['parent'] ) ? sanitize_text_field( $_GET['parent'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$search   = isset( $_GET['search'] ) ? sanitize_text_field( $_GET['search'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page     = isset( $_GET['page'] ) ? (int) $_GET['page'] : 1; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$page     = $page >= 1 ? $page : 1;
			$per_page = 500;
			$settings = et_theme_builder_get_flat_template_settings_options();

			if ( ! isset( $settings[ $parent ] ) || empty( $settings[ $parent ]['options'] ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'Invalid parent setting specified.', 'et_builder' ),
					)
				);
			}

			$setting = $settings[ $parent ];
			$results = et_theme_builder_get_template_setting_child_options( $setting, array(), $search, $page, $per_page );

			wp_send_json_success(
				array(
					'results' => array_values( $results ),
				)
			);
		}
		
		add_action( 'wp_ajax_divioverlays_get_template_settings', 'divioverlays_get_template_settings' );
		
	endif;

	if ( ! function_exists( 'do_manualtriggers_callback' ) ) :

		function do_manualtriggers_callback() {
			
			$post_id = get_the_ID();
			?>
			<div class="divilife_meta_box et-fb-form__group">
				<label class="label-color-field dlattention"><?php esc_html_e( 'CSS Class', 'DiviOverlays' ); ?>:</label>
				divioverlay-<?php print et_core_esc_previously( $post_id ) ?>
			</div> 
			<div class="divilife_meta_box et-fb-form__group">
				<label class="label-color-field dlattention"><?php esc_html_e( 'CSS ID', 'DiviOverlays' ); ?>:</label>
				overlay_unique_id_<?php print et_core_esc_previously( $post_id ) ?>
			</div> 
			<div class="divilife_meta_box et-fb-form__group">
				<label class="label-color-field dlattention"><?php esc_html_e( 'Menu ID', 'DiviOverlays' ); ?>:</label>
				unique_overlay_menu_id_<?php print et_core_esc_previously( $post_id ) ?>
			</div>
			<?php
		}
		
	endif;

	function dov_style_devices_settings() {
		
		return '<ul class="et-fb-settings-tab-titles dov-style-devices-settings do-hide">
					<li>
						<button class="et-fb-settings-tab-title et-fb-settings-tab-title-active" data-tip="Desktop" data-index="desktop" data-for="styles-tab-tooltip-desktop" currentitem="false">
							<div class="et-fb-icon et-fb-icon--desktop">
								<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision">
									<g>
										<path d="M20 7H8C7.5 7 7 7.5 7 8v10c0 0.5 0.5 1 1 1h5v1h-1c-0.5 0-1 0.5-1 1s0.5 1 1 1h4c0.5 0 1-0.5 1-1s-0.5-1-1-1h-1v-1h5c0.5 0 1-0.5 1-1V8C21 7.5 20.5 7 20 7zM15 18h-2v-1h2V18zM19 16H9V9h10V16z" fill-rule="evenodd"></path>
									</g>
								</svg>
							</div>
							<div class="place-top type-dark ">' . esc_html__( 'Desktop', 'DiviOverlays' ) . '</div>
						</button>
					</li>
					<li>
						<button class="et-fb-settings-tab-title" data-tip="Tablet" data-index="tablet" data-for="styles-tab-tooltip-tablet" currentitem="false">
							<div class="et-fb-icon et-fb-icon--tablet">
								<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision">
									<g>
										<path d="M19 7H9C8.5 7 8 7.5 8 8v12c0 0.5 0.5 1 1 1h10c0.5 0 1-0.5 1-1V8C20 7.5 19.5 7 19 7zM15 20h-2v-1h2V20zM18 18h-8V9h8V18z" fill-rule="evenodd"></path>
									</g>
								</svg>
							</div>
							<div class="place-top type-dark ">' . esc_html__( 'Tablet', 'DiviOverlays' ) . '</div>
						</button>
					</li>
					<li>
						<button class="et-fb-settings-tab-title" data-tip="Phone" data-index="phone" data-for="styles-tab-tooltip-phone" currentitem="false">
							<div class="et-fb-icon et-fb-icon--phone">
								<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision">
									<g>
										<path d="M17 7h-6c-0.5 0-1 0.5-1 1v12c0 0.5 0.5 1 1 1h6c0.5 0 1-0.5 1-1V8C18 7.5 17.5 7 17 7zM15 20h-2v-1h2V20zM16 18h-4V9h4V18z" fill-rule="evenodd"></path>
									</g>
								</svg>
							</div>
							<div class="place-top type-dark ">' . esc_html__( 'Phone', 'DiviOverlays' ) . '</div>
						</button>
					</li>
				</ul>';
	}

	if ( ! function_exists( 'do_closecustoms_callback' ) ) :

		function do_closecustoms_callback() {
			
			$post_id = get_the_ID();
			
			wp_nonce_field( 'do_closecustoms', 'do_closecustoms_nonce' );
			
			$textcolor = get_post_meta( $post_id, 'post_doclosebtn_text_color', true );
			$bgcolor = get_post_meta( $post_id, 'post_doclosebtn_bg_color', true );
			$fontsize = get_post_meta( $post_id, 'post_doclosebtn_fontsize', true );
			$borderradius = get_post_meta( $post_id, 'post_doclosebtn_borderradius', true );
			$padding = get_post_meta( $post_id, 'post_doclosebtn_padding', true );
			$close_cookie = get_post_meta( $post_id, 'dov_closebtn_cookie', true );
			
			if( !isset( $textcolor ) || $textcolor === '' ) { $textcolor = '#FFFFFF'; }
			if( !isset( $bgcolor ) || $bgcolor === '' ) { $bgcolor = '#333333'; }
			if( !isset( $fontsize ) || $fontsize === '' ) { $fontsize = '25px'; }
			if( !isset( $borderradius ) || $borderradius === '' || $borderradius == 0 ) { $borderradius = '50%'; }
			if( !isset( $padding ) || $padding === '' || $padding == 0 ) { $padding = '0px'; }
			if( !isset( $close_cookie ) || $close_cookie === '' || $close_cookie == 0 ) { $close_cookie = '0' . ' ' . esc_html__( 'Days', 'DiviOverlays' ); }
			
			if ( is_numeric( $fontsize ) === TRUE ) { $fontsize = $fontsize . 'px'; }
			if ( is_numeric( $borderradius ) === TRUE ) { $borderradius = $borderradius . '%'; }
			if ( is_numeric( $padding ) === TRUE ) { $padding = $padding . 'px'; }
			if ( is_numeric( $close_cookie ) === TRUE ) { $close_cookie = $close_cookie . ' ' . esc_html__( 'Days', 'DiviOverlays' ); }
			
			$fontsize_control = floatval( $fontsize );
			$borderradius_control = floatval( $borderradius );
			$padding_control = floatval( $padding );
			$close_cookie_control = floatval( $close_cookie );
			
			$closebtnclickingoutside = get_post_meta( $post_id, 'post_do_closebtnclickingoutside' );
			if( !isset( $closebtnclickingoutside[0] ) ) {
				
				$closebtnclickingoutside[0] = '0';
			}
			
			$hideclosebtn = get_post_meta( $post_id, 'post_do_hideclosebtn' );
			if( !isset( $hideclosebtn[0] ) ) { $hideclosebtn[0] = '0'; }
			
			$customizeclosebtn = get_post_meta( $post_id, 'post_do_customizeclosebtn' );
			if( !isset( $customizeclosebtn[0] ) ) { $customizeclosebtn[0] = '0'; }
			
			$color_palette       = get_post_meta( $post_id, '_et_pb_color_palette', true );
			$default             = et_pb_get_default_color_palette( $post_id );
			$et_pb_color_palette = '' !== $color_palette ? $color_palette : $default;
			
			?>
			<script>
			var dov_et_pb_color_palette = '<?php print implode( '|', et_core_esc_previously( $et_pb_color_palette ) ) ?>';
			</script>
			<div class="et-fb-form__group">
				<div class="et-fb-form__label">
					<div class="et-fb-form__label-text inline-block">
						<label for="dov_effect_entrance_speed"><?php esc_html_e( 'Close Button Cookie', 'DiviOverlays' ); ?>:</label>
					</div>
				</div>
				<div class="et-fb-settings-options et-fb-option--range">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap" data-styletagclass="closebutton">
								<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $close_cookie_control ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_closebtn_cookie" placeholder="0 Days" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $close_cookie ); ?>" data-unit="Days" data-keepunit="true" data-defaultvalue="0 Days">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<div class="et-fb-form__group">
			<?php 
			
				$input_id = 'dov_closebtn_pointoforigin';
				$styletagclass = 'closebuttonpositioning_pointoforigin';
				$styletagclassoffset = 'closebuttonpositioning';
				$blacklist_point_origins = [ 'center_center' => 1 ];
				$margins = false;
				
				do_positioning_settings( $input_id, $styletagclass, $styletagclassoffset, $blacklist_point_origins, $margins );
			?>
			</div>
			
			<?php 
				$closebtninoverlay = get_post_meta( $post_id, 'closebtninoverlay' );
				if( !isset( $closebtninoverlay[0] ) ) { $closebtninoverlay[0] = '0'; }
			?>
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Close Button within Divi Overlays container', 'DiviOverlays' ); ?></span>
				</span>
				<div class="et-fb-settings-options et-fb-option--yes-no_button updateDiviOverlaysStylesOnDiviBuilder closebtninoverlay">
					<div class="et-fb-option-container">
						<div class="et-core-control-toggle<?php if ( $closebtninoverlay[0] == 0 ) { ?> et-core-control-toggle--off<?php } else if ( $closebtninoverlay[0] == 1 ) { ?> et-core-control-toggle--on<?php } ?>">
							<div class="et-core-control-toggle__label et-core-control-toggle__label--on">
								<div class="et-core-control-toggle__text"><?php esc_html_e( 'Yes', 'DiviOverlays' ); ?></div>
								<div class="et-core-control-toggle__handle"></div>
							</div>
							<div class="et-core-control-toggle__label et-core-control-toggle__label--off">
								<div class="et-core-control-toggle__text"><?php esc_html_e( 'No', 'DiviOverlays' ); ?></div>
								<div class="et-core-control-toggle__handle"></div>
							</div>
							<input class="do-hide" type="checkbox" id="closebtninoverlay" name="closebtninoverlay" value="none" <?php checked( $closebtninoverlay[0], 1 ); ?> data-updatebuilder="true" data-css="display">
						</div>
					</div>
				</div>
			</div>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Close Clicking outside the content', 'DiviOverlays' ); ?></span>
				</span>
				<div class="et-fb-settings-options et-fb-option--yes-no_button">
					<div class="et-fb-option-container">
						<div class="et-core-control-toggle<?php if ( $closebtnclickingoutside[0] == 0 ) { ?> et-core-control-toggle--off<?php } else if ( $closebtnclickingoutside[0] == 1 ) { ?> et-core-control-toggle--on<?php } ?>">
							<div class="et-core-control-toggle__label et-core-control-toggle__label--on">
								<div class="et-core-control-toggle__text"><?php esc_html_e( 'Yes', 'DiviOverlays' ); ?></div>
								<div class="et-core-control-toggle__handle"></div>
							</div>
							<div class="et-core-control-toggle__label et-core-control-toggle__label--off">
								<div class="et-core-control-toggle__text"><?php esc_html_e( 'No', 'DiviOverlays' ); ?></div>
								<div class="et-core-control-toggle__handle"></div>
							</div>
							<input class="do-hide" type="checkbox" id="post_do_closebtnclickingoutside" name="post_do_closebtnclickingoutside" value="none" <?php checked( $closebtnclickingoutside[0], 1 ); ?>>
						</div>
					</div>
				</div>
			</div>
			
			<div class="divilife_meta_box" data-styletagclass="closebutton">
			
				<div class="et-fb-form__group">
					<span class="et-fb-form__label">
						<span class="et-fb-form__label-text"><?php esc_html_e( 'Hide Main Close Button', 'DiviOverlays' ); ?></span>
					</span>
					<div class="et-fb-settings-options et-fb-option--yes-no_button updateDiviOverlaysStylesOnDiviBuilder">
						<div class="et-fb-option-container">
							<div class="et-core-control-toggle<?php if ( $hideclosebtn[0] == 0 ) { ?> et-core-control-toggle--off<?php } else if ( $hideclosebtn[0] == 1 ) { ?> et-core-control-toggle--on<?php } ?>">
								<div class="et-core-control-toggle__label et-core-control-toggle__label--on">
									<div class="et-core-control-toggle__text"><?php esc_html_e( 'Yes', 'DiviOverlays' ); ?></div>
									<div class="et-core-control-toggle__handle"></div>
								</div>
								<div class="et-core-control-toggle__label et-core-control-toggle__label--off">
									<div class="et-core-control-toggle__text"><?php esc_html_e( 'No', 'DiviOverlays' ); ?></div>
									<div class="et-core-control-toggle__handle"></div>
								</div>
								<input class="do-hide" type="checkbox" id="post_do_hideclosebtn" name="post_do_hideclosebtn" value="none" <?php checked( $hideclosebtn[0], 1 ); ?> data-updatebuilder="true" data-css="display">
							</div>
						</div>
					</div>
				</div>
			
				<div class="et-fb-form__group">
					<span class="et-fb-form__label">
						<span class="et-fb-form__label-text"><?php esc_html_e( 'Customize Close Button', 'DiviOverlays' ); ?></span>
					</span>
					<div class="et-fb-settings-options et-fb-option--yes-no_button">
						<div class="et-fb-option-container">
							<div class="do_ccbtn do_enable_toggle et-core-control-toggle<?php if ( $customizeclosebtn[0] == 0 ) { ?> et-core-control-toggle--off<?php } else if ( $customizeclosebtn[0] == 1 ) { ?> et-core-control-toggle--on<?php } ?>" data-divioverlay-elementshowing=".enable_customizations">
								<div class="et-core-control-toggle__label et-core-control-toggle__label--on">
									<div class="et-core-control-toggle__text"><?php esc_html_e( 'Yes', 'DiviOverlays' ); ?></div>
									<div class="et-core-control-toggle__handle"></div>
								</div>
								<div class="et-core-control-toggle__label et-core-control-toggle__label--off">
									<div class="et-core-control-toggle__text"><?php esc_html_e( 'No', 'DiviOverlays' ); ?></div>
									<div class="et-core-control-toggle__handle"></div>
								</div>
								<input class="do-hide" type="checkbox" id="post_do_customizeclosebtn" name="post_do_customizeclosebtn" value="1" <?php checked( $customizeclosebtn[0], 1 ); ?>>
							</div>
						</div>
					</div>
				</div>
				
				<div class="enable_customizations<?php if ( $customizeclosebtn[0] == 1 ) { ?> do-show<?php } ?>" data-styletagclass="closebutton">
				
					<div class="et-fb-form__group">
						<span class="et-fb-form__label">
							<span class="et-fb-form__label-text"><?php esc_html_e( 'Text color', 'DiviOverlays' ); ?>:</span>
							<span class="et-fb-form__responsive" style="margin-left: 20px; opacity: 0;"><svg width="20" height="20" viewBox="-3 -3 20 20"><path d="M10 0H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM7 13a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm3-3H4V2h6z"></path></svg></span>
						</span>
						<?php print et_core_esc_previously( dov_style_devices_settings() ) ?>
						<div class="et-fb-settings-options" data-responsivemode="desktop">
							<div class="et-fb-settings-options et-fb-option--color-alpha updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
								<div class="et-fb-option-container">
									<div class="et-fb-settings-option-color et-fb-settings-option-color--has-color-manager">
										<div class="et-fb-settings-option-color-wrap--picker">
											<div class="et-fb-settings-option-color-picker show-palettes hide-result-button">
												<input class="doclosebtn-text-color color-picker et-fb-settings-option-color et-fb-settings-option-color--alpha et-fb-color-type-rgb" data-alpha="true" type="text" name="post_doclosebtn_text_color" value="<?php echo esc_attr( $textcolor ) ?>" data-updatebuilder="true" data-css="color">
											</div>
										</div>
										<div class="et-fb-settings-option-color-wrap--manager">
											<div class="et-fb-settings-color-manager et-fb-settings-color-manager--animated et-fb-settings-color-manager--collapsed has-links">
												<div class="et-fb-settings-color-manager__row">
													<div class="et-fb-settings-color-manager__column">
														<div class="et-fb-settings-color-manager__current-color-wrapper">
															<div class="et-fb-settings-color-manager__current-color et-fb-settings-color-manager__current-color-empty">
																<div class="et-fb-settings-color-manager__current-color-overlay"></div>
															</div>
															<div class="et-fb-settings-color-manager__toggle-palette-wrapper" data-for="title_text_color-color-tooltip-current-color" data-tip="true" currentitem="false">
																<div class="et-fb-settings-color-manager__toggle-palette">
																	<div class="et-fb-icon et-fb-icon--expand-palette" style="fill: rgb(162, 176, 193); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
																		<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision">
																			<g>
																				<circle cx="14" cy="20" r="2"></circle>
																				<circle cx="14" cy="13" r="2"></circle>
																				<circle cx="14" cy="6" r="2"></circle>
																			</g>
																		</svg>
																	</div>
																</div>
															</div>
														</div>
														<div class="et-fb-settings-color-manager__swatches">
															<div class="et-fb-settings-color-manager__swatches-rotator"></div>
														</div>
														<div class="et-fb-settings-color-manager__reset-color" style="background-image: url(&quot;<?php print et_core_esc_previously( get_template_directory_uri() ) . '/includes/builder/images' ?>/no-color.png&quot;);">
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
						
						$textcolor_tablet = get_post_meta( $post_id, 'post_doclosebtn_text_color_tablet', true );
						if( !isset( $textcolor_tablet ) || $textcolor_tablet === '' ) { $textcolor_tablet = '#FFFFFF'; }
						
						?>
						<div class="et-fb-settings-options do-hide" data-responsivemode="tablet">
							<div class="et-fb-settings-options et-fb-option--color-alpha updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
								<div class="et-fb-option-container">
									<div class="et-fb-settings-option-color et-fb-settings-option-color--has-color-manager">
										<div class="et-fb-settings-option-color-wrap--picker">
											<div class="et-fb-settings-option-color-picker show-palettes hide-result-button">
												<input class="doclosebtn-text-color color-picker et-fb-settings-option-color et-fb-settings-option-color--alpha et-fb-color-type-rgb" data-alpha="true" type="text" name="post_doclosebtn_text_color_tablet" value="<?php echo esc_attr( $textcolor_tablet ) ?>" data-updatebuilder="true" data-css="color">
											</div>
										</div>
										<div class="et-fb-settings-option-color-wrap--manager">
											<div class="et-fb-settings-color-manager et-fb-settings-color-manager--animated et-fb-settings-color-manager--collapsed has-links">
												<div class="et-fb-settings-color-manager__row">
													<div class="et-fb-settings-color-manager__column">
														<div class="et-fb-settings-color-manager__current-color-wrapper">
															<div class="et-fb-settings-color-manager__current-color et-fb-settings-color-manager__current-color-empty">
																<div class="et-fb-settings-color-manager__current-color-overlay"></div>
															</div>
															<div class="et-fb-settings-color-manager__toggle-palette-wrapper" data-for="title_text_color-color-tooltip-current-color" data-tip="true" currentitem="false">
																<div class="et-fb-settings-color-manager__toggle-palette">
																	<div class="et-fb-icon et-fb-icon--expand-palette" style="fill: rgb(162, 176, 193); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
																		<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision">
																			<g>
																				<circle cx="14" cy="20" r="2"></circle>
																				<circle cx="14" cy="13" r="2"></circle>
																				<circle cx="14" cy="6" r="2"></circle>
																			</g>
																		</svg>
																	</div>
																</div>
															</div>
														</div>
														<div class="et-fb-settings-color-manager__swatches">
															<div class="et-fb-settings-color-manager__swatches-rotator"></div>
														</div>
														<div class="et-fb-settings-color-manager__reset-color" style="background-image: url(&quot;<?php print et_core_esc_previously( get_template_directory_uri() ) . '/includes/builder/images' ?>/no-color.png&quot;);">
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
						
						$textcolor_phone = get_post_meta( $post_id, 'post_doclosebtn_text_color_phone', true );
						if( !isset( $textcolor_phone ) || $textcolor_phone === '' ) { $textcolor_phone = '#FFFFFF'; }
						
						?>
						<div class="et-fb-settings-options do-hide" data-responsivemode="phone">
							<div class="et-fb-settings-options et-fb-option--color-alpha updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
								<div class="et-fb-option-container">
									<div class="et-fb-settings-option-color et-fb-settings-option-color--has-color-manager">
										<div class="et-fb-settings-option-color-wrap--picker">
											<div class="et-fb-settings-option-color-picker show-palettes hide-result-button">
												<input class="doclosebtn-text-color color-picker et-fb-settings-option-color et-fb-settings-option-color--alpha et-fb-color-type-rgb" data-alpha="true" type="text" name="post_doclosebtn_text_color_phone" value="<?php echo esc_attr( $textcolor_phone ) ?>" data-updatebuilder="true" data-css="color">
											</div>
										</div>
										<div class="et-fb-settings-option-color-wrap--manager">
											<div class="et-fb-settings-color-manager et-fb-settings-color-manager--animated et-fb-settings-color-manager--collapsed has-links">
												<div class="et-fb-settings-color-manager__row">
													<div class="et-fb-settings-color-manager__column">
														<div class="et-fb-settings-color-manager__current-color-wrapper">
															<div class="et-fb-settings-color-manager__current-color et-fb-settings-color-manager__current-color-empty">
																<div class="et-fb-settings-color-manager__current-color-overlay"></div>
															</div>
															<div class="et-fb-settings-color-manager__toggle-palette-wrapper" data-for="title_text_color-color-tooltip-current-color" data-tip="true" currentitem="false">
																<div class="et-fb-settings-color-manager__toggle-palette">
																	<div class="et-fb-icon et-fb-icon--expand-palette" style="fill: rgb(162, 176, 193); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
																		<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision">
																			<g>
																				<circle cx="14" cy="20" r="2"></circle>
																				<circle cx="14" cy="13" r="2"></circle>
																				<circle cx="14" cy="6" r="2"></circle>
																			</g>
																		</svg>
																	</div>
																</div>
															</div>
														</div>
														<div class="et-fb-settings-color-manager__swatches">
															<div class="et-fb-settings-color-manager__swatches-rotator"></div>
														</div>
														<div class="et-fb-settings-color-manager__reset-color" style="background-image: url(&quot;<?php print et_core_esc_previously( get_template_directory_uri() ) . '/includes/builder/images' ?>/no-color.png&quot;);">
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<div class="et-fb-form__group">
						<span class="et-fb-form__label">
							<span class="et-fb-form__label-text"><?php esc_html_e( 'Background color', 'DiviOverlays' ); ?>:</span>
							<span class="et-fb-form__responsive" style="margin-left: 20px; opacity: 0;"><svg width="20" height="20" viewBox="-3 -3 20 20"><path d="M10 0H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM7 13a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm3-3H4V2h6z"></path></svg></span>
						</span>
						<?php print et_core_esc_previously( dov_style_devices_settings() ) ?>
						<div class="et-fb-settings-options" data-responsivemode="desktop">
							<div class="et-fb-settings-options et-fb-option--color-alpha updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
								<div class="et-fb-option-container">
									<div class="et-fb-settings-option-color et-fb-settings-option-color--has-color-manager">
										<div class="et-fb-settings-option-color-wrap--picker">
											<div class="et-fb-settings-option-color-picker show-palettes hide-result-button">
												<input class="doclosebtn-bg-color color-picker et-fb-settings-option-color et-fb-settings-option-color--alpha et-fb-color-type-rgb" data-alpha="true" type="text" name="post_doclosebtn_bg_color" value="<?php echo esc_attr( $bgcolor ) ?>" data-updatebuilder="true" data-css="background-color">
											</div>
										</div>
										<div class="et-fb-settings-option-color-wrap--manager">
											<div class="et-fb-settings-color-manager et-fb-settings-color-manager--animated et-fb-settings-color-manager--collapsed has-links">
												<div class="et-fb-settings-color-manager__row">
													<div class="et-fb-settings-color-manager__column">
														<div class="et-fb-settings-color-manager__current-color-wrapper">
															<div class="et-fb-settings-color-manager__current-color et-fb-settings-color-manager__current-color-empty">
																<div class="et-fb-settings-color-manager__current-color-overlay"></div>
															</div>
															<div class="et-fb-settings-color-manager__toggle-palette-wrapper" data-for="title_text_color-color-tooltip-current-color" data-tip="true" currentitem="false">
																<div class="et-fb-settings-color-manager__toggle-palette">
																	<div class="et-fb-icon et-fb-icon--expand-palette" style="fill: rgb(162, 176, 193); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
																		<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision">
																			<g>
																				<circle cx="14" cy="20" r="2"></circle>
																				<circle cx="14" cy="13" r="2"></circle>
																				<circle cx="14" cy="6" r="2"></circle>
																			</g>
																		</svg>
																	</div>
																</div>
															</div>
														</div>
														<div class="et-fb-settings-color-manager__swatches">
															<div class="et-fb-settings-color-manager__swatches-rotator">
																
															</div>
														</div>
														<div class="et-fb-settings-color-manager__reset-color" style="background-image: url(&quot;<?php print et_core_esc_previously( get_template_directory_uri() ) . '/includes/builder/images' ?>/no-color.png&quot;);">
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
						
						$bgcolor_tablet = get_post_meta( $post_id, 'post_doclosebtn_bg_color_tablet', true );
						if( !isset( $bgcolor_tablet ) || $bgcolor_tablet === '' ) { $bgcolor_tablet = '#333333'; }
						
						?>
						<div class="et-fb-settings-options do-hide" data-responsivemode="tablet">
							<div class="et-fb-settings-options et-fb-option--color-alpha updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
								<div class="et-fb-option-container">
									<div class="et-fb-settings-option-color et-fb-settings-option-color--has-color-manager">
										<div class="et-fb-settings-option-color-wrap--picker">
											<div class="et-fb-settings-option-color-picker show-palettes hide-result-button">
												<input class="doclosebtn-bg-color color-picker et-fb-settings-option-color et-fb-settings-option-color--alpha et-fb-color-type-rgb" data-alpha="true" type="text" name="post_doclosebtn_bg_color_tablet" value="<?php echo esc_attr( $bgcolor_tablet ) ?>" data-updatebuilder="true" data-css="background-color">
											</div>
										</div>
										<div class="et-fb-settings-option-color-wrap--manager">
											<div class="et-fb-settings-color-manager et-fb-settings-color-manager--animated et-fb-settings-color-manager--collapsed has-links">
												<div class="et-fb-settings-color-manager__row">
													<div class="et-fb-settings-color-manager__column">
														<div class="et-fb-settings-color-manager__current-color-wrapper">
															<div class="et-fb-settings-color-manager__current-color et-fb-settings-color-manager__current-color-empty">
																<div class="et-fb-settings-color-manager__current-color-overlay"></div>
															</div>
															<div class="et-fb-settings-color-manager__toggle-palette-wrapper" data-for="title_text_color-color-tooltip-current-color" data-tip="true" currentitem="false">
																<div class="et-fb-settings-color-manager__toggle-palette">
																	<div class="et-fb-icon et-fb-icon--expand-palette" style="fill: rgb(162, 176, 193); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
																		<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision">
																			<g>
																				<circle cx="14" cy="20" r="2"></circle>
																				<circle cx="14" cy="13" r="2"></circle>
																				<circle cx="14" cy="6" r="2"></circle>
																			</g>
																		</svg>
																	</div>
																</div>
															</div>
														</div>
														<div class="et-fb-settings-color-manager__swatches">
															<div class="et-fb-settings-color-manager__swatches-rotator">
																
															</div>
														</div>
														<div class="et-fb-settings-color-manager__reset-color" style="background-image: url(&quot;<?php print et_core_esc_previously( get_template_directory_uri() ) . '/includes/builder/images' ?>/no-color.png&quot;);">
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
						
						$bgcolor_phone = get_post_meta( $post_id, 'post_doclosebtn_bg_color_phone', true );
						if( !isset( $bgcolor_phone ) || $bgcolor_phone === '' ) { $bgcolor_phone = '#333333'; }
						
						?>
						<div class="et-fb-settings-options do-hide" data-responsivemode="phone">
							<div class="et-fb-settings-options et-fb-option--color-alpha updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
								<div class="et-fb-option-container">
									<div class="et-fb-settings-option-color et-fb-settings-option-color--has-color-manager">
										<div class="et-fb-settings-option-color-wrap--picker">
											<div class="et-fb-settings-option-color-picker show-palettes hide-result-button">
												<input class="doclosebtn-bg-color color-picker et-fb-settings-option-color et-fb-settings-option-color--alpha et-fb-color-type-rgb" data-alpha="true" type="text" name="post_doclosebtn_bg_color_phone" value="<?php echo esc_attr( $bgcolor_phone ) ?>" data-updatebuilder="true" data-css="background-color">
											</div>
										</div>
										<div class="et-fb-settings-option-color-wrap--manager">
											<div class="et-fb-settings-color-manager et-fb-settings-color-manager--animated et-fb-settings-color-manager--collapsed has-links">
												<div class="et-fb-settings-color-manager__row">
													<div class="et-fb-settings-color-manager__column">
														<div class="et-fb-settings-color-manager__current-color-wrapper">
															<div class="et-fb-settings-color-manager__current-color et-fb-settings-color-manager__current-color-empty">
																<div class="et-fb-settings-color-manager__current-color-overlay"></div>
															</div>
															<div class="et-fb-settings-color-manager__toggle-palette-wrapper" data-for="title_text_color-color-tooltip-current-color" data-tip="true" currentitem="false">
																<div class="et-fb-settings-color-manager__toggle-palette">
																	<div class="et-fb-icon et-fb-icon--expand-palette" style="fill: rgb(162, 176, 193); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
																		<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision">
																			<g>
																				<circle cx="14" cy="20" r="2"></circle>
																				<circle cx="14" cy="13" r="2"></circle>
																				<circle cx="14" cy="6" r="2"></circle>
																			</g>
																		</svg>
																	</div>
																</div>
															</div>
														</div>
														<div class="et-fb-settings-color-manager__swatches">
															<div class="et-fb-settings-color-manager__swatches-rotator">
																
															</div>
														</div>
														<div class="et-fb-settings-color-manager__reset-color" style="background-image: url(&quot;<?php print et_core_esc_previously( get_template_directory_uri() ) . '/includes/builder/images' ?>/no-color.png&quot;);">
														</div>
													</div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<div class="et-fb-form__group">
						<span class="et-fb-form__label">
							<span class="et-fb-form__label-text"><?php esc_html_e( 'Font size', 'DiviOverlays' ); ?>:</span>
							<span class="et-fb-form__responsive" style="margin-left: 20px; opacity: 0;"><svg width="20" height="20" viewBox="-3 -3 20 20"><path d="M10 0H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM7 13a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm3-3H4V2h6z"></path></svg></span>
						</span>
						<?php print et_core_esc_previously( dov_style_devices_settings() ) ?>
						<div class="et-fb-settings-options et-fb-option--range" data-responsivemode="desktop">
							<div class="et-fb-option-container">
								<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
									<div class="et-fb-settings-option-inputs-wrap post_doclosebtn_fontsize updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
										<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $fontsize_control ); ?>">
										<div class="et-fb-range-number">
											<div class="et-fb-settings-option--numeric-control">
												<input name="post_doclosebtn_fontsize" placeholder="25px" step="1" class="post_doclosebtn_fontsize et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $fontsize ); ?>" data-unit="px" data-defaultvalue="25px" data-updatebuilder="true" data-css="font-size">
												<div class="et-fb-incrementor">
													<div class="increase"></div>
													<div class="decrease"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
						
						$fontsize_tablet = get_post_meta( $post_id, 'post_doclosebtn_fontsize_tablet', true );
						if( !isset( $fontsize_tablet ) || $fontsize_tablet === '' ) { $fontsize_tablet = '25px'; }
						if ( is_numeric( $fontsize_tablet ) === TRUE ) { $fontsize_tablet = $fontsize_tablet . 'px'; }
						$fontsize_control_tablet = floatval( $fontsize_tablet );
						
						?>
						<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="tablet">
							<div class="et-fb-option-container">
								<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
									<div class="et-fb-settings-option-inputs-wrap post_doclosebtn_fontsize updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
										<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $fontsize_control_tablet ); ?>">
										<div class="et-fb-range-number">
											<div class="et-fb-settings-option--numeric-control">
												<input name="post_doclosebtn_fontsize_tablet" placeholder="25px" step="1" class="post_doclosebtn_fontsize et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $fontsize_tablet ); ?>" data-unit="px" data-defaultvalue="25px" data-updatebuilder="true" data-css="font-size">
												<div class="et-fb-incrementor">
													<div class="increase"></div>
													<div class="decrease"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
						
						$fontsize_phone = get_post_meta( $post_id, 'post_doclosebtn_fontsize_phone', true );
						if( !isset( $fontsize_phone ) || $fontsize_phone === '' ) { $fontsize_phone = '25px'; }
						if ( is_numeric( $fontsize_phone ) === TRUE ) { $fontsize_phone = $fontsize_phone . 'px'; }
						$fontsize_control_phone = floatval( $fontsize_phone );
						
						?>
						<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="phone">
							<div class="et-fb-option-container">
								<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
									<div class="et-fb-settings-option-inputs-wrap post_doclosebtn_fontsize updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
										<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $fontsize_control_phone ); ?>">
										<div class="et-fb-range-number">
											<div class="et-fb-settings-option--numeric-control">
												<input name="post_doclosebtn_fontsize_phone" placeholder="25px" step="1" class="post_doclosebtn_fontsize et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $fontsize_phone ); ?>" data-unit="px" data-defaultvalue="25px" data-updatebuilder="true" data-css="font-size">
												<div class="et-fb-incrementor">
													<div class="increase"></div>
													<div class="decrease"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<div class="et-fb-form__group">
						<span class="et-fb-form__label">
							<span class="et-fb-form__label-text"><?php esc_html_e( 'Border radius', 'DiviOverlays' ); ?>:</span>
							<span class="et-fb-form__responsive" style="margin-left: 20px; opacity: 0;"><svg width="20" height="20" viewBox="-3 -3 20 20"><path d="M10 0H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM7 13a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm3-3H4V2h6z"></path></svg></span>
						</span>
						<?php print et_core_esc_previously( dov_style_devices_settings() ) ?>
						<div class="et-fb-settings-options et-fb-option--range" data-responsivemode="desktop">
							<div class="et-fb-option-container">
								<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
									<div class="et-fb-settings-option-inputs-wrap post_doclosebtn_borderradius updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
										<input type="range" min="0" max="50" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $borderradius_control ); ?>">
										<div class="et-fb-range-number">
											<div class="et-fb-settings-option--numeric-control">
												<input name="post_doclosebtn_borderradius" placeholder="50%" step="1" class="post_doclosebtn_borderradius et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $borderradius ); ?>" data-unit="%" data-defaultvalue="50%" data-updatebuilder="true" data-css="border-radius">
												<div class="et-fb-incrementor">
													<div class="increase"></div>
													<div class="decrease"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
						
						$borderradius_tablet = get_post_meta( $post_id, 'post_doclosebtn_borderradius_tablet', true );
						if( !isset( $borderradius_tablet ) || $borderradius_tablet === '' || $borderradius_tablet == 0 ) { $borderradius_tablet = '50%'; }
						if ( is_numeric( $borderradius_tablet ) === TRUE ) { $borderradius_tablet = $borderradius_tablet . '%'; }
						$borderradius_control_tablet = floatval( $borderradius_tablet );
						
						?>
						<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="tablet">
							<div class="et-fb-option-container">
								<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
									<div class="et-fb-settings-option-inputs-wrap post_doclosebtn_borderradius updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
										<input type="range" min="0" max="50" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $borderradius_control_tablet ); ?>">
										<div class="et-fb-range-number">
											<div class="et-fb-settings-option--numeric-control">
												<input name="post_doclosebtn_borderradius_tablet" placeholder="50%" step="1" class="post_doclosebtn_borderradius et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $borderradius_tablet ); ?>" data-unit="%" data-defaultvalue="50%" data-updatebuilder="true" data-css="border-radius">
												<div class="et-fb-incrementor">
													<div class="increase"></div>
													<div class="decrease"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
						
						$borderradius_phone = get_post_meta( $post_id, 'post_doclosebtn_borderradius_phone', true );
						if( !isset( $borderradius_phone ) || $borderradius_phone === '' || $borderradius_phone == 0 ) { $borderradius_phone = '50%'; }
						if ( is_numeric( $borderradius_phone ) === TRUE ) { $borderradius_phone = $borderradius_phone . '%'; }
						$borderradius_control_phone = floatval( $borderradius_phone );
						
						?>
						<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="phone">
							<div class="et-fb-option-container">
								<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
									<div class="et-fb-settings-option-inputs-wrap post_doclosebtn_borderradius updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
										<input type="range" min="0" max="50" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $borderradius_control_phone ); ?>">
										<div class="et-fb-range-number">
											<div class="et-fb-settings-option--numeric-control">
												<input name="post_doclosebtn_borderradius_phone" placeholder="50%" step="1" class="post_doclosebtn_borderradius et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $borderradius_phone ); ?>" data-unit="%" data-defaultvalue="50%" data-updatebuilder="true" data-css="border-radius">
												<div class="et-fb-incrementor">
													<div class="increase"></div>
													<div class="decrease"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<div class="et-fb-form__group">
						<span class="et-fb-form__label">
							<span class="et-fb-form__label-text"><?php esc_html_e( 'Padding', 'DiviOverlays' ); ?>:</span>
							<span class="et-fb-form__responsive" style="margin-left: 20px; opacity: 0;"><svg width="20" height="20" viewBox="-3 -3 20 20"><path d="M10 0H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM7 13a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm3-3H4V2h6z"></path></svg></span>
						</span>
						<?php print et_core_esc_previously( dov_style_devices_settings() ) ?>
						<div class="et-fb-settings-options et-fb-option--range" data-responsivemode="desktop">
							<div class="et-fb-option-container">
								<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
									<div class="et-fb-settings-option-inputs-wrap post_doclosebtn_padding updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
										<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $padding_control ); ?>">
										<div class="et-fb-range-number">
											<div class="et-fb-settings-option--numeric-control">
												<input name="post_doclosebtn_padding" placeholder="0px" step="1" class="post_doclosebtn_padding et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $padding ); ?>" data-unit="px" data-defaultvalue="0px" data-updatebuilder="true" data-css="padding">
												<div class="et-fb-incrementor">
													<div class="increase"></div>
													<div class="decrease"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
						
						$padding_tablet = get_post_meta( $post_id, 'post_doclosebtn_padding_tablet', true );
						if( !isset( $padding_tablet ) || $padding_tablet === '' || $padding_tablet == 0 ) { $padding_tablet = '0px'; }
						if ( is_numeric( $padding_tablet ) === TRUE ) { $padding_tablet = $padding_tablet . 'px'; }
						$padding_control_tablet = floatval( $padding_tablet );
						
						?>
						<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="tablet">
							<div class="et-fb-option-container">
								<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
									<div class="et-fb-settings-option-inputs-wrap post_doclosebtn_padding updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
										<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $padding_control_tablet ); ?>">
										<div class="et-fb-range-number">
											<div class="et-fb-settings-option--numeric-control">
												<input name="post_doclosebtn_padding_tablet" placeholder="0px" step="1" class="post_doclosebtn_padding et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $padding_tablet ); ?>" data-unit="px" data-defaultvalue="0px" data-updatebuilder="true" data-css="padding">
												<div class="et-fb-incrementor">
													<div class="increase"></div>
													<div class="decrease"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<?php
						
						$padding_phone = get_post_meta( $post_id, 'post_doclosebtn_padding_phone', true );
						if( !isset( $padding_phone ) || $padding_phone === '' || $padding_phone == 0 ) { $padding_phone = '0px'; }
						if ( is_numeric( $padding_phone ) === TRUE ) { $padding_phone = $padding_phone . 'px'; }
						$padding_control_phone = floatval( $padding_phone );
						
						?>
						<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="phone">
							<div class="et-fb-option-container">
								<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
									<div class="et-fb-settings-option-inputs-wrap post_doclosebtn_padding updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="closebutton">
										<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $padding_control_phone ); ?>">
										<div class="et-fb-range-number">
											<div class="et-fb-settings-option--numeric-control">
												<input name="post_doclosebtn_padding_phone" placeholder="0px" step="1" class="post_doclosebtn_padding et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $padding_phone ); ?>" data-unit="px" data-defaultvalue="0px" data-updatebuilder="true" data-css="padding">
												<div class="et-fb-incrementor">
													<div class="increase"></div>
													<div class="decrease"></div>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<div class="et-fb-form__group">
						<div class="divilife_meta_box overlay-customclose-wrap">
							<div class="et-fb-form__label">
								<div class="et-fb-form__label-text inline-block">
									<label for="overlay-customclose-btn"><?php esc_html_e( 'Preview', 'DiviOverlays' ); ?>:</label>
								</div>
							</div>
							<button type="button" class="overlay-customclose-btn"><span>&times;</span></button>
						</div>
					</div>
			
				</div>
			</div>
			<?php
		}
		
	endif;		


	if ( ! function_exists( 'do_moresettings_callback' ) ) :

		function do_moresettings_callback() {
			
			?>
			
			<div class="et-fb-tabs__panel et-fb-tabs__panel--active et-fb-tabs__panel--filter-dropdown et-fb-tabs__panel--general">
				<div class="et-fb-form__toggle et-fb-form__toggle-enabled">
					<div class="et-fb-form__toggle-title">
						<h3><?php esc_html_e( 'Advanced', 'DiviOverlays' ); ?></h3>
						<div class="et-fb-icon et-fb-icon--next" style="fill: rgb(62, 80, 98); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
						<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision"><g><path d="M15.8 14L12.3 18C11.9 18.4 11.9 19 12.3 19.4 12.7 19.8 13.3 19.8 13.7 19.4L17.7 14.9C17.9 14.7 18 14.3 18 14 18.1 13.7 18 13.4 17.7 13.1L13.7 8.6C13.3 8.2 12.7 8.2 12.3 8.6 11.9 9 11.9 9.6 12.3 10L15.8 14 15.8 14Z" fill-rule="evenodd"></path></g></svg>
						</div>
					</div>
					<div class="dl-do-toggleContent">
						<?php do_advanced_settings(); ?>
					</div>
				</div>
				<div class="et-fb-form__toggle et-fb-form__toggle-enabled et-fb-form__toggle-opened">
					<div class="et-fb-form__toggle-title">
						<h3><?php esc_html_e( 'Conditions', 'DiviOverlays' ); ?></h3>
						<div class="et-fb-icon et-fb-icon--next" style="fill: rgb(62, 80, 98); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
						<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision"><g><path d="M15.8 14L12.3 18C11.9 18.4 11.9 19 12.3 19.4 12.7 19.8 13.3 19.8 13.7 19.4L17.7 14.9C17.9 14.7 18 14.3 18 14 18.1 13.7 18 13.4 17.7 13.1L13.7 8.6C13.3 8.2 12.7 8.2 12.3 8.6 11.9 9 11.9 9.6 12.3 10L15.8 14 15.8 14Z" fill-rule="evenodd"></path></g></svg>
						</div>
					</div>
					<div class="dl-do-toggleContent" style="display: block;">
						<?php do_conditions_settings(); ?>
					</div>
				</div>
			</div>
			
			<?php
		}
		
	endif;
	
	
	if ( ! function_exists( 'do_advanced_settings' ) ) :

		function do_advanced_settings() {
			
			$post_id = get_the_ID();
		
			wp_nonce_field( 'do_mainpage_preventscroll', 'do_mainpage_preventscroll_nonce' );
			
			$preventscroll = get_post_meta( $post_id, 'post_do_preventscroll' );
			
			$css_selector = get_post_meta( $post_id, 'post_css_selector', true );
			
			$enableurltrigger = get_post_meta( $post_id, 'post_enableurltrigger' );
			
			$do_enableajax = get_post_meta( $post_id, 'do_enableajax' );
			
			$do_enableesckey = get_post_meta( $post_id, 'do_enableesckey' );
			
			$do_forcerender = get_post_meta( $post_id, 'do_forcerender' );
			
			if( !isset( $preventscroll[0] ) ) {
				
				$preventscroll[0] = '0';
			}
			
			if( !isset( $enableurltrigger[0] ) ) {
				
				$enableurltrigger[0] = '0';
			}
			
			if( !isset( $do_enableajax[0] ) ) {
				
				$do_enableajax[0] = '0';
			}
			
			if( !isset( $do_enableesckey[0] ) ) {
				
				$do_enableesckey[0] = '1';
			}
			
			if( !isset( $do_forcerender[0] ) ) {
				
				$do_forcerender[0] = '0';
			}
			?>
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text">CSS <?php esc_html_e( 'Selector Trigger', 'DiviOverlays' ); ?></span>
				</span>
				<div class="et-fb-settings-options et-fb-option--text">
					<div class="et-fb-option-container">
						<input class="et-fb-settings-option-input et-fb-settings-option-input--block input450px" type="text" name="post_css_selector" placeholder="" value="<?php echo esc_attr( $css_selector ); ?>">
					</div>
				</div>
			</div>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Enable ESC Key (Closes the overlay when escape key is pressed)', 'DiviOverlays', 'DiviOverlays' ); ?></span>
				</span>
				<div class="et-fb-settings-options et-fb-option--yes-no_button">
					<div class="et-fb-option-container">
						<div class="et-core-control-toggle<?php if ( $do_enableesckey[0] == 0 ) { ?> et-core-control-toggle--off<?php } else if ( $do_enableesckey[0] == 1 ) { ?> et-core-control-toggle--on<?php } ?>">
							<div class="et-core-control-toggle__label et-core-control-toggle__label--on">
								<div class="et-core-control-toggle__text"><?php esc_html_e( 'Yes', 'DiviOverlays' ); ?></div>
								<div class="et-core-control-toggle__handle"></div>
							</div>
							<div class="et-core-control-toggle__label et-core-control-toggle__label--off">
								<div class="et-core-control-toggle__text"><?php esc_html_e( 'No', 'DiviOverlays' ); ?></div>
								<div class="et-core-control-toggle__handle"></div>
							</div>
							<input class="do-hide" type="checkbox" id="do_enableesckey" name="do_enableesckey" value="1" <?php checked( $do_enableesckey[0], 1 ); ?>>
						</div>
					</div>
				</div>
			</div>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Enable URL Trigger', 'DiviOverlays' ); ?></span>
				</span>
				<div class="et-fb-settings-options et-fb-option--yes-no_button">
					<div class="et-fb-option-container">
						<div class="et-core-control-toggle<?php if ( $enableurltrigger[0] == 0 ) { ?> et-core-control-toggle--off<?php } else if ( $enableurltrigger[0] == 1 ) { ?> et-core-control-toggle--on<?php } ?>">
							<div class="et-core-control-toggle__label et-core-control-toggle__label--on">
								<div class="et-core-control-toggle__text"><?php esc_html_e( 'Yes', 'DiviOverlays' ); ?></div>
								<div class="et-core-control-toggle__handle"></div>
							</div>
							<div class="et-core-control-toggle__label et-core-control-toggle__label--off">
								<div class="et-core-control-toggle__text"><?php esc_html_e( 'No', 'DiviOverlays' ); ?></div>
								<div class="et-core-control-toggle__handle"></div>
							</div>
							<input class="do-hide" type="checkbox" id="post_enableurltrigger" name="post_enableurltrigger" value="1" <?php checked( $enableurltrigger[0], 1 ); ?>>
						</div>
					</div>
				</div>
			</div>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Enable AJAX (Load content on call)', 'DiviOverlays' ); ?></span>
				</span>
				<div class="et-fb-settings-options et-fb-option--yes-no_button">
					<div class="et-fb-option-container">
						<div class="et-core-control-toggle<?php if ( $do_enableajax[0] == 0 ) { ?> et-core-control-toggle--off<?php } else if ( $do_enableajax[0] == 1 ) { ?> et-core-control-toggle--on<?php } ?>">
							<div class="et-core-control-toggle__label et-core-control-toggle__label--on">
								<div class="et-core-control-toggle__text"><?php esc_html_e( 'Yes', 'DiviOverlays' ); ?></div>
								<div class="et-core-control-toggle__handle"></div>
							</div>
							<div class="et-core-control-toggle__label et-core-control-toggle__label--off">
								<div class="et-core-control-toggle__text"><?php esc_html_e( 'No', 'DiviOverlays' ); ?></div>
								<div class="et-core-control-toggle__handle"></div>
							</div>
							<input class="do-hide" type="checkbox" id="do_enableajax" name="do_enableajax" value="1" <?php checked( $do_enableajax[0], 1 ); ?>>
						</div>
					</div>
				</div>
			</div>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Prevent main page scrolling', 'DiviOverlays' ); ?></span>
				</span>
				<div class="et-fb-settings-options et-fb-option--yes-no_button">
					<div class="et-fb-option-container">
						<div class="et-core-control-toggle<?php if ( $preventscroll[0] == 0 ) { ?> et-core-control-toggle--off<?php } else if ( $preventscroll[0] == 1 ) { ?> et-core-control-toggle--on<?php } ?>">
							<div class="et-core-control-toggle__label et-core-control-toggle__label--on">
								<div class="et-core-control-toggle__text"><?php esc_html_e( 'Yes', 'DiviOverlays' ); ?></div>
								<div class="et-core-control-toggle__handle"></div>
							</div>
							<div class="et-core-control-toggle__label et-core-control-toggle__label--off">
								<div class="et-core-control-toggle__text"><?php esc_html_e( 'No', 'DiviOverlays' ); ?></div>
								<div class="et-core-control-toggle__handle"></div>
							</div>
							<input class="do-hide" type="checkbox" id="post_do_preventscroll" name="post_do_preventscroll" value="1" <?php checked( $preventscroll[0], 1 ); ?>>
						</div>
					</div>
				</div>
			</div>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Force Render', 'DiviOverlays' ); ?></span>
				</span>
				<div class="et-fb-settings-options et-fb-option--yes-no_button">
					<div class="et-fb-option-container">
						<div class="et-core-control-toggle<?php if ( $do_forcerender[0] == 0 ) { ?> et-core-control-toggle--off<?php } else if ( $do_forcerender[0] == 1 ) { ?> et-core-control-toggle--on<?php } ?>">
							<div class="et-core-control-toggle__label et-core-control-toggle__label--on">
								<div class="et-core-control-toggle__text"><?php esc_html_e( 'Yes', 'DiviOverlays' ); ?></div>
								<div class="et-core-control-toggle__handle"></div>
							</div>
							<div class="et-core-control-toggle__label et-core-control-toggle__label--off">
								<div class="et-core-control-toggle__text"><?php esc_html_e( 'No', 'DiviOverlays' ); ?></div>
								<div class="et-core-control-toggle__handle"></div>
							</div>
							<input class="do-hide" type="checkbox" id="do_forcerender" name="do_forcerender" value="1" <?php checked( $do_forcerender[0], 1 ); ?>>
						</div>
					</div>
				</div>
			</div>
			
			<?php
		}
		
	endif;


	if ( ! function_exists( 'do_conditions_settings' ) ) :

		function do_conditions_settings() {
			
				$post_id = get_the_ID();
				$displayConditions = base64_decode( get_post_meta( $post_id, 'dov_displayConditionsJSON', true ) );
				
				if( !isset( $displayConditions ) || $displayConditions === '' ) {
					
					$displayConditions = '';
				}
			
			?>
			
			<div class="et-fb-form__group">
				<div class="et-fb-settings-options et-fb-option--display-conditions divioverlays-display-conditions">
					<div class="et-fb-option-container">
						<input type="hidden" name="dov_displayConditionsJSON" value="">
						<script>
						var divioverlays_displayconditions = '<?php print $displayConditions // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>';
						</script>
						<div>
							<div>
								<div>
									<ul class="et-fb-settings-sortable-rows">
										<div class="et-fb-display-conditions-container">
											<div class="et-fb-settings-custom-select-wrapper-outer">
												<div class="divilife-minioverlay-background"></div>
												<div class="divilife-et-fb-display-conditions divilife-minioverlay et-fb-settings-custom-select-wrapper et-fb-settings-option-select-closed et-fb-field-settings-modal" data-divilifeminioverlay="1" data-divilifeopenconditiontype="" data-divilifeopenconditionid="">
												</div>
											</div>
										</div>
										
										<span class="et-fb-item-button-wrap--add">
											<div class="et-fb-item-addable-button">
												<button type="button" data-tip="Add Condition" class="et-fb-button et-fb-button--round" style="opacity: 1; transform: scale(1);" role="add-condition-button" data-divilifeopenminioverlay="1">
													<div class="et-fb-icon et-fb-icon--add" style="fill: rgb(255, 255, 255); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
														<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision"><g><path d="M18 13h-3v-3a1 1 0 0 0-2 0v3h-3a1 1 0 0 0 0 2h3v3a1 1 0 0 0 2 0v-3h3a1 1 0 0 0 0-2z" fill-rule="evenodd"></path></g></svg>
													</div>
													<canvas style="border-radius: inherit; height: 200%; left: -50%; position: absolute; top: -50%; width: 200%;" width="0" height="0"></canvas>
												</button>
												<label class="et-fb-form__label">Add Condition</label>
											</div>
										</span>
									</ul>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<?php
		}
		
	endif;
	
	
	if ( ! function_exists( 'do_automatictriggers_callback' ) ) :

		function do_automatictriggers_callback() {
			
			$post_id = get_the_ID();
			$disablemobile = get_post_meta( $post_id, 'overlay_automatictrigger_disablemobile' );
			$disabletablet = get_post_meta( $post_id, 'overlay_automatictrigger_disabletablet' );
			$disabledesktop = get_post_meta( $post_id, 'overlay_automatictrigger_disabledesktop' );
			
			$onceperload = get_post_meta( $post_id, 'overlay_automatictrigger_onceperload' );
			
			$enable_scheduling = get_post_meta( $post_id, 'do_enable_scheduling' );
			$date_start = get_post_meta( $post_id, 'do_date_start', true );
			$date_end = get_post_meta( $post_id, 'do_date_end', true );
			$date_start = doConvertDateToUserTimezone( $date_start );
			$date_end = doConvertDateToUserTimezone( $date_end );
			
			$time_start = get_post_meta( $post_id, 'do_time_start', true );
			$time_end = get_post_meta( $post_id, 'do_time_end', true );
			
			$overlay_at_selected = get_post_meta( $post_id, 'overlay_automatictrigger', true );
			$overlay_ats = array(
				''   => esc_html__( 'None', 'Divi' ),
				'overlay-timed'   => esc_html__( 'Timed Delay', 'Divi' ),
				'overlay-scroll'    => esc_html__( 'Scroll Percentage', 'Divi' ),
				'overlay-exit' => esc_html__( 'Exit Intent', 'Divi' ),
			);
			
			for( $a = 1; $a <= 7; $a++ ) {
				
				$daysofweek[$a] = get_post_meta( $post_id, 'divioverlays_scheduling_daysofweek_' . $a );
				
				if ( !isset( $daysofweek[$a][0] ) ) {
					
					$daysofweek[$a][0] = '0';
				}
				else {
					
					$daysofweek[$a] = $daysofweek[$a][0];
				}
			}
			
			if( !isset( $disablemobile[0] ) ) {
				
				$disablemobile[0] = '1';
			}
			
			if( !isset( $disabletablet[0] ) ) {
				
				$disabletablet[0] = '0';
			}
			
			if( !isset( $disabledesktop[0] ) ) {
				
				$disabledesktop[0] = '0';
			}
			
			if( !isset( $onceperload[0] ) ) {
				
				$onceperload[0] = '1';
			}
			
			if( !isset( $enable_scheduling[0] ) ) {
				
				$enable_scheduling[0] = 0;
			}
			?>
			<p class="divi_automatictrigger_settings et_pb_single_title">
				<label for="post_overlay_automatictrigger"></label>
				<select id="post_overlay_automatictrigger" name="post_overlay_automatictrigger" class="post_overlay_automatictrigger chosen">
				<?php
				foreach ( $overlay_ats as $at_value => $at_name ) {
					printf( '<option value="%2$s"%3$s>%1$s</option>',
						esc_html( $at_name ),
						esc_attr( $at_value ),
						selected( $at_value, $overlay_at_selected, false )
					);
				} ?>
				</select>
			</p>
			
			<?php
			
				$at_timed_control = $at_timed = get_post_meta( $post_id, 'overlay_automatictrigger_timed_value', true );
				if ( $at_timed === '' || $at_timed == 0 ) {
					
					$at_timed = 0;
					$at_timed_control = '';
				}
				
				$at_scroll_from = get_post_meta( $post_id, 'overlay_automatictrigger_scroll_from_value', true );
				$at_scroll_to = get_post_meta( $post_id, 'overlay_automatictrigger_scroll_to_value', true );
			?>
			<div class="divi_automatictrigger_timed<?php if ( $overlay_at_selected == 'overlay-timed' ) { ?> do-show<?php } ?>">
				<div class="et-fb-form__group">
					<div class="et-fb-form__label">
						<div class="et-fb-form__label-text inline-block">
							<label for="et-fb-form__label-text"><?php esc_html_e( 'Specify timed delay (in seconds)', 'DiviOverlays' ); ?>:</label>
						</div>
					</div>
					<div class="et-fb-settings-options et-fb-option--range">
						<div class="et-fb-option-container">
							<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
								<div class="et-fb-settings-option-inputs-wrap">
									<input type="range" min="0" max="100" step="1" class="post_at_timed et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $at_timed ); ?>">
									<div class="et-fb-range-number">
										<div class="et-fb-settings-option--numeric-control">
											<input name="post_at_timed" placeholder="0" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $at_timed_control ); ?>" data-defaultvalue="0">
											<div class="et-fb-incrementor">
												<div class="increase"></div>
												<div class="decrease"></div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="et-fb-form__group"></div>
			</div>
			
			<div class="divi_automatictrigger_scroll<?php if ( $overlay_at_selected == 'overlay-scroll' ) { ?> do-show<?php } ?>">
				<div class="et-fb-form__group">
					<div class="et-fb-form__label">
						<div class="et-fb-form__label-text inline-block">
							<label for="et-fb-form__label-text"><?php esc_html_e( 'Specify in pixels or percentage', 'DiviOverlays' ); ?>:</label>
						</div>
					</div>
					<div class="et-fb-settings-options et-fb-option--custom-padding">
						<div class="et-fb-option-container">
							<div class="et-fb-settings-option-inner et-fb-settings-option-inner-responsive et-fb-settings-option-inner-input-margins">
								<div class="et-fb-settings-option-inputs-wrap">
									<div class="et-fb-settings-option-input-wrap top">
										<div class="et-fb-settings-option--numeric-spinner-control ">
											<div class="et-fb-settings-option--numeric-control">
												<input type="text" name="post_at_scroll_from" value="<?php print esc_attr( $at_scroll_from ) ?>" class="post_at_scroll et-fb-settings-option-input">
											</div>
											<div class="numeric-spinner-control-label-wrapper">
												<span class="et-fb-settings-option-input-label"><?php esc_html_e( 'From', 'DiviOverlays' ); ?></span>
											</div>
										</div>
									</div>
									<div id="datetimepicker11"></div>
									<div class="et-fb-settings-option-input-wrap left">
										<div class="et-fb-settings-option--numeric-spinner-control ">
											<div class="et-fb-settings-option--numeric-control">
												<input type="text" name="post_at_scroll_to" value="<?php print esc_attr( $at_scroll_to ) ?>" class="post_at_scroll et-fb-settings-option-input">
											</div>
											<div class="numeric-spinner-control-label-wrapper">
												<span class="et-fb-settings-option-input-label"><?php esc_html_e( 'to', 'DiviOverlays' ); ?></span>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="et-fb-form__group"></div>
				
			</div>
			
			<div class="divilife_meta_box do-at-devices<?php if ( strlen( $overlay_at_selected ) > 1 ) { ?> do-show<?php } ?>">
				
				<div class="et-fb-form__group">
					<span class="et-fb-form__label">
						<span class="et-fb-form__label-text">Disable on</span>
					</span>
					<div class="et-fb-settings-options et-fb-option--multiple-checkboxes">
						<div class="et-fb-option-container">
							<div class="et-fb-multiple-checkboxes-wrap">
								<p class="et-fb-multiple-checkbox">
									<label for="et-fb-multiple-checkbox-disabled_on-0">
										<input type="checkbox" name="post_at_disablemobile" value="1" <?php checked( $disablemobile[0], 1 ); ?>> <?php esc_html_e( 'Phone', 'DiviOverlays' ); ?>
									</label>
								</p>
								<p class="et-fb-multiple-checkbox">
									<label for="et-fb-multiple-checkbox-disabled_on-1">
										<input type="checkbox" name="post_at_disabletablet" value="1" <?php checked( $disabletablet[0], 1 ); ?>> <?php esc_html_e( 'Tablet', 'DiviOverlays' ); ?>
									</label>
								</p>
								<p class="et-fb-multiple-checkbox">
									<label for="et-fb-multiple-checkbox-disabled_on-2">
										<input type="checkbox" name="post_at_disabledesktop" value="1" <?php checked( $disabledesktop[0], 1 ); ?>> <?php esc_html_e( 'Desktop', 'DiviOverlays' ); ?>
									</label>
								</p>
							</div>
						</div>
					</div>
				</div>
				
				<div class="et-fb-form__group">
					<span class="et-fb-form__label">
						<span class="et-fb-form__label-text"><?php esc_html_e( 'Display once per page load', 'DiviOverlays' ); ?></span>
					</span>
					<div class="et-fb-settings-options et-fb-option--yes-no_button">
						<div class="et-fb-option-container">
							<div class="et-core-control-toggle<?php if ( $onceperload[0] == 0 ) { ?> et-core-control-toggle--off<?php } else if ( $onceperload[0] == 1 ) { ?> et-core-control-toggle--on<?php } ?>">
								<div class="et-core-control-toggle__label et-core-control-toggle__label--on">
									<div class="et-core-control-toggle__text"><?php esc_html_e( 'Yes', 'DiviOverlays' ); ?></div>
									<div class="et-core-control-toggle__handle"></div>
								</div>
								<div class="et-core-control-toggle__label et-core-control-toggle__label--off">
									<div class="et-core-control-toggle__text"><?php esc_html_e( 'No', 'DiviOverlays' ); ?></div>
									<div class="et-core-control-toggle__handle"></div>
								</div>
								<input class="do-hide" type="checkbox" id="post_do_preventscroll" name="post_do_preventscroll" value="1" <?php checked( $onceperload[0], 1 ); ?>>
							</div>
						</div>
					</div>
				</div>
				<div class="et-fb-form__group"></div>
				
			</div>
			
			<div class="divilife_meta_box do-at-scheduling<?php if ( strlen( $overlay_at_selected ) > 1 ) { ?> do-show<?php } ?>">
				<div class="et-fb-form__group">
					<span class="et-fb-form__label">
						<span class="et-fb-form__label-text"><?php esc_html_e( 'Set Scheduling', 'DiviOverlays' ); ?></span>
					</span>
					<p class="divioverlay_placement et_pb_single_title">
						<select name="do_enable_scheduling" class="chosen divioverlay-enable-scheduling" data-dropdownshowhideblock="1">
							<option value="0"<?php if ( $enable_scheduling[0] == 0 ) { ?> selected="selected"<?php } ?>><?php esc_html_e( 'Disabled', 'DiviOverlays' ); ?></option>
							<option value="1"<?php if ( $enable_scheduling[0] == 1 ) { ?> selected="selected"<?php } ?> data-showhideblock=".do-onetime">
							<?php print esc_html__( 'Start &amp; End Time', 'DiviOverlays' ) ?>
							</option>
							<option value="2"<?php if ( $enable_scheduling[0] == 2 ) { ?> selected="selected"<?php } ?> data-showhideblock=".do-recurring">
							<?php print esc_html__( 'Recurring Scheduling', 'DiviOverlays' ) ?>
							</option>
						</select>
					</p>
				</div>
				
				<div class="do-onetime<?php if ( $enable_scheduling[0] == 1 ) { ?> do-show<?php } ?>">
					<div class="et-fb-form__group">
						<div class="et-fb-settings-options et-fb-option--custom-padding">
							<div class="et-fb-option-container">
								<div class="et-fb-settings-option-inner et-fb-settings-option-inner-responsive et-fb-settings-option-inner-input-margins">
									<div class="et-fb-settings-option-inputs-wrap">
										<div class="et-fb-settings-option-input-wrap top">
											<div class="et-fb-settings-option--numeric-spinner-control ">
												<div class="et-fb-settings-option--numeric-control">
													<input type="text" name="do_date_start" value="<?php print esc_attr( $date_start ) ?>" class="form-control et-fb-settings-option-input">
												</div>
												<div class="numeric-spinner-control-label-wrapper">
													<span class="et-fb-settings-option-input-label"><?php esc_html_e( 'Start date', 'DiviOverlays' ); ?></span>
												</div>
											</div>
										</div>
										<div id="datetimepicker11"></div>
										<div class="et-fb-settings-option-input-wrap left">
											<div class="et-fb-settings-option--numeric-spinner-control ">
												<div class="et-fb-settings-option--numeric-control">
													<input type="text" name="do_date_end" value="<?php print esc_attr( $date_end ) ?>" class="form-control et-fb-settings-option-input">
												</div>
												<div class="numeric-spinner-control-label-wrapper">
													<span class="et-fb-settings-option-input-label"><?php esc_html_e( 'End date', 'DiviOverlays' ); ?></span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<div class="do-recurring<?php if ( $enable_scheduling[0] == 2 ) { ?> do-show<?php } ?>">
					<div class="et-fb-form__group">
						<div class="et-fb-settings-options et-fb-option--multiple-checkboxes">
							<div class="et-fb-option-container">
								<div class="et-fb-multiple-checkboxes-wrap">
									<p class="et-fb-multiple-checkbox">
										<label for="et-fb-multiple-checkbox-disabled_on-0">
											<input type="checkbox" name="divioverlays_scheduling_daysofweek[]" value="1" <?php checked( $daysofweek[1][0], 1 ); ?>> <?php esc_html_e( 'Monday', 'DiviOverlays' ); ?>
										</label>
									</p>
									<p class="et-fb-multiple-checkbox">
										<label for="et-fb-multiple-checkbox-disabled_on-1">
											<input type="checkbox" name="divioverlays_scheduling_daysofweek[]" value="2" <?php checked( $daysofweek[2][0], 1 ); ?>> <?php esc_html_e( 'Tuesday', 'DiviOverlays' ); ?>
										</label>
									</p>
									<p class="et-fb-multiple-checkbox">
										<label for="et-fb-multiple-checkbox-disabled_on-2">
											<input type="checkbox" name="divioverlays_scheduling_daysofweek[]" value="3" <?php checked( $daysofweek[3][0], 1 ); ?>> <?php esc_html_e( 'Wednesday', 'DiviOverlays' ); ?>
										</label>
									</p>
									<p class="et-fb-multiple-checkbox">
										<label for="et-fb-multiple-checkbox-disabled_on-3">
											<input type="checkbox" name="divioverlays_scheduling_daysofweek[]" value="4" <?php checked( $daysofweek[4][0], 1 ); ?>> <?php esc_html_e( 'Thursday', 'DiviOverlays' ); ?>
										</label>
									</p>
									<p class="et-fb-multiple-checkbox">
										<label for="et-fb-multiple-checkbox-disabled_on-4">
											<input type="checkbox" name="divioverlays_scheduling_daysofweek[]" value="5" <?php checked( $daysofweek[5][0], 1 ); ?>> <?php esc_html_e( 'Friday', 'DiviOverlays' ); ?>
										</label>
									</p>
									<p class="et-fb-multiple-checkbox">
										<label for="et-fb-multiple-checkbox-disabled_on-4">
											<input type="checkbox" name="divioverlays_scheduling_daysofweek[]" value="6" <?php checked( $daysofweek[6][0], 1 ); ?>> <?php esc_html_e( 'Saturday', 'DiviOverlays' ); ?>
										</label>
									</p>
									<p class="et-fb-multiple-checkbox">
										<label for="et-fb-multiple-checkbox-disabled_on-4">
											<input type="checkbox" name="divioverlays_scheduling_daysofweek[]" value="7" <?php checked( $daysofweek[7][0], 1 ); ?>> <?php esc_html_e( 'Sunday', 'DiviOverlays' ); ?>
										</label>
									</p>
								</div>
							</div>
						</div>
					</div>
					
					<div class="et-fb-form__group">
						<div class="et-fb-settings-options et-fb-option--custom-padding">
							<div class="et-fb-option-container">
								<div class="et-fb-settings-option-inner et-fb-settings-option-inner-responsive et-fb-settings-option-inner-input-margins">
									<div class="et-fb-settings-option-inputs-wrap">
										<div class="et-fb-settings-option-input-wrap top">
											<div class="et-fb-settings-option--numeric-spinner-control ">
												<div class="et-fb-settings-option--numeric-control">
													<input type="text" name="do_time_start" value="<?php print esc_attr( $time_start ) ?>" class="form-control et-fb-settings-option-input">
												</div>
												<div class="numeric-spinner-control-label-wrapper">
													<span class="et-fb-settings-option-input-label"><?php esc_html_e( 'Start Time', 'DiviOverlays' ); ?></span>
												</div>
											</div>
										</div>
										<div id="datetimepicker11"></div>
										<div class="et-fb-settings-option-input-wrap left">
											<div class="et-fb-settings-option--numeric-spinner-control ">
												<div class="et-fb-settings-option--numeric-control">
													<input type="text" name="do_time_end" value="<?php print esc_attr( $time_end ) ?>" class="form-control et-fb-settings-option-input">
												</div>
												<div class="numeric-spinner-control-label-wrapper">
													<span class="et-fb-settings-option-input-label"><?php esc_html_e( 'End Time', 'DiviOverlays' ); ?></span>
												</div>
											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<div class="row">
					<div class="col-xs-12">
						<div class="do-recurring-user-msg alert alert-danger">
							
						</div>
					</div>
				</div>
				
				<div class="clear"></div> 
			</div>
			
			<?php
		}
		
	endif;


	if ( ! function_exists( 'do_single_animation_meta_box' ) ) :

		function do_single_animation_meta_box() {
			
			$post_id = get_the_ID();
			
			$et_pb_divioverlay_effect_entrance = get_post_meta( $post_id, 'et_pb_divioverlay_effect_entrance', true );
			$et_pb_divioverlay_effect_exit = get_post_meta( $post_id, 'et_pb_divioverlay_effect_exit', true );
			
			$overlay_effects_entrance = array(
			
				'Back entrances' => array(
				
					'backInDown' => esc_html__( 'Back Down', 'DiviOverlays' ), 
					'backInLeft' => esc_html__( 'Back Left', 'DiviOverlays' ),
					'backInRight' => esc_html__( 'Back Right', 'DiviOverlays' ), 
					'backInUp' => esc_html__( 'Back Up', 'DiviOverlays')
				),
				'Bouncing entrances' => array(
				
					'bounceIn' => esc_html__( 'Bounce', 'DiviOverlays' ), 
					'bounceInDown' => esc_html__( 'Bounce Down', 'DiviOverlays' ), 
					'bounceInLeft' => esc_html__( 'Bounce Left', 'DiviOverlays' ), 
					'bounceInRight' => esc_html__( 'Bounce Right', 'DiviOverlays' ), 
					'bounceInUp' => esc_html__( 'Bounce Up', 'DiviOverlays' )
				),
				'Fading entrances' => array(
				
					'fadeIn' => esc_html__( 'Fade', 'DiviOverlays' ), 
					'fadeInDown' => esc_html__( 'Fade Down', 'DiviOverlays' ), 
					'fadeInDownBig' => esc_html__( 'Fade Down Big', 'DiviOverlays' ), 
					'fadeInLeft' => esc_html__( 'Fade Left', 'DiviOverlays' ), 
					'fadeInLeftBig' => esc_html__( 'Fade Left Big', 'DiviOverlays' ), 
					'fadeInRight' => esc_html__( 'Fade Right', 'DiviOverlays' ), 
					'fadeInRightBig' => esc_html__( 'Fade Right Big', 'DiviOverlays' ), 
					'fadeInUp' => esc_html__( 'Fade Up', 'DiviOverlays' ), 
					'fadeInUpBig' => esc_html__( 'Fade Up Big', 'DiviOverlays' ), 
					'fadeInTopLeft' => esc_html__( 'Fade Top Left', 'DiviOverlays' ), 
					'fadeInTopRight' => esc_html__( 'Fade Top Right', 'DiviOverlays' ), 
					'fadeInBottomLeft' => esc_html__( 'Fade Bottom Left', 'DiviOverlays' ), 
					'fadeInBottomRight' => esc_html__( 'Fade Bottom Right', 'DiviOverlays' )
				),
				'Flippers entrances' => array(
				
					'flipInX' => esc_html__( 'Flip Vertically', 'DiviOverlays' ), 
					'flipInY' => esc_html__( 'Flip Horizontally', 'DiviOverlays' )
				),
				'Lightspeed entrances' => array(
				
					'lightSpeedInRight' => esc_html__( 'LightSpeed Right to Left', 'DiviOverlays' ), 
					'lightSpeedInLeft' => esc_html__( 'LightSpeed Left to Right', 'DiviOverlays' )
				),
				'Rotating entrances' => array(
				
					'rotateIn' => esc_html__( 'Rotate', 'DiviOverlays' ), 
					'rotateInDownLeft' => esc_html__( 'Rotate Down Left', 'DiviOverlays' ), 
					'rotateInDownRight' => esc_html__( 'Rotate Down Right', 'DiviOverlays' ), 
					'rotateInUpLeft' => esc_html__( 'Rotate Up Left', 'DiviOverlays' ), 
					'rotateInUpRight' => esc_html__( 'Rotate Up Right', 'DiviOverlays' )
				),
				'Specials entrances' => array(
				
					'hinge' => esc_html__( 'Hinge', 'DiviOverlays' ), 
					'jackInTheBox' => esc_html__( 'Jack In The Box', 'DiviOverlays' ), 
					'rollIn' => esc_html__( 'Roll', 'DiviOverlays' ), 
					'doorOpen' => esc_html__( 'Door Close', 'DiviOverlays' ), 
					'swashIn' => esc_html__( 'Swash', 'DiviOverlays' ),
					'foolishIn' => esc_html__( 'Foolish', 'DiviOverlays' ),
					'puffIn' => esc_html__( 'Puff', 'DiviOverlays' ),
					'vanishIn' => esc_html__( 'Vanish', 'DiviOverlays' )
				),
				'Zooming entrances' => array(
				
					'zoomIn' => esc_html__( 'Zoom', 'DiviOverlays' ), 
					'zoomInDown' => esc_html__( 'Zoom Down', 'DiviOverlays' ), 
					'zoomInLeft' => esc_html__( 'Zoom Left', 'DiviOverlays' ), 
					'zoomInRight' => esc_html__( 'Zoom Right', 'DiviOverlays' ), 
					'zoomInUp' => esc_html__( 'Zoom Up', 'DiviOverlays' )
				),
				'Sliding entrances' => array(
				
					'slideInDown' => esc_html__( 'Slide Down', 'DiviOverlays' ),
					'slideInLeft' => esc_html__( 'Slide Left', 'DiviOverlays' ),
					'slideInRight' => esc_html__( 'Slide Right', 'DiviOverlays' ),
					'slideInUp' => esc_html__( 'Slide Up', 'DiviOverlays' )
				)
			);
			
			$overlay_effects_exits = array(
			
				'Back exits' => array(
				
					'backOutDown' => esc_html__( 'Back Down', 'DiviOverlays' ), 
					'backOutLeft' => esc_html__( 'Back Left', 'DiviOverlays' ), 
					'backOutRight' => esc_html__( 'Back Right', 'DiviOverlays' ), 
					'backOutUp' => esc_html__( 'Back Up', 'DiviOverlays' )
				),
				'Bouncing exits' => array(
				
					'bounceOut' => esc_html__( 'Bounce', 'DiviOverlays' ), 
					'bounceOutDown' => esc_html__( 'Bounce Down', 'DiviOverlays' ), 
					'bounceOutLeft' => esc_html__( 'Bounce Left', 'DiviOverlays' ), 
					'bounceOutRight' => esc_html__( 'Bounce Right', 'DiviOverlays' ), 
					'bounceOutUp' => esc_html__( 'Bounce Up', 'DiviOverlays' )
				),
				'Fading exits' => array(
				
					'fadeOut' => esc_html__( 'Fade', 'DiviOverlays' ), 
					'fadeOutDown' => esc_html__( 'Fade Down', 'DiviOverlays' ), 
					'fadeOutDownBig' => esc_html__( 'Fade Down Big', 'DiviOverlays' ), 
					'fadeOutLeft' => esc_html__( 'Fade Left', 'DiviOverlays' ), 
					'fadeOutLeftBig' => esc_html__( 'Fade Left Big', 'DiviOverlays' ), 
					'fadeOutRight' => esc_html__( 'Fade Right', 'DiviOverlays' ), 
					'fadeOutRightBig' => esc_html__( 'Fade Right Big', 'DiviOverlays' ), 
					'fadeOutUp' => esc_html__( 'Fade Up', 'DiviOverlays' ), 
					'fadeOutUpBig' => esc_html__( 'Fade Up Big', 'DiviOverlays' ), 
					'fadeOutTopLeft' => esc_html__( 'Fade Top Left', 'DiviOverlays' ), 
					'fadeOutTopRight' => esc_html__( 'Fade Top Right', 'DiviOverlays' ), 
					'fadeOutBottomRight' => esc_html__( 'Fade Bottom Right', 'DiviOverlays' ), 
					'fadeOutBottomLeft' => esc_html__( 'Fade Bottom Left', 'DiviOverlays' )
				),
				'Rotating exits' => array(
				
					'rotateOut' => esc_html__( 'Rotate', 'DiviOverlays' ), 
					'rotateOutDownLeft' => esc_html__( 'Rotate Down Left', 'DiviOverlays' ), 
					'rotateOutDownRight' => esc_html__( 'Rotate Down Right', 'DiviOverlays' ), 
					'rotateOutUpLeft' => esc_html__( 'Rotate Up Left', 'DiviOverlays' ), 
					'rotateOutUpRight' => esc_html__( 'Rotate Up Right', 'DiviOverlays' )
				),
				'Flippers exits' => array(
				
					'flipOutX' => esc_html__( 'Flip Vertically', 'DiviOverlays' ), 
					'flipOutY' => esc_html__( 'Flip Horizontally', 'DiviOverlays' )
				),
				'Lightspeed exits' => array(
				
					'lightSpeedOutRight' => esc_html__( 'LightSpeed Right', 'DiviOverlays' ), 
					'lightSpeedOutLeft' => esc_html__( 'LightSpeed Left', 'DiviOverlays' )
				),
				'Specials exits' => array(
					'rollOut' => esc_html__( 'Roll Out', 'DiviOverlays' ),
					'doorClose' => esc_html__( 'Door Open', 'DiviOverlays' ),
					'swashOut' => esc_html__( 'Swash', 'DiviOverlays' ),
					'foolishOut' => esc_html__( 'Foolish', 'DiviOverlays' ),
					'holeOut' => esc_html__( 'To the space', 'DiviOverlays' ),
					'puffOut' => esc_html__( 'Puff', 'DiviOverlays' ),
					'vanishOut' => esc_html__( 'Vanish', 'DiviOverlays' )
				),
				'Zooming exits' => array(
				
					'zoomOut' => esc_html__( 'Zoom', 'DiviOverlays' ),
					'zoomOutDown' => esc_html__( 'Zoom Down', 'DiviOverlays' ),
					'zoomOutLeft' => esc_html__( 'Zoom Left', 'DiviOverlays' ),
					'zoomOutRight' => esc_html__( 'Zoom Right', 'DiviOverlays' ),
					'zoomOutUp' => esc_html__( 'Zoom Up', 'DiviOverlays' )
				),
				'Sliding exits' => array(
				
					'slideOutDown' => esc_html__( 'Slide Down', 'DiviOverlays' ),
					'slideOutLeft' => esc_html__( 'Slide Left', 'DiviOverlays' ),
					'slideOutRight' => esc_html__( 'Slide Right', 'DiviOverlays' ),
					'slideOutUp' => esc_html__( 'Slide Up', 'DiviOverlays' )
				)
			);
			
			?>
			<p class="et_pb_page_settings et_pb_single_title">
				<label for="et_pb_divioverlay_effect_entrance_hidden" class="dlattention"><?php esc_html_e( 'Select Animation Entrance', 'DiviOverlays' ); ?>: </label>
				<select id="et_pb_divioverlay_effect_entrance_hidden" name="et_pb_divioverlay_effect_entrance_hidden" class="do-hide">
				<?php
				
				foreach ( $overlay_effects_entrance as $overlay_effects_title => $overlay_effects_animations ) {
					
					print '<optgroup label="' . et_core_esc_previously( $overlay_effects_title ) . '">';
					
					foreach ( $overlay_effects_animations as $overlay_value => $overlay_name ) {
						
						printf( '<option value="%2$s"%3$s>%1$s</option>',
							esc_html( $overlay_name ),
							esc_attr( $overlay_value ),
							selected( $overlay_value, $et_pb_divioverlay_effect_entrance, false )
						);
					}
					
					print '</optgroup>';
				}
				
				$dov_effect_entrance_speed_control = $dov_effect_entrance_speed = get_post_meta( $post_id, 'dov_effect_entrance_speed', true );
				
				if ( $dov_effect_entrance_speed === '' ) {
					
					$dov_effect_entrance_speed = 1;
				}
				
				if ( $dov_effect_entrance_speed === '' || $dov_effect_entrance_speed == 1 ) {
					
					$dov_effect_entrance_speed_control = '';
				}
				
				?>
				</select>
				<select id="et_pb_divioverlay_effect_entrance" name="et_pb_divioverlay_effect_entrance" class="overlay-animations"></select>
				<div class="et-fb-form__label">
					<div class="et-fb-form__label-text inline-block">
						<label for="dov_effect_entrance_speed"><?php esc_html_e( 'Speed', 'DiviOverlays' ); ?>:</label>
					</div>
				</div>
				
				<div class="et-fb-settings-options et-fb-option--range">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap divilife-input-animatebox">
								<input type="range" min="0.5" max="5" step="0.5" class="et-fb-range divilife-input-animatebox" data-shortcuts-allowed="true" data-slidebar="#et_pb_divioverlay_effect_entrance" data-demobox=".divioverlay-demo-box-entrance" value="<?php echo esc_attr( $dov_effect_entrance_speed ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_effect_entrance_speed" placeholder="1" step="0.5" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $dov_effect_entrance_speed_control ); ?>" data-defaultvalue="1">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<div class="divioverlay-demo-container">
					<div class="divioverlay-demo-bg"></div>
					<div class="divioverlay-demo-box divioverlay-demo-box-entrance animate__animated">
						<div class="divi-life-logo-container">
							<div class="divi-life-logo-icon"></div>
						</div>
					</div>
				</div>
			</p>
			<p class="et_pb_page_settings et_pb_single_title">
				<label for="et_pb_divioverlay_effect_exit_hidden" class="dlattention"><?php esc_html_e( 'Select Animation Exit', 'DiviOverlays' ); ?>: </label>
				<select id="et_pb_divioverlay_effect_exit_hidden" name="et_pb_divioverlay_effect_exit_hidden" class="do-hide">
				<?php
				
				foreach ( $overlay_effects_exits as $overlay_effects_title => $overlay_effects_animations ) {
					
					print '<optgroup label="' . et_core_esc_previously( $overlay_effects_title ) . '">';
					
					foreach ( $overlay_effects_animations as $overlay_value => $overlay_name ) {
						
						printf( '<option value="%2$s"%3$s>%1$s</option>',
							esc_html( $overlay_name ),
							esc_attr( $overlay_value ),
							selected( $overlay_value, $et_pb_divioverlay_effect_exit, false )
						);
					}
					
					print '</optgroup>';
				}
				
				$dov_effect_exit_speed_control = $dov_effect_exit_speed = get_post_meta( $post_id, 'dov_effect_exit_speed', true );
				if ( $dov_effect_exit_speed === '' ) {
					
					$dov_effect_exit_speed = 1;
				}
				
				if ( $dov_effect_exit_speed === '' || $dov_effect_exit_speed == 1 ) {
					
					$dov_effect_exit_speed_control = '';
				}
				
				?>
				</select>
				<select id="et_pb_divioverlay_effect_exit" name="et_pb_divioverlay_effect_exit" class="overlay-animations"></select>
				<div class="et-fb-form__label">
					<div class="et-fb-form__label-text inline-block">
						<label for="dov_effect_exit_speed"><?php esc_html_e( 'Speed', 'DiviOverlays' ); ?>:</label>
					</div>
				</div>
				
				<div class="et-fb-settings-options et-fb-option--range">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap divilife-input-animatebox">
								<input type="range" min="0.5" max="5" step="0.5" class="et-fb-range divilife-input-animatebox" data-shortcuts-allowed="true" data-slidebar="#et_pb_divioverlay_effect_exit" data-demobox=".divioverlay-demo-box-exit" value="<?php echo esc_attr( $dov_effect_exit_speed ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_effect_exit_speed" placeholder="1" step="0.5" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $dov_effect_exit_speed_control ); ?>" data-defaultvalue="1">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<div class="divioverlay-demo-container">
					<div class="divioverlay-demo-bg"></div>
					<div class="divioverlay-demo-box divioverlay-demo-box-exit animate__animated">
						<div class="divi-life-logo-container">
							<div class="divi-life-logo-icon"></div>
						</div>
					</div>
				</div>
			</p>
			
		<?php }
		
	endif;



	if ( ! function_exists( 'do_background_settings' ) ) :

		function do_background_settings() {
			
			$post_id = get_the_ID();
			
			wp_nonce_field( 'overlay_color_box', 'overlay_color_box_nonce' );
			$color = get_post_meta( $post_id, 'post_overlay_bg_color', true );
			
			$do_enablebgblur = get_post_meta( $post_id, 'do_enablebgblur' );
			if( !isset( $do_enablebgblur[0] ) ) {
				
				$do_enablebgblur[0] = '0';
			}
			?>
						<span class="et-fb-form__label">
							<span class="et-fb-form__label-text"><?php esc_html_e( 'Select Overlay Background Color', 'DiviOverlays' ); ?>: </span>
						</span>
						<div class="et-fb-settings-background-tabs">
							<div class="et-fb-settings-background-tab et-fb-settings-background-tab--color et-fb-settings-background-tab--active">
								<div class="et-fb-form__group et-fb-form__group--background_color">
									<div class="et-fb-settings-options et-fb-option--color-alpha updateDiviOverlaysStylesOnDiviBuilder">
										<div class="et-fb-option-container">
											<div class="et-fb-settings-option-color et-fb-settings-option-color--has-preview et-fb-settings-option-color--has-color-manager">
												<div class="et-fb-settings-option-color-wrap--picker">
													<div class="et-fb-settings-option-color-picker hide-result-button">
														<input class="do-post_bg color-picker et-fb-settings-option-color et-fb-settings-option-color--alpha et-fb-color-type-rgb" data-alpha="true" type="text" name="post_bg" value="<?php echo esc_attr( $color ) ?>" data-updatebuilder="true" data-css="background-color">
													</div>
												</div>
												<div class="et-fb-settings-option-color-wrap--preview">
													<div class="et-fb-settings-option-preview" style="background-color: <?php echo esc_attr( $color ) ?>;"></div>
												</div>

												<div class="et-fb-settings-option-color-wrap--manager">
													<div class="et-fb-settings-color-manager et-fb-settings-color-manager--animated et-fb-settings-color-manager--collapsed has-links">
														<div class="et-fb-settings-color-manager__row">
															<div class="et-fb-settings-color-manager__column">
																<div class="et-fb-settings-color-manager__current-color-wrapper">
																	<div class="et-fb-settings-color-manager__toggle-palette-wrapper" data-for="title_text_color-color-tooltip-current-color" data-tip="true" currentitem="false">
																		<div class="et-fb-settings-color-manager__toggle-palette">
																			<div class="et-fb-icon et-fb-icon--expand-palette" style="fill: rgb(162, 176, 193); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
																				<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision">
																					<g>
																						<circle cx="14" cy="20" r="2"></circle>
																						<circle cx="14" cy="13" r="2"></circle>
																						<circle cx="14" cy="6" r="2"></circle>
																					</g>
																				</svg>
																			</div>
																		</div>
																	</div>
																</div>
																<div class="et-fb-settings-color-manager__swatches">
																	<div class="et-fb-settings-color-manager__swatches-rotator">
																		
																	</div>
																</div>
																<div class="et-fb-settings-color-manager__reset-color" style="background-image: url(&quot;<?php print et_core_esc_previously( get_template_directory_uri() ) . '/includes/builder/images' ?>/no-color.png&quot;);">
																</div>
															</div>
														</div>
													</div>
												</div>

											</div>
										</div>
									</div>
								</div>
							</div>
						</div>
						<div class="et-fb-form__group">
							<span class="et-fb-form__label">
								<span class="et-fb-form__label-text"><?php esc_html_e( 'Enable Background Blur', 'DiviOverlays' ); ?></span>
							</span>
							<div class="et-fb-settings-options et-fb-option--yes-no_button">
								<div class="et-fb-option-container">
									<div class="et-core-control-toggle<?php if ( $do_enablebgblur[0] == 0 ) { ?> et-core-control-toggle--off<?php } else if ( $do_enablebgblur[0] == 1 ) { ?> et-core-control-toggle--on<?php } ?>">
										<div class="et-core-control-toggle__label et-core-control-toggle__label--on">
											<div class="et-core-control-toggle__text"><?php esc_html_e( 'Yes', 'DiviOverlays' ); ?></div>
											<div class="et-core-control-toggle__handle"></div>
										</div>
										<div class="et-core-control-toggle__label et-core-control-toggle__label--off">
											<div class="et-core-control-toggle__text"><?php esc_html_e( 'No', 'DiviOverlays' ); ?></div>
											<div class="et-core-control-toggle__handle"></div>
										</div>
										<input class="do-hide" type="checkbox" id="do_enablebgblur" name="do_enablebgblur" value="1" <?php checked( $do_enablebgblur[0], 1 ); ?>>
									</div>
								</div>
							</div>
						</div>
		<?php }
		
	endif;

	
	if ( ! function_exists( 'do_triggers_settings' ) ) :

		function do_triggers_settings() {
			
			?>
				<div class="et-fb-tabs__panel et-fb-tabs__panel--active et-fb-tabs__panel--filter-dropdown et-fb-tabs__panel--general">
					<div class="et-fb-form__toggle et-fb-form__toggle-enabled">
						<div class="et-fb-form__toggle-title">
							<h3><?php esc_html_e( 'Manual Triggers', 'DiviOverlays' ); ?></h3>
							<div class="et-fb-icon et-fb-icon--next" style="fill: rgb(62, 80, 98); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
							<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision"><g><path d="M15.8 14L12.3 18C11.9 18.4 11.9 19 12.3 19.4 12.7 19.8 13.3 19.8 13.7 19.4L17.7 14.9C17.9 14.7 18 14.3 18 14 18.1 13.7 18 13.4 17.7 13.1L13.7 8.6C13.3 8.2 12.7 8.2 12.3 8.6 11.9 9 11.9 9.6 12.3 10L15.8 14 15.8 14Z" fill-rule="evenodd"></path></g></svg>
							</div>
						</div>
						<div class="dl-do-toggleContent">
							<?php
							
							$screen = get_current_screen();
							if ( 'add' != $screen->action ) {
								
								do_manualtriggers_callback();
							}
							
							?>
						</div>
					</div>
					<div class="et-fb-form__toggle et-fb-form__toggle-enabled et-fb-form__toggle-opened">
						<div class="et-fb-form__toggle-title">
							<h3><?php esc_html_e( 'Automatic Triggers', 'DiviOverlays' ); ?></h3>
							<div class="et-fb-icon et-fb-icon--next" style="fill: rgb(62, 80, 98); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
							<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision"><g><path d="M15.8 14L12.3 18C11.9 18.4 11.9 19 12.3 19.4 12.7 19.8 13.3 19.8 13.7 19.4L17.7 14.9C17.9 14.7 18 14.3 18 14 18.1 13.7 18 13.4 17.7 13.1L13.7 8.6C13.3 8.2 12.7 8.2 12.3 8.6 11.9 9 11.9 9.6 12.3 10L15.8 14 15.8 14Z" fill-rule="evenodd"></path></g></svg>
							</div>
						</div>
						<div class="dl-do-toggleContent" style="display: block;">
							<?php do_automatictriggers_callback() ?>
						</div>
					</div>
				</div>
				
			<?php
		}
		
	endif;
	

	if ( ! function_exists( 'do_design_settings' ) ) :

		function do_design_settings() {
			
			?>
				<div class="et-fb-tabs__panel et-fb-tabs__panel--active et-fb-tabs__panel--filter-dropdown et-fb-tabs__panel--general">
					<div class="et-fb-form__toggle et-fb-form__toggle-enabled et-fb-form__toggle-opened">
						<div class="et-fb-form__toggle-title">
							<h3><?php esc_html_e( 'Sizing', 'DiviOverlays' ); ?></h3>
							<div class="et-fb-icon et-fb-icon--next" style="fill: rgb(62, 80, 98); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
							<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision"><g><path d="M15.8 14L12.3 18C11.9 18.4 11.9 19 12.3 19.4 12.7 19.8 13.3 19.8 13.7 19.4L17.7 14.9C17.9 14.7 18 14.3 18 14 18.1 13.7 18 13.4 17.7 13.1L13.7 8.6C13.3 8.2 12.7 8.2 12.3 8.6 11.9 9 11.9 9.6 12.3 10L15.8 14 15.8 14Z" fill-rule="evenodd"></path></g></svg>
							</div>
						</div>
						<div class="dl-do-toggleContent" data-styletagclass="sizing" style="display: block;">
							<?php do_sizing_settings(); ?>
						</div>
					</div>
					<div class="et-fb-form__toggle et-fb-form__toggle-enabled">
						<div class="et-fb-form__toggle-title">
							<h3><?php esc_html_e( 'Positioning', 'DiviOverlays' ); ?></h3>
							<div class="et-fb-icon et-fb-icon--next" style="fill: rgb(62, 80, 98); width: 28px; min-width: 28px; height: 28px; margin: -6px;">
							<svg viewBox="0 0 28 28" preserveAspectRatio="xMidYMid meet" shape-rendering="geometricPrecision"><g><path d="M15.8 14L12.3 18C11.9 18.4 11.9 19 12.3 19.4 12.7 19.8 13.3 19.8 13.7 19.4L17.7 14.9C17.9 14.7 18 14.3 18 14 18.1 13.7 18 13.4 17.7 13.1L13.7 8.6C13.3 8.2 12.7 8.2 12.3 8.6 11.9 9 11.9 9.6 12.3 10L15.8 14 15.8 14Z" fill-rule="evenodd"></path></g></svg>
							</div>
						</div>
						<div class="dl-do-toggleContent">
							<?php do_positioning_settings() ?>
						</div>
					</div>
				</div>
				
			<?php
		}
		
	endif;


	if ( ! function_exists( 'do_sizing_settings' ) ) :

		function do_sizing_settings() {
			
			$post_id = get_the_ID();
			
			$minwidth_control = $minwidth = get_post_meta( $post_id, 'dov_minwidth', true );
			
			if( !isset( $minwidth ) || $minwidth === '' || $minwidth == 0 ) { $minwidth = '95%'; }
			
			if ( is_numeric( $minwidth ) === TRUE ) { $minwidth = $minwidth . '%'; }
			
			$minwidth_control = floatval( $minwidth );
			
			?>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Min Width', 'DiviOverlays' ); ?>:</span>
					<span class="et-fb-form__responsive" style="margin-left: 20px; opacity: 0;"><svg width="20" height="20" viewBox="-3 -3 20 20"><path d="M10 0H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM7 13a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm3-3H4V2h6z"></path></svg></span>
				</span>
				<?php print et_core_esc_previously( dov_style_devices_settings() ) ?>
				<div class="et-fb-settings-options et-fb-option--range" data-responsivemode="desktop">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="sizing">
								<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $minwidth_control ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_minwidth" placeholder="95%" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $minwidth ); ?>" data-unit="%" data-defaultvalue="95%" data-updatebuilder="true" data-css="min-width">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$minwidth_control_tablet = $minwidth_tablet = get_post_meta( $post_id, 'dov_minwidth_tablet', true );
				
				if( !isset( $minwidth_tablet ) || $minwidth_tablet === '' || $minwidth_tablet == 0 ) { $minwidth_tablet = '100%'; }
				
				if ( is_numeric( $minwidth_tablet ) === TRUE ) { $minwidth_tablet = $minwidth_tablet . '%'; }
				
				$minwidth_control_tablet = floatval( $minwidth_tablet );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="tablet">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="sizing">
								<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $minwidth_control_tablet ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_minwidth_tablet" placeholder="95%" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $minwidth_tablet ); ?>" data-unit="%" data-defaultvalue="95%" data-updatebuilder="true" data-css="min-width">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$minwidth_control_phone = $minwidth_phone = get_post_meta( $post_id, 'dov_minwidth_phone', true );
				
				if( !isset( $minwidth_phone ) || $minwidth_phone === '' || $minwidth_phone == 0 ) { $minwidth_phone = '100%'; }
				
				if ( is_numeric( $minwidth_phone ) === TRUE ) { $minwidth_phone = $minwidth_phone . '%'; }
				
				$minwidth_control_phone = floatval( $minwidth_phone );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="phone">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="sizing">
								<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $minwidth_control_phone ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_minwidth_phone" placeholder="95%" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $minwidth_phone ); ?>" data-unit="%" data-defaultvalue="95%" data-updatebuilder="true" data-css="min-width">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<?php
			
			$width_control = $width = get_post_meta( $post_id, 'dov_width', true );
			
			if( !isset( $width ) || $width === '' || $width == 0 ) { $width = '95%'; }
			
			if ( is_numeric( $width ) === TRUE ) { $width = $width . '%'; }
			
			$width_control = floatval( $width );
			
			?>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Width', 'DiviOverlays' ); ?>:</span>
					<span class="et-fb-form__responsive" style="margin-left: 20px; opacity: 0;"><svg width="20" height="20" viewBox="-3 -3 20 20"><path d="M10 0H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM7 13a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm3-3H4V2h6z"></path></svg></span>
				</span>
				<?php print et_core_esc_previously( dov_style_devices_settings() ) ?>
				<div class="et-fb-settings-options et-fb-option--range" data-responsivemode="desktop">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="sizing">
								<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $width_control ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_width" placeholder="95%" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $width ); ?>" data-unit="%" data-defaultvalue="95%" data-updatebuilder="true" data-css="width">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$width_control_tablet = $width_tablet = get_post_meta( $post_id, 'dov_width_tablet', true );
				
				if( !isset( $width_tablet ) || $width_tablet === '' || $width_tablet == 0 ) { $width_tablet = '100%'; }
				
				if ( is_numeric( $width_tablet ) === TRUE ) { $width_tablet = $width_tablet . '%'; }
				
				$width_control_tablet = floatval( $width_tablet );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="tablet">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="sizing">
								<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $width_control_tablet ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_width_tablet" placeholder="95%" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $width_tablet ); ?>" data-unit="%" data-defaultvalue="95%" data-updatebuilder="true" data-css="width">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$width_control_phone = $width_phone = get_post_meta( $post_id, 'dov_width_phone', true );
				
				if( !isset( $width_phone ) || $width_phone === '' || $width_phone == 0 ) { $width_phone = '100%'; }
				
				if ( is_numeric( $width_phone ) === TRUE ) { $width_phone = $width_phone . '%'; }
				
				$width_control_phone = floatval( $width_phone );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="phone">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="sizing">
								<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $width_control_phone ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_width_phone" placeholder="95%" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $width_phone ); ?>" data-unit="%" data-defaultvalue="95%" data-updatebuilder="true" data-css="width">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<?php
			
			$maxwidth_control = $maxwidth = get_post_meta( $post_id, 'dov_maxwidth', true );
			
			if( !isset( $maxwidth ) || $maxwidth === '' || $maxwidth == 0 ) { $maxwidth = '100%'; }
			
			if ( is_numeric( $maxwidth ) === TRUE ) { $maxwidth = $maxwidth . '%'; }
			
			$maxwidth_control = floatval( $maxwidth );
			
			?>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Max Width', 'DiviOverlays' ); ?>:</span>
					<span class="et-fb-form__responsive" style="margin-left: 20px; opacity: 0;"><svg width="20" height="20" viewBox="-3 -3 20 20"><path d="M10 0H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM7 13a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm3-3H4V2h6z"></path></svg></span>
				</span>
				<?php print et_core_esc_previously( dov_style_devices_settings() ) ?>
				<div class="et-fb-settings-options et-fb-option--range" data-responsivemode="desktop">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="sizing">
								<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $maxwidth_control ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_maxwidth" placeholder="none" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $maxwidth ); ?>" data-unit="%" data-defaultvalue="100%" data-updatebuilder="true" data-css="max-width">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$maxwidth_control_tablet = $maxwidth_tablet = get_post_meta( $post_id, 'dov_maxwidth_tablet', true );
				
				if( !isset( $maxwidth_tablet ) || $maxwidth_tablet === '' || $maxwidth_tablet == 0 ) { $maxwidth_tablet = '100%'; }
				
				if ( is_numeric( $maxwidth_tablet ) === TRUE ) { $maxwidth_tablet = $maxwidth_tablet . '%'; }
				
				$maxwidth_control_tablet = floatval( $maxwidth_tablet );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="tablet">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="sizing">
								<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $maxwidth_control_tablet ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_maxwidth_tablet" placeholder="none" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $maxwidth_tablet ); ?>" data-unit="%" data-defaultvalue="100%" data-updatebuilder="true" data-css="max-width">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$maxwidth_control_phone = $maxwidth_phone = get_post_meta( $post_id, 'dov_maxwidth_phone', true );
				
				if( !isset( $maxwidth_phone ) || $maxwidth_phone === '' || $maxwidth_phone == 0 ) { $maxwidth_phone = '100%'; }
				
				if ( is_numeric( $maxwidth_phone ) === TRUE ) { $maxwidth_phone = $maxwidth_phone . '%'; }
				
				$maxwidth_control_phone = floatval( $maxwidth_phone );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="phone">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="sizing">
								<input type="range" min="0" max="100" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $maxwidth_control_phone ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_maxwidth_phone" placeholder="none" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $maxwidth_phone ); ?>" data-unit="%" data-defaultvalue="100%" data-updatebuilder="true" data-css="max-width">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<?php
			
			$minheight_control = $minheight = get_post_meta( $post_id, 'dov_minheight', true );
			
			if( !isset( $minheight ) || $minheight === '' || $minheight == 0 ) { $minheight = '1000px'; }
			
			if ( is_numeric( $minheight ) === TRUE ) { $minheight = $minheight . 'px'; }
			
			$minheight_control = floatval( $minheight );
			
			?>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Min Height', 'DiviOverlays' ); ?>:</span>
					<span class="et-fb-form__responsive" style="margin-left: 20px; opacity: 0;"><svg width="20" height="20" viewBox="-3 -3 20 20"><path d="M10 0H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM7 13a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm3-3H4V2h6z"></path></svg></span>
				</span>
				<?php print et_core_esc_previously( dov_style_devices_settings() ) ?>
				<div class="et-fb-settings-options et-fb-option--range" data-responsivemode="desktop">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap">
								<input type="range" min="100" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $minheight_control ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_minheight" placeholder="auto" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $minheight ); ?>" data-unit="px" data-defaultvalue="1000px">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$minheight_control_tablet = $minheight_tablet = get_post_meta( $post_id, 'dov_minheight_tablet', true );
				
				if( !isset( $minheight_tablet ) || $minheight_tablet === '' || $minheight_tablet == 0 ) { $minheight_tablet = '1000px'; }
				
				if ( is_numeric( $minheight_tablet ) === TRUE ) { $minheight_tablet = $minheight_tablet . 'px'; }
				
				$minheight_control_tablet = floatval( $minheight_tablet );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="tablet">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap">
								<input type="range" min="100" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $minheight_control_tablet ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_minheight_tablet" placeholder="auto" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $minheight_tablet ); ?>" data-unit="px" data-defaultvalue="1000px">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$minheight_control_phone = $minheight_phone = get_post_meta( $post_id, 'dov_minheight_phone', true );
				
				if( !isset( $minheight_phone ) || $minheight_phone === '' || $minheight_phone == 0 ) { $minheight_phone = '1000px'; }
				
				if ( is_numeric( $minheight_phone ) === TRUE ) { $minheight_phone = $minheight_phone . 'px'; }
				
				$minheight_control_phone = floatval( $minheight_phone );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="phone">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap">
								<input type="range" min="100" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $minheight_control_phone ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_minheight_phone" placeholder="auto" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $minheight_phone ); ?>" data-unit="px" data-defaultvalue="1000px">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<?php
			
			$height_control = $height = get_post_meta( $post_id, 'dov_height', true );
			
			if( !isset( $height ) || $height === '' || $height == 0 ) { $height = '1000px'; }
			
			if ( is_numeric( $height ) === TRUE ) { $height = $height . 'px'; }
			
			$height_control = floatval( $height );
			
			?>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Height', 'DiviOverlays' ); ?>:</span>
					<span class="et-fb-form__responsive" style="margin-left: 20px; opacity: 0;"><svg width="20" height="20" viewBox="-3 -3 20 20"><path d="M10 0H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM7 13a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm3-3H4V2h6z"></path></svg></span>
				</span>
				<?php print et_core_esc_previously( dov_style_devices_settings() ) ?>
				<div class="et-fb-settings-options et-fb-option--range" data-responsivemode="desktop">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap">
								<input type="range" min="100" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $height_control ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_height" placeholder="auto" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $height ); ?>" data-unit="px" data-defaultvalue="1000px">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$height_control_tablet = $height_tablet = get_post_meta( $post_id, 'dov_height_tablet', true );
				
				if( !isset( $height_tablet ) || $height_tablet === '' || $height_tablet == 0 ) { $height_tablet = '1000px'; }
				
				if ( is_numeric( $height_tablet ) === TRUE ) { $height_tablet = $height_tablet . 'px'; }
				
				$height_control_tablet = floatval( $height_tablet );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="tablet">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap">
								<input type="range" min="100" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $height_control_tablet ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_height_tablet" placeholder="auto" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $height_tablet ); ?>" data-unit="px" data-defaultvalue="1000px">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$height_control_phone = $height_phone = get_post_meta( $post_id, 'dov_height_phone', true );
				
				if( !isset( $height_phone ) || $height_phone === '' || $height_phone == 0 ) { $height_phone = '1000px'; }
				
				if ( is_numeric( $height_phone ) === TRUE ) { $height_phone = $height_phone . 'px'; }
				
				$height_control_phone = floatval( $height_phone );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="phone">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap">
								<input type="range" min="100" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $height_control_phone ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_height_phone" placeholder="auto" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $height_phone ); ?>" data-unit="px" data-defaultvalue="1000px">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<?php
			
			$maxheight_control = $maxheight = get_post_meta( $post_id, 'dov_maxheight', true );
			
			if( !isset( $maxheight ) || $maxheight === '' || $maxheight == 0 ) { $maxheight = '1000px'; }
			
			if ( is_numeric( $maxheight ) === TRUE ) { $maxheight = $maxheight . 'px'; }
			
			$maxheight_control = floatval( $maxheight );
			
			?>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Max Height', 'DiviOverlays' ); ?>:</span>
					<span class="et-fb-form__responsive" style="margin-left: 20px; opacity: 0;"><svg width="20" height="20" viewBox="-3 -3 20 20"><path d="M10 0H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM7 13a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm3-3H4V2h6z"></path></svg></span>
				</span>
				<?php print et_core_esc_previously( dov_style_devices_settings() ) ?>
				<div class="et-fb-settings-options et-fb-option--range" data-responsivemode="desktop">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap">
								<input type="range" min="100" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $maxheight_control ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_maxheight" placeholder="none" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $maxheight ); ?>" data-unit="px" data-defaultvalue="1000px">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$maxheight_control_tablet = $maxheight_tablet = get_post_meta( $post_id, 'dov_maxheight_tablet', true );
				
				if( !isset( $maxheight_tablet ) || $maxheight_tablet === '' || $maxheight_tablet == 0 ) { $maxheight_tablet = '1000px'; }
				
				if ( is_numeric( $maxheight_tablet ) === TRUE ) { $maxheight_tablet = $maxheight_tablet . 'px'; }
				
				$maxheight_control_tablet = floatval( $maxheight_tablet );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="tablet">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap">
								<input type="range" min="100" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $maxheight_control_tablet ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_maxheight_tablet" placeholder="none" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $maxheight_tablet ); ?>" data-unit="px" data-defaultvalue="1000px">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$maxheight_control_phone = $maxheight_phone = get_post_meta( $post_id, 'dov_maxheight_phone', true );
				
				if( !isset( $maxheight_phone ) || $maxheight_phone === '' || $maxheight_phone == 0 ) { $maxheight_phone = '1000px'; }
				
				if ( is_numeric( $maxheight_phone ) === TRUE ) { $maxheight_phone = $maxheight_phone . 'px'; }
				
				$maxheight_control_phone = floatval( $maxheight_phone );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="phone">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap">
								<input type="range" min="100" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $maxheight_control_phone ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="dov_maxheight_phone" placeholder="none" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $maxheight_phone ); ?>" data-unit="px" data-defaultvalue="1000px">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<?php
			
		}
		
	endif;


	if ( ! function_exists( 'do_positioning_settings' ) ) :

		function do_positioning_settings( $input_id	= 'dov_pointoforigin', $styletagclass = 'positioning_pointoforigin', $styletagclassoffset = 'positioning', $bl_point_origins = [], $margins = true ) {
			
			$post_id = get_the_ID();
			
			?>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Location', 'DiviOverlays' ); ?>:</span>
					<span class="et-fb-form__responsive" style="margin-left: 20px; opacity: 0;"><svg width="20" height="20" viewBox="-3 -3 20 20"><path d="M10 0H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM7 13a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm3-3H4V2h6z"></path></svg></span>
				</span>
				<?php print et_core_esc_previously( dov_style_devices_settings() ) ?>
				
				<?php
				
				$pointoforigin = get_post_meta( $post_id, $input_id, true );
				
				if ( !isset( $pointoforigin ) || $pointoforigin === '' || $pointoforigin === 0 ) { 
				
					if ( !isset( $bl_point_origins[ 'center_center' ] ) ) {
						
						$pointoforigin = 'center_center';
						
					} else {
						
						$pointoforigin = 'top_right';
					}
				}
				
				if ( $margins === true ) {
				
					$pointoforigin_marginleft = get_post_meta( $post_id, $input_id . '_marginleft', true );
					
					if ( !isset( $pointoforigin_marginleft ) 
						|| $pointoforigin_marginleft === '' 
						|| $pointoforigin_marginleft == 0 ) { $pointoforigin_marginleft = 'auto'; }
					
					if ( is_numeric( $pointoforigin_marginleft ) === TRUE ) { $pointoforigin_marginleft = $pointoforigin_marginleft . 'px'; }
					
					
					$pointoforigin_marginright = get_post_meta( $post_id, $input_id . '_marginright', true );
					
					if ( !isset( $pointoforigin_marginright ) 
						|| $pointoforigin_marginright === '' 
						|| $pointoforigin_marginright == 0 ) { $pointoforigin_marginright = 'auto'; }
					
					if ( is_numeric( $pointoforigin_marginright ) === TRUE ) { $pointoforigin_marginright = $pointoforigin_marginright . 'px'; }
					
					
					$pointoforigin_paddingtop = get_post_meta( $post_id, $input_id . '_paddingtop', true );
					
					if ( !isset( $pointoforigin_paddingtop ) 
						|| $pointoforigin_paddingtop === '' 
						|| $pointoforigin_paddingtop == 0 ) { $pointoforigin_paddingtop = '30px'; }
					
					if ( is_numeric( $pointoforigin_paddingtop ) === TRUE ) { $pointoforigin_paddingtop = $pointoforigin_paddingtop . 'px'; }
					
					
					$pointoforigin_paddingbottom = get_post_meta( $post_id, $input_id . '_paddingbottom', true );
					
					if ( !isset( $pointoforigin_paddingbottom ) 
						|| $pointoforigin_paddingbottom === '' 
						|| $pointoforigin_paddingbottom == 0 ) { $pointoforigin_paddingbottom = '30px'; }
					
					if ( is_numeric( $pointoforigin_paddingbottom ) === TRUE ) { $pointoforigin_paddingbottom = $pointoforigin_paddingbottom . 'px'; }
				}
				
				?>
				<div class="et-fb-settings-options et-fb-option--position" data-responsivemode="desktop">
					<input name="<?php print et_core_esc_previously( $input_id ); ?>" type="hidden" value="<?php echo esc_attr( $pointoforigin ); ?>">
					<?php if ( $margins === true ) { ?>
					<div class="updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="<?php print et_core_esc_previously( $styletagclass ) ?>">
						<input name="<?php print et_core_esc_previously( $input_id ); ?>_marginleft" type="hidden" value="<?php echo esc_attr( $pointoforigin_marginleft ); ?>" data-unit="px" data-defaultvalue="auto" data-updatebuilder="true" data-css="margin-left">
						<input name="<?php print et_core_esc_previously( $input_id ); ?>_marginright" type="hidden" value="<?php echo esc_attr( $pointoforigin_marginright ); ?>" data-unit="px" data-defaultvalue="auto" data-updatebuilder="true" data-css="margin-right">
						<input name="<?php print et_core_esc_previously( $input_id ); ?>_paddingtop" type="hidden" value="<?php echo esc_attr( $pointoforigin_paddingtop ); ?>" data-unit="px" data-defaultvalue="30px" data-updatebuilder="true" data-css="padding-top">
						<input name="<?php print et_core_esc_previously( $input_id ); ?>_paddingbottom" type="hidden" value="<?php echo esc_attr( $pointoforigin_paddingbottom ); ?>" data-unit="px" data-defaultvalue="30px" data-updatebuilder="true" data-css="padding-bottom">
					</div>
					<?php } ?>
					<div class="et-fb-option-container">
						<div class="et-fb-settings-position-container">
							<div class="et-fb-settings-position-hr" style="background-color: #f1f5f9;"></div>
							<div class="et-fb-settings-position-vr" style="background-color: #f1f5f9;"></div>
							<div class="et-fb-settings-position-control-guide et-fb-settings-position-absolute">
								<div class="et-fb-settings-position-control-inner-top">
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
								</div>
								<?php if ( !isset( $bl_point_origins[ 'center_center' ] ) ) { ?>
								<div class="et-fb-settings-position-control-inner-mid">
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
								</div>
								<?php } ?>
								<div class="et-fb-settings-position-control-inner-bottom">
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
								</div>
							</div>
							<div class="et-fb-settings-position-control-frame et-fb-settings-position-absolute"></div>
							<div class="et-fb-settings-position-control-guide et-fb-settings-position-absolute">
								<div class="et-fb-settings-position-control-inner-top">
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin === 'top_left' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="top_left"></div>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin === 'top_center' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="top_center"></div>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin === 'top_right' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="top_right"></div>
								</div>
								<?php if ( !isset( $bl_point_origins[ 'center_center' ] ) ) { ?>
								<div class="et-fb-settings-position-control-inner-mid">
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin === 'center_left' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="center_left"></div>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin === 'center_center' ) { ?>-active<?php } ?>  et-fb-settings-position-absolute" data-origin_type="center_center"></div>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin === 'center_right' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="center_right"></div>
								</div>
								<?php } ?>
								<div class="et-fb-settings-position-control-inner-bottom">
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin === 'bottom_left' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="bottom_left"></div>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin === 'bottom_center' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="bottom_center"></div>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin === 'bottom_right' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="bottom_right"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<?php
				
				$pointoforigin_tablet = get_post_meta( $post_id, $input_id . '_tablet', true );
				
				if ( !isset( $pointoforigin_tablet ) || $pointoforigin_tablet === '' || $pointoforigin_tablet === 0 ) { 
				
					if ( !isset( $bl_point_origins[ 'center_center' ] ) ) {
						
						$pointoforigin_tablet = 'center_center';
						
					} else {
						
						$pointoforigin_tablet = 'top_right';
					}
				}
				
				if ( $margins === true ) {
				
					$pointoforigin_marginleft_tablet = get_post_meta( $post_id, $input_id . '_marginleft_tablet', true );
					
					if ( !isset( $pointoforigin_marginleft_tablet ) 
						|| $pointoforigin_marginleft_tablet === '' 
						|| $pointoforigin_marginleft_tablet == 0 ) { $pointoforigin_marginleft_tablet = 'auto'; }
					
					if ( is_numeric( $pointoforigin_marginleft_tablet ) === TRUE ) { $pointoforigin_marginleft_tablet = $pointoforigin_marginleft_tablet . 'px'; }
					
					
					$pointoforigin_marginright_tablet = get_post_meta( $post_id, $input_id . '_marginright_tablet', true );
					
					if ( !isset( $pointoforigin_marginright_tablet ) 
						|| $pointoforigin_marginright_tablet === '' 
						|| $pointoforigin_marginright_tablet == 0 ) { $pointoforigin_marginright_tablet = 'auto'; }
					
					if ( is_numeric( $pointoforigin_marginright_tablet ) === TRUE ) { $pointoforigin_marginright_tablet = $pointoforigin_marginright_tablet . 'px'; }
					
					
					$pointoforigin_paddingtop_tablet = get_post_meta( $post_id, $input_id . '_paddingtop_tablet', true );
					
					if ( !isset( $pointoforigin_paddingtop_tablet ) 
						|| $pointoforigin_paddingtop_tablet === '' 
						|| $pointoforigin_paddingtop_tablet == 0 ) { $pointoforigin_paddingtop_tablet = '30px'; }
					
					if ( is_numeric( $pointoforigin_paddingtop_tablet ) === TRUE ) { $pointoforigin_paddingtop_tablet = $pointoforigin_paddingtop_tablet . 'px'; }
					
					
					$pointoforigin_paddingbottom_tablet = get_post_meta( $post_id, $input_id . '_paddingbottom_tablet', true );
					
					if ( !isset( $pointoforigin_paddingbottom_tablet ) 
						|| $pointoforigin_paddingbottom_tablet === '' 
						|| $pointoforigin_paddingbottom_tablet == 0 ) { $pointoforigin_paddingbottom_tablet = '30px'; }
					
					if ( is_numeric( $pointoforigin_paddingbottom_tablet ) === TRUE ) { $pointoforigin_paddingbottom_tablet = $pointoforigin_paddingbottom_tablet . 'px'; }
				}
				
				?>
				<div class="et-fb-settings-options et-fb-option--position do-hide" data-responsivemode="tablet">
					<input name="<?php print et_core_esc_previously( $input_id ); ?>_tablet" type="hidden" value="<?php echo esc_attr( $pointoforigin_tablet ); ?>">
					<?php if ( $margins === true ) { ?>
					<div class="updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="<?php print et_core_esc_previously( $styletagclass ) ?>">
						<input name="<?php print et_core_esc_previously( $input_id ); ?>_marginleft_tablet" type="hidden" value="<?php echo esc_attr( $pointoforigin_marginleft_tablet ); ?>" data-unit="px" data-defaultvalue="auto" data-updatebuilder="true" data-css="margin-left">
						<input name="<?php print et_core_esc_previously( $input_id ); ?>_marginright_tablet" type="hidden" value="<?php echo esc_attr( $pointoforigin_marginright_tablet ); ?>" data-unit="px" data-defaultvalue="auto" data-updatebuilder="true" data-css="margin-right">
						<input name="<?php print et_core_esc_previously( $input_id ); ?>_paddingtop_tablet" type="hidden" value="<?php echo esc_attr( $pointoforigin_paddingtop_tablet ); ?>" data-unit="px" data-defaultvalue="30px" data-updatebuilder="true" data-css="padding-top">
						<input name="<?php print et_core_esc_previously( $input_id ); ?>_paddingbottom_tablet" type="hidden" value="<?php echo esc_attr( $pointoforigin_paddingbottom_tablet ); ?>" data-unit="px" data-defaultvalue="30px" data-updatebuilder="true" data-css="padding-bottom">
					</div>
					<?php } ?>
					<div class="et-fb-option-container">
						<div class="et-fb-settings-position-container">
							<div class="et-fb-settings-position-hr" style="background-color: #f1f5f9;"></div>
							<div class="et-fb-settings-position-vr" style="background-color: #f1f5f9;"></div>
							<div class="et-fb-settings-position-control-guide et-fb-settings-position-absolute">
								<div class="et-fb-settings-position-control-inner-top">
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
								</div>
								<div class="et-fb-settings-position-control-inner-mid">
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<?php if ( !isset( $bl_point_origins[ 'center_center' ] ) ) { ?>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<?php } ?>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
								</div>
								<div class="et-fb-settings-position-control-inner-bottom">
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
								</div>
							</div>
							<div class="et-fb-settings-position-control-frame et-fb-settings-position-absolute"></div>
							<div class="et-fb-settings-position-control-guide et-fb-settings-position-absolute">
								<div class="et-fb-settings-position-control-inner-top">
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_tablet === 'top_left' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="top_left"></div>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_tablet === 'top_center' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="top_center"></div>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_tablet === 'top_right' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="top_right"></div>
								</div>
								<div class="et-fb-settings-position-control-inner-mid">
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_tablet === 'center_left' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="center_left"></div>
									<?php if ( !isset( $bl_point_origins[ 'center_center' ] ) ) { ?>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_tablet === 'center_center' ) { ?>-active<?php } ?>  et-fb-settings-position-absolute" data-origin_type="center_center"></div>
									<?php } ?>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_tablet === 'center_right' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="center_right"></div>
								</div>
								<div class="et-fb-settings-position-control-inner-bottom">
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_tablet === 'bottom_left' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="bottom_left"></div>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_tablet === 'bottom_center' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="bottom_center"></div>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_tablet === 'bottom_right' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="bottom_right"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
				
				<?php
				
				$pointoforigin_phone = get_post_meta( $post_id, $input_id . '_phone', true );
				
				if ( !isset( $pointoforigin_phone ) || $pointoforigin_phone === '' || $pointoforigin_phone === 0 ) { 
				
					if ( !isset( $bl_point_origins[ 'center_center' ] ) ) {
						
						$pointoforigin_phone = 'center_center';
						
					} else {
						
						$pointoforigin_phone = 'top_right';
					}
				}
				
				if ( $margins === true ) {
				
					$pointoforigin_marginleft_phone = get_post_meta( $post_id, $input_id . '_marginleft_phone', true );
					
					if ( !isset( $pointoforigin_marginleft_phone ) 
						|| $pointoforigin_marginleft_phone === '' 
						|| $pointoforigin_marginleft_phone == 0 ) { $pointoforigin_marginleft_phone = 'auto'; }
					
					if ( is_numeric( $pointoforigin_marginleft_phone ) === TRUE ) { $pointoforigin_marginleft_phone = $pointoforigin_marginleft_phone . 'px'; }
					
					
					$pointoforigin_marginright_phone = get_post_meta( $post_id, $input_id . '_marginright_phone', true );
					
					if ( !isset( $pointoforigin_marginright_phone ) 
						|| $pointoforigin_marginright_phone === '' 
						|| $pointoforigin_marginright_phone == 0 ) { $pointoforigin_marginright_phone = 'auto'; }
					
					if ( is_numeric( $pointoforigin_marginright_phone ) === TRUE ) { $pointoforigin_marginright_phone = $pointoforigin_marginright_phone . 'px'; }
					
					
					$pointoforigin_paddingtop_phone = get_post_meta( $post_id, $input_id . '_paddingtop_phone', true );
					
					if ( !isset( $pointoforigin_paddingtop_phone ) 
						|| $pointoforigin_paddingtop_phone === '' 
						|| $pointoforigin_paddingtop_phone == 0 ) { $pointoforigin_paddingtop_phone = '30px'; }
					
					if ( is_numeric( $pointoforigin_paddingtop_phone ) === TRUE ) { $pointoforigin_paddingtop_phone = $pointoforigin_paddingtop_phone . 'px'; }
					
					
					$pointoforigin_paddingbottom_phone = get_post_meta( $post_id, $input_id . '_paddingbottom_phone', true );
					
					if ( !isset( $pointoforigin_paddingbottom_phone ) 
						|| $pointoforigin_paddingbottom_phone === '' 
						|| $pointoforigin_paddingbottom_phone == 0 ) { $pointoforigin_paddingbottom_phone = '30px'; }
					
					if ( is_numeric( $pointoforigin_paddingbottom_phone ) === TRUE ) { $pointoforigin_paddingbottom_phone = $pointoforigin_paddingbottom_phone . 'px'; }
				}
				
				?>
				<div class="et-fb-settings-options et-fb-option--position do-hide" data-responsivemode="phone">
					<input name="<?php print et_core_esc_previously( $input_id ); ?>_phone" type="hidden" value="<?php echo esc_attr( $pointoforigin_phone ); ?>">
					<?php if ( $margins === true ) { ?>
					<div class="updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="<?php print et_core_esc_previously( $styletagclass ) ?>">
						<input name="<?php print et_core_esc_previously( $input_id ); ?>_marginleft_phone" type="hidden" value="<?php echo esc_attr( $pointoforigin_marginleft_phone ); ?>" data-unit="px" data-defaultvalue="auto" data-updatebuilder="true" data-css="margin-left">
						<input name="<?php print et_core_esc_previously( $input_id ); ?>_marginright_phone" type="hidden" value="<?php echo esc_attr( $pointoforigin_marginright_phone ); ?>" data-unit="px" data-defaultvalue="auto" data-updatebuilder="true" data-css="margin-right">
						<input name="<?php print et_core_esc_previously( $input_id ); ?>_paddingtop_phone" type="hidden" value="<?php echo esc_attr( $pointoforigin_paddingtop_phone ); ?>" data-unit="px" data-defaultvalue="30px" data-updatebuilder="true" data-css="padding-top">
						<input name="<?php print et_core_esc_previously( $input_id ); ?>_paddingbottom_phone" type="hidden" value="<?php echo esc_attr( $pointoforigin_paddingbottom_phone ); ?>" data-unit="px" data-defaultvalue="30px" data-updatebuilder="true" data-css="padding-bottom">
					</div>
					<?php } ?>
					<div class="et-fb-option-container">
						<div class="et-fb-settings-position-container">
							<div class="et-fb-settings-position-hr" style="background-color: #f1f5f9;"></div>
							<div class="et-fb-settings-position-vr" style="background-color: #f1f5f9;"></div>
							<div class="et-fb-settings-position-control-guide et-fb-settings-position-absolute">
								<div class="et-fb-settings-position-control-inner-top">
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
								</div>
								<div class="et-fb-settings-position-control-inner-mid">
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<?php if ( !isset( $bl_point_origins[ 'center_center' ] ) ) { ?>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<?php } ?>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
								</div>
								<div class="et-fb-settings-position-control-inner-bottom">
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
									<div class="et-fb-settings-position-button-guide et-fb-settings-position-absolute"></div>
								</div>
							</div>
							<div class="et-fb-settings-position-control-frame et-fb-settings-position-absolute"></div>
							<div class="et-fb-settings-position-control-guide et-fb-settings-position-absolute">
								<div class="et-fb-settings-position-control-inner-top">
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_phone === 'top_left' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="top_left"></div>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_phone === 'top_center' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="top_center"></div>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_phone === 'top_right' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="top_right"></div>
								</div>
								<div class="et-fb-settings-position-control-inner-mid">
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_phone === 'center_left' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="center_left"></div>
									<?php if ( !isset( $bl_point_origins[ 'center_center' ] ) ) { ?>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_phone === 'center_center' ) { ?>-active<?php } ?>  et-fb-settings-position-absolute" data-origin_type="center_center"></div>
									<?php } ?>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_phone === 'center_right' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="center_right"></div>
								</div>
								<div class="et-fb-settings-position-control-inner-bottom">
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_phone === 'bottom_left' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="bottom_left"></div>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_phone === 'bottom_center' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="bottom_center"></div>
									<div class="et-fb-settings-position-button<?php if ( $pointoforigin_phone === 'bottom_right' ) { ?>-active<?php } ?>   et-fb-settings-position-absolute" data-origin_type="bottom_right"></div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			<div data-styletagclass="<?php print et_core_esc_previously( $styletagclassoffset ) ?>">
			
			<?php
			
			if ( $input_id === 'dov_pointoforigin' ) { $input_id = ''; }
			
			$vertical_offset_control = $vertical_offset = get_post_meta( $post_id, $input_id . 'dov_vertical_offset', true );
			
			if( !isset( $vertical_offset ) || $vertical_offset === '' || $vertical_offset == 0 ) { $vertical_offset = '0px'; }
			
			if ( is_numeric( $vertical_offset ) === TRUE ) { $vertical_offset = $vertical_offset . 'px'; }
			
			$vertical_offset_control = floatval( $vertical_offset );
			
			?>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Vertical Offset', 'DiviOverlays' ); ?>:</span>
					<span class="et-fb-form__responsive" style="margin-left: 20px; opacity: 0;"><svg width="20" height="20" viewBox="-3 -3 20 20"><path d="M10 0H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM7 13a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm3-3H4V2h6z"></path></svg></span>
				</span>
				<?php print et_core_esc_previously( dov_style_devices_settings() ) ?>
				<div class="et-fb-settings-options et-fb-option--range" data-responsivemode="desktop">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap dov_vertical_offset updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="<?php print et_core_esc_previously( $styletagclassoffset ) ?>">
								<input type="range" min="-1000" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $vertical_offset_control ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="<?php print et_core_esc_previously( $input_id ); ?>dov_vertical_offset" placeholder="0px" step="1" class="et-fb-settings-option-input" type="text"  value="<?php echo esc_attr( $vertical_offset ); ?>" data-unit="px" data-keepunit="true" data-defaultvalue="0px" data-updatebuilder="true" data-css="top">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$vertical_offset_control_tablet = $vertical_offset_tablet = get_post_meta( $post_id, $input_id . 'dov_vertical_offset_tablet', true );
				
				if( !isset( $vertical_offset_tablet ) || $vertical_offset_tablet === '' || $vertical_offset_tablet == 0 ) { $vertical_offset_tablet = '0px'; }
				
				if ( is_numeric( $vertical_offset_tablet ) === TRUE ) { $vertical_offset_tablet = $vertical_offset_tablet . 'px'; }
				
				$vertical_offset_control_tablet = floatval( $vertical_offset_tablet );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="tablet">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap dov_vertical_offset updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="<?php print et_core_esc_previously( $styletagclassoffset ) ?>">
								<input type="range" min="-1000" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $vertical_offset_control_tablet ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="<?php print et_core_esc_previously( $input_id ); ?>dov_vertical_offset_tablet" placeholder="0px" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $vertical_offset_tablet ); ?>" data-unit="px" data-keepunit="true" data-defaultvalue="0px" data-updatebuilder="true" data-css="top">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$vertical_offset_control_phone = $vertical_offset_phone = get_post_meta( $post_id, $input_id . 'dov_vertical_offset_phone', true );
				
				if( !isset( $vertical_offset_phone ) || $vertical_offset_phone === '' || $vertical_offset_phone == 0 ) { $vertical_offset_phone = '0px'; }
				
				if ( is_numeric( $vertical_offset_phone ) === TRUE ) { $vertical_offset_phone = $vertical_offset_phone . 'px'; }
				
				$vertical_offset_control_phone = floatval( $vertical_offset_phone );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="phone">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap dov_vertical_offset updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="<?php print et_core_esc_previously( $styletagclassoffset ) ?>">
								<input type="range" min="-1000" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $vertical_offset_control_phone ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="<?php print et_core_esc_previously( $input_id ); ?>dov_vertical_offset_phone" placeholder="0px" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $vertical_offset_phone ); ?>" data-unit="px" data-keepunit="true" data-defaultvalue="0px" data-updatebuilder="true" data-css="top">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			
			<?php
			
			$horizontal_offset_control = $horizontal_offset = get_post_meta( $post_id, $input_id . 'dov_horizontal_offset', true );
			
			if( !isset( $horizontal_offset ) || $horizontal_offset === '' || $horizontal_offset == 0 ) { $horizontal_offset = '0px'; }
			
			if ( is_numeric( $horizontal_offset ) === TRUE ) { $horizontal_offset = $horizontal_offset . 'px'; }
			
			$horizontal_offset_control = floatval( $horizontal_offset );
			
			?>
			
			<div class="et-fb-form__group">
				<span class="et-fb-form__label">
					<span class="et-fb-form__label-text"><?php esc_html_e( 'Horizontal Offset', 'DiviOverlays' ); ?>:</span>
					<span class="et-fb-form__responsive" style="margin-left: 20px; opacity: 0;"><svg width="20" height="20" viewBox="-3 -3 20 20"><path d="M10 0H4a2 2 0 0 0-2 2v10a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V2a2 2 0 0 0-2-2zM7 13a1 1 0 1 1 1-1 1 1 0 0 1-1 1zm3-3H4V2h6z"></path></svg></span>
				</span>
				<?php print et_core_esc_previously( dov_style_devices_settings() ) ?>
				<div class="et-fb-settings-options et-fb-option--range" data-responsivemode="desktop">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap dov_horizontal_offset updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="<?php print et_core_esc_previously( $styletagclassoffset ) ?>">
								<input type="range" min="-1000" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $horizontal_offset_control ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="<?php print et_core_esc_previously( $input_id ); ?>dov_horizontal_offset" placeholder="0px" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $horizontal_offset ); ?>" data-unit="px" data-keepunit="true" data-defaultvalue="0px" data-updatebuilder="true" data-css="left">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$horizontal_offset_control_tablet = $horizontal_offset_tablet = get_post_meta( $post_id, $input_id . 'dov_horizontal_offset_tablet', true );
				
				if( !isset( $horizontal_offset_tablet ) || $horizontal_offset_tablet === '' || $horizontal_offset_tablet == 0 ) { $horizontal_offset_tablet = '0px'; }
				
				if ( is_numeric( $horizontal_offset_tablet ) === TRUE ) { $horizontal_offset_tablet = $horizontal_offset_tablet . 'px'; }
				
				$horizontal_offset_control_tablet = floatval( $horizontal_offset_tablet );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="tablet">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap dov_horizontal_offset updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="<?php print et_core_esc_previously( $styletagclassoffset ) ?>">
								<input type="range" min="-1000" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $horizontal_offset_control_tablet ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="<?php print et_core_esc_previously( $input_id ); ?>dov_horizontal_offset_tablet" placeholder="0px" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $horizontal_offset_tablet ); ?>" data-unit="px" data-keepunit="true" data-defaultvalue="0px" data-updatebuilder="true" data-css="left">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
				<?php
				
				$horizontal_offset_control_phone = $horizontal_offset_phone = get_post_meta( $post_id, $input_id . 'dov_horizontal_offset_phone', true );
				
				if( !isset( $horizontal_offset_phone ) || $horizontal_offset_phone === '' || $horizontal_offset_phone == 0 ) { $horizontal_offset_phone = '0px'; }
				
				if ( is_numeric( $horizontal_offset_phone ) === TRUE ) { $horizontal_offset_phone = $horizontal_offset_phone . 'px'; }
				
				$horizontal_offset_control_phone = floatval( $horizontal_offset_phone );
				
				?>
				<div class="et-fb-settings-options et-fb-option--range do-hide" data-responsivemode="phone">
					<div class="et-fb-option-container">
						<div class="et-fb-settings-option-inner et-fb-settings-option-inner-range">
							<div class="et-fb-settings-option-inputs-wrap dov_horizontal_offset updateDiviOverlaysStylesOnDiviBuilder" data-styletagclass="<?php print et_core_esc_previously( $styletagclassoffset ) ?>">
								<input type="range" min="-1000" max="1000" step="1" class="et-fb-range" data-shortcuts-allowed="true" value="<?php echo esc_attr( $horizontal_offset_control_phone ); ?>">
								<div class="et-fb-range-number">
									<div class="et-fb-settings-option--numeric-control">
										<input name="<?php print et_core_esc_previously( $input_id ); ?>dov_horizontal_offset_phone" placeholder="0px" step="1" class="et-fb-settings-option-input" type="text" value="<?php echo esc_attr( $horizontal_offset_phone ); ?>" data-unit="px" data-keepunit="true" data-defaultvalue="0px" data-updatebuilder="true" data-css="left">
										<div class="et-fb-incrementor">
											<div class="increase"></div>
											<div class="decrease"></div>
										</div>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			
			</div>
			
			<?php
		}
		
	endif;


	function divioverlay_settings_callback( $post ) {	

		?>
		<div class="divi-life-logo-container divi-life-logo-head">
			<div class="divi-life-logo-icon brightness"></div>
		</div>
		
		<ul class="do-tab">
			<li role="tab"><a href="#tabc-background" id="do-default-tab-btn" class="do-tablinks active" data-tabid="do-tab-1"><?php esc_html_e( 'Background', 'DiviOverlays' ); ?></a></li>
			<li role="tab"><a href="#tabc-animation" class="do-tablinks" data-tabid="do-tab-2"><?php esc_html_e( 'Animation', 'DiviOverlays' ); ?></a></li>
			<li role="tab"><a href="#tabc-displaylocations" class="do-tablinks" data-tabid="do-tab-3"><?php esc_html_e( 'Locations', 'DiviOverlays' ); ?></a></li>
			<li role="tab"><a href="#tabc-closebtnsettings" class="do-tablinks" data-tabid="do-tab-5"><?php esc_html_e( 'Close Button', 'DiviOverlays' ); ?></a></li>
			<li role="tab"><a href="#tabc-automatictriggers" class="do-tablinks" data-tabid="do-tab-6"><?php esc_html_e( 'Triggers', 'DiviOverlays' ); ?></a></li>
			<li role="tab"><a href="#tabc-additionalsettings" class="do-tablinks" data-tabid="do-tab-4"><?php esc_html_e( 'Advanced', 'DiviOverlays' ); ?></a></li>
			<li role="tab"><a href="#tabc-design" class="do-tablinks" data-tabid="do-tab-7"><?php esc_html_e( 'Design', 'DiviOverlays' ); ?></a></li>
		</ul>
		
		<div class="et-db">
			<div id="et-boc" class="et-boc">
				<div class="et-l">

					<div id="do-tab-1" class="do-tabcontent" data-styletagclass="background">
						<div class="et-fb-tabs__panel et-fb-tabs__panel--active et-fb-tabs__panel--filter-dropdown et-fb-tabs__panel--general">
							<?php do_background_settings() ?>
						</div>
					</div>

					<div id="do-tab-2" class="do-tabcontent">
						<div class="et-fb-tabs__panel et-fb-tabs__panel--active et-fb-tabs__panel--filter-dropdown et-fb-tabs__panel--general">
							<?php do_single_animation_meta_box() ?>
						</div>
					</div>

					<div id="do-tab-3" class="do-tabcontent">
						<?php do_displaylocations_callback() ?>
					</div> 
					
					<div id="do-tab-4" class="do-tabcontent">
						<?php do_moresettings_callback() ?>
					</div>
					
					<div id="do-tab-5" class="do-tabcontent">
						<div class="et-fb-tabs__panel et-fb-tabs__panel--active et-fb-tabs__panel--filter-dropdown et-fb-tabs__panel--general">
							<?php do_closecustoms_callback() ?>
						</div>
					</div>
					
					<div id="do-tab-6" class="do-tabcontent">
						<?php do_triggers_settings() ?>
					</div>
					
					<div id="do-tab-7" class="do-tabcontent">
						<?php do_design_settings() ?>
					</div>
				</div>
			</div>
		</div>
		<?php
	}


	function is_divioverlays_post() {
		
		if ( isset( $_REQUEST['post_type'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			
			$post_type = sanitize_text_field( wp_unslash( $_REQUEST['post_type'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			
			if ( DOV_CUSTOM_POST_TYPE === $post_type ) {
				
				return true;
			}
		}
		
		return false;
	}


	function is_divioverlays_post_id() {

		if ( isset( $_REQUEST['post'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			
			$overlay_id = sanitize_text_field( wp_unslash( $_REQUEST['post'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			
			$overlay_id = (int) $overlay_id;
			
			$post = get_post( $overlay_id );
			
			if ( DOV_CUSTOM_POST_TYPE === $post->post_type ) {
				
				return true;
			}
		}
		
		return false;
	}


	function divi_overlay_config( $hook ) {
		
		if ( !is_divioverlays_post() && !is_divioverlays_post_id() && !is_divioverlays_license_page() ) {
			
			return false;
		}
		
		// enqueue st yle
		et_core_enqueue_js_admin();
		
		wp_register_style( 'divi-overlays-select2', DOV_PLUGIN_URL . 'assets/css/admin/select2.4.0.9.min.css', array(), '4.0.9', 'all' );
		wp_register_script( 'divi-overlays-select2', DOV_PLUGIN_URL . 'assets/js/admin/select2.4.0.9.min.js', array('jquery'), '4.0.9', true );
		wp_register_style( 'divi-overlays-select2-bootstrap', DOV_PLUGIN_URL . 'assets/css/admin/select2-bootstrap.min.css', array('divi-overlays-admin-bootstrap'), '1.0.0', 'all' );
		
		
		/* Scheduling requirements */
		wp_register_script( 'divi-overlays-datetime-moment', '//cdn.jsdelivr.net/momentjs/latest/moment.min.js', array('jquery'), '1.0.0', true );
		wp_register_script( 'divi-overlays-datetime-moment-timezone', '//cdn.jsdelivr.net/npm/moment-timezone@0.5.13/builds/moment-timezone-with-data.min.js', array('jquery'), '1.0.0', true );
		wp_register_style( 'divi-overlays-admin-bootstrap', DOV_PLUGIN_URL . 'assets/css/admin/bootstrap.css', array(), '1.0.0', 'all' );
		wp_register_script( 'divi-overlays-datetime-bootstrap', '//maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js', array('jquery'), '1.0.0', true );
		wp_register_script( 'divi-overlays-datetime-bootstrap-select', '//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.1/js/bootstrap-select.min.js', array('jquery'), '1.0.0', true );
		wp_register_style( 'divi-overlays-admin-bootstrap-select', '//cdnjs.cloudflare.com/ajax/libs/bootstrap-select/1.12.1/css/bootstrap-select.min.css', array(), '1.0.0', 'all' );
		
		/* Include Date Range Picker */
		wp_register_style( 'divi-overlays-datetime-corecss', DOV_PLUGIN_URL . 'assets/css/admin/bootstrap-datetimepicker.min.css', array( 'divi-overlays-admin-bootstrap' ), '1.0.0', 'all' );
		wp_register_script( 'divi-overlays-datetime-corejs', DOV_PLUGIN_URL . 'assets/js/admin/bootstrap-datetimepicker.min.js', array( 'jquery', 'divi-overlays-datetime-bootstrap' ), '1.0.0', true );
		
		wp_register_style( 'divi-overlays-animate-style', DOV_PLUGIN_URL . 'assets/css/animate.min.css', array(), '1.0.0', 'all' );
		wp_register_style( 'divi-overlays-customanimations', DOV_PLUGIN_URL . 'assets/css/custom_animations.css', array(), DOV_VERSION, 'all' );
		
		wp_register_style( 'divi-overlays-admin', DOV_PLUGIN_URL . 'assets/css/admin/admin.css', array(), DOV_VERSION, 'all' );
		wp_register_script( 'divi-overlays-admin-feature-conditions', DOV_PLUGIN_URL . 'assets/js/admin/admin-feature-conditions.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-draggable', 'jquery-ui-sortable', 'jquery-ui-resizable', 'jquery-ui-slider', 'divi-overlays-select2' ), DOV_VERSION, true );
		wp_register_script( 'divi-overlays-admin-functions', DOV_PLUGIN_URL . 'assets/js/admin/admin-functions.js', array( 'jquery', 'jquery-ui-core', 'jquery-ui-slider', 'divi-overlays-select2' ), DOV_VERSION, true );
		
		if ( function_exists( 'et_fb_enqueue_bundle' ) ) {
			
			if ( et_builder_bfb_enabled() || is_divioverlays_license_page() !== false ) {

				et_builder_enqueue_open_sans();

				$secondary_css_bundles = glob( ET_BUILDER_DIR . 'frontend-builder/build/bundle.*.css' );

				if ( $secondary_css_bundles ) {
					$bundles = array( 'et-frontend-builder' );

					foreach ( $secondary_css_bundles as $css_bundle ) {
						$slug  = basename( $css_bundle, '.css' );
						$parts = explode( '.', $slug, -1 );

						// Drop "bundle" from array.
						array_shift( $parts );

						$slug = implode( '-', $parts );

						et_fb_enqueue_bundle( "et-fb-{$slug}", basename( $css_bundle ), $bundles, null );

						$bundles[] = $slug;
					}
				}
			}
			
			et_fb_enqueue_bundle( 'et-theme-builder', 'theme-builder.css', array( 'et-core-admin' ) );
		}
	}
	add_action('admin_init', 'divi_overlay_config');


	function divi_overlay_high_priority_includes( $hook ) {
		
		if ( !dov_is_divi_builder_enabled() ) {
		
			$screen = get_current_screen();
			
			if ( $screen->post_type != DOV_CUSTOM_POST_TYPE ) {
				
				return;
			}
			
			wp_enqueue_script( 'divi-overlays-datetime-moment' );
			wp_enqueue_script( 'divi-overlays-datetime-moment-timezone' );
			wp_enqueue_style( 'divi-overlays-admin-bootstrap' );
			wp_enqueue_script( 'divi-overlays-datetime-bootstrap' );
			wp_enqueue_script( 'divi-overlays-datetime-bootstrap-select' );
			wp_enqueue_style( 'divi-overlays-admin-bootstrap-select' );
			
			wp_enqueue_style( 'divi-overlays-select2' );
			wp_enqueue_style( 'divi-overlays-select2-bootstrap' );
			wp_enqueue_script( 'divi-overlays-select2' );
			
			wp_enqueue_style( 'divi-overlays-datetime-corecss' );
			wp_enqueue_script( 'divi-overlays-datetime-corejs' );
			
			wp_enqueue_script( 'divi-overlays-admin-feature-conditions' );
			wp_enqueue_script( 'divi-overlays-admin-functions' );
		}
	}
	add_action('admin_enqueue_scripts', 'divi_overlay_high_priority_includes', 999);

	function divi_overlay_low_priority_includes( $hook ) {

		if ( !dov_is_divi_builder_enabled() ) {
		
			$screen = get_current_screen();
			
			if ( $screen->post_type != DOV_CUSTOM_POST_TYPE ) {
				return;
			}
			
			wp_enqueue_style('divi-overlays-animate-style');
			wp_enqueue_style('divi-overlays-customanimations');
			wp_enqueue_style( 'divi-overlays-admin' );
		}
	}
	add_action('admin_enqueue_scripts', 'divi_overlay_low_priority_includes', 10, 1 );


	/*===================================================================*/

	// Save Meta Box Value //
	function et_divi_overlay_settings_save_details( $post_id, $post ) {
		
		global $pagenow;
		
		$_REQUEST['et_pb_use_builder'] = 'on';
		$_POST['et_pb_use_builder']    = 'on';
		
		update_post_meta( $post_id, '_et_pb_use_builder', 'on' );
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			
			return $post_id;
		}
		
		// Only set for post_type = divi_overlay
		if ( DOV_CUSTOM_POST_TYPE !== $post->post_type ) {
			
			return;
		}

		$post_type = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) )
			return $post_id;
		
		et_divi_overlay_savefb_post( $post_id, $post );
		
		if ( !isset( $_POST['divioverlays_displaylocations_nonce'] ) ) {
			
			return;
		}
		
		$nonce = sanitize_text_field( wp_unslash( $_POST['divioverlays_displaylocations_nonce'] ) );
		if ( ! wp_verify_nonce( $nonce, 'divioverlays_displaylocations' ) ) {
			
			 die();
		}
		
		
		if ( 'post.php' !== $pagenow ) return $post_id;
		
		$post_value = '';
		if ( isset( $_POST['et_pb_divioverlay_effect_entrance'] ) ) {
			
			$post_value = sanitize_option( 'et_pb_divioverlay_effect_entrance', wp_unslash( $_POST['et_pb_divioverlay_effect_entrance'] ) );
			update_post_meta( $post_id, 'et_pb_divioverlay_effect_entrance', $post_value );
			
		} else {
			
			update_post_meta( $post_id, 'et_pb_divioverlay_effect_entrance', '' );
		}
		
		$post_value = '';
		if ( isset( $_POST['et_pb_divioverlay_effect_exit'] ) ) {
			
			$post_value = sanitize_option( 'et_pb_divioverlay_effect_exit', wp_unslash( $_POST['et_pb_divioverlay_effect_exit'] ) );
			update_post_meta( $post_id, 'et_pb_divioverlay_effect_exit', $post_value );
			
		} else {
			
			update_post_meta( $post_id, 'et_pb_divioverlay_effect_exit', '' );
		}
		
		if ( isset( $_POST['dov_effect_entrance_speed'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_effect_entrance_speed'] ) );
			update_post_meta( $post_id, 'dov_effect_entrance_speed', $post_value );
		}
		
		if ( isset( $_POST['dov_effect_exit_speed'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_effect_exit_speed'] ) );
			update_post_meta( $post_id, 'dov_effect_exit_speed', $post_value );
		}
		
		if ( isset( $_POST['post_bg'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_bg'] ) );
			update_post_meta( $post_id, 'post_overlay_bg_color', $post_value );
		}
		
		if ( isset( $_POST['do_enablebgblur'] ) ) {
			
			$do_enablebgblur = 1;
			
		} else {
			
			$do_enablebgblur = 0;
		}
		update_post_meta( $post_id, 'do_enablebgblur', $do_enablebgblur );
		
		if ( isset( $_POST['post_do_preventscroll'] ) ) {
			
			$post_do_preventscroll = 1;
			
		} else {
			
			$post_do_preventscroll = 0;
		}
		update_post_meta( $post_id, 'post_do_preventscroll', $post_do_preventscroll );
		
		/* By Force render */
		if ( isset( $_POST['do_forcerender'] ) ) {
			
			$do_forcerender = 1;
			
		} else {
			
			$do_forcerender = 0;
		}
		update_post_meta( $post_id, 'do_forcerender', $do_forcerender );
		
		
		/* Display Locations */
		if ( isset( $_POST['divilife_useon'] ) ) {
			
			$post_value = sanitize_option( 'divilife_useon', wp_unslash( $_POST['divilife_useon'] ) );
			update_post_meta( $post_id, 'displaylocations_useon', $post_value );
		}
		
		if ( isset( $_POST['divilife_excludefrom'] ) ) {
			
			$post_value = sanitize_option( 'divilife_excludefrom', wp_unslash( $_POST['divilife_excludefrom'] ) );
			update_post_meta( $post_id, 'displaylocations_excludefrom', $post_value );
		}
		
		
		/* Additional Settings */
		if ( isset( $_POST['post_css_selector'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_css_selector'] ) );
			update_post_meta( $post_id, 'post_css_selector', $post_value );
		}
		
		if ( isset( $_POST['post_enableurltrigger'] ) ) {
			
			$post_enableurltrigger = 1;
			
		} else {
			
			$post_enableurltrigger = 0;
		}
		update_post_meta( $post_id, 'post_enableurltrigger', $post_enableurltrigger );
		
		
		if ( isset( $_POST['do_enableajax'] ) ) {
			
			$do_enableajax = 1;
			
		} else {
			
			$do_enableajax = 0;
		}
		update_post_meta( $post_id, 'do_enableajax', $do_enableajax );
		
		
		if ( isset( $_POST['do_enableesckey'] ) ) {
			
			$do_enableesckey = 1;
			
		} else {
			
			$do_enableesckey = 0;
		}
		update_post_meta( $post_id, 'do_enableesckey', $do_enableesckey );
		
		
		if ( isset( $_POST['post_overlay_automatictrigger'] ) && $_POST['post_overlay_automatictrigger'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_overlay_automatictrigger'] ) );
			update_post_meta( $post_id, 'overlay_automatictrigger', $post_value );
		
			if ( isset( $_POST['post_at_timed'] ) ) {
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['post_at_timed'] ) );
				update_post_meta( $post_id, 'overlay_automatictrigger_timed_value', $post_value );
			}
			
			if ( isset( $_POST['post_at_scroll_from'] ) || isset( $_POST['post_at_scroll_to'] ) ) {
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['post_at_scroll_from'] ) );
				update_post_meta( $post_id, 'overlay_automatictrigger_scroll_from_value', $post_value );
				
				$post_value = sanitize_text_field( wp_unslash( $_POST['post_at_scroll_to'] ) );
				update_post_meta( $post_id, 'overlay_automatictrigger_scroll_to_value', $post_value );
			}
			
			if ( isset( $_POST['post_at_disablemobile'] ) ) {
				
				$post_at_disablemobile = 1;
				
			} else {
				
				$post_at_disablemobile = 0;
			}
			
			if ( isset( $_POST['post_at_disabletablet'] ) ) {
				
				$post_at_disabletablet = 1;
				
			} else {
				
				$post_at_disabletablet = 0;
			}
			
			if ( isset( $_POST['post_at_disabledesktop'] ) ) {
				
				$post_at_disabledesktop = 1;
				
			} else {
				
				$post_at_disabledesktop = 0;
			}
			
			
			if ( isset( $_POST['post_at_onceperload'] ) ) {
				
				$post_at_onceperload = 1;
				
			} else {
				
				$post_at_onceperload = 0;
			}
			
			update_post_meta( $post_id, 'overlay_automatictrigger_onceperload', $post_at_onceperload );
			
			
		} else {
			
			update_post_meta( $post_id, 'overlay_automatictrigger', 0 );
			update_post_meta( $post_id, 'overlay_automatictrigger_onceperload', 0 );
			$post_at_disablemobile = 0;
			$post_at_disabletablet = 0;
			$post_at_disabledesktop = 0;
		}
		update_post_meta( $post_id, 'overlay_automatictrigger_disablemobile', $post_at_disablemobile );
		update_post_meta( $post_id, 'overlay_automatictrigger_disabletablet', $post_at_disabletablet );
		update_post_meta( $post_id, 'overlay_automatictrigger_disabledesktop', $post_at_disabledesktop );
		
		
		/* Close Button Customizations */
		if ( isset( $_POST['dov_closebtn_cookie'] ) ) {
			
			$post_value = floatval( sanitize_text_field( wp_unslash( $_POST['dov_closebtn_cookie'] ) ) );
			update_post_meta( $post_id, 'dov_closebtn_cookie', $post_value );
		}
		
		if ( isset( $_POST['closebtninoverlay'] ) ) {
			
			$closebtninoverlay = 1;
			
		} else {
			
			$closebtninoverlay = 0;
		}
		update_post_meta( $post_id, 'closebtninoverlay', $closebtninoverlay );
		
		if ( isset( $_POST['post_do_closebtnclickingoutside'] ) ) {
			
			$post_do_closebtnclickingoutside = 1;
			
		} else {
			
			$post_do_closebtnclickingoutside = 0;
		}
		update_post_meta( $post_id, 'post_do_closebtnclickingoutside', $post_do_closebtnclickingoutside );
		
		if ( isset( $_POST['post_do_hideclosebtn'] ) ) {
			
			$post_do_hideclosebtn = 1;
			
		} else {
			
			$post_do_hideclosebtn = 0;
		}
		update_post_meta( $post_id, 'post_do_hideclosebtn', $post_do_hideclosebtn );
		
		if ( isset( $_POST['post_do_customizeclosebtn'] ) ) {
			
			$post_do_customizeclosebtn = 1;
			
		} else {
			
			$post_do_customizeclosebtn = 0;
		}
		update_post_meta( $post_id, 'post_do_customizeclosebtn', $post_do_customizeclosebtn );
		
		if ( isset( $_POST['post_doclosebtn_text_color'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_text_color'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_text_color', $post_value );
		}
		
		if ( isset( $_POST['post_doclosebtn_text_color_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_text_color_tablet'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_text_color_tablet', $post_value );
		}
		
		if ( isset( $_POST['post_doclosebtn_text_color_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_text_color_phone'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_text_color_phone', $post_value );
		}
		
		
		if ( isset( $_POST['post_doclosebtn_bg_color'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_bg_color'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_bg_color', $post_value );
		}
		
		if ( isset( $_POST['post_doclosebtn_bg_color_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_bg_color_tablet'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_bg_color_tablet', $post_value );
		}
		
		if ( isset( $_POST['post_doclosebtn_bg_color_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_bg_color_phone'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_bg_color_phone', $post_value );
		}
		
		
		if ( isset( $_POST['post_doclosebtn_fontsize'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_fontsize'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_fontsize', $post_value );
		}
		
		if ( isset( $_POST['post_doclosebtn_fontsize_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_fontsize_tablet'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_fontsize_tablet', $post_value );
		}
		
		if ( isset( $_POST['post_doclosebtn_fontsize_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_fontsize_phone'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_fontsize_phone', $post_value );
		}
		
		
		if ( isset( $_POST['post_doclosebtn_borderradius'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_borderradius'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_borderradius', $post_value );
		}
		
		if ( isset( $_POST['post_doclosebtn_borderradius_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_borderradius_tablet'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_borderradius_tablet', $post_value );
		}
		
		if ( isset( $_POST['post_doclosebtn_borderradius_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_borderradius_phone'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_borderradius_phone', $post_value );
		}
		
		
		if ( isset( $_POST['post_doclosebtn_padding'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_padding'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_padding', $post_value );
		}
		
		if ( isset( $_POST['post_doclosebtn_padding_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_padding_tablet'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_padding_tablet', $post_value );
		}
		
		if ( isset( $_POST['post_doclosebtn_padding_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_padding_phone'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_padding_phone', $post_value );
		}
		
		
		if ( isset( $_POST['post_doclosebtn_positionright'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_positionright'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_positionright', $post_value );
		}
		if ( isset( $_POST['post_doclosebtn_positionright_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_positionright_tablet'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_positionright_tablet', $post_value );
		}
		if ( isset( $_POST['post_doclosebtn_positionright_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_positionright_phone'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_positionright_phone', $post_value );
		}
		
		if ( isset( $_POST['post_doclosebtn_positiontop'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_positiontop'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_positiontop', $post_value );
		}
		if ( isset( $_POST['post_doclosebtn_positiontop_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_positiontop_tablet'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_positiontop_tablet', $post_value );
		}
		if ( isset( $_POST['post_doclosebtn_positiontop_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['post_doclosebtn_positiontop_phone'] ) );
			update_post_meta( $post_id, 'post_doclosebtn_positiontop_phone', $post_value );
		}
		
		/* Save Close Button Positioning Data */
		if ( isset( $_POST['dov_closebtn_pointoforigin'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_closebtn_pointoforigin'] ) );
			update_post_meta( $post_id, 'dov_closebtn_pointoforigin', $post_value );
		}
		
		if ( isset( $_POST['dov_closebtn_pointoforigin_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_closebtn_pointoforigin_tablet'] ) );
			update_post_meta( $post_id, 'dov_closebtn_pointoforigin_tablet', $post_value );
		}
		
		if ( isset( $_POST['dov_closebtn_pointoforigin_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_closebtn_pointoforigin_phone'] ) );
			update_post_meta( $post_id, 'dov_closebtn_pointoforigin_phone', $post_value );
		}
		
		
		if ( isset( $_POST['dov_closebtn_pointoforigindov_vertical_offset'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_closebtn_pointoforigindov_vertical_offset'] ) );
			update_post_meta( $post_id, 'dov_closebtn_pointoforigindov_vertical_offset', $post_value );
		}
		
		if ( isset( $_POST['dov_closebtn_pointoforigindov_vertical_offset_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_closebtn_pointoforigindov_vertical_offset_tablet'] ) );
			update_post_meta( $post_id, 'dov_closebtn_pointoforigindov_vertical_offset_tablet', $post_value );
		}
		
		if ( isset( $_POST['dov_closebtn_pointoforigindov_vertical_offset_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_closebtn_pointoforigindov_vertical_offset_phone'] ) );
			update_post_meta( $post_id, 'dov_closebtn_pointoforigindov_vertical_offset_phone', $post_value );
		}
		
		
		if ( isset( $_POST['dov_closebtn_pointoforigindov_horizontal_offset'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_closebtn_pointoforigindov_horizontal_offset'] ) );
			update_post_meta( $post_id, 'dov_closebtn_pointoforigindov_horizontal_offset', $post_value );
		}
		
		if ( isset( $_POST['dov_closebtn_pointoforigindov_horizontal_offset_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_closebtn_pointoforigindov_horizontal_offset_tablet'] ) );
			update_post_meta( $post_id, 'dov_closebtn_pointoforigindov_horizontal_offset_tablet', $post_value );
		}
		
		if ( isset( $_POST['dov_closebtn_pointoforigindov_horizontal_offset_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_closebtn_pointoforigindov_horizontal_offset_phone'] ) );
			update_post_meta( $post_id, 'dov_closebtn_pointoforigindov_horizontal_offset_phone', $post_value );
		}
		
		/* Save Scheduling */
		if ( isset( $_POST['do_enable_scheduling'] ) ) {
			
			$enable_scheduling = (int) $_POST['do_enable_scheduling'];
			
		} else {
			
			$enable_scheduling = 0;
		}
		update_post_meta( $post_id, 'do_enable_scheduling', $enable_scheduling );
		
		/* Save Scheduling */
		if ( $enable_scheduling ) {
			
			$timezone = DOV_SERVER_TIMEZONE;
			
			$wp_timezone = wp_timezone();
			
			if ( $wp_timezone !== false ) {
				
				$timezone = $wp_timezone;
			}
			
			if ( $enable_scheduling == 1 ) {
				
				if ( isset( $_POST['do_date_start'] ) ) {
					
					$post_value = sanitize_text_field( wp_unslash( $_POST['do_date_start'] ) );
					$date_string = doConvertDateToUTC( $post_value, $timezone );
					update_post_meta( $post_id, 'do_date_start', $date_string );
				}
				
				if ( isset( $_POST['do_date_end'] ) ) {
					
					$post_value = sanitize_text_field( wp_unslash( $_POST['do_date_end'] ) );
					$date_string = doConvertDateToUTC( $post_value, $timezone );
					update_post_meta( $post_id, 'do_date_end', $date_string );
				}
			}
			
			if ( $enable_scheduling == 2 ) {
				
				if ( isset( $_POST['do_time_start'] ) ) {
					
					$date_string = sanitize_text_field( wp_unslash( $_POST['do_time_start'] ) );
					update_post_meta( $post_id, 'do_time_start', $date_string );
				}
				
				if ( isset( $_POST['do_time_end'] ) ) {
					
					$date_string = sanitize_text_field( wp_unslash( $_POST['do_time_end'] ) );
					update_post_meta( $post_id, 'do_time_end', $date_string );
				}
				
				if ( isset( $_POST['divioverlays_scheduling_daysofweek'] ) ) {
				
					$daysofweek = array_map( 'sanitize_text_field', wp_unslash( $_POST['divioverlays_scheduling_daysofweek'] ) );
					
					// Reset all daysofweek values
					for( $a = 1; $a <= 7; $a++ ) {
						update_post_meta( $post_id, 'divioverlays_scheduling_daysofweek_' . $a, 0 );
					}
					
					foreach( $daysofweek as $day ) {
						update_post_meta( $post_id, 'divioverlays_scheduling_daysofweek_' . $day, 1);
					}
				}
			}
		}
		
		
		/* Save Sizing data */
		$sizing_minwidth = '95%';
		$sizing_minwidth_tablet_phone = '100%';
		$sizing_width = '95%';
		$sizing_width_tablet_phone = '100%';
		$sizing_maxwidth = 'none';
		$sizing_minheight = 'auto';
		$sizing_height = 'auto';
		$sizing_maxheight = 'none';
		
		if ( isset( $_POST['dov_minwidth'] ) && $_POST['dov_minwidth'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_minwidth'] ) );
			update_post_meta( $post_id, 'dov_minwidth', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_minwidth', $sizing_minwidth );
		}
		
		if ( isset( $_POST['dov_minwidth_tablet'] ) && $_POST['dov_minwidth_tablet'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_minwidth_tablet'] ) );
			update_post_meta( $post_id, 'dov_minwidth_tablet', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_minwidth_tablet', $sizing_minwidth_tablet_phone );
		}
		if ( isset( $_POST['dov_minwidth_phone'] ) && $_POST['dov_minwidth_phone'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_minwidth_phone'] ) );
			update_post_meta( $post_id, 'dov_minwidth_phone', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_minwidth_phone', $sizing_minwidth_tablet_phone );
		}
		
		if ( isset( $_POST['dov_width'] ) && $_POST['dov_width'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_width'] ) );
			update_post_meta( $post_id, 'dov_width', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_width', $sizing_width );
		}
		if ( isset( $_POST['dov_width_tablet'] ) && $_POST['dov_width_tablet'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_width_tablet'] ) );
			update_post_meta( $post_id, 'dov_width_tablet', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_width_tablet', $sizing_width_tablet_phone );
		}
		if ( isset( $_POST['dov_width_phone'] ) && $_POST['dov_width_phone'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_width_phone'] ) );
			update_post_meta( $post_id, 'dov_width_phone', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_width_phone', $sizing_width_tablet_phone );
		}
		
		if ( isset( $_POST['dov_maxwidth'] ) && $_POST['dov_maxwidth'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_maxwidth'] ) );
			update_post_meta( $post_id, 'dov_maxwidth', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_maxwidth', $sizing_maxwidth );
		}
		if ( isset( $_POST['dov_maxwidth_tablet'] ) && $_POST['dov_maxwidth_tablet'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_maxwidth_tablet'] ) );
			update_post_meta( $post_id, 'dov_maxwidth_tablet', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_maxwidth_tablet', $sizing_maxwidth );
		}
		if ( isset( $_POST['dov_maxwidth_phone'] ) && $_POST['dov_maxwidth_phone'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_maxwidth_phone'] ) );
			update_post_meta( $post_id, 'dov_maxwidth_phone', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_maxwidth_phone', $sizing_maxwidth );
		}
		
		if ( isset( $_POST['dov_minheight'] ) && $_POST['dov_minheight'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_minheight'] ) );
			update_post_meta( $post_id, 'dov_minheight', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_minheight', $sizing_minheight );
		}
		if ( isset( $_POST['dov_minheight_tablet'] ) && $_POST['dov_minheight_tablet'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_minheight_tablet'] ) );
			update_post_meta( $post_id, 'dov_minheight_tablet', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_minheight_tablet', $sizing_minheight );
		}
		if ( isset( $_POST['dov_minheight_phone'] ) && $_POST['dov_minheight_phone'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_minheight_phone'] ) );
			update_post_meta( $post_id, 'dov_minheight_phone', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_minheight_phone', $sizing_minheight );
		}
		
		if ( isset( $_POST['dov_height'] ) && $_POST['dov_height'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_height'] ) );
			update_post_meta( $post_id, 'dov_height', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_height', $sizing_height );
		}
		if ( isset( $_POST['dov_height_tablet'] ) && $_POST['dov_height_tablet'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_height_tablet'] ) );
			update_post_meta( $post_id, 'dov_height_tablet', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_height_tablet', $sizing_height );
		}
		if ( isset( $_POST['dov_height_phone'] ) && $_POST['dov_height_phone'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_height_phone'] ) );
			update_post_meta( $post_id, 'dov_height_phone', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_height_phone', $sizing_height );
		}
		
		if ( isset( $_POST['dov_maxheight'] ) && $_POST['dov_maxheight'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_maxheight'] ) );
			update_post_meta( $post_id, 'dov_maxheight', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_maxheight', $sizing_maxheight );
		}
		if ( isset( $_POST['dov_maxheight_tablet'] ) && $_POST['dov_maxheight_tablet'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_maxheight_tablet'] ) );
			update_post_meta( $post_id, 'dov_maxheight_tablet', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_maxheight_tablet', $sizing_maxheight );
		}
		if ( isset( $_POST['dov_maxheight_phone'] ) && $_POST['dov_maxheight_phone'] != '' ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_maxheight_phone'] ) );
			update_post_meta( $post_id, 'dov_maxheight_phone', $post_value );
		}
		else {
			
			update_post_meta( $post_id, 'dov_maxheight_phone', $sizing_maxheight );
		}
		
		
		/* Save Positioning data */
		if ( isset( $_POST['dov_pointoforigin'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin', $post_value );
		}
		
		if ( isset( $_POST['dov_pointoforigin_marginleft'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin_marginleft'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin_marginleft', $post_value );
		}
		
		if ( isset( $_POST['dov_pointoforigin_marginright'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin_marginright'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin_marginright', $post_value );
		}
		
		if ( isset( $_POST['dov_pointoforigin_paddingtop'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin_paddingtop'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin_paddingtop', $post_value );
		}
		
		if ( isset( $_POST['dov_pointoforigin_paddingbottom'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin_paddingbottom'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin_paddingbottom', $post_value );
		}
		
		
		if ( isset( $_POST['dov_pointoforigin_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin_tablet'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin_tablet', $post_value );
		}
		
		if ( isset( $_POST['dov_pointoforigin_marginleft_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin_marginleft_tablet'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin_marginleft_tablet', $post_value );
		}
		
		if ( isset( $_POST['dov_pointoforigin_marginright_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin_marginright_tablet'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin_marginright_tablet', $post_value );
		}
		
		if ( isset( $_POST['dov_pointoforigin_paddingtop_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin_paddingtop_tablet'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin_paddingtop_tablet', $post_value );
		}
		
		if ( isset( $_POST['dov_pointoforigin_paddingbottom_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin_paddingbottom_tablet'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin_paddingbottom_tablet', $post_value );
		}
		
		
		if ( isset( $_POST['dov_pointoforigin_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin_phone'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin_phone', $post_value );
		}
		
		if ( isset( $_POST['dov_pointoforigin_marginleft_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin_marginleft_phone'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin_marginleft_phone', $post_value );
		}
		
		if ( isset( $_POST['dov_pointoforigin_marginright_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin_marginright_phone'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin_marginright_phone', $post_value );
		}
		
		if ( isset( $_POST['dov_pointoforigin_paddingtop_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin_paddingtop_phone'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin_paddingtop_phone', $post_value );
		}
		
		if ( isset( $_POST['dov_pointoforigin_paddingbottom_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_pointoforigin_paddingbottom_phone'] ) );
			update_post_meta( $post_id, 'dov_pointoforigin_paddingbottom_phone', $post_value );
		}
		
		
		if ( isset( $_POST['dov_vertical_offset'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_vertical_offset'] ) );
			update_post_meta( $post_id, 'dov_vertical_offset', $post_value );
		}
		
		if ( isset( $_POST['dov_vertical_offset_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_vertical_offset_tablet'] ) );
			update_post_meta( $post_id, 'dov_vertical_offset_tablet', $post_value );
		}
		
		if ( isset( $_POST['dov_vertical_offset_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_vertical_offset_phone'] ) );
			update_post_meta( $post_id, 'dov_vertical_offset_phone', $post_value );
		}
		
		
		if ( isset( $_POST['dov_horizontal_offset'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_horizontal_offset'] ) );
			update_post_meta( $post_id, 'dov_horizontal_offset', $post_value );
		}
		
		if ( isset( $_POST['dov_horizontal_offset_tablet'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_horizontal_offset_tablet'] ) );
			update_post_meta( $post_id, 'dov_horizontal_offset_tablet', $post_value );
		}
		
		if ( isset( $_POST['dov_horizontal_offset_phone'] ) ) {
			
			$post_value = sanitize_text_field( wp_unslash( $_POST['dov_horizontal_offset_phone'] ) );
			update_post_meta( $post_id, 'dov_horizontal_offset_phone', $post_value );
		}
		
		if ( isset( $_POST['dov_displayConditionsJSON'] ) ) {
			
			if ( $_POST['dov_displayConditionsJSON'] !== '' ) {
			
				$post_value = sanitize_text_field( wp_unslash( $_POST['dov_displayConditionsJSON'] ) );
				$post_value = base64_encode( $post_value );
				update_post_meta( $post_id, 'dov_displayConditionsJSON', $post_value );
			}
		}
		
		DiviOverlays::super_clear_cache( $post_id );
		
		// This function only clear all Divi Builder files starting with 'et-core-unified'
		DiviOverlays::super_clear_cache( 'all', 'all' );
		
		if ( function_exists( 'et_theme_builder_clear_wp_cache' ) ) {
		
			et_theme_builder_clear_wp_cache( 'all' );
		}
		
		if ( class_exists( 'ET_Core_Cache_File' ) ) {
			
			// Always reset the cached templates on last request after data stored into database.
			ET_Core_Cache_File::set( 'et_theme_builder_templates', array() );
		}
		
		if ( class_exists( 'ET_Core_Cache_File' ) ) {
			
			// Remove static resources on save. It's necessary because how we are generating the dynamic assets for the TB.
			ET_Core_PageResource::remove_static_resources( 'all', 'all', false, 'dynamic' );
		}
	}
	add_action( 'save_post_' . DOV_CUSTOM_POST_TYPE, 'et_divi_overlay_settings_save_details', 10, 2 );


	function et_divi_overlay_savefb_post( $post_id, $post ) {
		
		remove_action( 'save_post_divi_overlay', array( 'DiviOverlays', 'et_divi_overlay_settings_save_details' ), 10 );
		remove_action( 'replace_editor', array( 'DiviOverlays', 'divi_builder_always_on' ), 10 );
		
		if ( isset( $_POST['divioverlays_nonce'] ) ) {
			
			$nonce = sanitize_text_field( wp_unslash( $_POST['divioverlays_nonce'] ) );
			if ( ! wp_verify_nonce( $nonce, 'divioverlays_displaylocations' ) ) {
				
				wp_die( '<pre>Undefined nonce</pre>' );
			}
		}
		
		if ( isset( $_POST['post'] ) ) {
			
			$bkp_post = sanitize_text_field( wp_unslash( $_POST['post'] ) );
			
		} else {
			
			$bkp_post = false;
		}
		
		$_POST['post'] = $post_id;
		
		apply_filters( 'et_builder_render_layout', $post->post_content );
		
		if ( $bkp_post ) {
			
			$_POST['post'] = $bkp_post;
			
		} else {
			
			unset( $_POST['post'] );
		}
		
		add_action( 'save_post_divi_overlay', 'et_divi_overlay_settings_save_details', 10, 2 );
		add_filter( 'replace_editor', array( 'DiviOverlays', 'divi_builder_always_on' ), 10, 2 );
	}
	
	function et_save_post_not_divi_overlay( $post_id, $post ) {
		
		global $pagenow;
		
		if ( 'post.php' !== $pagenow ) return $post_id;
		
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
			return $post_id;
		
		$post_type = get_post_type_object( $post->post_type );
		if ( ! current_user_can( $post_type->cap->edit_post, $post_id ) )
			return $post_id;
		
		// Clear all Divi cache
		DiviOverlays::super_clear_cache();
	}
	
	
	function doConvertDateToUTC( $date = null, $timezone = DOV_SERVER_TIMEZONE, $format = DOV_SCHEDULING_DATETIME_FORMAT ) {
				
		if ( $date === null ) {
			
			return;
		}
		
		if ( !doValidateDate( $date, $format ) ) {
			
			return;
		}
		
		$timezone = wp_timezone();
		
		$date = new DateTime( $date, $timezone );
		$str_server_now = $date->format( $format );
		
		return $str_server_now;
	}


	function doConvertDateToUserTimezone( $date = null, $format = DOV_SCHEDULING_DATETIME_FORMAT ) {
				
		if ( $date === null ) {
			
			return;
		}
		
		if ( !doValidateDate( $date, $format ) ) {
			
			return;
		}
		
		$timezone = wp_timezone();
		
		$date = new DateTime( $date, $timezone );
		$str_server_now = $date->format( $format );
		
		return $str_server_now;
	}

	function doValidateDate( $dateStr, $format ) {
				
		$date = DateTime::createFromFormat($format, $dateStr);
		return $date && ($date->format($format) === $dateStr);
	}
	
	if ( get_option( 'divi-overlays-activated' ) === 'yes' ) {
		
		delete_option( 'divi-overlays-activated' );
	
		function OnceMigrateCbcValues() {
			
			if ( get_option( 'OnceMigrateCbcValues', '0' ) == '1' ) {
				return;
			}
			
			/* Search Divi Overlays with Custom Close Buttons */
			$args = array(
				'meta_key'   => 'post_do_customizeclosebtn',
				'meta_value' => '',
				'meta_compare' => '!=',
				'post_type' => DOV_CUSTOM_POST_TYPE,
				'cache_results'  => false
			);
			$query = new WP_Query( $args );
			
			$posts = $query->get_posts();
			
			if ( isset( $posts[0] ) ) {
				
				migrateCbcValues( $posts );
			}

			// Add or update the wp_option
			update_option( 'OnceMigrateCbcValues', '1' );
		}
		add_action( 'init', 'OnceMigrateCbcValues' );

		function migrateCbcValues( $posts = null ){
			
			if ( is_array( $posts ) ) {
			
				foreach( $posts as $dv_post ) {
					
					$post_id = $dv_post->ID;
					
					updateCbcValues( $post_id );
				}
			}
		}

		function updateCbcValues( $post_id = null ) {
			
			if ( $post_id ) {
			
				$old_cbc_textcolor = get_post_meta( $post_id, 'post_closebtn_text_color', true );
				$old_cbc_bgcolor = get_post_meta( $post_id, 'post_closebtn_bg_color', true );
				$old_cbc_fontsize = get_post_meta( $post_id, 'post_closebtn_fontsize', true );
				$old_cbc_borderradius = get_post_meta( $post_id, 'post_closebtn_borderradius', true );
				$old_cbc_padding = get_post_meta( $post_id, 'post_closebtn_padding', true );
				
				if ( $old_cbc_textcolor != '' ) {
					update_post_meta( $post_id, 'post_doclosebtn_text_color', sanitize_text_field( $old_cbc_textcolor ) );
				}
				
				if ( $old_cbc_bgcolor != '' ) {
					update_post_meta( $post_id, 'post_doclosebtn_bg_color', sanitize_text_field( $old_cbc_bgcolor ) );
				}
				
				if ( $old_cbc_fontsize != '' ) {
					update_post_meta( $post_id, 'post_doclosebtn_fontsize', sanitize_text_field( $old_cbc_fontsize ) );
				}
				
				if ( $old_cbc_borderradius != '' ) {
					update_post_meta( $post_id, 'post_doclosebtn_borderradius', sanitize_text_field( $old_cbc_borderradius ) );
				}
				
				if ( $old_cbc_padding != '' ) {
					update_post_meta( $post_id, 'post_doclosebtn_padding', sanitize_text_field( $old_cbc_padding ) );
				}
				
				// Reset old values
				update_post_meta( $post_id, 'post_closebtn_text_color', '' );
				update_post_meta( $post_id, 'post_closebtn_bg_color', '' );
				update_post_meta( $post_id, 'post_closebtn_fontsize', '' );
				update_post_meta( $post_id, 'post_closebtn_borderradius', '' );
				update_post_meta( $post_id, 'post_closebtn_padding', '' );
			}
		}


		function OnceMigrateURLTriggerByLocationValues() {
			
			// Add or update the wp_option
			update_option( 'OnceMigrateUTValues', '1' );
			
			if ( get_option( 'OnceMigrateUTValues', '0' ) === '1' ) {
				return;
			}
			
			/* Search Divi Overlays with Custom Close Buttons */
			$args = array(
				'meta_key'   => 'post_enableurltrigger_pages',
				'meta_value' => '',
				'meta_compare' => '!=',
				'post_type' => DOV_CUSTOM_POST_TYPE,
				'cache_results'  => false,
				'posts_per_page' => -1
			);
			$query = new WP_Query( $args );
			
			$posts = $query->get_posts();
			
			if ( isset( $posts[0] ) ) {
				
				migrateUTValues( $posts );
			}

			// Add or update the wp_option
			update_option( 'OnceMigrateUTValues', '1' );
		}
		add_action( 'init', 'OnceMigrateURLTriggerByLocationValues' );

		function migrateUTValues( $posts = null ){
			
			if ( is_array( $posts ) ) {
				
				foreach( $posts as $dv_post ) {
					
					$post_id = $dv_post->ID;
					
					updateUTValues( $post_id );
				}
			}
		}

		function updateUTValues( $post_id = null ) {
			
			if ( $post_id !== '' ) {
			
				$old_ut_post_enableurltrigger_pages = get_post_meta( $post_id, 'post_enableurltrigger_pages', true );
				$old_ut_post_dolistpages = get_post_meta( $post_id, 'post_dolistpages', true );
				
				$post_at_pages = get_post_meta( $post_id, 'do_at_pages', true );
				$post_at_pages_selected = get_post_meta( $post_id, 'do_at_pages_selected', true );
				
				if ( $post_at_pages === '' && $old_ut_post_enableurltrigger_pages !== '' ) {
					
					update_post_meta( $post_id, 'do_at_pages', $old_ut_post_enableurltrigger_pages );
				}
				
				if ( $post_at_pages_selected === '' && $old_ut_post_dolistpages !== '' ) {
					
					update_post_meta( $post_id, 'do_at_pages_selected', $old_ut_post_dolistpages );
				}
			}
		}



		function OnceMigrateSingleAnimationToEntranceExitAnimation() {
			
			// Add or update the wp_option
			if ( get_option( 'OnceMigrateSAValues', '0' ) === '1' ) {
				return;
			}
			
			/* Search Divi Overlays with Custom Close Buttons */
			$args = array(
				'meta_key'   => '_et_pb_overlay_effect',
				'meta_value' => '',
				'meta_compare' => '!=',
				'post_type' => DOV_CUSTOM_POST_TYPE,
				'cache_results'  => false,
				'posts_per_page' => -1
			);
			$query = new WP_Query( $args );
			
			$posts = $query->get_posts();
			
			if ( isset( $posts[0] ) ) {
				
				migrateSAValues( $posts );
			}
			
			// Add or update the wp_option
			update_option( 'OnceMigrateSAValues', '1' );
		}
		add_action( 'init', 'OnceMigrateSingleAnimationToEntranceExitAnimation' );

		function migrateSAValues( $posts = null ){
			
			if ( is_array( $posts ) ) {
				
				foreach( $posts as $dv_post ) {
					
					$post_id = $dv_post->ID;
					
					updateSAValues( $post_id );
				}
			}
		}

		function updateSAValues( $post_id = null ) {
			
			if ( $post_id !== '' ) {
			
				$old_overlay_effect = get_post_meta( $post_id, '_et_pb_overlay_effect', true );
				
				$et_pb_divioverlay_effect_entrance = get_post_meta( $post_id, 'et_pb_divioverlay_effect_entrance', true );
				$et_pb_divioverlay_effect_exit = get_post_meta( $post_id, 'et_pb_divioverlay_effect_exit', true );
				
				$default_effect_in = 'fadeIn';
				$default_effect_out = 'fadeOut';
				
				$effect_in = '';
				$effect_out = '';
				
				$old_effects = array(
					'overlay-hugeinc'   => array( 'fadeInDown', 'fadeOutUp' ),
					'overlay-corner'    => array( 'fadeInBottomRight', 'fadeOutBottomRight' ),
					'overlay-slidedown' => array( 'slideInDown', 'slideOutUp' ),
					'overlay-scale' => array( 'zoomIn', 'zoomOut' ),
					'overlay-door' => array( 'doorOpen', 'doorClose' ),
					'overlay-contentpush' => array( 'fadeIn', 'fadeOut' ),
					'overlay-contentscale' => array( 'vanishIn', 'vanishOut' ),
					'overlay-cornershape' => array( 'fadeInDown', 'fadeOutUp' ),
					'overlay-boxes' => array( 'foolishIn', 'foolishOut' ),
					'overlay-simplegenie' => array( 'zoomInUp', 'zoomOutDown' ),
					'overlay-genie' => array( 'fadeIn', 'fadeOut' )
				);
				
				if ( isset( $old_effects[ $old_overlay_effect ] ) ) {
					
					$effect_in = $old_effects[ $old_overlay_effect ][0];
					$effect_out = $old_effects[ $old_overlay_effect ][1];
				}
				else {
					
					$effect_in = $default_effect_in;
					$effect_out = $default_effect_out;
				}
				
				if ( $et_pb_divioverlay_effect_entrance === '' ) {
					
					update_post_meta( $post_id, 'et_pb_divioverlay_effect_entrance', $effect_in );
				}
				
				if ( $et_pb_divioverlay_effect_exit === '' ) {
					
					update_post_meta( $post_id, 'et_pb_divioverlay_effect_exit', $effect_out );
				}
			}
		}
		
		
		function callsOnPluginActivation() {
			
			// Add or update callsOnPluginActivation
			if ( get_option( 'callsOnPluginActivation', '0' ) === '1' ) {
				
				return;
			}
			
			/* Search all Divi Overlays */
			$args = array(
				'post_type' => DOV_CUSTOM_POST_TYPE,
				'cache_results'  => false,
				'posts_per_page' => -1
			);
			$query = new WP_Query( $args );
			
			$posts = $query->get_posts();
			
			if ( isset( $posts[0] ) ) {
				
				migrateOldDisplayLocationsValues( $posts );
				
				setDefaultSizingData( $posts );
			}
			
			// update the callsOnPluginActivation
			update_option( 'callsOnPluginActivation', '1' );
		}
		add_action( 'admin_init', 'callsOnPluginActivation' );
	
		function migrateOldDisplayLocationsValues( $posts = null ){
			
			if ( is_array( $posts ) ) {
				
				foreach( $posts as $dv_post ) {
					
					$post_id = $dv_post->ID;
					
					$old_displaylocations = checkOldDisplayLocationsData( $post_id );
					
					if ( $old_displaylocations !== false ) {
						
						update_post_meta( $post_id, 'displaylocations_useon', $old_displaylocations['useon'] );
						update_post_meta( $post_id, 'displaylocations_excludefrom', $old_displaylocations['excludefrom'] );
					}
					else if ( $old_displaylocations === false ) {
						
						$useon = [];
						$useon[0] = 'singular:post_type:page:all';
						$useon[1] = 'singular:post_type:post:all';
						
						update_post_meta( $post_id, 'displaylocations_useon', $useon );
					}
				}
			}
		}

		function checkOldDisplayLocationsData( $postid ) {
			
			if ( isset( $postid ) ) {
				
				$display_locations = [];
			
				$display_locations[ 'at_pages' ] = get_post_meta( $postid, 'do_at_pages', true );
				$display_locations[ 'selectedpages' ] = get_post_meta( $postid, 'do_at_pages_selected' );
				$display_locations[ 'selectedexceptpages' ] = get_post_meta( $postid, 'do_at_pagesexception_selected' );
				$display_locations[ 'at_categories' ] = get_post_meta( $postid, 'category_at_categories', true );
				$display_locations[ 'selectedcategories' ] = get_post_meta( $postid, 'category_at_categories_selected' );
				$display_locations[ 'selectedexceptcategories' ] = get_post_meta( $postid, 'category_at_exceptioncategories_selected' );
				$display_locations[ 'at_tags' ] = get_post_meta( $postid, 'tag_at_tags', true );
				$display_locations[ 'selectedtags' ] = get_post_meta( $postid, 'tag_at_tags_selected' );
				$display_locations[ 'selectedexcepttags' ] = get_post_meta( $postid, 'tag_at_exceptiontags_selected' );
				$display_locations[ 'do_displaylocations_archive' ] = get_post_meta( $postid, 'do_displaylocations_archive', true );
				$display_locations[ 'do_displaylocations_author' ] = get_post_meta( $postid, 'do_displaylocations_author', true );
				
				if ( $display_locations[ 'at_pages' ] != ''
					|| ( isset( $display_locations[ 'selectedpages' ][0] ) && isset( $display_locations[ 'selectedpages' ][0] ) && $display_locations[ 'selectedpages' ][0][0] !== '' )
					|| ( isset( $display_locations[ 'selectedexceptpages' ][0] ) && isset( $display_locations[ 'selectedexceptpages' ][0] ) && $display_locations[ 'selectedexceptpages' ][0][0] != '' )
					|| $display_locations[ 'at_categories' ] != ''
					|| ( isset( $display_locations[ 'selectedcategories' ][0] ) && isset( $display_locations[ 'selectedcategories' ][0] ) && $display_locations[ 'selectedcategories' ][0][0] != '' )
					|| ( isset( $display_locations[ 'selectedexceptcategories' ][0] ) && isset( $display_locations[ 'selectedexceptcategories' ][0] ) && $display_locations[ 'selectedexceptcategories' ][0][0] != '' )
					|| $display_locations[ 'at_tags' ] != ''
					|| ( isset( $display_locations[ 'selectedtags' ][0] ) && isset( $display_locations[ 'selectedtags' ][0] ) && $display_locations[ 'selectedtags' ][0][0] != '' )
					|| ( isset( $display_locations[ 'selectedexcepttags' ][0] ) && isset( $display_locations[ 'selectedexcepttags' ][0] ) && $display_locations[ 'selectedexcepttags' ][0][0] != '' )
					|| ( isset( $display_locations[ 'do_displaylocations_archive' ][0] ) && $display_locations[ 'do_displaylocations_archive' ] == '1' )
					|| ( isset( $display_locations[ 'do_displaylocations_author' ][0] ) && $display_locations[ 'do_displaylocations_author' ] == '1' ) ) {
					
					return migrateDisplayLocationsData( $postid, $display_locations );
				}
				
				return false;
			}
		}

		function migrateDisplayLocationsData( $postid, $old_display_locations ) {
			
			$useon = [];
			$excludefrom = [];
			
			foreach ( $old_display_locations as $type => $value ) {
				
				if ( $type === 'at_pages' && $value == 'all' ) {
					
					$useon[] = 'singular:post_type:page:all';
					$useon[] = 'singular:post_type:post:all';
				}
				
				if ( $type === 'selectedpages' && isset( $value[0][0] ) ) {
					
					foreach( $value[0] as $pageid ) {
						
						$post_type = get_post_type( $pageid );
						
						$useon[] = 'singular:post_type:' . $post_type . ':id:' . $pageid;
					}
				}
				
				if ( $type === 'selectedexceptpages' && isset( $value[0][0] ) ) {
					
					foreach( $value[0] as $pageid ) {
						
						$post_type = get_post_type( $pageid );
						
						$excludefrom[] = 'singular:post_type:' . $post_type . ':id:' . $pageid;
					}
				}
				
				
				if ( $type === 'at_categories' && $value == 'all' ) {
					
					$useon[] = 'archive:taxonomy:category:all';
				}
				
				if ( $type === 'selectedcategories' && isset( $value[0][0] ) ) {
					
					foreach( $value[0] as $catid ) {
						
						$useon[] = 'archive:taxonomy:category:term:id:' . $catid;
					}
				}
				
				if ( $type === 'selectedexceptcategories' && isset( $value[0][0] ) ) {
					
					foreach( $value[0] as $catid ) {
						
						$excludefrom[] = 'archive:taxonomy:category:term:id:' . $catid;
					}
				}
				
				
				if ( $type === 'at_tags' && $value == 'all' ) {
					
					$useon[] = 'archive:taxonomy:post_tag:all';
				}
				
				if ( $type === 'selectedtags' && isset( $value[0][0] ) ) {
					
					foreach( $value[0] as $tagid ) {
						
						$useon[] = 'archive:taxonomy:post_tag:term:id:' . $tagid;
					}
				}
				
				if ( $type === 'selectedexcepttags' && isset( $value[0][0] ) ) {
					
					foreach( $value[0] as $tagid ) {
						
						$excludefrom[] = 'archive:taxonomy:post_tag:term:id:' . $tagid;
					}
				}
				
				
				if ( $type === 'do_displaylocations_archive' && $value == '1' ) {
					
					$useon[] = 'archive:all';
				}
				
				if ( $type === 'do_displaylocations_author' && $value == '1' ) {
					
					$useon[] = 'archive:user:all';
				}
			}
			
			return [ 'useon' => $useon, 'excludefrom' => $excludefrom ];
		}
		
		
		function setDefaultSizingData( $posts = null ){
			
			if ( is_array( $posts ) ) {
				
				foreach( $posts as $dv_post ) {
					
					$post_id = $dv_post->ID;
					
					$check_sizing_minwidth = get_post_meta( $post_id, 'dov_minwidth', true );
					$check_sizing_width = get_post_meta( $post_id, 'dov_width', true );
					
					if ( $check_sizing_minwidth === '' ) {
						
						$sizing_minwidth = '95%';
						$sizing_minwidth_tablet_phone = '100%';
						$sizing_width = '95%';
						$sizing_width_tablet_phone = '100%';
						$sizing_maxwidth = 'none';
						$sizing_minheight = 'auto';
						$sizing_height = 'auto';
						$sizing_maxheight = 'none';
						
						update_post_meta( $post_id, 'dov_minwidth', $sizing_minwidth );
						update_post_meta( $post_id, 'dov_minwidth_tablet', $sizing_minwidth_tablet_phone );
						update_post_meta( $post_id, 'dov_minwidth_phone', $sizing_minwidth_tablet_phone );
						update_post_meta( $post_id, 'dov_width', $sizing_width );
						update_post_meta( $post_id, 'dov_width_tablet', $sizing_width_tablet_phone );
						update_post_meta( $post_id, 'dov_width_phone', $sizing_width_tablet_phone );
						update_post_meta( $post_id, 'dov_maxwidth', $sizing_maxwidth );
						update_post_meta( $post_id, 'dov_maxwidth_tablet', $sizing_maxwidth );
						update_post_meta( $post_id, 'dov_maxwidth_phone', $sizing_maxwidth );
						update_post_meta( $post_id, 'dov_minheight', $sizing_minheight );
						update_post_meta( $post_id, 'dov_minheight_tablet', $sizing_minheight );
						update_post_meta( $post_id, 'dov_minheight_phone', $sizing_minheight );
						update_post_meta( $post_id, 'dov_height', $sizing_height );
						update_post_meta( $post_id, 'dov_height_tablet', $sizing_height );
						update_post_meta( $post_id, 'dov_height_phone', $sizing_height );
						update_post_meta( $post_id, 'dov_maxheight', $sizing_maxheight );
						update_post_meta( $post_id, 'dov_maxheight_tablet', $sizing_maxheight );
						update_post_meta( $post_id, 'dov_maxheight_phone', $sizing_maxheight );
					}
				}
			}
		}
	}
	
	