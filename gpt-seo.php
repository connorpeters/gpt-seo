<?php
/*
 * Plugin Name:       GPT SEO by Connor
 * Plugin URI:        https:/locke.id
 * Description:       Configure GPT4 to automatically rewrite specific content in your website on a regular basis to please Google and rank number 1.
 * Version:           1.0
 * Requires at least: 5.2
 * Requires PHP:      7.2
 * Author:            Connor Peters
 * Author URI:        https://locke.id
 * Text Domain:       gptseo
 */

require_once 'page-settings.php';
require_once 'post-snippet.php';
require_once 'cron.php';
require_once 'vendor/autoload.php';

/**
 * Activate the plugin and init
 */
function gptseo_activate() { 
	gptseo_init(); 

	// Init cron
	if ( ! wp_next_scheduled( 'gptseo_cron_hook' ) ) {
		wp_schedule_event( time(), 'weekly', 'gptseo_cron_hook' );
	}

	// Clear the permalinks after the post type has been registered.
	flush_rewrite_rules(); 
}
register_activation_hook( __FILE__, 'gptseo_activate' );


function gptseo_init() {
	// Add cron hook
	add_action( 'gptseo_cron_hook', 'gptseo_cron_exec' );

	// Add shortcode
	add_shortcode( 'seo-snippet', 'gptseo_display_snippet_shortcode' );

	// Add custom post type "snippet"
	$labels = array(
		'name'                  => _x( 'SEO Snippets', 'Post type general name', 'gptseo' ),
		'singular_name'         => _x( 'SEO Snippet', 'Post type singular name', 'gptseo' ),
		'menu_name'             => _x( 'SEO Snippets', 'Admin Menu text', 'gptseo' ),
		'name_admin_bar'        => _x( 'SEO Snippet', 'Add New on Toolbar', 'gptseo' ),
		'add_new'               => __( 'Add New', 'gptseo' ),
		'add_new_item'          => __( 'Add New SEO Snippet', 'gptseo' ),
		'new_item'              => __( 'New SEO Snippet', 'gptseo' ),
		'edit_item'             => __( 'Edit SEO Snippet', 'gptseo' ),
		'view_item'             => __( 'View SEO Snippet', 'gptseo' ),
		'all_items'             => __( 'All SEO Snippets', 'gptseo' ),
		'search_items'          => __( 'Search SEO Snippets', 'gptseo' ),
		'not_found'             => __( 'No SEO Snippets found.', 'gptseo' ),
		'not_found_in_trash'    => __( 'No SEO Snippets found in Trash.', 'gptseo' ),
	);

	$args = array(
		'labels'             => $labels,
		'public'             => false,
		'publicly_queryable' => false,
		'show_ui'            => true,
		'show_in_menu'       => true,
		'query_var'          => false,
		'rewrite'            => array( 'slug' => 'snippet' ),
		'capability_type'    => 'post',
		'has_archive'        => false,
		'hierarchical'       => false,
		'menu_position'      => 1200,
		'supports'           => array( 'title', 'editor', 'author', 'revisions' ),
	);

	register_post_type( 'snippet', $args );
} 
add_action( 'init', 'gptseo_init' );


function gptseo_display_snippet_shortcode( $atts = array(), $content = null, $tag = '' ) {
	$atts = array_change_key_case( (array) $atts, CASE_LOWER );
	$atts = shortcode_atts(
		array(
			'id' => 0,
		), $atts, $tag
	);

	if($atts['id'] == 0) {
		return "Empty ID";
	}
	$post = get_post($atts['id']);
	if($post->post_type !== "snippet") {
		return "No snippet found for this ID";
	}

	return esc_html( $post->post_content );
}

register_deactivation_hook(__FILE__, 'gptseo_deactivate');
function gptseo_deactivate() { 
	// Remove crons
	$timestamp = wp_next_scheduled( 'gptseo_cron_hook' );
	wp_unschedule_event( $timestamp, 'gptseo_cron_hook' );

	unregister_post_type( 'snippet' );

	// Clear the permalinks after the post type has been registered.
	flush_rewrite_rules(); 
}

?>