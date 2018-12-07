// Enrico Simonetti
// 2018-11-12

({
    initialize: function(options)
    {
        this._super('initialize', [options]);
        this.context.on('button:cancel_button:click', this.cancelAction, this);
        this.context.on('button:save_button:click', this.disableSaveButton, this);
        this.context.on('outbound-emails-deployer:refresh', this.disableSaveButton, this);
        this.context.on('outbound-emails-deployer:response', this.enableSaveButton, this);
    },

    enableSaveButton: function()
    {
        app.alert.dismiss('outbound-emails-deployer-wait');
        this.getField('save_button').setDisabled(false);
    },

    disableSaveButton: function()
    {
        this.getField('save_button').setDisabled(true);
    },

    cancelAction: function()
    {
        // back to admin
        app.router.navigate("#Administration", {trigger:true});
    },

    _dispose: function()
    {
        this.context.off('button:cancel_button:click', null, this);
        this.context.off('button:save_button:click', null, this);
        this.context.off('outbound-emails-deployer:refresh', null, this);
        this.context.off('outbound-emails-deployer:response', null, this);
        this._super('_dispose');
    }
});
