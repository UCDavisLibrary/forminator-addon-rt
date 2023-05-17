<?php
// defaults.
$vars = array(
	'error_message'       => '',
	'rt_host'           => '',
	'rt_secret'       => '',
	'rt_secret' => '',
	'rt_host_error'     => '',
);
/** @var array $template_vars */
foreach ( $template_vars as $key => $val ) {
	$vars[ $key ] = $val;
}
?>

<div class="forminator-integration-popup__header">

	<h3 id="forminator-integration-popup__title" class="sui-box-title sui-lg" style="overflow: initial; white-space: normal; text-overflow: initial;">
		<?php
		/* translators: ... */
		echo esc_html( sprintf( __( 'Set Up %1$s Connection', 'forminator' ), 'RT' ) );
		?>
	</h3>

  <p id="forminator-integration-popup__description" class="sui-description"><?php esc_html_e( 'Set up RT to be used by Forminator so that tickets can be created on form submission.', 'forminator' ); ?></p>

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

          <p><?php echo esc_html( $vars['error_message'] ); ?></p>

        </div>

      </div>

    </div>
  <?php endif; ?>

</div>

<form>

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['rt_host_error'] ) ? 'sui-form-field-error' : '' ); ?>">
		<label class="sui-label"><?php esc_html_e( 'RT Host Domain', 'forminator' ); ?></label>
		<input
				class="sui-form-control"
				name="rt_host" placeholder="<?php echo esc_attr( __( 'https://yourRtServer.com', 'forminator' ) ); ?>"
				value="<?php echo esc_attr( $vars['rt_host'] ); ?>">
		<?php if ( ! empty( $vars['rt_host_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['rt_host_error'] ); ?></span>
		<?php endif; ?>
	</div>

	<div class="sui-form-field <?php echo esc_attr( ! empty( $vars['rt_secret_error'] ) ? 'sui-form-field-error' : '' ); ?>">
		<label class="sui-label"><?php esc_html_e( 'RT Secret', 'forminator' ); ?></label>
		<input
				class="sui-form-control"
				name="rt_secret" placeholder="<?php echo esc_attr( __( 'A secret API Key', 'forminator' ) ); ?>"
				value="<?php echo esc_attr( $vars['rt_secret'] ); ?>">
		<?php if ( ! empty( $vars['rt_secret_error'] ) ) : ?>
			<span class="sui-error-message"><?php echo esc_html( $vars['rt_secret_error'] ); ?></span>
		<?php endif; ?>
	</div>

</form>
