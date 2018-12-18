<?php

use Sugarcrm\Sugarcrm\custom\OutboundEmailsDeployer\OutboundEmailsDeployer;

class afterSaveOutboundEmail
{
    public function callAfterSave($b, $e, $a)
    {
        if (\SugarBean::inOperation('outbound_emails_deployer_save') !== true && $b->type === 'user') {
            $oed = new OutboundEmailsDeployer();

            $mailboxId = $b->id;
            if (!empty($b->parendmailbox_id)) {
                $mailboxId = $b->parendmailbox_id;
            }

            if ($oed->countMailboxCopies($mailboxId) >= $oed->getUiRecordLimit()) {

                global $current_user;
                $user_id = false;
                if ($current_user->isAdmin()) {
                    $user_id = $current_user->id;
                }

                $oed->scheduleForBackgroundProcessing($user_id);
            } else {
                // sync
            $oed->setAllowNonAdmin(true);
                $oed->deployCurrentMapping(false, $mailboxId);
            }
        }
    }
}
