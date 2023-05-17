<?php
// defaults.
$vars = array(
	'error_message' => [],
  'queues' => [],
  'queues_selected' => [],
  'queues_selected_error' => ''
);
/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
  if ( $key === 'error_message' && ! is_array( $val ) ) {
    $val = [ $val ];
  }
	$vars[ $key ] = $val;
}
?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php
		/* translators: ... */
		echo esc_html( sprintf( __( 'Pick %1$s Queues', 'forminator' ), 'RT' ) );
		?>
	</h3>

  <p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Form editors will only be able to select from the queues that you pick.', 'forminator' ); ?></p>


  <?php if ( ! empty( $vars['error_message'] ) ) : ?>
    <div
      role="alert"
      class="sui-notice sui-notice-red sui-active"
      style="display: block; text-align: left;"
      aria-live="assertive"
    >

      <div class="sui-notice-content">

        <div class="sui-notice-message">

          <span class="sui-notice-icon sui-icon-info" aria-hidden="true"></span>

          <?php foreach ( $vars['error_message'] as $message ) : ?>
            <p><?php echo esc_html( $message ); ?></p>
          <?php endforeach; ?>

        </div>

      </div>

    </div>
  <?php endif; ?>

</div>

<form>
  <input type="hidden" name='dummy' style='display:none;'>
  <div class="sui-form-field <?php echo esc_attr( ! empty( $vars['queues_selected_error'] ) ? 'sui-form-field-error' : '' ); ?>">
		<label class="sui-label"><?php esc_html_e( 'Select From Queues Your API Key Has Access To:', 'forminator' ); ?></label>
		<select class="sui-select" multiple name="queues_selected[]" data-placeholder="<?php esc_html_e( 'Please Select At Least One Queue', 'forminator' ); ?>">
      <?php foreach ( $vars['queues'] as $queue ) : ?>
        <option value="<?php echo $queue['id']; ?>"
         <?php selected($queue['id'], array_search($queue['id'], $vars['queues_selected']) === false ? '' : $queue['id']);?>><?php echo $queue['Name']; ?></option>
      <?php endforeach; ?>
    </select>
		<?php if ( ! empty( $vars['queues_selected_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['queues_selected_error'] ); ?></span>
		<?php endif; ?>
	</div>

</form>
