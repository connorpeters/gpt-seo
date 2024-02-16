<?php

function gptseo_settings_init() {
	// Register a new setting for "gptseo" page.
	register_setting( 'gptseo', 'gptseo_options' );

	// Register a new section in the "gptseo" page.
	add_settings_section(
		'gptseo_section_developers',
		__( 'Settings', 'gptseo' ), 'gptseo_section_developers_callback',
		'gptseo'
	);

	// Register a new field in the "gptseo_section_developers" section, inside the "gptseo" page.
	add_settings_field(
		'gptseo_field_api_key', // As of WP 4.6 this value is used only internally.
		                        // Use $args' label_for to populate the id inside the callback.
			__( 'OpenAI API Key', 'gptseo' ),
		'gptseo_field_api_key_cb',
		'gptseo',
		'gptseo_section_developers',
		array(
			'label_for'         => 'gptseo_field_api_key',
			'class'             => 'gptseo_row',
		)
	);

	add_settings_field(
		'gptseo_field_cron_schedule',
		__( 'Update Regularity', 'gptseo' ),
		'gptseo_field_cron_schedule_cb',
		'gptseo',
		'gptseo_section_developers',
		array(
			'label_for'         => 'gptseo_field_cron_schedule',
			'class'             => 'gptseo_row',
		)
	);
}
add_action( 'admin_init', 'gptseo_settings_init' );

/**
 * Developers section callback function.
 * Required to show fields
 */
function gptseo_section_developers_callback( $args ) { }


/**
 *
 * WordPress has magic interaction with the following keys: label_for, class.
 * - the "label_for" key value is used for the "for" attribute of the <label>.
 * - the "class" key value is used for the "class" attribute of the <tr> containing the field.
 * Note: you can add custom key value pairs to be used inside your callbacks.
 *
 * @param array $args
 */
function gptseo_field_api_key_cb( $args ) {
	// Get the value of the setting we've registered with register_setting()
	$options = get_option( 'gptseo_options' );
    $key = "";
    if(isset($options[ $args['label_for'] ] )) {
        $key = $options[ $args['label_for'] ];
    }
	?>
    <input class="regular-text" type="text" name="gptseo_options[<?php echo esc_attr( $args['label_for'] ); ?>]" value="<?php echo $key; ?>">
	<?php
}

function gptseo_field_cron_schedule_cb( $args ) {
	// Get the value of the setting we've registered with register_setting()
	$options = get_option( 'gptseo_options' );
    $key = "";
    if(isset($options[ $args['label_for'] ] )) {
        $key = $options[ $args['label_for'] ];
    }
	?>
    <input class="regular-text" type="text" name="gptseo_options[<?php echo esc_attr( $args['label_for'] ); ?>]" value="<?php echo $key; ?>">
	<?php
}

/**
 * Add the top level menu page.
 */
function gptseo_options_page() {
	add_menu_page(
		'GPT SEO',
		'SEO Options',
		'manage_options',
		'gptseo', // slug
		'gptseo_options_page_html' // callback
	);
}
add_action( 'admin_menu', 'gptseo_options_page' );

function gptseo_enqueue($hook) {
    $cpt = 'snippet';
    $screen = get_current_screen();
    if( ( $hook == 'toplevel_page_gptseo' || in_array($hook, array('post.php', 'post-new.php') ) && is_object( $screen ) && $cpt == $screen->post_type) ){
        wp_register_style('options_page_style', plugin_dir_url( __FILE__ ) . 'style.css');
        wp_enqueue_style('options_page_style');
    }
}
add_action( 'admin_enqueue_scripts', 'gptseo_enqueue' );


/**
 * Top level menu callback function
 */
function gptseo_options_page_html() {
	// check user capabilities
	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	// add error/update messages

	// check if the user have submitted the settings
	// WordPress will add the "settings-updated" $_GET parameter to the url
	if ( isset( $_GET['settings-updated'] ) ) {
		// add settings saved message with the class of "updated"
		add_settings_error( 'gptseo_messages', 'gptseo_message', __( 'Settings Saved', 'gptseo' ), 'updated' );
	}

	// show error/update messages
	settings_errors( 'gptseo_messages' );
	?>
	<div class="wrap">
		<h1><?php echo esc_html( get_admin_page_title() ); ?></h1>
		<form action="options.php" method="post">
			<?php
			// output security fields for the registered setting "gptseo"
			settings_fields( 'gptseo' );
			// output setting sections and their fields
			// (sections are registered for "gptseo", each field is registered to a specific section)
			do_settings_sections( 'gptseo' );
			// output save settings button
			submit_button( 'Save Settings' );
			?>
		</form>
	</div>
	<?php
}