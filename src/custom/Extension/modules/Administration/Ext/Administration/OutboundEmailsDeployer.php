<?php
$admin_option_defs = array();
$admin_option_defs['Administration']['outbound-emails-deployer'] = array(
    'Administration',
    'LBL_OUTBOUND_EMAILS_DEPLOYER_HEADER',
    'LBL_OUTBOUND_EMAILS_DEPLOYER_DESCRIPTION',
    'javascript:parent.SUGAR.App.router.navigate("OutboundEmailsDeployer", {trigger: true});',
);

$admin_group_header[] = array(
    'LBL_OUTBOUND_EMAILS_DEPLOYER_HEADER',
    '',
    false,
    $admin_option_defs, 
    'LBL_OUTBOUND_EMAILS_DEPLOYER_DESCRIPTION'
);
