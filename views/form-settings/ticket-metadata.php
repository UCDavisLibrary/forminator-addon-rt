<?php
  // defaults.
  $vars = array(
    'error_message' => [],
    'requestor' => '',
    'requestor_options' => [
      'wp-user' => 'Logged In WordPress User',
      'none' => 'No Requestor',
      'email' => 'Email Address (from form field)',
    ],
);
  foreach ( $template_vars as $key => $val ) {
    if ( $key === 'error_message' && ! is_array( $val ) ) {
      $val = [ $val ];
    }
    $vars[ $key ] = $val;
  }
?>

<div class="forminator-integration-popup__header">
  <h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
    <?php echo "Ticket Metadata";?>
  </h3>
  <p id="forminator-integration-popup__description" class="sui-description">
    <?php esc_html_e( 'Customize the properties posted to the RT API during ticket creation.', 'forminator' ); ?>
  </p>

  <?php include forminator_addon_rt_dir() . 'views/form-settings/errors.php'; ?>

</div>



<form>
  <div class="sui-form-field">
		<label class="sui-label"><?php esc_html_e( 'Requestor', 'forminator' ); ?></label>
		<select class="sui-select" name="requestor">
      <?php foreach ( $vars['requestor_options'] as $value => $label ) : ?>
        <option value="<?php echo esc_attr( $value ); ?>" <?php selected( $vars['requestor'], $value ); ?>>
          <?php echo esc_html( $label ); ?>
        </option>
      <?php endforeach; ?>
    </select>
	</div>

</form>
