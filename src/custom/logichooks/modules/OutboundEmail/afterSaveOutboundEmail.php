<?php

use Sugarcrm\Sugarcrm\custom\OutboundEmailsDeployer\OutboundEmailsDeployer;

class afterSaveOutboundEmail
{
    public function callAfterSave($b, $e, $a)
    {
        if ($b->saveFromDeployer !== true && $b->type == 'user') {
            $oed = new OutboundEmailsDeployer();
            $oed->setAllowNonAdmin(true);
            $oed->deployCurrentMapping();
        }
    }
}
