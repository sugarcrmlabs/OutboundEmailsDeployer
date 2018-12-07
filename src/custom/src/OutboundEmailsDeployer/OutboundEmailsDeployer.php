<?php

// Enrico Simonetti
// 2018-11-09

namespace Sugarcrm\Sugarcrm\custom\OutboundEmailsDeployer;

class OutboundEmailsDeployer
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
        $builder->andWhere($builder->expr()->isNull('parentmailbox_id'));
        $res = $builder->execute();

        while ($row = $res->fetch()) {
            if (!empty($row['email_address_id'])) {
                $emailAddress = \BeanFactory::getBean('EmailAddresses', $row['email_address_id'], $beanOptions);
                if (!empty($emailAddress->id)) {

                    // retrieve user
                    $user = \BeanFactory::getBean('Users', $row['user_id']);
                    $user_string = '';
                    if (!empty($user->id)) {
                        $user_string = '(' . $user->user_name . ') - ';
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
                $oe = \BeanFactory::getBean('OutboundEmail', $outboundMailboxId, $beanOptions);
                if (!empty($oe->id)) {
                    $emailAddress = \BeanFactory::getBean('EmailAddresses', $oe->email_address_id, $beanOptions);

                    // populate email
                    $return[$oe->id]['email'] = $emailAddress->email_address;
                    $return[$oe->id]['mailbox'] = $outboundEmailValue;
                    $return[$oe->id]['user_id'] = $oe->user_id;
                    $return[$oe->id]['mailbox_id'] = $oe->id;
                    $return[$oe->id]['teams'] = [];

                    $oe->load_relationship('teams_outboundemail_1');
                    $teams = $oe->teams_outboundemail_1->get();
                    if (!empty($teams)) {
                        foreach ($teams as $teamId) {
                            $team = \BeanFactory::getBean('Teams', $teamId, $beanOptions);
                            if (!empty($team->id)) {
                            $return[$oe->id]['teams'][$teamId] = $team->name;
                            } else {
                                // attempt to soft delete the non-deleted orphan relationship record
                                // retrieve deleted Team if possible
                                $team = \BeanFactory::getBean('Teams', $teamId, $beanOptions, false); 
                                if (!empty($team->id)) {
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
            $oe = \BeanFactory::getBean('OutboundEmail', $mailboxId, $beanOptions);
            if (!empty($oe->id)) {
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
            $oe = \BeanFactory::getBean('OutboundEmail', $mailboxId, $beanOptions);
            if (!empty($oe->id)) {
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
            $builder->andWhere('parentmailbox_id = ' . $builder->createPositionalParameter($ouboundMailboxId));
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
        $builder->select(array('id', 'email_address_id', 'user_id', 'parentmailbox_id'))->from('outbound_email');
        $builder->where('type = ' . $builder->createPositionalParameter('user'));
        $builder->andWhere($builder->expr()->isNotNull('parentmailbox_id'));
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

                if (!empty($copy['parentmailbox_id']) && !empty($copy['user_id'])) {
                    if (!empty($mapping[$copy['parentmailbox_id']]) && !empty($mapping[$copy['parentmailbox_id']]['teams'])) {
                        foreach ($mapping[$copy['parentmailbox_id']]['teams'] as $teamId => $teamName) {
                            $team = \BeanFactory::getBean('Teams', $teamId, $beanOptions);
                            if (!empty($team->id)) {
                                $team->load_relationship('users');
                                $users = $team->users->get();
                                if (in_array($copy['user_id'], $users)) {
                                    $GLOBALS['log']->info(__METHOD__ . ' the user id ' . $copy['user_id'] . ' is part of the team ' . $teamName . ', mapped to the outbound mailbox id ' . $copy['parentmailbox_id'] . '. Keeping record');
                                    $found = true;
                                }
                            }
                        
                        }
                    }
                }

                if (!$found) {
                    $emailAddress = \BeanFactory::getBean('EmailAddresses', $copy['email_address_id'], $beanOptions);

                    $user = \BeanFactory::getBean('Users', $copy['user_id'], $beanOptions);

                    $GLOBALS['log']->info(__METHOD__ . ' the user ' . $user->user_name . ' is not part of a team mapped to the outbound mailbox id ' . 
                        $copy['parentmailbox_id']. ' for the email address ' . $emailAddress->email_address  . '. Deleting record id ' . $copy['id']);

                    $oe = \BeanFactory::getBean('OutboundEmail', $copy['id'], $beanOptions);
                    $oe->mark_deleted($copy['id']);
   
                    $message = sprintf(
                        translate('LBL_OUTBOUND_EMAILS_DEPLOYER_MESSAGE_SUCCESSFUL_DELETE', 'Administration'),
                        $emailAddress->email_address,
                        $user->user_name
                    );

                    $return['completed'][] = $message;
                }
            }
        }
        
        return $return;
    }

    protected function areOutboundMailboxesInSync($originalMailbox, $usersMailbox)
    {
        $this->enforcePermissions();

        if (!empty($originalMailbox->id) && !empty($usersMailbox->id)) {
            foreach ($this->fields as $field) {
                // if the strings are not identical
                if (strcmp($originalMailbox->$field, $usersMailbox->$field) !== 0) {
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
            if (!empty($usersMailboxId)) {
                // here we need to force the cache to be flushed, to make sure the mailboxes are identical
                $beanOptionsMailbox = array_merge($beanOptions, array('use_cache' => false));
                $userMailbox = \BeanFactory::getBean('OutboundEmail', $usersMailboxId, $beanOptionsMailbox);
            }

            $user = \BeanFactory::getBean('Users', $userId, $beanOptions);

            if (!empty($user) && !empty($user->id)) {

                $emailAddress = \BeanFactory::getBean('EmailAddresses', $originalMailbox->email_address_id, $beanOptions);

                if (!empty($emailAddress->id)) {
                    // update or create
                    if (!empty($userMailbox) && !empty($userMailbox->id)) {
                        if (!$this->areOutboundMailboxesInSync($originalMailbox, $userMailbox)) {
                            // update only if not already the same
                            $needsSave = true;
                            $oe = \BeanFactory::getBean('OutboundEmail', $userMailbox->id, $beanOptions);
                            $GLOBALS['log']->info(__METHOD__ . ' updating existing mailbox for the email address ' . $emailAddress->email_address . ' and user id ' . $userId);
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
                        $GLOBALS['log']->info(__METHOD__ . ' creating new outbound mailbox for the email address id ' . $emailAddress->email_address . ' and user id ' . $userId);
                    }
                }

                if ($needsSave) {
                    foreach ($this->fields as $field) {
                        $oe->$field = $originalMailbox->$field;
                    }
                    $oe->name = $user->user_name . ' - ' . $emailAddress->email_address;
                    $oe->user_id = $user->id;
                    // new field to track the parent
                    $oe->parentmailbox_id = $originalMailbox->id;
                    // attribute to detect on after save hook
                    $oe->saveFromDeployer = true;
                    $oe->save(false);
                }
            }
        }
        return $needsSave;
    }

    public function deployCurrentMapping()
    {
        $beanOptions = $this->enforcePermissions();

        $return = [];
        $return['errors'] = [];
        $return['completed'] = [];
        $mapping = $this->getFullMapping();

        // remove all orphans to re-align the mailboxes
        $removals = $this->removeOrphanMailboxes($mapping);
        if (!empty($removals['completed'])) {
            $return['completed'] = $removals['completed'];
        }

        if (!empty($mapping)) {
            foreach ($mapping as $outboundMailboxId => $emailMapping) {
                if (!empty($emailMapping['teams'])) {
    
                    // get original outbound email mailbox
                    $originalMailbox = \BeanFactory::getBean('OutboundEmail', $outboundMailboxId, $beanOptions);
                    $emailAddress = \BeanFactory::getBean('EmailAddresses', $originalMailbox->email_address_id, $beanOptions);
                    if (!empty($originalMailbox->id) && !empty($emailAddress->id)) {
                        foreach ($emailMapping['teams'] as $teamId => $teamName) {
                            // need to retrieve users members of the team and then their outbound mailboxes to compare them
                            $team = \BeanFactory::getBean('Teams', $teamId, $beanOptions);
                            if (!empty($team->id)) {
                                $team->load_relationship('users');
                                $users = $team->users->get();
                                if (!empty($users)) {
                                    foreach ($users as $userId) {
                                        // skip for current user
                                        if ($userId != $originalMailbox->user_id) {
                                            $user = \BeanFactory::getBean('Users', $userId, $beanOptions);
                                            if ($this->copyOutboundMailboxToUser($originalMailbox, $userId)) {
                                                // the record was modified
                                                $message = sprintf(
                                                    translate('LBL_OUTBOUND_EMAILS_DEPLOYER_MESSAGE_SUCCESSFUL_CHANGE', 'Administration'),
                                                    $emailAddress->email_address,
                                                    $user->user_name
                                                );
                                            } else {
                                                // the record was the same
                                                $message = sprintf(
                                                    translate('LBL_OUTBOUND_EMAILS_DEPLOYER_MESSAGE_NO_CHANGE', 'Administration'),
                                                    $emailAddress->email_address,
                                                    $user->user_name
                                                );
                                            }
                                            $return['completed'][] = $message;
                                            $GLOBALS['log']->info(__METHOD__ . ' ' . $message);
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
}
