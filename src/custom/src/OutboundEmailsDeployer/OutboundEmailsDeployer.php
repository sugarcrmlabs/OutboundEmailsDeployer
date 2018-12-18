<?php

// Enrico Simonetti
// 2018-11-09

namespace Sugarcrm\Sugarcrm\custom\OutboundEmailsDeployer;
use Sugarcrm\Sugarcrm\Security\Crypto\Blowfish;

class OutboundEmailsDeployer extends \OutboundEmail
{
    protected $fields = [
        'type',
        'email_address_id',
        'mail_sendtype',
        'mail_smtptype',
        'mail_smtpserver',
        'mail_smtpport',
        'mail_smtpuser',
        'mail_smtppass',
        'mail_smtpauth_req',
        'mail_smtpssl',
    ];

    protected $fieldsToDecode = [
        'mail_smtppass'
    ];

    protected $allowNonAdmin = false;

    protected function enforcePermissions()
    {
        global $current_user, $app_strings;
        if (empty($current_user) || (!$current_user->isAdmin() && !$this->allowNonAdmin)) {
            throw new SugarApiExceptionNotAuthorized($app_strings['EXCEPTION_NOT_AUTHORIZED']);
        }

        if ($this->allowNonAdmin) {
            return array('disable_row_level_security' => true);
        } else {
            return array();
        }
    }

    public function setAllowNonAdmin($state = true)
    {
        $this->allowNonAdmin = $state;
    }

    protected function getUserNameFromId($id)
    {
        $this->enforcePermissions();

        // this overrides visibility and sugar query and beans, completed for performance reasons only

        $builder = \DBManagerFactory::getInstance()->getConnection()->createQueryBuilder();
        $builder->select(array('id', 'user_name'))->from('users');
        $builder->where('id = ' . $builder->createPositionalParameter($id));
        $res = $builder->execute();

        if ($row = $res->fetch()) {
            return $row;
        }

        return array();
    }

    protected function retrieveMailboxFromDB($mailboxId)
    {
        $this->enforcePermissions();

        // this overrides visibility and sugar query and beans, completed for performance reasons only

        $fields = array_merge(array('id'), $this->fields);

        $builder = \DBManagerFactory::getInstance()->getConnection()->createQueryBuilder();
        $builder->select($fields)->from('outbound_email');
        $builder->where('id = ' . $builder->createPositionalParameter($mailboxId));
        $res = $builder->execute();

        if ($row = $res->fetch()) {
            return (object) $row;
        }

        return (object) array();
    }

    public function countMailboxCopies($mailboxId)
    {
        $this->enforcePermissions();

        // this overrides visibility and sugar query and beans, completed for performance reasons only

        $builder = \DBManagerFactory::getInstance()->getConnection()->createQueryBuilder();
        $builder->select(array('id'))->from('outbound_email');
        $builder->where('parentmailbox_id_c = ' . $builder->createPositionalParameter($mailboxId));
        $res = $builder->execute();
        return $res->rowCount();
    }

    public function getUiRecordLimit()
    {
        return (int)\SugarConfig::getInstance()->get('outbound_mailbox_deployer.ui_record_limit', 100);
    }

    protected function getActiveInboundMailboxes()
    {
        $this->enforcePermissions();

        $q = new \SugarQuery();
        $q->from(\BeanFactory::newBean('InboundEmail'));
        $q->select(array('id', 'email_user'));
        //$q->where()->equals('mailbox_type', 'createcase');
        $q->where()->equals('status', 'Active');

        $inbound = $q->execute();
        $validAddresses = [];
        if (!empty($inbound)) {
            foreach ($inbound as $account) {
                if (!empty($account['email_user'])) {
                    $validAddresses[$account['id']] = strtolower($account['email_user']);
                }
            }
        }

        return $validAddresses;
    }

