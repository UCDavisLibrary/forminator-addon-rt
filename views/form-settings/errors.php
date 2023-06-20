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
