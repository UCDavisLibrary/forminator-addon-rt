<?php

require_once dirname( __FILE__ ) . '/forminator-addon-rt-api.php';

class Forminator_Addon_Rt_Form_Hooks extends Forminator_Addon_Form_Hooks_Abstract {
  /**
	 * Forminator_Addon_Rt_Form_Hooks constructor.
	 *
	 * @since 1.0 Rt Addon
	 *
	 * @param Forminator_Addon_Abstract $addon
	 * @param                           $form_id
	 *
	 * @throws Forminator_Addon_Exception
	 */
	public function __construct( Forminator_Addon_Abstract $addon, $form_id ) {
		parent::__construct( $addon, $form_id );
		$this->_submit_form_error_message = __( 'Failed to create an RT ticket for your form submission! Please try again later.', 'forminator' );

    $rt_host = $this->addon->get_rt_host();
    $rt_secret= $this->addon->get_rt_secret();
    $form_settings = $this->form_settings_instance->get_form_settings_values();
    $queue = $form_settings['queue'];
    $this->rt_api = $this->addon->get_api($rt_host, $rt_secret, $queue);
    $this->form_settings = $form_settings;

	}

  public function on_form_submit( $submitted_data ) {
    $addon_slug = $this->addon->get_slug();
		$form_id = $this->form_id;
    $form = $this->custom_form;
    $form_fields = $this->form_settings_instance->get_form_fields();
    $is_success = true;

    // get custom fields
    $custom_field_registry = [];
    $custom_fields = [];
    if ( isset($this->form_settings['custom_fields']) && is_array($this->form_settings['custom_fields']) ) {
      foreach ( $this->form_settings['custom_fields'] as $field ) {
        if ( !empty($field['rt_field_name']) && !empty($field['form_field_id']) ){
          $custom_field_registry[$field['form_field_id']] = $field['rt_field_name'];
        }
      }
    }

    // combine submitted data with form fields
    $submitted_form = [];
    foreach ( $form_fields as $form_field ) {
      $element_id  = $form_field['element_id'];
			$field_type  = $form_field['type'];
			$field_label = $form_field['field_label'];

      // The postdata field type is used to create wordpress posts from form submissions.
      // We have no current use case for this functionality, so we will skip it until needed.
      if ( stripos( $field_type, 'postdata' ) !== false ) continue;

      if ( self::element_is_calculation( $element_id ) ) {
        $formula = forminator_addon_replace_custom_vars( $form_field['formula'], $submitted_data, $this->custom_form, [], false );
        $field_value = eval( "return {$formula};");
      } else {
        $field_value = forminator_addon_replace_custom_vars( '{' . $element_id . '}', $submitted_data, $this->custom_form, [], false );
      }
      $submitted_form[] = [
        'element_id' => $element_id,
        'field_type' => $field_type,
        'field_label' => $field_label,
        'field_value' => $field_value,
        'field_object' => $form_field
      ];

      if ( !empty($custom_field_registry[$element_id]) ){
        $custom_fields[$custom_field_registry[$element_id]] = $field_value;
      }

      while ( true ) {
        $next_field = $this->find_next_grouped_field($submitted_data, $form_field);
        if ( $next_field === null ) break;
        $next_field_number = $this->next_form_field[$element_id] - 1;
        $submitted_form[] = [
          'element_id' => $element_id . '-' . $next_field_number,
          'field_type' => $field_type,
          'field_label' => $field_label . ' (' . $next_field_number . ')',
          'field_value' => $next_field,
          'field_object' => $form_field
        ];
      }
    }

    try {
      # ticket subject
      $has_custom_subject = isset($this->form_settings['subject']) && !empty($this->form_settings['subject']);
      $ticket_subject = $has_custom_subject ? forminator_addon_replace_custom_vars( $this->form_settings['subject'], $submitted_data, $this->custom_form, [], false ) : "New Submission from {$form->name}";

      # additional body content
      $additional_body_content = [];
      if ( isset($this->form_settings['body_fields_ip']) && $this->form_settings['body_fields_ip'] == '1' ){
        $ip_address = Forminator_Geo::get_user_ip();
        $additional_body_content[] = [
          'field_label' => 'IP Address',
          'field_value' => $ip_address,
          'field_type' => 'text'
        ];
      }
      if ( isset($this->form_settings['body_fields_user_agent']) && $this->form_settings['body_fields_user_agent'] == '1' ){
        $additional_body_content[] = [
          'field_label' => 'User Agent',
          'field_value' => isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : '',
          'field_type' => 'text'
        ];
      }

      $data = [
        'Subject' => $ticket_subject,
        'Content' => $this->rt_api->formToContent( $submitted_form, $additional_body_content ),
        'Requestor' =>  $this->form_settings_instance->get_requestor_email( $submitted_form )
      ];
      if ( count($custom_fields) ){
        $data['CustomFields'] = $custom_fields;
      }
      $r = $this->rt_api->createTicket($data);
      $is_success = $this->rt_api->responseIsSuccess($r, 201);
      json_decode(wp_remote_retrieve_body($r), true);
    } catch (\Throwable $th) {
      $is_success = false;
      forminator_addon_maybe_log( __METHOD__, $th->getMessage() );
    }
    if ( $is_success === false ) {
      $is_success = $this->_submit_form_error_message;
    }

    return $is_success;
  }