    public function getOutboundEmails()
    {
        $beanOptions = $this->enforcePermissions();
       
        $return = [];
        $return['errors'] = [];
        $return['values'] = [];

        $checkInboundMailbox = (bool)\SugarConfig::getInstance()->get('outbound_mailbox_deployer.check_inbound_mailbox', true);

        if ($checkInboundMailbox) {
            // first of all, check if there are active inbound email accounts, otherwise exit
            $validAddresses = $this->getActiveInboundMailboxes();
            if (empty($validAddresses)) {
                $message = translate('LBL_OUTBOUND_EMAILS_DEPLOYER_ERROR_INBOUND_MAILBOX_MISSING', 'Administration');
                $GLOBALS['log']->info(__METHOD__ . ' ' . $message);
                $return['errors'][] = $message;
                return $return;
            }
        }

        $outboundMailboxes = [];

        // we only need the outbound emails overlapping with inbound emails (depending on the configuration setting)
        // but we do need to override the visibility through query builder, or won't be able to see all addresses (visibility filters out current user)

        // retrieving user addresses without a parent
        $builder = \DBManagerFactory::getInstance()->getConnection()->createQueryBuilder();
        $builder->select(array('id', 'name', 'user_id', 'email_address_id', 'mail_smtpuser'))->from('outbound_email');
        $builder->where('type = ' . $builder->createPositionalParameter('user'));
        $builder->andWhere('deleted = ' . $builder->createPositionalParameter(0));
        $builder->andWhere($builder->expr()->isNull('parentmailbox_id_c'));
        $res = $builder->execute();

        while ($row = $res->fetch()) {
            if (!empty($row['email_address_id'])) {
                $emailAddress = \BeanFactory::retrieveBean('EmailAddresses', $row['email_address_id'], $beanOptions);
                if (!empty($emailAddress)) {

                    // retrieve user
                    $user = $this->getUserNameFromId($row['user_id']);
                    $user_string = '';
                    if (!empty($user)) {
                        $user_string = '(' . $user['user_name'] . ') - ';
                    }

                    // if we need to check for inbound mailbox
                    if ($checkInboundMailbox) {
                        if (!empty($row['mail_smtpuser']) && !empty($row['email_address_id']) && 
                            (in_array(strtolower($row['mail_smtpuser']), $validAddresses) || in_array(strtolower($emailAddress->email_address), $validAddresses))) {

                            // we have an outbound mailbox with the same username as the inbound
                            $outboundMailboxes[$row['id']] = $user_string . $row['name'] . ' - ' . $emailAddress->email_address;
                            $GLOBALS['log']->info(__METHOD__ . ' identified outbound email address with the same username as the inbound address');
                        }
                    } else {
                        $outboundMailboxes[$row['id']] = $user_string . $row['name'] . ' - ' . $emailAddress->email_address;
                    }
                }
            }
        }

        if (empty($outboundMailboxes)) {
            $message = translate('LBL_OUTBOUND_EMAILS_DEPLOYER_ERROR_OUTBOUND_MAILBOX_MISSING', 'Administration');
            $GLOBALS['log']->info(__METHOD__ . ' ' . $message);
            $return['errors'][] = $message;
            return $return;
        }

        $return['values'] = $outboundMailboxes;
        asort($return['values'], SORT_NATURAL | SORT_FLAG_CASE);
        
        return $return;
    }

    public function getFullMapping()
    {
        $beanOptions = $this->enforcePermissions();

        $return = [];
        
        $outboundEmails = $this->getOutboundEmails();
        if (!empty($outboundEmails) && !empty($outboundEmails['values'])) {
            foreach ($outboundEmails['values'] as $outboundMailboxId => $outboundEmailValue) {
                $oe = \BeanFactory::retrieveBean('OutboundEmail', $outboundMailboxId, $beanOptions);
                if (!empty($oe)) {
                    // populate email
                    $return[$oe->id]['email'] = $oe->email_address;
                    $return[$oe->id]['mailbox'] = $outboundEmailValue;
                    $return[$oe->id]['user_id'] = $oe->user_id;
                    $return[$oe->id]['mailbox_id'] = $oe->id;
                    $return[$oe->id]['teams'] = [];

                    $oe->load_relationship('teams_outboundemail_1');
                    $teams = $oe->teams_outboundemail_1->get();
                    if (!empty($teams)) {
                        foreach ($teams as $teamId) {
                            $team = \BeanFactory::retrieveBean('Teams', $teamId, $beanOptions);
                            if (!empty($team)) {
                            $return[$oe->id]['teams'][$teamId] = $team->name;
                            } else {
                                // attempt to soft delete the non-deleted orphan relationship record
                                // retrieve deleted Team if possible
                                $team = \BeanFactory::retrieveBean('Teams', $teamId, $beanOptions, false);
                                if (!empty($team)) {
                                    // calling a relationship delete to flush the record
                                    $oe->teams_outboundemail_1->delete($oe->id, $team);
                                }
                            }
                        }
                        asort($return[$oe->id]['teams'], SORT_NATURAL | SORT_FLAG_CASE);
                    }
                }
            }
            $GLOBALS['log']->info(__METHOD__ . ' returning valid mapping');
        }

        return $return;
    }

