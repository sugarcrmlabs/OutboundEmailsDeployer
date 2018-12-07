<?php

$hook_array['after_save'][] = array(
    1,
    'OutboundEmail after save hook',
    'custom/logichooks/modules/OutboundEmail/afterSaveOutboundEmail.php',
    'afterSaveOutboundEmail', 
    'callAfterSave'
);
