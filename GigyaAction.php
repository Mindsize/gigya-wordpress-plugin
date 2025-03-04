<?php
/**
 * The main plugin class.
 */
class GigyaAction
{
	protected $login_options;
	protected $global_options;
	protected $session_options;

	/**
	 * Constructor.
	 */
	public function __construct() {
		// Gigya configuration values.
		$this->login_options = get_option( GIGYA__SETTINGS_LOGIN );
		$this->global_options = get_option( GIGYA__SETTINGS_GLOBAL );
		$this->session_options = get_option( GIGYA__SETTINGS_SESSION );

		// Gigya CMS
		if ( ! empty( $this->global_options ) )
		{
			define( 'GIGYA__API_KEY', $this->global_options['api_key'] );
			if ( isset( $this->global_options['user_key'] ) ) /* Backwards compatibility */
				define( 'GIGYA__USER_KEY', $this->global_options['user_key'] );
			define( 'GIGYA__API_SECRET', $this->global_options['api_secret'] );
			define( 'GIGYA__API_DOMAIN', $this->global_options['data_center'] );
			define( 'GIGYA__API_DEBUG', $this->global_options['debug'] );
		}
		else
		{
			define( 'GIGYA__API_KEY', '' );
			define( 'GIGYA__USER_KEY', '' );
			define( 'GIGYA__API_SECRET', '' );
			define( 'GIGYA__API_DOMAIN', '' );
			define( 'GIGYA__API_DEBUG', '' );
		}

		add_action( 'init', array( $this, 'init' ) );
		add_action( 'admin_action_update', array( $this, 'adminActionUpdate' ) );
		add_action( 'wp_ajax_gigya_login', array( $this, 'ajaxLogin' ) );
		add_action( 'wp_ajax_nopriv_gigya_login', array( $this, 'ajaxLogin' ) );
		add_action( 'wp_ajax_gigya_raas', array( $this, 'ajaxRaasLogin' ) );
		add_action( 'wp_ajax_nopriv_gigya_raas', array( $this, 'ajaxRaasLogin' ) );
		add_action( 'wp_ajax_gigya_process_field_mapping', array( $this, 'ajaxProcessFieldMapping' ) );
		add_action( 'wp_ajax_nopriv_gigya_process_field_mapping', array( $this, 'ajaxProcessFieldMapping' ) );
		add_action( 'wp_ajax_custom_login', array( $this, 'ajaxCustomLogin' ) );
		add_action( 'wp_ajax_nopriv_custom_login', array( $this, 'ajaxCustomLogin' ) );
		add_action( 'wp_ajax_fixed_session_cookie', array( $this, 'ajaxSetFixedSessionCookie' ) );
		add_action( 'wp_ajax_nopriv_fixed_session_cookie', array( $this, 'ajaxSetFixedSessionCookie' ) );
		add_action( 'wp_ajax_debug_log', array( $this, 'ajaxDebugLog' ) );
		add_action( 'wp_ajax_clean_db', array( $this, 'ajaxCleanDB' ) );
		add_action( 'wp_ajax_gigya_logout', array( $this, 'ajaxLogout' ) );
		add_action( 'wp_ajax_raas_update_profile', array( $this, 'ajaxUpdateProfile' ) );
		add_action( 'wp_login', array( $this, 'wpLogin' ), 10, 2 );
		add_action( 'user_register', array( $this, 'userRegister' ), 10, 1 );
		add_action( 'delete_user', array( $this, 'deleteUser' ) );
		add_action( 'wpmu_delete_user', array( $this, 'deleteUser' ) );
		add_action( 'widgets_init', array( $this, 'widgetsInit' ) );
		add_action( 'set_logged_in_cookie', 'updateCookie', 10, 2 );
		add_action( 'rest_api_init', array( $this, 'appendUserMetaToRestAPI' ) );
		add_action( 'gigya_offline_sync_cron', array( $this, 'executeOfflineSyncCron' ) );
		add_shortcode( 'gigya_user_info', array( $this, 'shortcodeUserInfo' ) );
		add_filter( 'the_content', array( $this, 'theContent' ) );
		add_filter( 'get_avatar', array( $this, 'getGigyaAvatar' ), 10, 5 );
		add_filter( 'login_message', array( $this, 'raas_wp_login_custom_message' ) );
		add_filter( 'cron_schedules', array( $this, 'getOfflineSyncSchedules' ) );

		$comments_on = $this->gigya_comments_on();
		if ( $comments_on ) {
			add_filter( 'comments_template', [ $this, 'commentsTemplate' ] );
		}

		/* Plugins shortcode activation switches */
		require_once GIGYA__PLUGIN_DIR . 'features/gigyaPluginsShortcodes.php';
		$shortcodes_class = new gigyaPluginsShortcodes();

		add_shortcode( 'gigya-raas-login', array( $shortcodes_class, 'gigyaRaas' ) );
		add_shortcode( 'gigya-raas-profile', array( $shortcodes_class, 'gigyaRaas' ) );
		add_shortcode( 'gigya-social-login', array( $shortcodes_class, 'gigyaSocialLoginScode' ) );

		$comments_switch = get_option( GIGYA__SETTINGS_COMMENTS );
		if ( ! empty( $comments_switch ) and ( count( $comments_switch ) > 0 ) and ( $comments_switch['on'] == true or $comments_switch['on'] == '1' ) ) {
			add_shortcode( 'gigya-comments', array( $shortcodes_class, 'gigyaCommentsScode' ) );
		}
		$reaction_switch = get_option( GIGYA__SETTINGS_REACTIONS );
		if ( ! empty( $reaction_switch ) and ( count( $reaction_switch ) > 0 ) and ( $reaction_switch['on'] == true or $reaction_switch['on'] == '1' ) ) {
			add_shortcode( 'gigya-reactions', array( $shortcodes_class, 'gigyaReactionsScode' ) );
		}
		$share_switch = get_option( GIGYA__SETTINGS_SHARE );
		if ( ! empty( $share_switch ) and ( count( $share_switch ) > 0 ) and ( $share_switch['on'] == true or $share_switch['on'] == '1' ) ) {
			add_shortcode( 'gigya-share-bar', array( $shortcodes_class, 'gigyaShareBarScode' ) );
		}
		$gm_switch = get_option( GIGYA__SETTINGS_GM );
		if ( ! empty( $gm_switch ) and ( count( $gm_switch ) > 0 ) and ( $gm_switch['on'] == true or $gm_switch['on'] == '1' ) ) {
			add_shortcode( 'gigya-gm-achievements', array( $shortcodes_class, 'gigyaGmScode' ) );
			add_shortcode( 'gigya-gm-challenge-status', array( $shortcodes_class, 'gigyaGmScode' ) );
			add_shortcode( 'gigya-gm-leaderboard', array( $shortcodes_class, 'gigyaGmScode' ) );
			add_shortcode( 'gigya-gm-user-status', array( $shortcodes_class, 'gigyaGmScode' ) );
		}
		/* End plugins shortcodes activation switches */
	}

