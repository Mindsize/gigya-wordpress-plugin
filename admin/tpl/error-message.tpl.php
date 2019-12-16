<?php
/**
 * Template -  markup error message for Gigya settings pages.
 * Render with @see _gigya_render_tpl().
 */

?>

<div
	<?php if ( isset( $attrs ) ) {
		foreach ( $attrs as $attr => $parent_attr_value ) {
			echo $attr . '="' . $parent_attr_value . '"' . PHP_EOL;
		}
	}; ?>>
	<p <?php if ( isset( $p_attrs ) ) {
		foreach ( $p_attrs as $attr => $parent_attr_value ) {
			echo $attr . '="' . $parent_attr_value . '"' . PHP_EOL;
		}
	}; ?>>
		<?php if ( is_array( $error_message ) ): ?>
			<?php foreach ( $error_message as $key => $str_line ): ?>
				<?php if ( $key === 0 ) {
					echo '<strong> ' . __( $str_line ) . '  </strong>';
				} else {
					echo '<br>' . __( $str_line );
				}
				?>
			<?php endforeach ?>
		<?php else: ?>
			<?php
			echo '<strong>' . __( $error_message ) . '</strong>'; ?>
		<?php endif ?>
	</p>
	<button type="button" class="notice-dismiss gigya-hide-notice-error-message"><span class="screen-reader-text">Dismiss this notice.</span>
	</button>
</div>
