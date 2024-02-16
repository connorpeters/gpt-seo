<?php
function gptseo_register_meta_boxes() {
	add_meta_box( 'meta-box-id', 'GPT SEO Options', 'gptseo_my_display_callback', 'snippet', 'normal', 'high' );

}
add_action( 'add_meta_boxes', 'gptseo_register_meta_boxes' );

function gptseo_save_meta_box( $post_id ) {
	$is_valid_nonce = ( isset( $_POST[ 'gptseo_nonce' ] ) && wp_verify_nonce( $_POST[ 'gptseo_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
	if ( !$is_valid_nonce ) return;
 
	if ( array_key_exists( 'gptseo_instructions', $_POST ) ) {
		update_post_meta( $post_id, 'gptseo_instructions', sanitize_text_field( $_POST[ 'gptseo_instructions' ] ) );
	}
	if ( array_key_exists( 'gptseo_reference', $_POST ) ) {
		update_post_meta( $post_id, 'gptseo_reference', sanitize_text_field( $_POST[ 'gptseo_reference' ] ) );
	}
	if ( array_key_exists( 'gptseo_seo_result', $_POST ) ) {
		update_post_meta( $post_id, 'gptseo_seo_result', sanitize_text_field( $_POST[ 'gptseo_seo_result' ] ) );
	}
}
add_action( 'save_post_snippet', 'gptseo_save_meta_box' );


function gptseo_approve_snippet( $new_status, $old_status, $post ) {
	$is_valid_nonce = ( isset( $_POST[ 'gptseo_nonce' ] ) && wp_verify_nonce( $_POST[ 'gptseo_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
	if ( !$is_valid_nonce ) return;
    if ( $old_status == $new_status ) return;

	if ( $post->post_type == 'snippet' && $old_status == 'draft' && $new_status == 'publish' ) {
		$post_meta = get_post_meta( $post->ID );
		if ( isset($post_meta['gptseo_seo_result'][0]) ) {
			$seo_text = sanitize_text_field( $post_meta['gptseo_seo_result'][0]  );
			$update_post = array(
				'ID'           => $post->ID,
				'post_content' => $seo_text,
				'post_status' => 'publish',
			);
			wp_update_post( $update_post );
		}
	}
}
add_action( 'transition_post_status', 'gptseo_approve_snippet', 10, 3 );

function gptseo_my_display_callback( $post ) {
	wp_nonce_field( basename( __FILE__ ), 'gptseo_nonce' );
	$post_meta = get_post_meta( $post->ID );
	?>
	<p>
        <label for="gptseo_reference"><?php _e( 'Reference:', 'gptseo' )?></label>
        <input class="regular-text" type="text" name="gptseo_reference" id="gptseo_reference" value="<?php if ( isset ( $post_meta['gptseo_reference'] ) ) echo $post_meta['gptseo_reference'][0]; ?>" />
    </p>
	<p>
        <label for="gptseo_seo_result"><?php _e( 'SEO text:', 'gptseo' )?></label>
        <input class="regular-text" type="text" name="gptseo_seo_result" id="gptseo_seo_result" value="<?php if ( isset ( $post_meta['gptseo_seo_result'] ) ) echo $post_meta['gptseo_seo_result'][0]; ?>" />
    </p>
	<p>
        <label for="gptseo_instructions"><?php _e( 'Custom instructions:', 'gptseo' )?></label>
        <input class="regular-text" type="text" name="gptseo_instructions" id="gptseo_instructions" value="<?php if ( isset ( $post_meta['gptseo_instructions'] ) ) echo $post_meta['gptseo_instructions'][0]; ?>" />
    </p>
	<p>
		<button type="button" class="button" id="generate-snippet" value="gptseo_ajax_generate">Generate Snippet</button>
	</p>
	<script>
		jQuery(document).ready(function(){
			jQuery('#generate-snippet').click(function(){
				var clickBtnValue = jQuery(this).val();
				console.log(clickBtnValue)
				jQuery.post("/wp-admin/admin-ajax.php", {
					gptseo_nonce: "<?php echo wp_create_nonce(); ?>",
					action: "gptseo_ajax_generate",
					post_id: <?php echo $post->ID; ?>,
					gptseo_instructions: jQuery("input[type=text][name=gptseo_instructions]").val(),
					gptseo_reference: jQuery("input[type=text][name=gptseo_reference]").val()
					}, function(data) {
						alert("Successfully generated new SEO snippet");
                        $res = data['data'];
                        jQuery("input[type=text][name=gptseo_seo_result]").val($res);
                        console.log(data);
					}
				);
			});
		});
	</script>
	<?php
}

function gptseo_ajax_generate(){
    $is_valid_nonce = ( isset( $_POST[ 'gptseo_nonce' ] ) && wp_verify_nonce( $_POST[ 'gptseo_nonce' ], basename( __FILE__ ) ) ) ? 'true' : 'false';
	if ( !$is_valid_nonce ) {
        error_log("INVALID NONCE");
        wp_die();
        return;
    }
    gptseo_save_meta_box($_POST['post_id']);
    $res = gptseo_rewrite_content($_POST['post_id']);
    wp_send_json_success($res);
    // wp_die(); // ajax call must die to avoid trailing 0 in your response
    // return "data";
}
add_action( "wp_ajax_gptseo_ajax_generate", "gptseo_ajax_generate" );


/**
 * Query GPT to rewrite content
 */
function gptseo_rewrite_content( $post_id ) {
    $reference = get_post_meta( $post_id, "gptseo_reference" )[0];

	// Query gpt
	$yourApiKey = get_option('gptseo_options')['gptseo_field_api_key'];
	$client = OpenAI::client($yourApiKey);
	$prompt = "Rewrite the following reference text in order optimize it for search engine ranking and visibility. Make sure it is a similar length of characters and carries with it a similar message. It doesn't have to be that different from the original, if that is what you think is best for readability. You are a great master SEO and salesman, thank you for making the following text awesome for my website: ";
	$result = $client->chat()->create([
		// 'model' => 'gpt-4-0125-preview',
		'model' => 'gpt-3.5-turbo',
		'messages' => [
			['role' => 'user', 'content' => $prompt . $reference],
		],
		'max_tokens' => ceil(strlen($reference)/4), // about 4 chars per token
		'temperature' => 0.8 // between 0 & 2
	]);

    // Update post meta with GPT content
	update_post_meta( $post_id, 'gptseo_seo_result', sanitize_text_field( $result->choices[0]->message->content ) );

    // Set post as draft
    $update_post = array(
        'ID'           => $post_id,
        'post_status' => 'draft',
    );
    wp_update_post( $update_post );
    return sanitize_text_field( $result->choices[0]->message->content );
}