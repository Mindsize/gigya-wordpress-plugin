<?php
/**
 * Template - Text-field form element for Gigya settings pages.
 * Render with @see _gigya_render_tpl().
 */
?>
<div class="gigya-form-field row password-field <?php echo (isset($class)) ? $class : ''; ?>">
	<label for="gigya_<?php echo $id; ?>"><?php echo $label; ?></label>
	<input type="password" size="60" class="input" value="<?php echo $value; ?>" id="gigya_<?php echo $id; ?>" name="<?php echo $name ?>" />
	<?php if ( ! empty( $desc ) ): ?>
		<small><?php echo $desc; ?></small>
	<?php endif; ?>
	<?php
		if ( isset($markup) ):
			echo $markup;
		endif;
	?>
</div>