    public function getTeams()
    {
        $this->enforcePermissions();

        $return = [];
        $return['values'] = \BeanFactory::newBean('Teams')::getArrayAllAvailable();

        // sorting based on team name
        asort($return['values'], SORT_NATURAL | SORT_FLAG_CASE);

        return $return;
    }

    public function addTeamsToMailbox($mailboxId, $teams = array())
    {
        $beanOptions = $this->enforcePermissions();

        if (!empty($mailboxId) && !empty($teams)) {
            $oe = \BeanFactory::retrieveBean('OutboundEmail', $mailboxId, $beanOptions);
            if (!empty($oe)) {
                $oe->load_relationship('teams_outboundemail_1');
                foreach ($teams as $teamId) {
                    $oe->teams_outboundemail_1->add($teamId);
                }
                return true;
            }
        }

        return false;
    }

    public function removeTeamFromMailbox($mailboxId, $teamId)
    {
        $beanOptions = $this->enforcePermissions();

        if (!empty($mailboxId) && !empty($teamId)) {
            $oe = \BeanFactory::retrieveBean('OutboundEmail', $mailboxId, $beanOptions);
            if (!empty($oe)) {
                $oe->load_relationship('teams_outboundemail_1');
                $oe->teams_outboundemail_1->delete($mailboxId, $teamId);
                return true;
            }
        }

        return false;
    }

    protected function getUserCopiedOutboundEmailId($userId, $ouboundMailboxId)
    {
        $this->enforcePermissions();

        $return = false;

        if (!empty($userId) && !empty($ouboundMailboxId)) {
            $builder = \DBManagerFactory::getInstance()->getConnection()->createQueryBuilder();
            $builder->select(array('id'))->from('outbound_email');
            $builder->where('user_id = ' . $builder->createPositionalParameter($userId));
            $builder->andWhere('type = ' . $builder->createPositionalParameter('user'));
            $builder->andWhere('parentmailbox_id_c = ' . $builder->createPositionalParameter($ouboundMailboxId));
            $builder->andWhere('deleted = ' . $builder->createPositionalParameter(0));
            $res = $builder->execute();
            
            if ($row = $res->fetch()) {
                if (!empty($row['id'])) {
                    $GLOBALS['log']->info(__METHOD__ . ' found copy of the outbound mailbox od id ' . $ouboundMailboxId . ' with id ' . $row['id'] . ' for user with id ' . $userId);
                    return $row['id'];
                }
            } else {
                $GLOBALS['log']->info(__METHOD__ . ' outbound mailbox with id ' . $ouboundMailboxId . ' for user with id ' . $userId . ' not found');
            }
        }

        return $return;
    }

    protected function getAllOutboundEmailCopies()
    {
        $this->enforcePermissions();

        $return = [];

        $builder = \DBManagerFactory::getInstance()->getConnection()->createQueryBuilder();
        $builder->select(array('id', 'email_address_id', 'user_id', 'parentmailbox_id_c'))->from('outbound_email');
        $builder->where('type = ' . $builder->createPositionalParameter('user'));
        $builder->andWhere($builder->expr()->isNotNull('parentmailbox_id_c'));
        $builder->andWhere('deleted = ' . $builder->createPositionalParameter(0));
        $res = $builder->execute();
        
        while ($row = $res->fetch()) {
            if (!empty($row['id'])) {
                $return[$row['id']] = $row;
            }
        }

        return $return;
    }

