<?php

/**
 * Class Forminator_Addon_Rt_Api
 *
 * @since 1.0 Rt Addon
 */
class Forminator_Addon_Rt_Api {

  public $host;
  public $secret;
  public $defaultQueue;
  public $path;
  public $_ticketCreationResponses;
  public $_commentCreationResponses;

  public function __construct($host, $secret, $defaultQueue=''){
    $this->host = trailingslashit($host);
    $this->defaultQueue = $defaultQueue;
    $this->secret = $secret;
    $this->path = 'REST/2.0';
    $this->_ticketCreationResponses = [];
    $this->_commentCreationResponses = [];
  }

  public function postTicketUrl(){
    return $this->host . $this->path . '/ticket';
  }

  public function postCommentUrl($id){
    return trailingslashit($this->postTicketUrl()) . $id . '/comment';
  }

  public function createTicket($data=[]){
    if ( !array_key_exists('Queue', $data) || empty($data['Queue']) ) {
      $data['Queue'] = $this->defaultQueue;
    }
    if ( !array_key_exists('ContentType', $data) || empty($data['ContentType']) ) {
      $data['ContentType'] = 'text/html';
    }

    $r = wp_remote_post(
      $this->postTicketUrl(),
      ['headers' => ['Authorization' => 'token ' . $this->secret, 'Content-Type' => 'application/json' ],
       'body' => wp_json_encode($data),
       'blocking'    => true,
       'data_format' => 'body'
      ]
    );
    $this->_ticketCreationResponses[] = $r;
    return $r;
  }

  public function createComment($ticket_id, $data ){

    if ( !array_key_exists('ContentType', $data) || empty($data['ContentType']) ) {
      $data['ContentType'] = 'text/html';
    }

    $r = wp_remote_post(
      $this->postCommentUrl($ticket_id),
      ['headers' => ['Authorization' => 'token ' . $this->secret, 'Content-Type' => 'application/json' ],
       'body' => wp_json_encode($data),
       'blocking'    => true,
       'data_format' => 'body'
      ]
    );
    if ( !isset($this->_commentCreationResponses[$ticket_id]) ) {
      $this->_commentCreationResponses[$ticket_id] = [];
    }
    $this->_commentCreationResponses[$ticket_id][] = $r;
    return $r;
  }

  public function getTicket($id){
    $url = trailingslashit($this->postTicketUrl()) . $id;
    $r = wp_remote_get(
      $url,
      ['headers' => ['Authorization' => 'token ' . $this->secret]]
    );
    return $r;
  }

  public function getLastTicketCreated($returnCreationResponse=false){
    if ( empty($this->_ticketCreationResponses) ) return null;
    $r = end($this->_ticketCreationResponses);
    if ( is_wp_error($r) || wp_remote_retrieve_response_code($r) != 201 ) return null;
    $decoded = json_decode(wp_remote_retrieve_body($r), true);
    if ( $returnCreationResponse ) {
      return $decoded;
    }
    try {
      $ticket = $this->getTicket($decoded['id']);
      if ( is_wp_error($ticket) ) {
        throw new \Exception("Error retrieving ticket {$decoded['id']}. Error: " . $ticket->get_error_message());
      }
      if ( wp_remote_retrieve_response_code($ticket) != 200 ) {
        throw new \Exception("Error retrieving ticket {$decoded['id']}. Response code: " . wp_remote_retrieve_response_code($ticket));
      }
      return json_decode(wp_remote_retrieve_body($ticket), true);
    } catch (\Throwable $th) {
      forminator_addon_maybe_log( __METHOD__, $th->getMessage() );
      return null;
    }
    return null;

  }

  public function formToContent($submitted_data, $prepend_data=[]){
    if ( !is_array($submitted_data) ) return '';
    if ( !is_array($prepend_data) ) $prepend_data = [];
    $submitted_data = array_merge($prepend_data, $submitted_data);
    $content = '';
    foreach($submitted_data as $field){
      $block_label = false;
      if ( $field['field_type'] == 'textarea' ){
        $field['field_value'] = nl2br($field['field_value']);
        $block_label = true;
      } else if ( $field['field_type'] == 'upload') {
        continue;
      }
      $content .= "<b>{$field['field_label']}</b>: ";
      if ( $block_label ) $content .= "<br>";
      $content .= "{$field['field_value']}<br>";
    }
    return $content;

  }

  public function formatFailedAttachments($uploads){
    $content = 'Failed to attach the following files to the ticket:<br><ul>';
    foreach($uploads as $upload){
      $content .= "<li>{$upload['url']}</li>";
    }
    $content .= '</ul>';
    return $content;
  }

  public function responseIsSuccess($r, $code=201){
    $is_success = true;
    if ( is_wp_error($r) ) {
      $is_success = false;
      forminator_addon_maybe_log( __METHOD__, $r->get_error_message() );
    } else if(wp_remote_retrieve_response_code($r) != $code){
      $is_success = false;
      forminator_addon_maybe_log( __METHOD__, wp_remote_retrieve_response_message($r) );
    }
    return $is_success;
  }
}
