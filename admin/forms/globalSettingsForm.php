<?php
/**
 * Form builder for 'Global Settings' configuration page.
 */
function globalSettingsForm() {
	$values = get_option( GIGYA__SETTINGS_GLOBAL );
	$form   = array();

	$form['api_key'] = array(
			'type'  => 'text',
			'label' => __( 'Gigya Socialize API Key' ),
			'value' => _gigParam( $values['api_key'], '' )
	);

	$form['api_secret'] = array(
			'type'  => 'text',
			'label' => __( 'Gigya Socialize Secret Key' ),
			'value' => _gigParam( $values['api_secret'], '' )
	);

	$form['data_center'] = array(
			'type'    => 'select',
			'options' => array(
					'us1.gigya.com' => __( 'US Data Center' ),
					'eu1.gigya.com' => __( 'EU Data Center' )
			),
			'label'   => __( 'Data Center' ),
			'class'   => 'data_center',
			'value'   => _gigParam( $values['data_center'], 'us1.gigya.com' )
	);

	$form['providers'] = array(
			'type'  => 'text',
			'label' => __( 'List of providers' ),
			'value' => _gigParam( $values['providers'], '*' ),
			'desc'  => __( 'Comma separated list of networks that would be included. For example: Facebook, Twitter, Yahoo means all networks. See list of available' ) . '<a href="http://developers.gigya.com/020_Client_API/020_Methods/Socialize.showLoginUI">Providers</a>'
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
			'value'   => _gigParam( $values['lang'], 'en' ),
			'label'   => __( 'Language' ),
			'desc'    => __( 'Please select the interface language' )
	);

	$form['advanced'] = array(
			'type'  => 'textarea',
			'value' => _gigParam( $values['advanced'], '' ),
			'label' => __( 'Additional Parameters (advanced)' ),
			'desc'  => __( 'Enter validate JSON format' ) . ' <br> ' . __( 'See list of available:' ) . ' <a href="http://developers.gigya.com/030_API_reference/010_Client_API/010_Objects/Conf_object" target="_blank">parameters</a>'
	);

	$form['google_analytics'] = array(
			'type'  => 'checkbox',
			'label' => __( "Google's Social Analytics" ),
			'value' => _gigParam( $values['google_analytics'], 0 )
	);

	$form['debug'] = array(
			'type'  => 'checkbox',
			'label' => __( 'Enable Gigya debug log' ),
			'value' => _gigParam( $values['debug'], 0 )
	);

	echo _gigya_form_render( $form, GIGYA__SETTINGS_GLOBAL );
}