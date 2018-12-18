<?php

use Sugarcrm\Sugarcrm\custom\OutboundEmailsDeployer\OutboundEmailsDeployer;

class CustomOutboundEmailApi extends OutboundEmailApi
{
    public function registerApiRest()
    {
        return [
            'delete' => [
                'reqType' => 'DELETE',
                'path' => ['OutboundEmail','?'],
                'pathVars' => ['module','record'],
                'method' => 'deleteRecord',
                'shortHelp' => 'This method deletes a record of the specified type',
                'longHelp' => 'include/api/help/module_record_delete_help.html',
            ],
        ];
    }

    public function deleteRecord(ServiceBase $api, array $args)
    {
        $returnValues = parent::deleteRecord($api, $args);

        // trigger mailboxes rebuild
        global $current_user;
        $oed = new OutboundEmailsDeployer();

        global $current_user;
        $user_id = false;
        if ($current_user->isAdmin()) {
            $user_id = $current_user->id;
        }

        $oed->scheduleForBackgroundProcessing($user_id);

        return $returnValues;
    }
}
