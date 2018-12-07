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
        $oed = new OutboundEmailsDeployer();
        $oed->setAllowNonAdmin(true);
        $oed->deployCurrentMapping();

        return $returnValues;
    }
}
