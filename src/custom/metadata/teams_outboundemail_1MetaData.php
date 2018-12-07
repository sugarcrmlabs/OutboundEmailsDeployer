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
      'join_table' => 'teams_outboundemail',
      'join_key_lhs' => 'team_id',
      'join_key_rhs' => 'outboundemail_id',
    ),
  ),
  'table' => 'teams_outboundemail',
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
    'team_id' => 
    array (
      'name' => 'team_id',
      'type' => 'id',
    ),
    'outboundemail_id' => 
    array (
      'name' => 'outboundemail_id',
      'type' => 'id',
    ),
  ),
  'indices' => 
  array (
    0 => 
    array (
      'name' => 'idx_teams_outbemail_pk',
      'type' => 'primary',
      'fields' => 
      array (
        0 => 'id',
      ),
    ),
    1 => 
    array (
      'name' => 'idx_teams_outbemail_team_del',
      'type' => 'index',
      'fields' => 
      array (
        0 => 'team_id',
        1 => 'deleted',
      ),
    ),
    2 => 
    array (
      'name' => 'idx_teams_outbemail_outb_del',
      'type' => 'index',
      'fields' => 
      array (
        0 => 'outboundemail_id',
        1 => 'deleted',
      ),
    ),
    3 => 
    array (
      'name' => 'teams_outboundemail_alt',
      'type' => 'alternate_key',
      'fields' => 
      array (
        0 => 'team_id',
        1 => 'outboundemail_id',
      ),
    ),
  ),
);