	/**
	 * Initialize hook.
	 */
	public function init() {
		/* Require SDK libraries */
		require_once GIGYA__PLUGIN_DIR . 'sdk/GigyaJsonObject.php';
		require_once GIGYA__PLUGIN_DIR . 'sdk/GigyaUserFactory.php';
		require_once GIGYA__PLUGIN_DIR . 'sdk/GigyaProfile.php';
		require_once GIGYA__PLUGIN_DIR . 'sdk/GigyaUser.php';
		require_once GIGYA__PLUGIN_DIR . 'sdk/GSFactory.php';
		require_once GIGYA__PLUGIN_DIR . 'sdk/GSException.php';
		require_once GIGYA__PLUGIN_DIR . 'sdk/GSKeyNotFoundException.php';
		require_once GIGYA__PLUGIN_DIR . 'sdk/GSApiException.php';
		require_once GIGYA__PLUGIN_DIR . 'sdk/GSRequest.php';
		require_once GIGYA__PLUGIN_DIR . 'sdk/GSResponse.php';
		require_once GIGYA__PLUGIN_DIR . 'sdk/GSObject.php';
		require_once GIGYA__PLUGIN_DIR . 'sdk/GSArray.php';
		require_once GIGYA__PLUGIN_DIR . 'sdk/GigyaApiRequest.php';
		require_once GIGYA__PLUGIN_DIR . 'sdk/SigUtils.php';
		GSResponse::init();

		require_once GIGYA__PLUGIN_DIR . 'sdk/GigyaApiHelper.php';
		require_once GIGYA__PLUGIN_DIR . 'sdk/gigyaCMS.php';

		/* Load jQuery and jQueryUI from WP */
		wp_enqueue_script( 'jquery' );
		wp_enqueue_script( 'jquery-ui-core' );
		wp_enqueue_script( 'jquery-ui-dialog' );
		wp_enqueue_script( 'gigya_js', GIGYA__PLUGIN_URL . 'gigya.js' );
		wp_enqueue_style( 'gigya_css', GIGYA__PLUGIN_URL . 'gigya.css' );

		/* Parameters to be sent to the DOM */
		$params = array(
			'ajaxurl'                     => admin_url( 'admin-ajax.php' ),
			'logoutUrl'                   => wp_logout_url(),
			'connectWithoutLoginBehavior' => _gigParam( $this->login_options, 'connectWithoutLoginBehavior', 'loginExistingUser' ),
			'jsonExampleURL'              => GIGYA__PLUGIN_URL . 'admin/forms/json/advance_example.json',
			'enabledProviders'            => _gigParam( $this->global_options, 'enabledProviders', '*' ),
			'lang'                        => _gigParam( $this->global_options, 'lang', 'en' ),
			'sessionExpiration'           => gigyaSyncLoginSession(
				( isset( $this->login_options['mode'] ) ? $this->login_options['mode'] : '' ), $this->session_options
			),
		);

		/* Add advanced parameters if exist */
		if ( ! empty( $this->global_options['advanced'] ) )
		{
			$advanced = gigyaCMS::parseJSON( _gigParam( $this->global_options, 'advanced', '' ) );
			if ( is_array( $advanced ) )
				$params = array_merge( $params, $advanced );
		}

		/* Let others plugins to modify the global parameters */
		$params = apply_filters( 'gigya_global_params', $params );

		/* Load params to be available to client-side script */
		wp_localize_script( 'gigya_js', 'gigyaParams', $params );

		/* Checking that we have an API key and Gigya's plugin is turned on */
		$api_key = GIGYA__API_KEY;
		if ( ! empty( $api_key ) )
		{
			// Loads requirements for any Gigya's login.
			// Load Gigya's socialize.js from CDN.
			wp_enqueue_script( 'gigya_cdn', GIGYA__JS_CDN . GIGYA__API_KEY . '&lang=' . $params['lang'] );

			if ( ! empty( $this->login_options ) ) /* Empty only happens on initial plugin enable, before configuring it */
			{
				// Loads requirements for any Gigya's social login.
				if ( $this->login_options['mode'] == 'wp_sl' )
				{
					require_once GIGYA__PLUGIN_DIR . 'features/login/GigyaLoginSet.php';
					$gigyaLoginSet = new GigyaLoginSet;
					$gigyaLoginSet->init();
				}

				// Loads requirements for any Gigya's RaaS login.
				if ( $this->login_options['mode'] == 'raas' and defined( 'GIGYA__USER_KEY' ) and ( ! empty( GIGYA__USER_KEY ) ) )
				{
					require_once GIGYA__PLUGIN_DIR . 'features/raas/GigyaOfflineSync.php';

					// Loads RaaS links class.
					require_once GIGYA__PLUGIN_DIR . 'features/raas/GigyaRaasSet.php';
					$gigyaRaasSet = new GigyaRaasSet;
					$gigyaRaasSet->init();

					// Updates GltExp cookie
					require_once GIGYA__PLUGIN_DIR . 'features/raas/GigyaRaasAjax.php';
					$raasAjaxObject = new GigyaRaasAjax();
					$raasAjaxObject->updateGltExpCookie();
				}
			}

			// Loads requirements for any Gigya's Google-Analytics integration.
			if ( ! empty( $this->global_options['google_analytics'] ) )
			{
				wp_enqueue_script( 'gigya_ga', GIGYA__CDN_PROTOCOL . '.gigya.com/js/gigyaGAIntegration.js' );
			}
		}

		if ( is_admin() )
		{
			// Loads requirements for the admin settings section.
			require_once GIGYA__PLUGIN_DIR . 'admin/admin.GigyaSettings.php';
			new GigyaSettings;
		}
	}

