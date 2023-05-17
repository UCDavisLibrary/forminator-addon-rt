<?php
// defaults.
$vars = array(
	'error_message' => [],
  'queues' => [],
  'queue' => [],
  'queue_error' => ''
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
		echo esc_html( sprintf( __( 'Pick a %1$s Queue', 'forminator' ), 'RT' ) );
		?>
	</h3>

  <p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'When the form is submitted, a ticket will be created in the following queue', 'forminator' ); ?></p>


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
  <div class="sui-form-field <?php echo esc_attr( ! empty( $vars['queue_error'] ) ? 'sui-form-field-error' : '' ); ?>">
		<label class="sui-label"><?php esc_html_e( 'Queue', 'forminator' ); ?></label>
		<select class="sui-select" name="queue">
      <option value=""><?php esc_html_e( 'Select a Queue', 'forminator' ); ?></option>
      <?php foreach ( $vars['queues'] as $queue ) : ?>
        <option value="<?php echo $queue['id']; ?>"
         <?php selected($queue['id'], $vars['queue']);?>><?php echo $queue['name']; ?></option>
      <?php endforeach; ?>
    </select>
		<?php if ( ! empty( $vars['queue_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['queue_error'] ); ?></span>
		<?php endif; ?>
	</div>

</form>
