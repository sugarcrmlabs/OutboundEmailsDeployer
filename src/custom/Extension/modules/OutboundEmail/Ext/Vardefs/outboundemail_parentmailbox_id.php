<?php
$dictionary['OutboundEmail']['fields']['parentmailbox_id'] = [
  'name' => 'parentmailbox_id',
  'type' => 'id',
  'reportable' => 'false',
  'vname' => 'LBL_PARENTMAILBOX_ID',
];

$dictionary['OutboundEmail']['indices'][] = [
    'name' => 'oe_type_par_del_idx',
    'type' => 'index',
    'fields' => ['type', 'parentmailbox_id', 'deleted'],
];
