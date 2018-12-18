<?php

use Sugarcrm\Sugarcrm\custom\OutboundEmailsDeployer\OutboundEmailsDeployer;

class afterRelationshipDeleteTeams
{
    public function callAfterRelationshipDelete($b, $e, $a)
    {
        if ($a['module'] === 'Teams' && $a['related_module'] === 'Users') {
            $oed = new OutboundEmailsDeployer();

            // background only
            global $current_user;
            $user_id = false;
            if ($current_user->isAdmin()) {
                $user_id = $current_user->id;
            }

            $oed->scheduleForBackgroundProcessing($user_id);
        }
    }
}
