<?php
// created: 2018-11-09 17:43:47
$dictionary["teams_outboundemail_1"] = array (
  'true_relationship_type' => 'many-to-many',
  'from_studio' => true,
  'relationships' => 
  array (
    'teams_outboundemail_1' => 
    array (
      'lhs_module' => 'Teams',
      'lhs_table' => 'teams',
      'lhs_key' => 'id',
      'rhs_module' => 'OutboundEmail',
      'rhs_table' => 'outbound_email',
      'rhs_key' => 'id',
      'relationship_type' => 'many-to-many',
      'join_table' => 'teams_outboundemail_1_c',
      'join_key_lhs' => 'teams_outboundemail_1teams_ida',
      'join_key_rhs' => 'teams_outboundemail_1outboundemail_idb',
    ),
  ),
  'table' => 'teams_outboundemail_1_c',
  'fields' => 
  array (
    'id' => 
    array (
      'name' => 'id',
      'type' => 'id',
    ),
    'date_modified' => 
    array (
      'name' => 'date_modified',
      'type' => 'datetime',
    ),
    'deleted' => 
    array (
      'name' => 'deleted',
      'type' => 'bool',
      'default' => 0,
    ),
    'teams_outboundemail_1teams_ida' => 
    array (
      'name' => 'teams_outboundemail_1teams_ida',
      'type' => 'id',
    ),
    'teams_outboundemail_1outboundemail_idb' => 
    array (
      'name' => 'teams_outboundemail_1outboundemail_idb',
      'type' => 'id',
    ),
  ),
  'indices' => 
  array (
    0 => 
    array (
      'name' => 'idx_teams_outboundemail_1_pk',
      'type' => 'primary',
      'fields' => 
      array (
        0 => 'id',
      ),
    ),
    1 => 
    array (
      'name' => 'idx_teams_outboundemail_1_ida1_deleted',
      'type' => 'index',
      'fields' => 
      array (
        0 => 'teams_outboundemail_1teams_ida',
        1 => 'deleted',
      ),
    ),
    2 => 
    array (
      'name' => 'idx_teams_outboundemail_1_idb2_deleted',
      'type' => 'index',
      'fields' => 
      array (
        0 => 'teams_outboundemail_1outboundemail_idb',
        1 => 'deleted',
      ),
    ),
    3 => 
    array (
      'name' => 'teams_outboundemail_1_alt',
      'type' => 'alternate_key',
      'fields' => 
      array (
        0 => 'teams_outboundemail_1teams_ida',
        1 => 'teams_outboundemail_1outboundemail_idb',
      ),
    ),
  ),
);