	/**
	 * admin_action_ hook
	 * Fires when an 'action' REQUEST variable is sent.
	 *
	 * @throws Exception
	 */
	public function adminActionUpdate() {
		require_once GIGYA__PLUGIN_DIR . 'admin/admin.GigyaSettings.php';
		GigyaSettings::onSave();
	}

	/**
	 * Hook AJAX login
	 *
	 * @throws Exception
	 */
	public function ajaxLogin() {
		// Loads Gigya's social login class.
		require_once GIGYA__PLUGIN_DIR . 'features/login/GigyaLoginAjax.php';
		$gigyaLoginAjax = new GigyaLoginAjax;
		$gigyaLoginAjax->init();
	}

	/**
	 * Hook AJAX RaaS login.
	 *
	 * @throws Exception
	 */
	public function ajaxRaasLogin() {
		// Loads Gigya's RaaS class.
		require_once GIGYA__PLUGIN_DIR . 'features/raas/GigyaRaasAjax.php';
		$gigyaLoginAjax = new GigyaRaasAjax;
		$gigyaLoginAjax->init();
	}

	/**
	 * Process field mapping
	 *
	 * @throws Exception
	 */
	public function ajaxProcessFieldMapping() {
		$wp_uid      = get_current_user_id();
		$generic_msg = 'You are not logged in correctly';

		if ( ! empty( $wp_uid ) ) {
			$gigya_uid = get_user_meta( $wp_uid, 'gigya_uid', true );

			if ( ! empty( $gigya_uid ) ) {
				$gigya_cms = new GigyaCMS();

				try {
					$gigya_account = $gigya_cms->getAccount( $gigya_uid );

					_gigya_add_to_wp_user_meta( $gigya_account, $wp_uid );

					wp_send_json_success();
				} catch ( Exception $e ) {
					error_log( 'Unable to process field mapping for SAP Customer Data Cloud user ' . $gigya_uid );

					wp_send_json_error( [ 'msg' => $generic_msg ] );
				}
			} else {
				wp_send_json_error( [ 'msg' => $generic_msg ] );
			}
		} else {
			wp_send_json_error( [ 'msg' => $generic_msg ] );
		}
	}

