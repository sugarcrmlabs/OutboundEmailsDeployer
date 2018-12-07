<?php
$viewdefs['Administration']['base']['layout']['outbound-emails-deployer'] = array(
    'name' => 'main-pane',
    'css_class' => 'main-pane row-fluid',
    'type' => 'simple',
    'span' => 12,
    'components' => array(
        array(
            'view' => 'outbound-emails-deployer-header',
        ),
        array(
            'view' => 'outbound-emails-deployer',
        ),
    ),
);