  /**
   * @description - Find the next field in the form that is grouped with the given field, if any.
   * Is auto-incremented by 1 in the field id suffix
   */
  private function find_next_grouped_field($submitted_data, $form_field){
    $next_field_num = 2;
    if ( !isset($this->next_form_field) ){
      $this->next_form_field = [];
    } elseif ( isset($this->next_form_field[$form_field['element_id']]) ){
      $next_field_num = $this->next_form_field[$form_field['element_id']];
    }
    $next_field_id = $form_field['element_id'] . '-' . $next_field_num;
    if ( isset($submitted_data[$next_field_id]) ){
      $this->next_form_field[$form_field['element_id']] = $next_field_num + 1;
      return forminator_addon_replace_custom_vars($submitted_data[$next_field_id], $submitted_data, $this->custom_form, [], false );
    }
    return null;
  }

  /**
   * @description - Fires after a form is submitted and RT ticket is created.
   * We use this to add the RT ticket id to the entry meta data.
   * And to send any uploads as a separate as attachments in an comment to RT ticket.
   * Not ideal, but I couldn't figure out how to do it from the submission hook.
   */
  public function add_entry_fields( $submitted_data, $form_entry_fields = array(), $entry = null ) {
    $out = [];
    $entry_field = [
      'name'  => 'rt_ticket',
      'value' => '',
    ];

    // Save RT ticket id to entry meta data
    $lastTicket = $this->rt_api->getLastTicketCreated();
    if ( empty($lastTicket) ){
      return $out;
    } else {
      $entry_field['value'] = $lastTicket;
    }

    $uploads = $this->get_uploads( $form_entry_fields );
    if ( !count( $uploads ) ) {
      $out[] = $entry_field;
      return $out;
    }
    $upload_status = $this->handle_uploads( $uploads, $lastTicket );
    foreach ($upload_status['uploads'] as &$upload) {
      if ( isset($upload['file']) ) {
        unset($upload['file']);
      }
    }
    $entry_field['value']['uploadStatus'] = $upload_status;
    $out[] = $entry_field;
    return $out;
  }

  public function on_render_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {
    $addon_slug             = $this->addon->get_slug();
		$form_id                = $this->form_id;
		$form_settings_instance = $this->form_settings_instance;

    $entry_items = array();
    foreach ( $addon_meta_data as $meta ) {
      if ( 0 !== strpos( $meta['name'], 'rt_ticket' ) ) {
        continue;
      }

      if ( ! isset( $meta['value'] ) || ! is_array( $meta['value'] ) ) {
        continue;
      }

      $additional_entry_item = array(
        'label' => __( 'Request Tracker (RT) Integration', 'forminator' ),
        'value' => '',
      );

      $ticket = $meta['value'];
      $sub_entries = [];
      if ( isset( $ticket['id'] ) ) {
        $sub_entries[] = array(
          'label' => __( 'Ticket Id', 'forminator' ),
          'value' => $ticket['id']
        );
      }
      if ( isset( $ticket['_hyperlinks'] ) ) {
        foreach ( $ticket['_hyperlinks'] as $link ) {
          if ( $link['ref'] === 'self'){
            $sub_entries[] = array(
              'label' => __( 'Ticket API Url', 'forminator' ),
              'value' => '<a href="' . $link['_url'] . '" target="_blank">' . $link['_url'] . '</a>'
            );
          }
        }

      }
      if ( isset( $ticket['uploadStatus']['commentCreated']) ){
        $uploadStatus = $ticket['uploadStatus'];
        $sub_entries[] = array(
          'label' => __( 'Comment With Form Attachments', 'forminator' ),
          'value' => $uploadStatus['commentCreated'] ? 'Sent' : 'Failed',
        );
      }
      if ( count( $sub_entries ) ) {
        $additional_entry_item['sub_entries'] = $sub_entries;
        $entry_items[] = $additional_entry_item;
      }
    }

    return $entry_items;
  }