    protected function removeOrphanMailboxes($mapping)
    {
        $beanOptions = $this->enforcePermissions();

        $return = [];
        $return['completed'] = [];

        $copies = $this->getAllOutboundEmailCopies();
        if (!empty($copies)) {
            foreach ($copies as $copy) {
                // check if the user is part of a team on the mapping for this outbound mailbox (parent mailbox)
                $found = false;

                if (!empty($copy['parentmailbox_id_c']) && !empty($copy['user_id'])) {
                    if (!empty($mapping[$copy['parentmailbox_id_c']]) && !empty($mapping[$copy['parentmailbox_id_c']]['teams'])) {
                        foreach ($mapping[$copy['parentmailbox_id_c']]['teams'] as $teamId => $teamName) {
                            $team = \BeanFactory::retrieveBean('Teams', $teamId, $beanOptions);
                            if (!empty($team)) {
                                $team->load_relationship('users');
                                $users = $team->users->get();
                                if (in_array($copy['user_id'], $users)) {
                                    $GLOBALS['log']->info(__METHOD__ . ' the user id ' . $copy['user_id'] . ' is part of the team ' . $teamName . ', mapped to the outbound mailbox id ' . $copy['parentmailbox_id_c'] . '. Keeping record');
                                    $found = true;
                                }
                            }
                        
                        }
                    }
                }

                if (!$found) {
                    $oe = \BeanFactory::retrieveBean('OutboundEmail', $copy['id'], $beanOptions);

                    $user = $this->getUserNameFromId($copy['user_id']);

                    if (!empty($oe)) {
                        $GLOBALS['log']->info(__METHOD__ . ' the user ' . $user['user_name'] . ' is not part of a team mapped to the outbound mailbox id ' .
                            $copy['parentmailbox_id_c']. ' for the email address ' . $oe->email_address  . '. Deleting record id ' . $copy['id']);

                        $oe->mark_deleted($copy['id']);

                        $message = sprintf(
                            translate('LBL_OUTBOUND_EMAILS_DEPLOYER_MESSAGE_SUCCESSFUL_DELETE', 'Administration'),
                            $oe->email_address,
                            $user['user_name']
                        );

                        $return['completed'][] = $message;
                    }
                }
            }
        }
        
        return $return;
    }

    protected function areOutboundMailboxesInSync($originalMailbox, $usersMailboxDB)
    {
        $this->enforcePermissions();

        if (!empty($originalMailbox->id) && !empty($usersMailboxDB->id)) {
            // decode the pass for comparison, since we now compare against the database values
            foreach ($this->fieldsToDecode as $fieldToDecode) {
                $usersMailboxDB->$fieldToDecode = Blowfish::decode(Blowfish::getKey($originalMailbox->module_key), $usersMailboxDB->$fieldToDecode);
            }

            foreach ($this->fields as $field) {
                // if the strings are not identical
                if (strcmp($originalMailbox->$field, $usersMailboxDB->$field) !== 0) {
                    $GLOBALS['log']->info(__METHOD__ . ' found non-identical field for the mailboxes');
                    return false;
                }
            }
        }
        return true;
    }

    protected function copyOutboundMailboxToUser($originalMailbox, $userId)
    {
        $beanOptions = $this->enforcePermissions();

        $needsSave = false;
        if (!empty($originalMailbox->id) && !empty($userId)) {

            // retrieve user's mailboxes, replica of the current one
            $userMailbox = false;
            $usersMailboxId = $this->getUserCopiedOutboundEmailId($userId, $originalMailbox->id);

            $user = $this->getUserNameFromId($userId);

            if (!empty($user)) {

                if (!empty($usersMailboxId)) {
                    $userMailboxDB = $this->retrieveMailboxFromDB($usersMailboxId);
                }
                // update or create
                if (!empty($userMailboxDB)) {
                    if (!$this->areOutboundMailboxesInSync($originalMailbox, $userMailboxDB)) {
                        // update only if not already the same
                        $needsSave = true;
                        $oe = \BeanFactory::retrieveBean('OutboundEmail', $userMailboxDB->id, $beanOptions);
                        $GLOBALS['log']->info(__METHOD__ . ' updating existing mailbox for the email address ' . $originalMailbox->email_address . ' and user id ' . $userId);
                    }
                } else {
                    // create
                    $needsSave = true;
                    $oe = \BeanFactory::newBean('OutboundEmail');
                    if (!empty($beanOptions)) {
                        foreach ($beanOptions as $oKey => $oValue) {
                            $oe->$oKey = $oValue;
                        }
                    }
                    $GLOBALS['log']->info(__METHOD__ . ' creating new outbound mailbox for the email address id ' . $originalMailbox->email_address . ' and user id ' . $userId);
                }

                if ($needsSave) {
                    foreach ($this->fields as $field) {
                        $oe->$field = $originalMailbox->$field;
                    }
                    $oe->name = $user['user_name'] . ' - ' . $originalMailbox->email_address;
                    $oe->user_id = $user['id'];
                    // new field to track the parent
                    $oe->parentmailbox_id_c = $originalMailbox->id;
                    // operation to detect on after save hook
                    $opFlag = \SugarBean::enterOperation('outbound_emails_deployer_save');
                    $oe->save(false);
                    \SugarBean::leaveOperation('outbound_emails_deployer_save', $opFlag);
                }
            }
        }
        return $needsSave;
    }

