<?php
  // defaults.
  $vars = array(
    'error_message' => [],
    'subject' => '',
    'requestor' => '',
    'requestor_options' => [
      'wp-user' => 'Logged In WordPress User',
      'none' => 'No Requestor',
      'email' => 'Email Address (from form field)',
    ],
    'custom_fields' => [[
      'rt_field_name' => '',
      'form_field_id' => ''
    ]]
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
		<label class="sui-label"><?php esc_html_e( 'Subject', 'forminator' ); ?></label>
    <input
      class="sui-form-control"
      name="subject" placeholder="<?php echo esc_attr( __( 'Ticket Subject', 'forminator' ) ); ?>"
      value="<?php echo esc_attr( $vars['subject'] ); ?>">
	</div>
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
  <div class="sui-form-field">
		<label class="sui-label"><?php esc_html_e( 'Custom Fields', 'forminator' ); ?></label>
    <div class='rt-custom-fields'>
    <?php foreach ( $vars['custom_fields'] as $field ) : ?>
      <div class='rt-custom-field' style="margin-bottom:1rem;display:flex;align-items:center;flex-wrap:wrap;">
        <div style="flex-grow:1;margin-right:1rem;">
          <input
            style="margin-bottom:0.5rem;"
            class="sui-form-control"
            name="custom-field-name[]" placeholder="<?php echo esc_attr( __( 'RT Custom Field Name', 'forminator' ) ); ?>"
            value="<?php echo esc_attr( $field['rt_field_name'] ); ?>">
          <input
            style="margin-bottom:0.5rem;"
            class="sui-form-control"
            name="custom-field-value[]" placeholder="<?php echo esc_attr( __( 'Form Field ID', 'forminator' ) ); ?>"
            value="<?php echo esc_attr( $field['form_field_id'] ); ?>">
        </div>
        <button class="sui-button sui-button-ghost remove-rt-custom-field" type="button">Remove</button>
      </div>
    <?php endforeach; ?>
    </div>
    <div class='sui-actions-right'>
      <button class="sui-button add-rt-custom-field" type="button">Add Custom Field Mapping</button>
    </div>
    <script>
      jQuery(document).ready(function($) {
        $('.add-rt-custom-field').click(function() {
          const n = $('.rt-custom-field:last').clone(true);
          n.find('input').val('');
          n.appendTo('.rt-custom-fields');
        });
        $('.remove-rt-custom-field').click(function() {
          // clear inputs if first in less, else remove
          if ($('.rt-custom-field').length > 1) {
            $(this).parent().remove();
          } else {
            $(this).parent().find('input').val('');
          }
        });
      });
    </script>
	</div>

</form>