	/**
	 * @throws GSApiException
	 * @throws GSException
	 */
	public function ajaxUpdateProfile() {
		// Loads Gigya's RaaS class.
		require_once GIGYA__PLUGIN_DIR . 'features/raas/GigyaRaasAjax.php';
		$gigyaAjax = new GigyaRaasAjax;
		$data = $_POST['data'];
		$gigyaAjax->updateProfile( $data );
	}

	/**
	 * Hook AJAX Custom forms login.
	 */
	public function ajaxCustomLogin() {
		// Loads Gigya's social login class.
		require_once GIGYA__PLUGIN_DIR . 'features/login/GigyaLoginAjax.php';
		GigyaLoginAjax::customLogin();
	}

	/**
	 * Hook AJAX Debug Log.
	 */
	public function ajaxDebugLog() {
		if ( current_user_can( 'manage_options' ) )
		{
			wp_send_json_success( array( 'data' => get_option( 'gigya_log' ) ) );
		}

		wp_send_json_error();
	}

	public function ajaxLogout() {
		wp_logout();
		if ( isset( $_COOKIE['gltexp_' . GIGYA__API_KEY] ) )
		{
			unset( $_COOKIE['gltexp_' . GIGYA__API_KEY] );
			setrawcookie( 'gltexp_' . GIGYA__API_KEY, null, -1, '/' );
		}
		wp_send_json_success();
	}

	public function ajaxSetFixedSessionCookie() {
		$return = array(
			$_POST['expiration'],
			time() + $this->session_options['session_duration'],
		);

		$expiration = intval( $_POST['expiration'] / 1000 ) - time();
		if ( $this->login_options['mode'] == 'raas' and $this->session_options['session_type_numeric'] > 0 ) /* Fixed session in RaaS */
		{
			$return[] = gigyaSyncLoginSession( 'raas', $this->session_options, $expiration );
		}

		echo json_encode( $return );

		wp_die();
	}

	public function appendUserMetaToRestAPI() {
		register_rest_field( 'user',
			'gigya_fields',
			[
				'get_callback' => function ( $user_data ) {
					if ( apply_filters( 'rest_show_user_meta', $user_data['id'] ) ) {
						$nonce = ( isset( $_REQUEST['_wpnonce'] ) ) ? $_REQUEST['_wpnonce'] : '';
						if ( wp_verify_nonce( $nonce, 'wp_rest' ) ) {
							$login_opts = get_option( GIGYA__SETTINGS_LOGIN );
							$meta       = get_user_meta( $user_data['id'] );

							if ( ! empty( $login_opts['map_raas_full_map'] ) ) /* Fully customized field mapping options */ {
								foreach ( json_decode( $login_opts['map_raas_full_map'] ) as $meta_key ) {
									$meta_key = ( (array) $meta_key );
									$key      = $meta_key['cmsName'];

									if ( isset( $meta[ $key ] ) ) {
										$meta_trimmed[ $key ] = $meta[ $key ];
									}
								}

								return ( ! empty( $meta_trimmed ) ) ? $meta_trimmed : []; /* array() for fallback compatibility */
							} else {
								return [];
							}
						} else {
							return [];
						}
					} else {
						return [];
					}
				},
			]
		);
	}

