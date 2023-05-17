<?php

class Forminator_Addon_Rt_Form_Settings extends Forminator_Addon_Form_Settings_Abstract {
  protected $addon;

  public function __construct( Forminator_Addon_Abstract $addon, $form_id ) {
    parent::__construct( $addon, $form_id );
  }

  public function form_settings_wizards() {
		// numerical array steps.
		return array(
			array(
				'callback'     => array( $this, 'pick_queue' ),
				'is_completed' => array( $this, 'pick_queue_is_completed' ),
			),
		);
	}

  public function pick_queue($submitted_data){
    $template = forminator_addon_rt_dir() . 'views/form-settings/pick-queue.php';
    $settings = $this->get_form_settings_values();
    $has_errors = false;
    $buttons = [];
    $is_close = false;
    $template_params = array(
			'queue' => isset( $settings['queue'] ) ? $settings['queue'] : '',
			'queue_error' => '',
			'queues' => $this->addon->get_queues_selected(),
		);

    $is_submit  = ! empty( $submitted_data );
    if ( $is_submit ) {
      if ( empty( isset($submitted_data['queue']) ? $submitted_data['queue'] : '' ) ) {
				$template_params['queue_error'] = __( 'Please select a queue', 'forminator' );
				$has_errors = true;
			} else {
        $this->save_form_settings_values([
          'queue' => $submitted_data['queue']
        ]);
        $is_close = true;
      }
    }

    if ( $this->pick_queue_is_completed() ){
      $buttons['disconnect']['markup'] = Forminator_Addon_Abstract::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this Rt Integration from this Form.', 'forminator' )
			);
    }
    $buttons['next']['markup'] = '<div class="sui-actions-right">' .
    Forminator_Addon_Abstract::get_button_markup( esc_html__( 'CONNECT', 'forminator' ), 'forminator-addon-next' ) .
    '</div>';

    return array(
			'html'       => Forminator_Addon_Abstract::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => $has_errors,
      'is_close'   => $is_close,
		);

  }

  public function pick_queue_is_completed(){
    $settings = $this->get_form_settings_values();
    return array_key_exists('queue', $settings) && !empty($settings['queue']);
  }

  public function is_form_settings_complete(){
    return $this->pick_queue_is_completed();
  }

  public function get_queue(){
    $settings = $this->get_form_settings_values();
    $queue = '';
    if ( array_key_exists('queue', $settings) && !empty($settings['queue']) ){
      $queue = $settings['queue'];
    }
    return $queue;
  }

}
