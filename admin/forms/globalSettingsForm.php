<?php
/**
 * Form builder for 'Global Settings' configuration page.
 */
function globalSettingsForm() {

    $values = _getGigyaSettingsValues( GIGYA__SETTINGS_GLOBAL );
	$form   = array();

	$form['api_key'] = array(
			'type'  => 'text',
			'label' => __( 'API Key' ),
			'size' => 64,
			'style' => 'font-family: monospace',
			'value' => _gigParam( $values, 'api_key', '' )
	);

	$form['user_key'] = array(
		'type'  => 'text',
		'label' => __( 'User Key' ),
		'value' => trim(_gigParam( $values, 'user_key', '' ))
	);

 	if ( current_user_can( GIGYA__SECRET_PERMISSION_LEVEL ) || current_user_can( CUSTOM_GIGYA_EDIT_SECRET ) ) {
		$form['api_secret'] = array(
			'type'  => 'password',
			'label' => __( 'User Secret' ),
			'value' => '',
			'desc' => 'Secret key: '. _gigParam( $values, 'api_secret', '', true ),
		);
	} else {
		$form['api_secret'] = array(
			'type'  => 'customText',
			'label' => __( 'Secret Key' ),
			'class' => 'secret_key_placeholder',
			'size' => 100,
			'id' => 'secret_key_placeholder'
		);
	}

	$dataCenter = _gigParam( $values, 'data_center', 'us1.gigya.com' );
	$options = array(
				'us1.gigya.com' => __( 'US Data Center' ),
				'eu1.gigya.com' => __( 'EU Data Center' ),
				'au1.gigya.com' => __( 'AU Data Center' ),
				'ru1.gigya.com' => __( 'RU Data Center' ),
				'cn1.gigya-api.cn' => __( 'CN Data Center' ),
				'other' => __( 'Other' )
	);
	if (!array_key_exists($dataCenter, $options)) {
	     $dataCenter = "other";
	}
	$val = $dataCenter == "other" ? current(explode('.', $values['data_center'])) : "";
	$form['data_center'] = array(
			'type'    => 'select',
			'options' => $options,
			'label'   => __( 'Data Center' ),
			'class'   => 'data_center',
			'value'   => $dataCenter,
			'markup' => "<span class='other_dataCenter'><input type='text' size='15' class='input-xlarge' id='other_ds' name='other_ds' value='" . $val . "' /> <p>Please specify the SAP CDC data center in which your site is defined. For example: 'eu1.gigya.com'. To verify your site location contact your SAP Customer Data Cloud implementation manager.</p></span>"
	);

	$form['enabledProviders'] = array(
			'type'  => 'text',
			'label' => __( 'List of providers' ),
			'value' => _gigParam( $values, 'enabledProviders', '*' ),
			'desc'  => __( 'Comma separated list of providers to include. For example: facebook,twitter,google. Leave empty or type * for all providers. See the entire ' ) . ' <a href="https://developers.gigya.com/display/GD/socialize.showLoginUI+JS">list of available providers</a>.'
	);

	$form['lang'] = array(
			'type'    => 'select',
			'options' => array(
					'en'    => 'English',
					'zh-cn' => 'Chinese',
					'zh-hk' => 'Chinese (Hong Kong)',
					'zh-tw' => 'Chinese (Taiwan)',
					'cs'    => 'Czech',
					'da'    => 'Danish',
					'nl'    => 'Dutch',
					'fi'    => 'Finnish',
					'fr'    => 'French',
					'de'    => 'German',
					'el'    => 'Greek',
					'hu'    => 'Hungarian',
					'id'    => 'Indonesian',
					'it'    => 'Italian',
					'ja'    => 'Japanese',
					'ko'    => 'Korean',
					'ms'    => 'Malay',
					'no'    => 'Norwegian',
					'pl'    => 'Polish',
					'pt'    => 'Portuguese',
					'pt-br' => 'Portuguese (Brazil)',
					'ro'    => 'Romanian',
					'ru'    => 'Russian',
					'es'    => 'Spanish',
					'es-mx' => 'Spanish (Mexican)',
					'sv'    => 'Swedish',
					'tl'    => 'Tagalog (Philippines)',
					'th'    => 'Thai',
					'tr'    => 'Turkish',
					'uk'    => 'Ukrainian',
					'vi'    => 'Vietnamese',
			),
			'value'   => _gigParam( $values, 'lang', 'en' ),
			'label'   => __( 'Language' ),
			'desc'    => __( 'Please select the interface language' )
	);

	$form['advanced'] = array(
			'type'  => 'textarea',
			'value' => _gigParam( $values, 'advanced', '' ),
			'label' => __( 'Additional Parameters (advanced)' ),
			'desc'  => sprintf( __( 'Enter valid %s. See list of available ' ), '<a class="gigya-json-example" href="javascript:void(0)">' . __( 'JSON format' ) . '</a>' ) . ' <a href="https://developers.gigya.com/display/GD/Global+Configuration#GlobalConfiguration-DataMembers" target="_blank" rel="noopener noreferrer">' . __( 'parameters' ) . '</a>'
	);

	$form['google_analytics'] = array(
			'type'  => 'checkbox',
			'label' => __( "Enable Google Social Analytics" ),
			'value' => _gigParam( $values, 'google_analytics', 0 )
	);

	$form['debug'] = array(
			'type'  => 'checkbox',
			'label' => __( 'Enable SAP CDC debug log' ),
			'value' => _gigParam( $values, 'debug', 0 ),
			'desc'  => __( 'Log all SAP Customer Data Cloud\'s requests and responses. You can then find the log' ) . ' <a href="javascript:void(0)" class="gigya-debug-log">' . __( 'here' ) . '</a>'
	);

    /* Use this field in multisite to flag when sub site settings are saved locally for site */
	if ( is_multisite() ) {
		$form['sub_site_settings_saved'] = array(
			'type'  => 'hidden',
			'id'    => 'sub_site_settings_saved',
			'value' => 1,
			'class' => 'gigya-raas-warn'
		);

		if ( empty( $values['sub_site_settings_saved'] ) ) {
			$form['sub_site_settings_saved']['msg']     = 1;
			$form['sub_site_settings_saved']['msg_txt'] = __( 'Settings are set to match the main site. Once saved they will become independent' );
		}
	}

	if ( get_option( 'gigya_settings_fields' ) ) {
		$form['clean_db'] = array(
				'markup' => '<a href="javascript:void(0)" class="clean-db">Database cleaner after upgrade</a><br><small>Press this button to remove all unnecessary elements of the previous version from your database.Please make sure to backup your database before performing the clean. Learn more about upgrading from the previous version <a href="https://developers.gigya.com/display/GD/WordPress+Plugin#WordPressPlugin-InstallingtheGigyaPluginforWordPress">here.</a></small>'
		);
	}

	echo _gigya_form_render( $form, GIGYA__SETTINGS_GLOBAL );
}