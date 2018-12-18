<?php
$dictionary['OutboundEmail']['fields']['parentmailbox_id_c'] = [
  'name' => 'parentmailbox_id_c',
  'type' => 'id',
  'reportable' => 'false',
  'vname' => 'LBL_PARENTMAILBOX_ID_C',
];

$dictionary['OutboundEmail']['indices'][] = [
    'name' => 'oe_u_type_par_del_idx',
    'type' => 'index',
    'fields' => ['user_id', 'type', 'parentmailbox_id_c', 'deleted'],
];
