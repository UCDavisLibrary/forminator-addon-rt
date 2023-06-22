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

	}

  public function on_form_submit( $submitted_data ) {
    $addon_slug = $this->addon->get_slug();
		$form_id = $this->form_id;
    $form = $this->custom_form;
    $form_fields = $this->form_settings_instance->get_form_fields();
    $is_success = true;
    set_transient( 'forminator_addon_rt_form_submitted_data', $submitted_data);

    // combine submitted data with form fields
    $submitted_form = [];
    foreach ( $form_fields as $form_field ) {
      $element_id  = $form_field['element_id'];
			$field_type  = $form_field['type'];
			$field_label = $form_field['field_label'];

      // todo: figure this out. The slack addon does some special handingling of postdata fields
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
    }

    try {
      set_transient( 'forminator_addon_rt_form_submit', $submitted_form );
      $ticket_subject = "New Submission from {$form->name}";
      $data = [
        'Subject' => $ticket_subject,
        'Content' => $this->rt_api->formToContent( $submitted_form ),
        'Requestor' =>  $this->form_settings_instance->get_requestor_email( $submitted_form )
      ];
      $r = $this->rt_api->createTicket($data);
      if ( is_wp_error($r) ) {
        $is_success = false;
        forminator_addon_maybe_log( __METHOD__, $r->get_error_message() );
      } else if(wp_remote_retrieve_response_code($r) != 201){
        $is_success = false;
        forminator_addon_maybe_log( __METHOD__, wp_remote_retrieve_response_message($r) );
      }
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
   * @description - Fires after a form is submitted and RT ticket is created.
   * We use this to add the RT ticket id to the entry meta data.
   * And to send any uploads as a separate as attachments in an comment to RT ticket.
   * Not ideal, but I couldn't figure out how to do it from the submission hook.
   */
  public function add_entry_fields( $submitted_data, $form_entry_fields = array(), $entry = null ) {
    $out = [];

    // Save RT ticket id to entry meta data
    $lastTicket = $this->rt_api->getLastTicketCreated();
    if ( empty($lastTicket) ){
      return $out;
    } else {
      $out[] = [
        'name'  => 'rt_ticket',
        'value' => $lastTicket,
      ];
    }

    $uploads = $this->get_uploads( $form_entry_fields );
    if ( !count( $uploads ) ) {
      return $out;
    }
    $upload_status = $this->handle_uploads( $uploads );
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

  private function handle_uploads($uploads){
    // convert urls to file paths
    $upload_paths = [];
    $upload_dir = wp_upload_dir();
    foreach ( $uploads as $url ) {
      $upload_paths[] = str_replace( $upload_dir['baseurl'], $upload_dir['basedir'], $url );
    }
    set_transient( 'forminator_addon_rt_form_upload_paths', $upload_paths );
    /**
     * Hey Steve,
     *
     * Pick up here when you are back from vacation.
     * You will want to:
     * - check if the uploads are files that exist
     * - base64 encode the files
     * - send them to RT as attachments in a comment
     * - If the attachments arent there, or base64 encoding fails, then send urls in comment
     * - If sending comment fails, then make a new entry in the form entry.
     * - in fact, make a new entry for any status.
     */
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
