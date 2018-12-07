// Enrico Simonetti
// 2018-11-12

({
    initialize: function (options)
    {
        this.events = _.extend({}, this.events, options.def.events, {
            'click .add-team-on-mailbox': 'addAction',
            'click .team-remove': 'removeAction'
        });
        this._super('initialize',[options]);
        this.context.on('button:save_button:click', this.confirmDeployAction, this);
        this.current_user_id = app.user.id;
    },

    confirmDeployAction: function ()
    {
        app.alert.show('outbound-emails-deployer-confirmation', {
            level: 'confirmation',
            messages: app.lang.get('LBL_OUTBOUND_EMAILS_DEPLOYER_DEPLOY_CONFIRMATION', this.module),
            onConfirm: _.bind(this.deployAction, this),
            onCancel: _.bind(function() {
                this.context.trigger('outbound-emails-deployer:response');
            }, this)
        });
    },

    deployAction: function ()
    {
        var self = this;

        app.alert.dismiss('outbound-emails-deployer-wait');
        app.alert.show('outbound-emails-deployer-wait', {
            level: 'info',
            messages: app.lang.get('LBL_OUTBOUND_EMAILS_DEPLOYER_WAIT', this.module),
            autoClose: false
        });

        var url = app.api.buildURL('Administration/OutboundEmailsDeployer/deployMailboxes');
        app.api.call('create', url, {}, {
            success: function (response) {
                self.outputMessages = response.completed;
                self.render();
            },
            error: function() {
                self.context.trigger('outbound-emails-deployer:response');
            },
            complete: function() {
                self.context.trigger('outbound-emails-deployer:response');
            }
        });
    },

    addAction: function ()
    {
        var self = this;

        var mailboxId = this.$el.find('.mailbox-select2').find(':selected').val();
        var teams = this.$el.find('.team-select2').select2('data');

        var data = {
            'teams': []
        };

        $.each(teams, function(k, v){
            data.teams.push(
                v.id
            );
        });

        if (!_.isEmpty(mailboxId) && data.teams.length > 0) {

            app.alert.dismiss('outbound-emails-deployer-wait');
            app.alert.show('outbound-emails-deployer-wait', {
                level: 'info',
                messages: app.lang.get('LBL_OUTBOUND_EMAILS_DEPLOYER_WAIT', this.module),
                autoClose: false
            });

            var url = app.api.buildURL('Administration/OutboundEmailsDeployer/' + mailboxId + '/addTeamsToMailbox');

            app.api.call('create', url, data, {
                success: function (response) {
                    // refreshing mapping
                    self.loadMapping(
                        function() {
                            self.render();
                            self.context.trigger('outbound-emails-deployer:refresh');
                            self.confirmDeployAction();
                        }
                    );
                },
                error: function() {
                    app.alert.dismiss('outbound-emails-deployer-wait');
                },
                complete: function() {
                    app.alert.dismiss('outbound-emails-deployer-wait');
                }
            });
        }
    },

    removeAction: function (e)
    {
        var self = this;

        var target = e.target;
        var mailboxId = $(target).attr('data-mailbox');
        var teamId = $(target).attr('data-team');

        if (!_.isEmpty(mailboxId) && !_.isEmpty(teamId)) {

            app.alert.dismiss('outbound-emails-deployer-wait');
            app.alert.show('outbound-emails-deployer-wait', {
                level: 'info',
                messages: app.lang.get('LBL_OUTBOUND_EMAILS_DEPLOYER_WAIT', this.module),
                autoClose: false
            });

            var url = app.api.buildURL('Administration/OutboundEmailsDeployer/' + mailboxId + '/removeTeamFromMailbox/' + teamId);

            app.api.call('create', url, {}, {
                success: function (response) {
                    // refreshing mapping
                    self.loadMapping(
                        function() {
                            self.render();
                            self.context.trigger('outbound-emails-deployer:refresh');
                            self.confirmDeployAction();
                        }
                    );
                },
                error: function() {
                    app.alert.dismiss('outbound-emails-deployer-wait');
                },
                complete: function() {
                    app.alert.dismiss('outbound-emails-deployer-wait');
                }
            });
        }
    },

    checkAddButton: function () 
    {
        this.$el.find('.add-team-on-mailbox').attr('disabled', true);

        // enable the add button if both mailbox and teams have been selected
        var selectedTeams = this.$el.find('.team-select2').select2('data');
        if (!_.isEmpty(this.$el.find('.mailbox-select2').find(':selected').val()) && !_.isEmpty(selectedTeams) && selectedTeams.length !== 0) {
            this.$el.find('.add-team-on-mailbox').attr('disabled', false);
        }
    },

    _renderHtml: function (ctx, options)
    {
        this._super('_renderHtml', [ctx, options]);
        var self = this;

        this.$('.mailbox-select2').select2({
            width: '30%',
            allowClear: true,
            minimumResultsForSearch: 6,
            placeholder: app.lang.get('LBL_SEARCH_SELECT')
        }).on('change', function (e) {
            if (_.isEmpty(e.val)) {
                self.checkAddButton();
            } else {
                self.checkAddButton();
            }
        });

        this.$('.team-select2').select2({
            width: '30%',
            allowClear: true,
            minimumResultsForSearch: 6,
            containerCssClass: 'select2-choices-pills-close',
            placeholder: app.lang.get('LBL_SEARCH_SELECT')
        }).on('change', function (e) {
            if (_.isEmpty(e.val)) {
                self.checkAddButton();
            } else {
                self.checkAddButton();
            }
        });
    },

    loadData: function(options)
    {
        var self = this;

        var bulkId = _.uniqueId();
        async.parallel(
            [
                function (callback) {
                    self.loadMailboxes(callback, bulkId);
                },
                function (callback) {
                    self.loadTeams(callback, bulkId);
                },
                function (callback) {
                    self.loadMapping(callback, bulkId);
                },
            ],
            function (error, results) {
        // render on completion
                self.render();
            }
        );
        app.api.triggerBulkCall(bulkId);
    },

    loadMailboxes: function(callback, bulkId)
    {
        var self = this;
        var url = app.api.buildURL('Administration/OutboundEmailsDeployer/getMailboxes');
        app.api.call('read', url, null, {
            success: function (response) {
                self.outboundMailboxes = [];
                $.each(response.values, function(k, v){
                    self.outboundMailboxes.push({
                        "id": k,
                        "text": v
                    });
                });
                var errors = '';
                $.each(response.errors, function(k, v){
                    errors += v + "\n";
                });
                if (!_.isEmpty(errors)) {
                    app.alert.dismiss('outbound-emails-deployer-message');
                    app.alert.show('outbound-emails-deployer-message', {
                        level: 'info',
                        messages: errors,
                        autoClose: true,
                        autoCloseDelay: 15000
                    });
                }
            },
            complete: function()
            {
                // on complete, call the callback
                callback();
            }
        }, {bulk:bulkId});
    },

    loadTeams: function(callback, bulkId)
    {
        var self = this;
        var url = app.api.buildURL('Administration/OutboundEmailsDeployer/getTeams');
        app.api.call('read', url, null, {
            success: function (response) {
                self.teams = [];
                $.each(response.values, function(k, v){
                    self.teams.push({
                        "id": k,
                        "text": v
                    });
                });
            },
            complete: function()
            {
                // on complete, call the callback
                callback();
            }
        }, {bulk:bulkId});
    },

    loadMapping: function(callback, bulkId)
    {
        var self = this;
        var url = app.api.buildURL('Administration/OutboundEmailsDeployer/getMapping');
        app.api.call('read', url, null, {
            success: function (response) {
                self.displayMapping = response;
            },
            complete: function()
            {
                // on complete, call the callback
                callback();
            }
        }, {bulk:bulkId});
    },

    _dispose: function()
    {
        this.context.off('button:save_button:click', null, this);
        this.$('.mailbox-select2').select2('destroy');
        this.$('.team-select2').select2('destroy');
        this._super('_dispose');
    }
})