  public function on_export_render_title_row() {
		$export_headers = array(
			'ticket_id' => 'RT Ticket ID',
		);

    return $export_headers;
  }

  public function on_export_render_entry( Forminator_Form_Entry_Model $entry_model, $addon_meta_data ) {
    $ticket_id = '';
    foreach ( $addon_meta_data as $meta ) {
      if ( 0 !== strpos( $meta['name'], 'rt_ticket' ) ) {
        continue;
      }

      if ( ! isset( $meta['value'] ) || ! is_array( $meta['value'] ) ) {
        continue;
      }
      $ticket = $meta['value'];
      if ( isset( $ticket['id'] ) ) {
        $ticket_id = $ticket['id'];
      }
    }
    $export_columns = array(
			'ticket_id' => $ticket_id
		);

    return $export_columns;

  }

  /**
   * @description - Send any uploads as attachments in a comment to RT ticket.
   * @param $upload_urls - array of upload urls from form_entry_fields
   * @param $lastTicket - RT ticket response created from form submission
   */
  private function handle_uploads($upload_urls, $lastTicket){

    $out = [
      'error' => false,
      'commentCreated' => false,
      'uploads' => []
    ];

    $uploads = [];
    $attachments = [];
    $upload_dir = wp_upload_dir();

    foreach ( $upload_urls as $url ) {
      $path = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );
      $upload = [
        'url' => $url,
        'path' => $path,
        'file_exists' => false,
        'file_read' => false,
        'mime_type' => false,
        'attached' => false
      ];

      // check if file exists
      if ( ! file_exists( $path ) ) {
        $out['error'] = true;
        $uploads[] = $upload;
        continue;
      }
      $upload['file_exists'] = true;

      // read file
      $file = file_get_contents( $path );
      if ( $file === false ) {
        $out['error'] = true;
        continue;
      }
      $upload['file_read'] = true;
      $upload['file'] = base64_encode( $file );

      // get mime type
      $mime_type = mime_content_type( $path );
      if ( $mime_type === false ) {
        $out['error'] = true;
        continue;
      }
      $upload['mime_type'] = $mime_type;

      // add to attachments array to send to RT
      $attachments[] = [
        'FileName' => basename( $path ),
        'FileType' => $upload['mime_type'],
        'FileContent' => $upload['file']
      ];
      $upload['attached'] = true;
      $uploads[] = $upload;
    }
    $out['uploads'] = $uploads;

    // send attachments to RT
    $rt_payload = [
      'Subject' => 'Submission Attachments',
      'Attachments' => $attachments
    ];
    if ( $out['error'] ){
      $rt_payload['Content'] = $this->rt_api->formatFailedAttachments( array_filter( $uploads, function($upload){
        return !$upload['attached'];
      }));
      $rt_payload['ContentType'] = 'text/html';
    }
    $r = $this->rt_api->createComment( $lastTicket['id'], $rt_payload );
    $is_success = $this->rt_api->responseIsSuccess($r, 201);
    if ( $is_success ) {
      $out['commentCreated'] = true;
      return $out;
    }

    // failed to create comment
    // try sending again without attachments
    $out['error'] = true;
    $rt_payload['Content'] = $this->rt_api->formatFailedAttachments($uploads);
    $rt_payload['ContentType'] = 'text/html';
    $r = $this->rt_api->createComment( $lastTicket['id'], $rt_payload );
    $is_success = $this->rt_api->responseIsSuccess($r, 201);
    if ( $is_success ) {
      $out['commentCreated'] = true;
    }
    return $out;
  }

	/**
	 * Get uploads to be added as attachments
	 */
	private function get_uploads( $fields ) {
		$uploads = array();

		foreach ( $fields as $i => $val ) {
			if ( 0 === stripos( $val['name'], 'upload-' ) ) {
				if ( ! empty( $val['value'] ) ) {
					$file_url = $val['value']['file']['file_url'];

					if ( is_array( $file_url ) ) {
						foreach ( $file_url as $url ) {
							$uploads[] = $url;
						}
					} else {
						$uploads[] = $file_url;
					}
				}
			}
		}

		return $uploads;
	}
}
