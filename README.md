# Outbound Group Email Account Deployer
The "Outbound Group Email Account Deployer" solution is meant to provide an automated way to deploy email accounts to large quantities of users at one time for the purpose of sending outbound emails.  This solution is installed through a Module Loadable package, by uploading and installing the .zip package file within Admin > Module Loader.  Note that to use this deployer, you must have at least one inbound group mailbox configured, and at least one user must already have this same inbound email account also configured as an outbound email account via the Emails module.
 
## Supported Platforms and Versions
This package supports only the MySQL database type.  It is not supported for DB2, Oracle, Microsoft SQL Server, or any other database type.
 
This package is only supported for Sugar versions 8.0.x (Sugar Cloud and On-Premise installations), 8.2.x and 8.3.x (Sugar Cloud installations).
 
## Prerequisites
The Sugar instance must have the following configured prior to using the Outbound Group Email Account Deployer:
1. Valid system email configuration in Admin > System Email Settings.
2. One or more group email accounts within Admin > Inbound Email.
3. At least one user with each of the mailboxes in #2 above already configured as an outbound email account in Emails > Email Settings. (for example, if your inbound email account is `support@examplecompany.com`, then you must have at least one user in the instance who already has valid SMTP settings configured for `support@examplecompany.com` in Emails > Email Settings)
4. Teams created, with users assigned to the teams, based on your requirements for deploying email accounts.
5. Valid, working email credentials configured in each user’s profile with the domain matching the same domain used in Admin > Inbound Email (for example, if your inbound email account is `support@examplecompany.com`, the users’ email accounts must also end in `examplecompany.com`)
6. Cron must be enabled.

### Example scenario to fulfill the prerequisites:

* Admin > System Email Settings has a valid email account called `system@examplecompany.com` configured.
* Admin > Inbound Email has a valid email account called `support@examplecompany.com` configured.
* Lisa, an Administrator user in the instance, has the `support@examplecompany.com` email account already configured for herself in Emails > Email Settings as a new outbound email account.
* Lisa configured her own personal email account on her user profile or Email Settings with her own credentials for `lisa@examplecompany.com`.
* Lisa created a Support team, which contains all customer support employees.
* All customer support employees in the Support team have configured their personal email accounts in their user profiles or Email Settings. For example, John, a regular user of the system, member of the Support team, has opened his user profile and configured his `john@examplecompany.com` email account with his own credentials.
* Cron has been enabled on the server and instance.

With this example scenario, the Outbound Group Email Account Deployer will use the `support@examplecompany.com` email account that Lisa has configured for herself as the "parent" configuration for deployment.  When Lisa deploys the email account to the Support team, the configuration that Lisa set for `support@examplecompany.com` in Emails > Email Settings for outbound email will propagate to each of the Support team users, including John.  The Outbound Group Email Account Deployer will help keep the account in sync for all the Support team users, for every change that Lisa will complete on the initial "parent" email account in the future.

## Installation Steps
1. Clone this repository and enter the cloned directory.
2. Retrieve the Sugar Module Packager dependency by running: `composer install`.
3. Generate the installable .zip Sugar module with: `./vendor/bin/package 2.1`.
4. Navigate to Admin > Module Loader and install the generated Outbound Group Email Account Deployer package.
5. Log out of the Sugar instance, clear your browser cache, and log back in to the Sugar instance once more.
6. Navigate to the Admin panel and verify that you now see a new section called "Outbound Group Email Account Deployer".

## Deploying an Outbound Email Account to a Team
1. Navigate to Admin > Outbound Group Email Account Deployer.
2. Select an available outbound mailbox from the "Select" dropdown list on the left side of the screen.
3. Select one or more teams from the "Select" text field, between the dropdown list and the Add button.
4. Once complete, click the "Add" button.  This will generate a prompt which states "This operations will deploy the mapped settings across all users, do you want to continue?".  If you are ready to deploy this email account to the teams listed, click "Confirm".  Otherwise, click "Cancel".
5. If you have deployed the mapped settings, you will see a list of actions completed on the next page where a list of mailboxes and user deployments will be listed.

## Changes to a Team
If you make changes to the membership of a team, you have two options for deploying the applicable outbound mailbox changes to users:
1. Wait for the Outbound Mailbox Deployer scheduler to run and propagate the changes to the users.  By default, this scheduler runs every 30 minutes.
2. Immediately allow the changes to take effect by opening the Outbound Group Email Account Deployer and clicking the blue "Force Refresh of Outbound Mailboxes" button to propagate the changes to users. 
 
## Removing a Team from an Outbound Mailbox Configuration          
To remove a team from the outbound mailbox mapping, navigate to Admin > Outbound Group Email Account Deployer, find the correct outbound mailbox in the list, and click the Remove button next to the team you want to remove from the mailbox.  After clicking this button, a pop-up will prompt you to deploy the mapped settings across all users.   If you are ready to deploy the changes, click "Confirm".  Otherwise, click "Cancel".

## Package Removal/Uninstallation Steps
To remove the functionality you can navigate to Admin > Module Loader and uninstall the package. Please note that once the package is uninstalled, all accounts generated by the system will be removed automatically.
 
## Important Notes
1. Optionally, you can set the following Sugar configuration option, via config_override.php, to allow any outbound mailbox to appear in the deployer dropdown list, even if the mailbox is not first configured as an Inbound Email Account.
```
$sugar_config['outbound_mailbox_deployer']['check_inbound_mailbox'] = false;
```
2. This package customizes the Outbound Email module to add a new field to track the parent outbound mailbox and to add an additional database index for performance purposes.
3. This package installs multiple application logic hooks to automate some of the deployment tasks.
After the installation, make sure to delete the browser's cache of your Sugar system.
4. The package installs a scheduler to automatically update users’ outbound mailboxes when a user is added to a team, and when a team is deleted.  By default, this scheduler runs every 30 minutes.

## Contributing
Everyone is welcome to contribute to this project! If you make a contribution, then the [Contributor Terms](CONTRIBUTOR_TERMS.pdf) apply to your submission.

Please check out our [Contribution Guidelines](CONTRIBUTING.md) for helpful hints and tips that will make it easier for us to accept your pull requests.


-----
Copyright (c) 2018 SugarCRM Inc. Licensed by SugarCRM under the Apache 2.0 license.
