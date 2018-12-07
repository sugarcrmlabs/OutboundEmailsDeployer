<?php

use Sugarcrm\Sugarcrm\custom\OutboundEmailsDeployer\OutboundEmailsDeployer;

class afterRelationshipDeleteTeams
{
    public function callAfterRelationshipDelete($b, $e, $a)
    {
        $GLOBALS['log']->fatal('triggered callAfterRelationshipDelete');
        if ($a['module'] == 'Teams' && $a['related_module'] == 'Users') {
            // TODO optimise execution only when needed
            $oed = new OutboundEmailsDeployer();
            $oed->deployCurrentMapping();
        }
    }
}