	/**
	 * Hook to WP user login.
	 * If user logs in with WP form, check if raas is enabled,
	 * if so check if user has allowed capabilities
	 * if not log user out, if yes notify gigya.
	 *
	 * @param $user_login
	 * @param $account
	 *
	 * @throws Exception
	 */
	public function wpLogin( $user_login, $account ) {
		/* Login through WP form. */
		if ( isset( $_POST['log'] ) and isset( $_POST['pwd'] ) )
		{
			/* Trap for non-admin user who tries to login through WP form on RaaS mode. */
			$_is_allowed_user = $this->check_raas_allowed_user_role( $account->roles );
			if ( $this->login_options['mode'] == 'raas' and ( ! $_is_allowed_user ) )
			{
				wp_logout();
				wp_safe_redirect( $_SERVER['REQUEST_URI'] . '?rperm=1' ); // rperm used to create custom error message in wp login screen
				exit;
			}

			/* Notify Gigya socialize.notifyLogin for a return user logged in from WP login form. */
			$gigyaCMS = new GigyaCMS();
			$gigyaCMS->notifyLogin( $account->ID );
		}

		if ( empty( $_POST['action'] ) and ! empty( $_POST['data']['action'] ) ) {
			$_POST['action'] = $_POST['data']['action'];
		} elseif ( empty( $_POST['action'] ) ) {
			error_log( 'Login: No POST action specified' );
		}

		/* RaaS Login */
		if ( $_POST['action'] === 'gigya_raas' )
		{
			/* Update Gigya UID in WordPress user meta if it isn't set already */
			if ( isset( $_POST['data']['UID'] ) )
			{
				$wp_gigya_uid = get_user_meta( $account->ID, 'gigya_uid', true );
				if ( empty( $wp_gigya_uid ) )
					add_user_meta( $account->ID, 'gigya_uid', $_POST['data']['UID'] );
				elseif ( $wp_gigya_uid !== $_POST['data']['UID'] )
					wp_send_json_error( array( 'msg' => __( 'Oops! Someone is already registered with the email' ) ) );
			}
		}

		/*
		 * These post vars are available when there is the same email on the site,
		 * with the one who try to register and we want to link-accounts
		 * after the user is logged in with password. Or login after email verify.
		 */
		if ( ( $_POST['action'] == 'link_accounts' or $_POST['action'] == 'custom_login' ) and ! empty ( $_POST['data'] ) )
		{
			parse_str( $_POST['data'], $data );
			if ( ! empty( $data['gigyaUID'] ) )
			{
				$gigyaCMS = new GigyaCMS();
				$gigyaCMS->notifyRegistration( $data['gigyaUID'], $account->ID );
			}
		}
	}

	/**
	 * Raas admin login
	 * Check if user role is marked by admin as allowed role for wp login access
	 * For unified comparison transform values to _lowercase_
	 * admin roles are auto allowed, subscriber role is auto denied.
	 *
	 * @param array $user_roles
	 *
	 * @return bool $allowed
	 */
	public function check_raas_allowed_user_role( $user_roles ) {
		$allowed = false;
		$login_options = array_change_key_case( get_option( GIGYA__SETTINGS_LOGIN ) );

		foreach ( $user_roles as $role )
		{

			$role = strtolower( $role );
			$role = str_replace( ' ', '_', $role );
			// first auto allow Administrator or Super Admin roles
			if ( $role == "administrator" or $role == "super_admin" )
			{
				$allowed = true;
				continue;
			}
			elseif ( $role == "subscriber" )
			{
				$allowed = false;
				continue;
			}
			else
			{
				// if this is not an Admin or super admin
				$user_role = "raas_allowed_admin_{$role}";

				// find if user role key exists and positive in options array
				foreach ( $login_options as $key => $value )
				{
					$key = str_replace( ' ', '_', $key );
					if ( $user_role == $key )
					{
						if ( $value == "1" or $value == true )
						{
							$allowed = true;
							continue;
						}
					}
				}
			}
		}
		/* If no role match then the user is not allowed login */
		return $allowed;
	}

	/**
	 * Custom error message in case raas user tries to log in via wordpress wp-login screen.
	 * Used by hook wp_login
	 *
	 * @return string|false
	 */
	public function raas_wp_login_custom_message() {
		if ( isset( $_GET['rperm'] ) )
		{
			$message = "<div id='login_error'><strong>Access denied: </strong> this login requires administrator permission. <br/>Click <a href='/wp-login.php'>here</a> to login to the site.</div>";

			return $message;
		}

		return false;
	}

