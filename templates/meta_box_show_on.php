<?php foreach($post_types as $post_type): ?>

	<?php $checked_str = in_array( $post_type->name, $post_types_selected ) ? 'checked="checked"' : ''; ?>
	
	<input id="db_signatures_post_type_<?php echo $post_type->name; ?>" type="checkbox" name="db_signatures_post_type[]" value="<?php echo $post_type->name; ?>" <?php echo $checked_str; ?>/>
	<label for="db_signatures_post_type_<?php echo $post_type->name; ?>"><?php echo $post_type->labels->name; ?></label><br/>

<?php endforeach; ?>