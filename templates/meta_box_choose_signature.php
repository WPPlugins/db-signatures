<select name="db_signatures_signature">

	<option value="random" <?php if($signature_selected == 'random') echo 'selected="selected"'; ?>>~ Random ~</option>
	<option value="disabled" <?php if($signature_selected == 'disabled') echo 'selected="selected"'; ?>>X Disabled X</option>

	<?php while ( $signatures->have_posts() ) : $signatures->next_post(); ?>

		<option value="<?php echo $signatures->post->ID ; ?>" <?php if($signature_selected == $signatures->post->ID ) echo 'selected="selected"'; ?>><?php echo get_the_title( $signatures->post->ID ); ?></option>

	<?php endwhile; ?>

	<?php wp_reset_postdata(); ?>

</select>