	/**
	 * Hook user register.
	 *
	 * @param $uid
	 *
	 * @throws Exception
	 */
	public function userRegister( $uid ) {
		/* Registered through RaaS */
		if ( isset( $_POST['data']['UID'] ) )
			add_user_meta( $uid, 'gigya_uid', $_POST['data']['UID'] );
		/* New user was registered through our custom extra-details form. */
		if ( isset( $_POST['form_name'] ) and $_POST['form_name'] == 'registerform-gigya-extra' and ! empty( $_POST['gigyaUID'] ) )
		{
			add_user_meta( $uid, 'gigya_uid', $_POST['gigyaUID'] );
		}

		// New user was registered through Gigya social login.
		// $_POST['action'] == 'gigya_login';
		if ( $this->login_options['mode'] == 'wp_sl' )
		{
			if ( ! empty( $_POST['data'] ) and ! empty( $_POST['data']['UID'] ) )
			{

				// We check if we can count on the email.
				if ( $_POST['data']['user']['email_not_verified'] == true )
				{
					// The mail is NOT verified, so we save Gigya's UID to DB
					// and do nothing.
					add_user_meta( $uid, 'gigya_uid', $_POST['data']['UID'] );
				}
				else
				{
					// The mail is verified, so we can merge IDs.
					$gigyaCMS = new GigyaCMS();
					$gigyaCMS->notifyRegistration( $_POST['data']['UID'], $uid );
				}
			}
		}

		/* New user was registered through WP form. */
		if ( isset( $_POST['user_login'] ) and isset( $_POST['user_email'] ) )
		{
			/*
			 * We notify to Gigya's 'socialize.notifyLogin'
			 * with a 'is_new_user' flag.
			 */
			$gigyaCMS = new GigyaCMS();
			$gigyaCMS->notifyLogin( $uid, true );
		}
	}

	/**
	 * Hook delete user.
	 *
	 * @param $user_id
	 *
	 * @throws Exception
	 */
	public function deleteUser( $user_id ) {
		$gigyaCMS = new GigyaCMS();

		if ( $this->login_options['mode'] == 'wp_sl' )
		{
			$gigyaCMS->deleteUser( $user_id );
		}
		elseif ( $this->login_options['mode'] == 'raas' )
		{
			$account = get_userdata( $user_id );
			$gigyaCMS->deleteAccountByEmail( $account->data->user_email );
		}
	}

	/**
	 * Shortcode for UserInfo.
	 *
	 * @param    array $atts
	 * @param          $info
	 *
	 * @return string
	 *
	 * @throws Exception
	 */
	private function shortcodeUserInfo( $atts, $info = null ) {
		/**
		 * @var    WP_User
		 */
		$wp_user = wp_get_current_user();
		$user_info = array();

		if ( $info == null )
		{
			$gigyaCMS = new GigyaCMS();
			$user_info = $gigyaCMS->getUserInfo( $wp_user->UID );
		}

		return json_encode( $user_info );
	}

	/**
	 * Register widgets.
	 */
	public function widgetsInit() {
		if ( empty( $this->login_options ) ) /* Only happens on initial activation, before configuring Gigya */
			return false;

		/* Screen-set Widget */
		require_once GIGYA__PLUGIN_DIR . 'features/raas/GigyaScreenSetWidget.php';
		register_widget( 'GigyaScreenSet_Widget' );

		/* RaaS Widget */
		$raas_on = $this->login_options['mode'] == 'raas';
		if ( ! empty( $raas_on ) )
		{
			require_once GIGYA__PLUGIN_DIR . 'features/raas/GigyaRaasWidget.php';
			register_widget( 'GigyaRaas_Widget' );
		}

		/* Login Widget */
		$login_on = $this->login_options['mode'] == 'wp_sl';
		if ( ! empty( $login_on ) )
		{
			require_once GIGYA__PLUGIN_DIR . 'features/login/GigyaLoginWidget.php';
			register_widget( 'GigyaLogin_Widget' );
		}

		/* Share Widget */
		$share_options = get_option( GIGYA__SETTINGS_SHARE );
		$share_on = _gigParamDefaultOn( $share_options, 'on' );
		if ( ! empty( $share_on ) )
		{
			require_once GIGYA__PLUGIN_DIR . 'features/share/GigyaShareWidget.php';
			register_widget( 'GigyaShare_Widget' );
		}

		/* Comment Widget */
		$comments_options = get_option( GIGYA__SETTINGS_COMMENTS );
		$comments_on = _gigParamDefaultOn( $comments_options, 'on' );
		if ( ! empty( $comments_on ) )
		{
			require_once GIGYA__PLUGIN_DIR . 'features/comments/GigyaCommentsWidget.php';
			register_widget( 'GigyaComments_Widget' );
		}

		/* Reactions Widget */
		$reactions_options = get_option( GIGYA__SETTINGS_REACTIONS );
		$reactions_on = _gigParamDefaultOn( $reactions_options, 'on' );
		if ( ! empty( $reactions_on ) )
		{
			require_once GIGYA__PLUGIN_DIR . 'features/reactions/GigyaReactionsWidget.php';
			register_widget( 'GigyaReactions_Widget' );
		}

		/* Gamification Widget */
		$gm_options = get_option( GIGYA__SETTINGS_GM );
		$gm_on = _gigParamDefaultOn( $gm_options, 'on' );
		if ( ! empty( $gm_on ) )
		{
			require_once GIGYA__PLUGIN_DIR . 'features/gamification/GigyaGamificationWidget.php';
			register_widget( 'GigyaGamification_Widget' );
		}

		return true;
	}

