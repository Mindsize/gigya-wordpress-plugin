<?php

/**
 * @file
 * GigyaCommentsSet.php
 * An AJAX handler for login or register user to WP.
 */
class GigyaCommentsSet {
	private $comments_options;

	public function __construct() {

		// Get settings variables.
		$this->comments_options = get_option( GIGYA__SETTINGS_COMMENTS );

		// Load custom Gigya comments script.
		wp_enqueue_script( 'gigya_comments_js', GIGYA__PLUGIN_URL . 'features/comments/gigya_comments.js' );
		wp_enqueue_style( 'gigya_comments_css', GIGYA__PLUGIN_URL . 'features/comments/gigya_comments.css' );

	}

	/**
	 * Generate the parameters for the Comments plugin.
	 * @return array
	 */
	public function getParams() {
		$params = array(
				'categoryID'            => _gigParam( $this->comments_options, 'categoryID', '' ),
				'rating'                => _gigParam( $this->comments_options, 'rating', 0 ),
				'enabledShareProviders' => _gigParam( $this->comments_options, 'enabledShareProviders', '*' ),
				'streamID'              => get_the_ID(),
				'scope'					=> '', /* Backwards compatibility for activity feed deprecation */
				'privacy'				=> '', /* Backwards compatibility for activity feed deprecation */
				'version'               => 2,
		);

		if ( ! empty( $this->comments_options['advanced'] ) ) {
			$advanced = gigyaCMS::parseJSON( _gigParam( $this->comments_options, 'advanced', '' ) );
			$params   = array_merge( $params, $advanced );
		}

		// Let other plugins modify the comments parameters.
		// For example:
		// $params['useSiteLogin'] = true;
		// $params['onSiteLoginClicked'] = 'onSiteLoginHandler';
		// To registering to the onSiteLoginClicked event.
		$params = apply_filters( 'gigya_comments_params', $params );

		return $params;
	}
}