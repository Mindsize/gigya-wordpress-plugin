<?php
/**
 * Template - Select form element for Gigya settings pages.
 * Render with @see _gigya_render_tpl().
 */
?>
<div class="gigya-form-field row select <?php echo isset($class) ? $class : ''; ?>">
	<label for="gigya_<?php echo $id; ?>"><?php echo $label; ?></label>
	<select id="gigya_<?php echo $id; ?>" name="<?php echo $name ?>">
		<?php foreach ( $options as $key => $option ) : ?>
			<option value="<?php echo $key; ?>"<?php if ( $value == $key ) echo ' selected="true"'; ?>><?php echo $option; ?></option>
		<?php endforeach ?>
	</select>
	<?php if ( isset($desc) ): ?>
		<small><?php echo $desc; ?></small>
	<?php endif; ?>
	<?php
		if ( isset($markup) ):
			echo $markup;
		endif;
	?>
</div>