	/**
	 * Hook content alter.
	 *
	 * @param    $content
	 *
	 * @return    string $content
	 */
	public function theContent( $content ) {
		// Share plugin.
		$share_options = get_option( GIGYA__SETTINGS_SHARE );
		$share_on = _gigParamDefaultOn( $share_options, 'on' );
		if ( ! empty( $share_on ) )
		{
			require_once GIGYA__PLUGIN_DIR . 'features/share/GigyaShareSet.php';
			$share = new GigyaShareSet();
			$content = $share->setDefaultPosition( $content );
		}

		// Reactions plugin.
		$reactions_options = get_option( GIGYA__SETTINGS_REACTIONS );
		$reactions_on = _gigParamDefaultOn( $reactions_options, 'on' );
		if ( ! empty( $reactions_on ) )
		{
			require_once GIGYA__PLUGIN_DIR . 'features/reactions/GigyaReactionsSet.php';
			$reactions = new GigyaReactionsSet();
			$content = $reactions->setDefaultPosition( $content );
		}

		return $content;
	}

	/**
	 * Check if the comments plugin is on
	 *
	 * @return bool plugin on/off
	 */
	public function gigya_comments_on() {
		$comments_options = get_option( GIGYA__SETTINGS_COMMENTS );
		$comments_on = _gigParamDefaultOn( $comments_options, 'on' );
		return ! empty( $comments_on ) ? true : false;
	}

	/**
	 * Hook comments_template.
	 *
	 * @param $comment_template
	 *
	 * @return string
	 */
	public function commentsTemplate( $comment_template ) {
		/* Spider trap.
		 * When a spider detect we render the comment in the HTML for SEO */
		$is_spider = gigyaCMS::isSpider();
		if ( ! empty( $is_spider ) )
		{
			/* Override default WP comments template with comment spider */
			return GIGYA__PLUGIN_DIR . 'admin/tpl/comments-spider.tpl.php';
		}

		/* Override default WP comments template */
		return GIGYA__PLUGIN_DIR . 'admin/tpl/comments.tpl.php';
	}

	/**
	 * Hook AJAX Clean DB.
	 */
	public function ajaxCleanDB() {
		if ( current_user_can( 'manage_options' ) )
		{
			require_once GIGYA__PLUGIN_DIR . 'install.php';
			GigyaInstall::cleanDB();
		}
	}

	/**
	 * Get WordPress user object by Gigya UID
	 *
	 * @param string $gigya_uid Gigya UID
	 *
	 * @return WP_User|false
	 */
	public function getWPUserByGigyaUid( $gigya_uid ) {
		$wp_user = get_users( [
			'meta_key'   => 'gigya_uid',
			'meta_value' => $gigya_uid,
		] );

		if ( ! empty( $wp_user ) ) {
			$wp_user = $wp_user[0];
		} else {
			return false;
		}

		return $wp_user;
	}

	public function getGigyaAvatar( $avatar, $id_or_email, $size, $default, $alt ) {
		if ( empty( $id_or_email ) )
		{
			$id = get_current_user_id();
		}
		else
		{
			if ( is_numeric( $id_or_email ) )
			{
				$id = $id_or_email;
			}
			elseif ( is_string( $id_or_email ) )
			{
				$user = get_user_by( 'email', $id_or_email );
				$id = $user->ID;
			}
			else
			{
				return $avatar;
			}
		}
		$url = get_user_meta( $id, "profile_image", true );
		if ( empty( $url ) )
		{
			return $avatar;
		}
		$alt = empty( $alt ) ? get_user_meta( $id, "first_name", true ) : $alt;
		return "<img src='{$url}' alt='{$alt}' width='{$size}' height='{$size}'>";
	}

