# Forminator Addon - Request Tracker (RT)

This is an addon for [Forminator](https://wordpress.org/plugins/forminator/) - a Wordpress forms plugin. When this [addon is installed and integrated](https://wpmudev.com/docs/wpmu-dev-plugins/forminator/#integrations), form builders will have the option to enable [Request Tracker (RT)](https://bestpractical.com/request-tracker) ticket creation upon form submission.

## Integration
1. Download this repository to your plugins directory, and activate it. 
2. Go to `Forminator Pro -> Integrations`
3. Select `Request Tracker (RT)` and enter your RT API credentials in the wizard.
4. Go to `Forminator Pro -> Forms`, select a form, and then `Integrations`.
5. Add `Request Tracker (RT)` 

Instead of entering and storing RT API credentials in the Wordpress database, you can use the following env variables:
- `FORMINATOR_ADDON_RT_HOST`
- `FORMINATOR_ADDON_RT_SECRET`
