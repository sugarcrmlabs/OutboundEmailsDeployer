<?php

// Enrico Simonetti
// 2018-11-09

use Sugarcrm\Sugarcrm\custom\OutboundEmailsDeployer\OutboundEmailsDeployer;

class OutboundEmailsDeployerApi extends AdministrationApi
{
    /**
     * Register endpoints
     * @return array
     */
    public function registerApiRest()
    {
        return [
            'addTeamsToMailbox' => [
                'reqType' => ['POST'],
                'path' => ['Administration', 'OutboundEmailsDeployer', '?', 'addTeamsToMailbox'],
                'pathVars' => ['', '', 'mailbox_id', ''],
                'method' => 'addTeamsToMailbox',
                'shortHelp' => '',
                'exceptions' => [
                    'SugarApiExceptionNotAuthorized',
                    'SugarApiExceptionSearchUnavailable',
                ],
            ],
            'removeTeamFromMailbox' => [
                'reqType' => ['POST'],
                'path' => ['Administration', 'OutboundEmailsDeployer', '?', 'removeTeamFromMailbox', '?'],
                'pathVars' => ['', '', 'mailbox_id', '', 'team_id'],
                'method' => 'removeTeamFromMailbox',
                'shortHelp' => '',
                'exceptions' => [
                    'SugarApiExceptionNotAuthorized',
                    'SugarApiExceptionSearchUnavailable',
                ],
            ],
            'deployMailboxes' => [
                'reqType' => ['POST'],
                'path' => ['Administration', 'OutboundEmailsDeployer', 'deployMailboxes'],
                'pathVars' => [''],
                'method' => 'deployMailboxes',
                'shortHelp' => '',
                'exceptions' => [
                    'SugarApiExceptionNotAuthorized',
                    'SugarApiExceptionSearchUnavailable',
                ],
            ],
            'getTeams' => [
                'reqType' => ['GET'],
                'path' => ['Administration', 'OutboundEmailsDeployer', 'getTeams'],
                'pathVars' => [''],
                'method' => 'getTeams',
                'shortHelp' => '',
                'exceptions' => [
                    'SugarApiExceptionNotAuthorized',
                    'SugarApiExceptionSearchUnavailable',
                ],
            ],
            'getMailboxes' => [
                'reqType' => ['GET'],
                'path' => ['Administration', 'OutboundEmailsDeployer', 'getMailboxes'],
                'pathVars' => [''],
                'method' => 'getMailboxes',
                'shortHelp' => '',
                'exceptions' => [
                    'SugarApiExceptionNotAuthorized',
                    'SugarApiExceptionSearchUnavailable',
                ],
            ],
            'getMapping' => [
                'reqType' => ['GET'],
                'path' => ['Administration', 'OutboundEmailsDeployer', 'getMapping'],
                'pathVars' => [''],
                'method' => 'getMapping',
                'shortHelp' => '',
                'exceptions' => [
                    'SugarApiExceptionNotAuthorized',
                    'SugarApiExceptionSearchUnavailable',
                ],
            ],
        ];
    }

    /**
     * addTeamsToMailbox
     * @param ServiceBase $api
     * @param array $args
     * @return array
     */
    public function addTeamsToMailbox(ServiceBase $api, array $args)
    {
        $this->ensureAdminUser();

        $oed = new OutboundEmailsDeployer();
        $output = $oed->addTeamsToMailbox($args['mailbox_id'], (empty($args['teams']) ? array() : $args['teams']));

        return $output;
    }

    /**
     * removeTeamFromMailbox
     * @param ServiceBase $api
     * @param array $args
     * @return array
     */
    public function removeTeamFromMailbox(ServiceBase $api, array $args)
    {
        $this->ensureAdminUser();

        $oed = new OutboundEmailsDeployer();
        $output = $oed->removeTeamFromMailbox($args['mailbox_id'], $args['team_id']);

        return $output;
    }

    /**
     * deployMailboxes
     * @param ServiceBase $api
     * @param array $args
     * @return array
     */
    public function deployMailboxes(ServiceBase $api, array $args)
    {
        $this->ensureAdminUser();

        $oed = new OutboundEmailsDeployer();
        $output = $oed->deployCurrentMapping(true);

        return $output;
    }

    /**
     * getTeams
     * @param ServiceBase $api
     * @param array $args
     * @return array
     */
    public function getTeams(ServiceBase $api, array $args)
    {
        $this->ensureAdminUser();

        $oed = new OutboundEmailsDeployer();
        $output = $oed->getTeams();

        return $output;
    }

    /**
     * getMailboxes
     * @param ServiceBase $api
     * @param array $args
     * @return array
     */
    public function getMailboxes(ServiceBase $api, array $args)
    {
        $this->ensureAdminUser();

        $oed = new OutboundEmailsDeployer();
        $output = $oed->getOutboundEmails();

        return $output;
    }

    /**
     * getMapping
     * @param ServiceBase $api
     * @param array $args
     * @return array
     */
    public function getMapping(ServiceBase $api, array $args)
    {
        $this->ensureAdminUser();

        $oed = new OutboundEmailsDeployer();
        $output = $oed->getFullMapping();

        return $output;
    }
}
