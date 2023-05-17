<?php
// defaults.
$vars = array(
	'error_message' => [],
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
		<?php esc_html_e( 'Success', 'forminator' ); ?>
	</h3>

  <p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'RT has been successfully integrated!', 'forminator' ); ?></p>


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
  <p></p>
</div>