    public function deployCurrentMapping($uiDriven = false, $srcMailboxID = false)
    {
        $beanOptions = $this->enforcePermissions();

        $uiRecordLimit = $this->getUiRecordLimit();
        $processedRecords = 0;

        $return = [];
        $return['uiwarning'] = '';
        $return['errors'] = [];
        $return['deleted'] = [];
        $return['no_changes'] = [];
        $return['updated'] = [];

        $mapping = $this->getFullMapping();

        // remove all orphans to re-align the mailboxes
        $removals = $this->removeOrphanMailboxes($mapping);
        if (!empty($removals['completed'])) {
            $return['deleted'] = $removals['completed'];
        }

        if (!empty($mapping)) {
            foreach ($mapping as $outboundMailboxId => $emailMapping) {

                // only process this mailbox, either if all mailboxes have to be processed, or if this specific mailbox has to be processed
                if ($srcMailboxID === false || $srcMailboxID === $outboundMailboxId) {

                    if (!empty($emailMapping['teams'])) {

                        // get original outbound email mailbox
                        $originalMailbox = \BeanFactory::retrieveBean('OutboundEmail', $outboundMailboxId, $beanOptions);
                        if (!empty($originalMailbox)) {
                            foreach ($emailMapping['teams'] as $teamId => $teamName) {
                                // need to retrieve users members of the team and then their outbound mailboxes to compare them
                                $team = \BeanFactory::retrieveBean('Teams', $teamId, $beanOptions);
                                if (!empty($team->id)) {
                                    $team->load_relationship('users');
                                    $users = $team->users->get();
                                    if (!empty($users)) {
                                        foreach ($users as $userId) {
                                            // skip for current user
                                            if ($userId != $originalMailbox->user_id) {
                                                $user = $this->getUserNameFromId($userId);
                                                if ($this->copyOutboundMailboxToUser($originalMailbox, $userId)) {
                                                    // the record was modified
                                                    $message = sprintf(
                                                        translate('LBL_OUTBOUND_EMAILS_DEPLOYER_MESSAGE_SUCCESSFUL_CHANGE', 'Administration'),
                                                        $originalMailbox->email_address,
                                                        $user['user_name']
                                                    );
                                                    $return['updated'][] = $message;
                                                } else {
                                                    // the record was the same
                                                    $message = sprintf(
                                                        translate('LBL_OUTBOUND_EMAILS_DEPLOYER_MESSAGE_NO_CHANGE', 'Administration'),
                                                        $originalMailbox->email_address,
                                                        $user['user_name']
                                                    );
                                                    $return['no_changes'][] = $message;
                                                }
                                                $GLOBALS['log']->info(__METHOD__ . ' ' . $message);
                                                $processedRecords++;

                                                // check if we have to go to the background after few records
                                                if ($uiDriven) {
                                                    if ($processedRecords >= $uiRecordLimit) {
                                                        // queue the process and send a message out to the user
                                                        $message = sprintf(
                                                            translate('LBL_OUTBOUND_EMAILS_DEPLOYER_SUMMARY_MESSAGE_TOO_MANY_RECORDS', 'Administration'),
                                                            $processedRecords
                                                        );
                                                        $return['uiwarning'] = $message;
                                                        $GLOBALS['log']->info(__METHOD__ . ' ' . $message);

                                                        global $current_user;

                                                        $this->scheduleForBackgroundProcessing($current_user->id);

                                                        // reset allow admin if set
                                                        $this->setAllowNonAdmin(false);

                                                        return $return;
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        // reset allow admin if set
        $this->setAllowNonAdmin(false);
    
        return $return;
    }

    public function scheduleForBackgroundProcessing($user_id = false)
    {
        $this->enforcePermissions();

        global $current_user;

        $job = new \SchedulersJob();
        if (!empty($user_id)) {
            $job->data = json_encode(
                [
                    'notify_user_id' => $user_id
                ]
            );
        }
        $job->name = 'Processing Outbound Emails Deployment';
        $job->target = 'class::OutboundEmailsDeployerJob';
        $job->assigned_user_id = $current_user->id;

        $jq = new \SugarJobQueue();
        $jobid = $jq->submitJob($job);
    }
}
