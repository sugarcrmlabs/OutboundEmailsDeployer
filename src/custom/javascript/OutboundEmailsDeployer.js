(function(app) {
    app.events.on('router:init', function() {
        app.router.route('OutboundEmailsDeployer', 'outbound-emails-deployer', function() {
            if (app.acl.hasAccess('admin', 'Administration')) {
                app.controller.loadView({
                    module: 'Administration',
                    layout: 'outbound-emails-deployer',
                    create: true
                });
            } else {
                app.alert.show('error-outbound-emails-deployer-access', {
                    level: 'error',
                    messages: 'EXCEPTION_NOT_AUTHORIZED',
                    autoClose: true
                });
                app.router.navigate('', {trigger: true});
            }
        });
    });
})(SUGAR.App);
