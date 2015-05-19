<?php

/**
 * WaitingList.InviteMembers API specification (optional)
 * This is used for documentation and validation.
 *
 * @param array $spec description of fields supported by this API call
 * @return void
 * @see http://wiki.civicrm.org/confluence/display/CRM/API+Architecture+Standards
 */
function _civicrm_api3_waiting_list_InviteMembers_spec(&$spec) {
//  $spec['magicword']['api.required'] = 1;
}

/**
 * WaitingList.InviteMembers API
 *
 * @param array $params
 * @return array API result descriptor
 * @see civicrm_api3_create_success
 * @see civicrm_api3_create_error
 * @throws API_Exception
 */
function civicrm_api3_waiting_list_InviteMembers($params) {
  $waiting_list_group = 9;
  $maximum_membership_count = 675;
  $invited_members = 0;
  $date = date('Y-m-d H:i:s');
  $mailing_name = 'Waiting List Invitation ' . $date;
  $msg_template_id = 74;

  require_once 'api/class.api.php';

  $api = new civicrm_api3();

  $params = array();

  $params['version'] = 3;
  $params['sequential'] = 1;
  $params['is_reserved'] = 1;
  $params['group_type'] = 2;
  $params['parents'] = $waiting_list_group;
  $params['title'] = $mailing_name;

  if (!$api->Group->Create($params)) {
    throw new API_Exception("Error creating invitation group " . print_r($api->errorMsg(), true));
  } 

  $mailing_group_id = $api->lastResult->id;

  unset($params);
  $params['version'] = 3;
  $params['sequential'] = 1;
  $params['status_id'] = array('IN' => array(1, 2, 8));

  if ($api->Membership->Getcount($params)) {
    $current_membership_count = $api->lastResult;
 var_dump($current_membership_count);
  } else {
    throw new API_Exception("Error retrieving current membership count " . print_r($api->errorMsg(), true));
  } 

  unset($params);
  $params['version'] = 3;
  $params['sequential'] = 1;
  $params['group_id'] = $waiting_list_group;
  $params['options'] = array('sort' => 'id', 'limit' => 0);

  if ($api->GroupContact->Get($params)) {
    $waiting_list = $api->lastResult->values;
    $count = $api->lastResult->count;

    if ($current_membership_count < $maximum_membership_count) {
      for ($i=0; $i<$count; $i++) {
        if ($current_membership_count >= $maximum_membership_count) {
          break;
        }

        waiting_list_move_contact($mailing_group_id, $waiting_list[$i]->id, $waiting_list[$i]->contact_id);

        $current_membership_count++;
        $invited_members++;
      }
      waiting_list_create_mailing($mailing_group_id, $mailing_name, $msg_template_id, $date);
    }
  } else {
    throw new API_Exception("Error retrieving Waiting List " . print_r($api->errorMsg(), true));
  } 

  $returnValues = array('invited_members' => $invited_members);

  return civicrm_api3_create_success($returnValues, $params, 'WaitingList', 'InviteMembers');
}

function waiting_list_move_contact($mailing_group_id, $group_contact_id, $contact_id) {
  $waiting_list_api = new civicrm_api3();

  $waiting_list_params = array();

  $waiting_list_params['version'] = 3;
  $waiting_list_params['contact_id'] = $contact_id;
  $waiting_list_params['group_id'] = $mailing_group_id;

  if (!$waiting_list_api->GroupContact->Create($waiting_list_params)) {
    throw new API_Exception("Error moving Contact to group " . $mailing_group_id . " using group_contact.id " . $group_contact_id . print_r($waiting_list_api->errorMsg(), true));
  }

  // @todo add delete
}

function waiting_list_create_mailing($mailing_group_id, $mailing_name, $msg_template_id, $date) {
  $waiting_list_mailing_api = new civicrm_api3();

  $waiting_list_mailing_params = array();
  $waiting_list_mailing_params['version'] = 3;
  $waiting_list_mailing_params['id'] = $msg_template_id;

  if ($waiting_list_mailing_api->MessageTemplate->Getsingle($waiting_list_mailing_params)) {
    $message_template = $waiting_list_mailing_api->lastResult;
  } else {
    throw new API_Exception("Error retrieving message template " . print_r($waiting_list_mailing_api->errorMsg(), true));
  }

  unset($waiting_list_mailing_params);

  $waiting_list_mailing_params['version']            = 3;
  $waiting_list_mailing_params['created_id']         = 237;
  $waiting_list_mailing_params['created_date']       = $date;
  $waiting_list_mailing_params['scheduled_id']       = 237;
  $waiting_list_mailing_params['scheduled_date']     = $date;
  $waiting_list_mailing_params['approver_id']        = 237;
  $waiting_list_mailing_params['approval_date']      = $date;
  $waiting_list_mailing_params['approval_status_id'] = 1;
  $waiting_list_mailing_params['subject']            = $message_template->msg_subject;
  $waiting_list_mailing_params['name']               = $mailing_name;
  $waiting_list_mailing_params['body_html']          = $message_template->msg_html;
  $waiting_list_mailing_params['body_text']          = $message_template->msg_text;
  $waiting_list_mailing_params['groups']             = array( 'include' => array(13), 'exclude' => array() );
  // @todo enable correct list 
//  $waiting_list_mailing_params['groups']             = array( 'include' => array($mailing_group_id), 'exclude' => array() );
  $waiting_list_mailing_params['reply_id']           = 8;
  $waiting_list_mailing_params['unsubscribe_id']     = 5;
  $waiting_list_mailing_params['optout_id']          = 7;
  $waiting_list_mailing_params['resubscribe_id']     = 6;
  $waiting_list_mailing_params['footer_id']          = 2;
  $waiting_list_mailing_params['open_tracking']      = 1;
  $waiting_list_mailing_params['dedupe_email']       = 1;

  if (!$waiting_list_mailing_api->Mailing->Create($waiting_list_mailing_params)) {
    throw new API_Exception("Error creating mailing " . print_r($waiting_list_mailing_api->errorMsg(), true));
  }
}