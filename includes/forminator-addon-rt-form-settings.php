<?php

/**
 * @description Displays wizard for setting up RT integration for a given form.
 */
class Forminator_Addon_Rt_Form_Settings extends Forminator_Addon_Form_Settings_Abstract {
  protected $addon;

  public function __construct( Forminator_Addon_Abstract $addon, $form_id ) {
    parent::__construct( $addon, $form_id );
  }

  /**
   * @description Set what displays in the form settings modal and in what order.
   */
  public function form_settings_wizards() {
		// numerical array steps.
		return array(
			array(
				'callback'     => array( $this, 'pick_queue' ),
				'is_completed' => array( $this, 'pick_queue_is_completed' ),
			),
      array(
        'callback'     => array( $this, 'ticket_metadata' ),
        'is_completed' => array( $this, 'metadata_is_completed' ),
      ),
		);
	}

  /**
   * @description Displays page for picking an RT queue to create a ticket in.
   */
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
        $this->save_form_settings_values(
          array_merge(
            $settings,
            array(
              'queue' => $submitted_data['queue'],
            )
          )
        );
      }
    }

    if ( $this->is_form_settings_complete() ){
      $buttons['disconnect']['markup'] = Forminator_Addon_Abstract::get_button_markup(
				esc_html__( 'Deactivate', 'forminator' ),
				'sui-button-ghost sui-tooltip sui-tooltip-top-center forminator-addon-form-disconnect',
				esc_html__( 'Deactivate this Rt Integration from this Form.', 'forminator' )
			);
    }
    $buttons['next']['markup'] = '<div class="sui-actions-right">' .
    Forminator_Addon_Abstract::get_button_markup( esc_html__( 'Next', 'forminator' ), 'forminator-addon-next' ) .
    '</div>';

    return array(
			'html'       => Forminator_Addon_Abstract::get_template( $template, $template_params ),
			'buttons'    => $buttons,
			'redirect'   => false,
			'has_errors' => $has_errors
		);

  }

  /**
   * @description Displays wizard page for setting up RT ticket metadata.
   */
  public function ticket_metadata($submitted_data){
    $template = forminator_addon_rt_dir() . 'views/form-settings/ticket-metadata.php';
    $is_submit  = ! empty( $submitted_data );
    $settings = $this->get_form_settings_values();
    $has_errors = false;
    $buttons = [];
    $is_close = false;
    $template_params = array(
      'requestor' => isset( $settings['requestor'] ) ? $settings['requestor'] : 'wp-user',
      'subject' => isset( $settings['subject'] ) ? $settings['subject'] : '',
      'body_fields_ip' => isset( $settings['body_fields_ip'] ) &&  $settings['body_fields_ip'] == '1' ? 1 : 0,
      'body_fields_user_agent' => isset( $settings['body_fields_user_agent'] ) && $settings['body_fields_user_agent'] == '1' ? 1 : 0,
      'body_fields_user_name' => isset( $settings['body_fields_user_name'] ) && $settings['body_fields_user_name'] == '1' ? 1 : 0
    );
    $custom_fields = [];
    if ( isset( $settings['custom_fields'] ) && is_array( $settings['custom_fields'] ) ) {
      foreach ( $settings['custom_fields'] as $field ) {
        $custom_fields[] = array(
          'rt_field_name' => isset( $field['rt_field_name'] ) ? $field['rt_field_name'] : '',
          'form_field_id' => isset( $field['form_field_id'] ) ? $field['form_field_id'] : ''
        );
      }
    }
    if ( count($custom_fields) ){
      $template_params['custom_fields'] = $custom_fields;
    }

    if ( $is_submit ) {
      $custom_fields = [];
      if ( $submitted_data['custom-field-name'] && $submitted_data['custom-field-value'] ){
        $name = is_array($submitted_data['custom-field-name']) ? $submitted_data['custom-field-name'] : [$submitted_data['custom-field-name']];
        $value = is_array($submitted_data['custom-field-value']) ? $submitted_data['custom-field-value'] : [$submitted_data['custom-field-value']];
        $zippedFields = array_map(null, $name, $value);
        foreach ( $zippedFields as $field ) {
          if ( count($field) != 2 ){
            continue;
          }
          $custom_fields[] = array(
            'rt_field_name' => $field[0],
            'form_field_id' => $field[1]
          );
        }
      }
      $this->save_form_settings_values(
        array_merge(
          $settings,
          array(
            'requestor' => $submitted_data['requestor'],
            'subject' => $submitted_data['subject'],
            'custom_fields' => $custom_fields,
            'body_fields_ip' => isset( $submitted_data['body-fields-ip'] ) ? 1 : 0,
            'body_fields_user_agent' => isset( $submitted_data['body-fields-user-agent'] ) ? 1 : 0,
            'body_fields_user_name' => isset( $submitted_data['body-fields-user-name'] ) ? 1 : 0
          )
        )
      );
      $is_close = true;
    }

    $buttons['next']['markup'] = '<div class="sui-actions-right">' .
    Forminator_Addon_Abstract::get_button_markup( esc_html__( 'CONNECT', 'forminator' ), 'forminator-addon-next' ) .
    '</div>';
    return array(
      'html'       => Forminator_Addon_Abstract::get_template( $template, $template_params ),
      'buttons'    => $buttons,
      'redirect'   => false,
      'has_errors' => $has_errors,
      'has_back'   => true,
      'is_close'   => $is_close
		);
  }

  public function pick_queue_is_completed(){
    $settings = $this->get_form_settings_values();
    return array_key_exists('queue', $settings) && !empty($settings['queue']);
  }

  public function metadata_is_completed(){
    $settings = $this->get_form_settings_values();
    $has_requestor = array_key_exists('requestor', $settings) && !empty($settings['requestor']);
    return $has_requestor;
  }

  public function is_form_settings_complete(){
    return $this->pick_queue_is_completed() && $this->metadata_is_completed();
  }

  public function get_queue(){
    $settings = $this->get_form_settings_values();
    $queue = '';
    if ( array_key_exists('queue', $settings) && !empty($settings['queue']) ){
      $queue = $settings['queue'];
    }
    return $queue;
  }

  /**
   * @description Get the email address of the RT requestor for this form submission.
   */
  public function get_requestor_email($submitted_form=null){
    $settings = $this->get_form_settings_values();
    $email = '';
    if ( !array_key_exists('requestor', $settings) || empty($settings['requestor']) ){
      return $email;
    }
    $requestor = $settings['requestor'];
    if ( $requestor == 'wp-user' ){
      $user = wp_get_current_user();
      if ( $user && $user->exists() && is_email($user->user_email) ) {
        $email = $user->user_email;
      }
    } else if ( $requestor == 'email' && !empty($submitted_form) ){
      foreach ( $submitted_form as $field ) {
        if ( $field['field_type'] == 'email' && is_email($field['field_value'])){
          $email = $field['field_value'];
          break;
        }
      }
    }
    return $email;
  }

}
