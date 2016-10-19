<?php

/**
 * Class Give_Aweber
 *
 * Give Aweber class, extension of the base newsletter classs
 *
 * @since       1.0
 */
class Give_Aweber {

	/**
	 * The ID for this newsletter Add-on, such as 'aweber'
	 */
	public $id;

	/**
	 * The label for the Add-on, probably just shown as the title of the metabox
	 */
	public $label;

	/**
	 * Newsletter lists retrieved from the API
	 */
	public $lists;

	/**
	 * Checkbox label
	 */
	public $checkbox_label;

	/**
	 * Give Options
	 */
	public $give_options;

	/**
	 * Give_Aweber constructor.
	 */
	public function __construct() {

		$this->id           = 'aweber';
		$this->label        = 'Aweber';
		$this->give_options = give_get_settings();

		add_action( 'init', array( $this, 'textdomain' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'save_post', array( $this, 'save_metabox' ) );

		add_filter( 'give_settings_addons', array( $this, 'settings' ) );
		add_action( 'give_purchase_form_before_submit', array( $this, 'form_fields' ), 100, 1 );
		add_action( 'give_insert_payment', array( $this, 'completed_donation_signup' ), 10, 2 );

		//Donation metabox.
		add_filter( 'give_view_order_details_totals_after', array( $this, 'donation_metabox_notification' ), 10, 1 );

		//Get it started.
		add_action( 'init', array( $this, 'init' ) );


	}

	/**
	 * Retrieve groups for a list
	 *
	 * @param  string $list_id List id for which groupings should be returned
	 *
	 * @return array  $groups_data Data about the groups
	 */
	public function get_groupings( $list_id = '' ) {
		return array();
	}


	/**
	 * Load the plugin's textdomain
	 */
	public function textdomain() {

		// Set filter for language directory.
		$lang_dir = GIVE_AWEBER_DIR . '/languages/';
		$lang_dir = apply_filters( 'give_aweber_languages_directory', $lang_dir );

		// Traditional WordPress plugin locale filter.
		$locale = apply_filters( 'plugin_locale', get_locale(), 'give-aweber' );
		$mofile = sprintf( '%1$s-%2$s.mo', 'give-aweber', $locale );

		// Setup paths to current locale file.
		$mofile_local  = $lang_dir . $mofile;
		$mofile_global = WP_LANG_DIR . '/give-aweber/' . $mofile;

		if ( file_exists( $mofile_global ) ) {
			// Look in global /wp-content/languages/give-aweber/ folder.
			load_textdomain( 'give-aweber', $mofile_global );
		} elseif ( file_exists( $mofile_local ) ) {
			// Look in local /wp-content/plugins/give-aweber/languages/ folder.
			load_textdomain( 'give-aweber', $mofile_local );
		} else {
			// Load the default language files.
			load_plugin_textdomain( 'give-aweber', false, $lang_dir );
		}

	}

	/**
	 * Output the signup checkbox, if enabled.
	 *
	 * @param int $form_id
	 */
	public function form_fields( $form_id ) {

		$enable_on_form  = get_post_meta( $form_id, '_give_' . $this->id . '_enable', true );
		$disable_on_form = get_post_meta( $form_id, '_give_' . $this->id . '_disable', true );

		//Check disable vars to see if this form should have the MC Opt-in field
		if ( ! $this->show_subscribe_checkbox() && $enable_on_form !== 'true' || $disable_on_form === 'true' ) {
			return;
		}

		$this->give_options    = give_get_settings();
		$custom_checkbox_label = get_post_meta( $form_id, '_give_' . $this->id . '_custom_label', true );

		//What's the label gonna be?
		if ( ! empty( $custom_checkbox_label ) ) {
			$this->checkbox_label = trim( $custom_checkbox_label );
		} elseif ( ! empty( $this->give_options[ 'give_' . $this->id . '_label' ] ) ) {
			$this->checkbox_label = trim( $this->give_options[ 'give_' . $this->id . '_label' ] );
		} else {
			$this->checkbox_label = __( 'Subscribe to our newsletter', 'give-aweber' );
		}

		//Should the opt-on be checked or unchecked by default?
		$form_option    = get_post_meta( $form_id, '_give_' . $this->id . '_checked_default', true );
		$checked_option = 'on';

		if ( ! empty( $form_option ) ) {
			//Nothing to do here, option already set above.
			$checked_option = $form_option;
		} elseif ( ! empty( $this->give_options['give_' . $this->id . '_label'] ) ) {
			$checked_option = $this->give_options['give_' . $this->id . '_checked_default'];
		}

		ob_start(); ?>
		<fieldset id="give_<?php echo $this->id; ?>" class="give-<?php echo $this->id; ?>-fieldset">
			<p>
				<input name="give_<?php echo $this->id; ?>_signup" id="give_<?php echo $this->id; ?>_signup"
				       type="checkbox" <?php echo( $checked_option !== 'no' ? 'checked="checked"' : '' ); ?>/>
				<label for="give_<?php echo $this->id; ?>_signup"><?php echo $this->checkbox_label; ?></label>
			</p>
		</fieldset>
		<?php
		echo ob_get_clean();
	}

	/**
	 * Checkout Signup
	 *
	 * Check if a donor needs to be subscribed upon donating.
	 *
	 * @param $posted
	 * @param $user_info
	 * @param $valid_data
	 */
	public function checkout_signup( $posted, $user_info, $valid_data ) {

		// Check for global newsletter
		if ( isset( $posted[ 'give_' . $this->id . '_signup' ] ) ) {

			$this->subscribe_email( $user_info );

		}

	}

	/**
	 * Complete Donation Sign up.
	 *
	 * Check if a donor needs to be subscribed upon completing donation on a specific donation form.
	 *
	 * @param $payment_id
	 * @param $payment_data array
	 */
	public function completed_donation_signup( $payment_id, $payment_data ) {

		//Check to see if the user has elected to subscribe.
		if ( ! isset( $_POST[ 'give_' . $this->id . '_signup' ] ) || $_POST[ 'give_' . $this->id . '_signup' ] !== 'on' ) {
			return;
		}

		$form_lists = get_post_meta( $payment_data['give_form_id'], '_give_' . $this->id, true );

		//Check if $form_lists is set.
		if ( empty( $form_lists ) ) {
			//Not set so use global list.
			$form_lists = array( 0 => give_get_option( 'give_' . $this->id . '_list' ) );
		}

		//Add meta to the donation post that this donation opted-in to.
		add_post_meta( $payment_id, '_give_' . $this->id . '_donation_optin_status', $form_lists );

		//Subscribe if array.
		if ( is_array( $form_lists ) ) {
			$lists = array_unique( $form_lists );
			foreach ( $lists as $list ) {
				//Subscribe the donor to the email lists.
				$this->subscribe_email( $payment_data['user_info'], $list );
			}
		} else {
			//Subscribe to single.
			$this->subscribe_email( $payment_data['user_info'], $form_lists );
		}

	}

	/**
	 * Show Line item on donation details screen if the donor opted-in to the newsletter.
	 *
	 * @param $payment_id
	 */
	function donation_metabox_notification( $payment_id ) {

		$opt_in_meta = get_post_meta( $payment_id, '_give_' . $this->id . '_donation_optin_status', true );

		if ( $opt_in_meta ) { ?>
			<div class="give-admin-box-inside">
				<p>
					<span class="label"><?php echo $this->label; ?>:</span>&nbsp;
					<span><?php _e( 'Opted-in', 'give-aweber' ); ?></span>
				</p>
			</div>
		<?php }

	}

	/**
	 * Register the metabox on the 'give_forms' post type.
	 */
	public function add_metabox() {

		if ( current_user_can( 'edit_give_forms', get_the_ID() ) ) {
			add_meta_box( 'give_' . $this->id, $this->label, array( $this, 'render_metabox' ), 'give_forms', 'side' );
		}

	}

	/**
	 * Display the metabox, which is a list of newsletter lists.
	 */
	public function render_metabox() {

		global $post;

		// Add an nonce field so we can check for it later.
		wp_nonce_field( 'give_' . $this->id . '_meta_box', 'give_' . $this->id . '_meta_box_nonce' );

		//Using a custom label?
		$custom_label = get_post_meta( $post->ID, '_give_' . $this->id . '_custom_label', true );

		//Global label
		$global_label = isset( $this->give_options[ 'give_' . $this->id . '_label' ] ) ? $this->give_options[ 'give_' . $this->id . '_label' ] : __( 'Signup for the newsletter', 'give-aweber' );;

		//Globally enabled option.
		$globally_enabled = give_get_option( 'give_' . $this->id . '_show_subscribe_checkbox' );
		$enable_option    = get_post_meta( $post->ID, '_give_' . $this->id . '_enable', true );
		$checked_option   = get_post_meta( $post->ID, '_give_' . $this->id . '_checked_default', true );
		$disable_option   = get_post_meta( $post->ID, '_give_' . $this->id . '_disable', true );

		//Start the buffer.
		ob_start();

		//Output option for this form
		if ( $globally_enabled == 'on' ) { ?>
			<p style="margin: 1em 0 0;">
				<label>
					<input type="checkbox" name="_give_<?php echo $this->id; ?>_disable"
					       class="give-<?php echo $this->id; ?>-disable"
					       value="true" <?php echo checked( 'true', $disable_option, false ); ?>>&nbsp;<?php echo sprintf( __( 'Disable %1$s Opt-in', 'give-aweber' ), $this->label ); ?>
				</label>
			</p>

			<?php
		} else {
			//Output option to ENABLE MC for this form
			?>
			<p style="margin: 1em 0 0;">
				<label>
					<input type="checkbox" name="_give_<?php echo $this->id; ?>_enable"
					       class="give-<?php echo $this->id; ?>-enable"
					       value="true" <?php echo checked( 'true', $enable_option, false ) ?>>&nbsp;<?php echo sprintf( __( 'Enable %1$s Opt-in', 'give-aweber' ), $this->label ); ?>
				</label>
			</p>
		<?php } // Display the form, using the current value. ?>
		<div
			class="give-<?php echo $this->id; ?>-field-wrap" <?php echo( $globally_enabled == false && empty( $enable_option ) ? "style='display:none;'" : '' ) ?>>
			<p>
				<label for="_give_<?php echo $this->id; ?>_custom_label"
				       style="font-weight:bold;"><?php _e( 'Custom Label', 'give-aweber' ); ?></label>
				<span class="cmb2-metabox-description"
				      style="margin: 0 0 10px;"><?php echo sprintf( __( 'Customize the label for the %1$s opt-in checkbox', 'give-aweber' ), $this->label ); ?></span>
				<input type="text" id="_give_<?php echo $this->id; ?>_custom_label"
				       name="_give_<?php echo $this->id; ?>_custom_label"
				       value="<?php echo esc_attr( $custom_label ); ?>"
				       placeholder="<?php echo esc_attr( $global_label ); ?>" size="35"/>
			</p>

			<?php //Field: Default checked or unchecked option. ?>
			<div>

				<label for="_give_<?php echo $this->id; ?>_checked_default"
				       style="font-weight:bold;"><?php _e( 'Opt-in Default', 'give-aweber' ); ?></label>
				<span class="cmb2-metabox-description"
				      style="margin: 0 0 10px;"><?php _e( 'Customize the newsletter opt-in option for this form.', 'give-aweber' ); ?></span>

				<ul class="cmb2-radio-list cmb2-list">

					<li>
						<input type="radio" class="cmb2-option" name="_give_<?php echo $this->id; ?>_checked_default"
						       id="give_<?php echo $this->id; ?>_checked_default1"
						       value="" <?php echo checked( '', $checked_option, false ); ?>>
						<label
							for="give_<?php echo $this->id; ?>_checked_default1"><?php _e( 'Global Option', 'give-aweber' ); ?></label>
					</li>

					<li>
						<input type="radio" class="cmb2-option" name="_give_<?php echo $this->id; ?>_checked_default"
						       id="give_<?php echo $this->id; ?>_checked_default2"
						       value="yes" <?php echo checked( 'yes', $checked_option, false ); ?>>
						<label
							for="give_<?php echo $this->id; ?>_checked_default2"><?php _e( 'Checked', 'give-aweber' ); ?></label>
					</li>
					<li>
						<input type="radio" class="cmb2-option" name="_give_<?php echo $this->id; ?>_checked_default"
						       id="give_<?php echo $this->id; ?>_checked_default3"
						       value="no" <?php echo checked( 'no', $checked_option, false ); ?>>
						<label
							for="give_<?php echo $this->id; ?>_checked_default3"><?php _e( 'Unchecked', 'give-aweber' ); ?></label>
					</li>
				</ul>

			</div>

			<?php //Field: lists and groups. ?>
			<div class="give-<?php echo $this->id; ?>-list-container">
				<label for="give_<?php echo $this->id; ?>_lists"
				       style="font-weight:bold;"><?php _e( 'Aweber Opt-in', 'give-aweber' ); ?></label>

				<span class="cmb2-metabox-description give-description"
				      style="margin: 0 0 10px;"><?php _e( 'Customize the lists and/or groups you wish donors to subscribe to.', 'give-aweber' ); ?></span>

				<?php $checked = (array) get_post_meta( $post->ID, '_give_' . esc_attr( $this->id ), true );
				?>

				<div class="give-<?php echo $this->id; ?>-list-wrap">

					<?php foreach ( $this->get_lists() as $list_id => $list_name ) { ?>
						<label class="list">
							<input type="checkbox" name="_give_<?php echo esc_attr( $this->id ); ?>[]"
							       value="<?php echo esc_attr( $list_id ); ?>" <?php echo checked( true, in_array( $list_id, $checked ), false ); ?>>
							<span><?php echo $list_name; ?></span>
						</label>

						<?php $groupings = $this->get_groupings( $list_id );
						if ( ! empty( $groupings ) ) {
							foreach ( $groupings as $group_id => $group_name ) { ?>
								<label class="group">
									<input type="checkbox" name="_give_<?php echo esc_attr( $this->id ); ?>[]"
									       value="<?php echo esc_attr( $group_id ); ?>" <?php echo checked( true, in_array( $group_id, $checked ), false ); ?>>
									<span><?php echo $group_name; ?></span>
								</label>
							<?php }
						}
					} ?>

				</div><!-- give-aweber-list-wrap -->
			</div> <!-- give-aweber-list-container -->
		</div>
		<?php

		//Return the metabox.
		echo ob_get_clean();

	}

	/**
	 * Save the metabox data.
	 *
	 * @param int $post_id The ID of the post being saved.
	 *
	 * @return void|string
	 */
	public function save_metabox( $post_id ) {

		$this->give_options = give_get_settings();

		/*
		 * We need to verify this came from our screen and with proper authorization,
		 * because the save_post action can be triggered at other times.
		 */
		// Check if our nonce is set.
		if ( ! isset( $_POST[ 'give_' . $this->id . '_meta_box_nonce' ] ) ) {
			return false;
		}

		// Verify that the nonce is valid.
		if ( ! wp_verify_nonce( $_POST['give_' . $this->id . '_meta_box_nonce'], 'give_' . $this->id . '_meta_box' ) ) {
			return false;
		}

		// If this is an autosave, our form has not been submitted, so we don't want to do anything.
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		// Check the user's permissions.
		if ( $_POST['post_type'] == 'give_forms' ) {

			if ( ! current_user_can( 'edit_give_forms', $post_id ) ) {
				return $post_id;
			}

		} else {

			if ( ! current_user_can( 'edit_give_forms', $post_id ) ) {
				return $post_id;
			}

		}

		// OK, its safe for us to save the data now.

		// Sanitize the user input.
		$give_custom_label = isset( $_POST['_give_' . $this->id . '_custom_label'] ) ? sanitize_text_field( $_POST['_give_' . $this->id . '_custom_label'] ) : '';
		$give_custom_lists = isset( $_POST['_give_' . $this->id . ''] ) ? $_POST['_give_' . $this->id . ''] : $this->give_options['give_' . $this->id . '_list'];
		$give_subscribe_enable       = isset( $_POST['_give_' . $this->id . '_enable'] ) ? esc_html( $_POST['_give_' . $this->id . '_enable'] ) : '';
		$give_subscribe_disable      = isset( $_POST['_give_' . $this->id . '_disable'] ) ? esc_html( $_POST['_give_' . $this->id . '_disable'] ) : '';
		$give_subscribe_checked      = isset( $_POST['_give_' . $this->id . '_checked_default'] ) ? esc_html( $_POST['_give_' . $this->id . '_checked_default'] ) : '';


		// Update the meta field.
		update_post_meta( $post_id, '_give_' . $this->id . '_custom_label', $give_custom_label );
		update_post_meta( $post_id, '_give_' . $this->id . '', $give_custom_lists );
		update_post_meta( $post_id, '_give_' . $this->id . '_enable', $give_subscribe_enable );
		update_post_meta( $post_id, '_give_' . $this->id . '_disable', $give_subscribe_disable );
		update_post_meta( $post_id, '_give_' . $this->id . '_checked_default', $give_subscribe_checked );

		return true;

	}


	/**
	 * Sets up the checkout label
	 */
	public function init() {
		$give_options = give_get_settings();
		if ( ! empty( $give_options['give_aweber_label'] ) ) {
			$this->checkbox_label = trim( $give_options['give_aweber_label'] );
		} else {
			$this->checkbox_label = 'Signup for the newsletter';
		}

	}

	/**
	 * Retrieves the lists from Aweber
	 */
	public function get_lists() {

		$give_options = give_get_settings();

		$lists_data = get_transient( 'give_aweber_lists' );

		if ( false === $lists_data ) {

			try {

				$aweber = $this->get_authenticated_instance();

				if ( ! is_object( $aweber ) || false === ( $secrets = get_option( 'aweber_secrets' ) ) ) {
					return array();
				}

				$account = $aweber->getAccount( $secrets['access_key'], $secrets['access_secret'] );

				if ( $account ) {
					foreach ( $account->lists as $list ) {
						$this->lists[ $list->id ] = $list->name;
					}
				}

				set_transient( 'give_aweber_lists', $this->lists, 24 * 24 * 24 );

			} catch ( Exception $e ) {

				$this->lists = array();

			}

		} else {
			$this->lists = $lists_data;
		}

		return (array) $this->lists;
	}

	/**
	 * Registers the plugin's settings.
	 *
	 * @param $settings
	 *
	 * @return array
	 */
	public function settings( $settings ) {

		$give_aweber_settings = array(
			array(
				'name' => __( 'Aweber Settings', 'give-aweber' ),
				'desc' => '<hr>',
				'id'   => 'give_title_' . $this->id,
				'type' => 'give_title'
			),
			array(
				'id'   => 'give_aweber_api',
				'name' => __( 'AWeber Authorization Code', 'give-aweber' ),
				'desc' => sprintf( __( 'Enter your <a target="_new" title="Will open new window" href="%s">AWeber Authorization Code</a>', 'give-aweber' ), 'https://auth.aweber.com/1.0/oauth/authorize_app/12d8f5e5' ),
				'type' => 'text',
				'size' => 'regular'
			),
			array(
				'id'      => 'give_aweber_checkout_signup',
				'name'    => __( 'Enable Globally', 'give-aweber' ),
				'desc'    => __( 'Allow donors to sign up for the list selected below on all donation forms? Note: the list(s) can be customized per form.', 'give-aweber' ),
				'type'    => 'radio_inline',
				'default' => 'enabled',
				'options' => array(
					'enabled'  => __( 'Enabled', 'give-aweber' ),
					'disabled' => __( 'Disabled', 'give-aweber' )
				)
			),
			array(
				'id'      => 'give_aweber_list',
				'name'    => __( 'Choose a list', 'give-aweber' ),
				'desc'    => __( 'Select the list you wish to subscribe donors. If you don\'t see your lists available you may need to refresh the page.', 'give-aweber' ),
				'type'    => 'select',
				'options' => $this->get_lists()
			),

			array(
				'id'      => 'give_aweber_checked_default',
				'name'    => __( 'Opt-in Default', 'give-aweber' ),
				'desc'    => __( 'Would you like the newsletter opt-in checkbox checked by default? This option can be customized per form.', 'give-aweber' ),
				'options' => array(
					'enabled'  => __( 'Checked', 'give-aweber' ),
					'disabled' => __( 'Unchecked', 'give-aweber' ),
				),
				'default' => 'enabled',
				'type'    => 'radio_inline'
			),
			array(
				'id'         => 'give_awebere_label',
				'name'       => __( 'Default Label', 'give-aweber' ),
				'desc'       => __( 'This is the text shown next to the signup option. This can also be customized per form.', 'give-aweber' ),
				'type'       => 'text',
				'size'       => 'regular',
				'attributes' => array(
					'placeholder' => __( 'Subscribe to our newsletter', 'give-aweber' ),
				),
			)
		);

		return array_merge( $settings, $give_aweber_settings );
	}

	/**
	 * Determines if the checkout signup option should be displayed.
	 *
	 * @return bool
	 */
	public function show_subscribe_checkbox() {

		return ! empty( $this->give_options['give_' . $this->id . '_checkout_signup'] );

	}

	/**
	 * Subscribe an email to a list.
	 *
	 * @param array $user_info
	 * @param bool $list_id
	 *
	 * @return bool
	 */
	public function subscribe_email( $user_info = array(), $list_id = false ) {

		// Retrieve the global list ID if none is provided.
		if ( ! $list_id ) {
			$list_id = ! empty( $this->give_options['give_aweber_list'] ) ? $this->give_options['give_aweber_list'] : false;

			if ( ! $list_id ) {
				return false;
			}
		}

		$authorization_code = isset( $this->give_options['give_aweber_api'] ) ? trim( $this->give_options['give_aweber_api'] ) : '';

		if ( strlen( $authorization_code ) > 0 ) {

			//Get API.
			if ( ! class_exists( 'AWeberAPI' ) ) {
				require_once( GIVE_AWEBER_PATH . '/aweber/aweber_api.php' );
			}

			$aweber = $this->get_authenticated_instance();

			if ( ! is_object( $aweber ) || false === ( $secrets = get_option( 'aweber_secrets' ) ) ) {
				return false;
			}

			try {

				$account = $aweber->getAccount( $secrets['access_key'], $secrets['access_secret'] );
				$listURL = "/accounts/{$account->id}/lists/{$list_id}";
				$list    = $account->loadFromUrl( $listURL );

				//create a subscriber.
				$params         = array(
					'email' => $user_info['email'],
					'name'  => $user_info['first_name'] . ' ' . $user_info['last_name']
				);
				$subscribers    = $list->subscribers;
				$new_subscriber = $subscribers->create( $params );

				//success!
				return true;

			} catch ( AWeberAPIException $exc ) {
				return false;
			}

		}


		return false;

	}

	/**
	 * Get authenticated instance.
	 *
	 * @return AWeberAPI|bool
	 */
	public function get_authenticated_instance() {

		$authorization_code = isset( $this->give_options['give_aweber_api'] ) ? trim( $this->give_options['give_aweber_api'] ) : '';

		$msg = '';
		if ( ! empty( $authorization_code ) ) {

			if ( ! class_exists( 'AWeberAPI' ) ) {
				require_once( GIVE_AWEBER_PATH . '/aweber/aweber_api.php' );
			}

			$error_code = "";

			if ( false !== get_option( 'aweber_secrets' ) ) {

				$options = get_option( 'aweber_secrets' );
				$msg     = $options;

				try {

					$api = new AWeberAPI( $options['consumer_key'], $options['consumer_secret'] );

				} catch ( AWeberAPIException $exc ) {

					$api = false;

				}

				return $api;

			} else {

				try {

					list( $consumer_key, $consumer_secret, $access_key, $access_secret ) = AWeberAPI::getDataFromAweberID( $authorization_code );

				} catch ( AWeberAPIException $exc ) {

					list( $consumer_key, $consumer_secret, $access_key, $access_secret ) = null;

					# make error messages customer friendly.
					$descr      = $exc->message;
					$descr      = preg_replace( '/http.*$/i', '', $descr );     # strip labs.aweber.com documentation url from error message
					$descr      = preg_replace( '/[\.\!:]+.*$/i', '', $descr ); # strip anything following a . : or ! character
					$error_code = " ($descr)";

				} catch ( AWeberOAuthDataMissing $exc ) {

					list( $consumer_key, $consumer_secret, $access_key, $access_secret ) = null;

				} catch ( AWeberException $exc ) {

					list( $consumer_key, $consumer_secret, $access_key, $access_secret ) = null;

				}

				//Check for secret.
				if ( ! $access_secret ) {

					//Error message.
					$msg = '<div id="aweber_access_token_failed" class="error">';

					$msg .= __( 'Unable to connect to your AWeber Account:', 'give-aweber' ) . '<br />' . $error_code;

					# show oauth_id if it failed and an api exception was not raised.
					if ( empty( $error_code ) ) {
						$msg .= __( 'Authorization code entered was:', 'give-aweber' ) . '<br />' . $authorization_code;
					}

					$msg .= __( 'Please make sure you entered the complete authorization code and try again.', 'give-aweber' );

					$msg .= '</div>';

				} else {

					$secrets = array(
						'consumer_key'    => $consumer_key,
						'consumer_secret' => $consumer_secret,
						'access_key'      => $access_key,
						'access_secret'   => $access_secret,
					);

					update_option( 'aweber_secrets', $secrets );
				}
			}
		} else {
			delete_option( 'aweber_secrets' );
		}

		$msg = isset( $msg ) ? $msg : '';

		update_option( 'aweber_response', $msg );

	}

}