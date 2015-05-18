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
  $maximum_membership_count = 675;
  $invited_members = 0;

  require_once 'api/class.api.php';

  $api = new civicrm_api3();

  $params = array();

  $params['version'] = 3;
  $params['sequential'] = 1;
  $params['is_reserved'] = 1;
  $params['group_type'] = 2;
  $params['parents'] = 9;
  $params['title'] = 'Invitation Sent ' . date('Y-m-d');

  if (!$api->Group->Create($params)) {
    throw new API_Exception("Error creating invitation group " . print_r($api->errorMsg(), true));
  } 

  unset($params);
  $params['version'] = 3;
  $params['sequential'] = 1;
  $params['status_id'] = array('IN' => array(1, 2, 8));

  if ($api->Membership->Getcount($params)) {
    $current_membership = $api->lastResult;
 var_dump($current_membership);
  } else {
    throw new API_Exception("Error retrieving current membership count " . print_r($api->errorMsg(), true));
  } 


  unset($params);
  $params['version'] = 3;
  $params['sequential'] = 1;
  $params['group_id'] = 9;
  $params['options'] = array('sort' => 'id', 'limit' => 0);

  if ($api->GroupContact->Get($params)) {
    $waiting_list = $api->lastResult->values;
    $count = $api->lastResult->count;

    print $count . " in waiting list\n";
  } else {
    throw new API_Exception("Error retrieving Waiting List " . print_r($api->errorMsg(), true));
  } 

  $returnValues = array('invited_members' => $invited_members);

  return civicrm_api3_create_success($returnValues, $params, 'WaitingList', 'InviteMembers');
}