	public function getOfflineSyncSchedules( $schedules ) {
		$schedules['every_five_seconds'] = array(
			'interval' => 5,
			'display' => __( 'Every five seconds' ),
		);

		$schedules['every_thirty_seconds'] = array(
			'interval' => 30,
			'display' => __( 'Every thirty seconds' ),
		);

		$schedules['every_minute'] = array(
			'interval' => 60,
			'display' => __( 'Every minute' ),
		);

		$schedules['every_two_hours'] = array(
			'interval' => 7200,
			'display' => __( 'Every two hours' ),
		);

		$settings = get_option( GIGYA__SETTINGS_FIELD_MAPPING );
		$schedules['gigya_offline_sync_custom'] = array(
			'interval' => ( ! empty( $settings['map_offline_sync_frequency'] ) ) ? ($settings['map_offline_sync_frequency'] * 60) : 3600,
			'display' => __( 'Custom' ),
		);

		return $schedules;
	}

	public function executeOfflineSyncCron() {
		/* Retrieve config variables */
		$config           = get_option( 'gigya_field_mapping_settings' );
		$job_config       = get_option( 'gigya_offline_sync_params' );
		$enable_job       = $config['map_offline_sync_enable'];
		$email_on_success = $config['map_offline_sync_email_on_success'];
		$email_on_failure = $config['map_offline_sync_email_on_failure'];

		$helper = new GigyaOfflineSync();

		if ( $enable_job ) {
			try {
				$last_customer_update = null;
				$gigya_query          = "SELECT * FROM accounts";
				if ( ! empty( $job_config['last_customer_update'] ) ) {
					$last_customer_update = $job_config['last_customer_update'];
					$gigya_query          .= ' WHERE lastUpdatedTimestamp > ' . $last_customer_update;
				}
				$gigya_query     .= " ORDER BY lastUpdatedTimestamp ASC LIMIT " . GIGYA__OFFLINE_SYNC_MAX_USERS;
				$gigya_cms       = new GigyaCMS();
				$gigya_users     = $gigya_cms->searchGigyaUsers( [ 'query' => $gigya_query ] );
				$processed_users = 0;
				$users_not_found = 0;
				$uids_not_found = [];

				foreach ( $gigya_users as $gigya_user ) {
					$gigya_uid                    = $gigya_user['UID'];
					$gigya_last_updated_timestamp = $gigya_user['lastUpdatedTimestamp'];

					if ( ! empty( $gigya_uid ) and ! empty( $gigya_last_updated_timestamp ) ) {
						$wp_user = $this->getWPUserByGigyaUid( $gigya_uid );
						if ( ! empty( $wp_user ) ) {
							_gigya_add_to_wp_user_meta( $gigya_user, $wp_user->ID );

							$job_config['last_customer_update'] = $gigya_last_updated_timestamp - GIGYA__OFFLINE_SYNC_UPDATE_DELAY;
							update_option( 'gigya_offline_sync_params', $job_config );

							$processed_users ++;
						} else {
							$users_not_found ++;
							$uids_not_found[] = $gigya_user['UID'];
						}
					} else {
						error_log( 'Gigya offline sync: unable to process user due to a lack of essential data. User data received: ' . json_encode( $gigya_user,
								JSON_PRETTY_PRINT ) );
					}
				}

				$job_config['last_run'] = round( microtime( true ) * 1000 );
				update_option( 'gigya_offline_sync_params', $job_config );

				error_log( 'Gigya offline sync completed. Users processed: ' . $processed_users . ( ( $users_not_found )
						? '. Users not found: ' . $users_not_found . PHP_EOL . implode( ',' . PHP_EOL, $uids_not_found )
						: '' ) );

				$status = ( $users_not_found > 0 ) ? 'completed with errors' : 'succeeded';
				$helper->sendCronEmail( 'offline sync', $status, $email_on_success, $processed_users, $users_not_found );
			} catch ( GigyaHookException $e ) {
				error_log( 'Gigya offline sync: There was a problem adding custom data to field mapping: ' . $e->getMessage() );
				$status = 'failed';
				$helper->sendCronEmail( 'offline sync', $status, $email_on_failure );
			} catch ( GSApiException $e ) {
				error_log( 'Offline sync failed: ' . $e->getErrorCode() . ' – ' . $e->getMessage() . '. Call ID: ' . $e->getCallId() );
				$status = 'failed';
				$helper->sendCronEmail( 'offline sync', $status, $email_on_failure );
			} catch ( GSException | Exception $e ) {
				error_log( 'Offline sync failed: ' . $e->getMessage() );
				$status = 'failed';
				$helper->sendCronEmail( 'offline sync', $status, $email_on_failure );
			}
		}
	}
}