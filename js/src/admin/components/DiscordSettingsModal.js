import SettingsModal from 'flarum/components/SettingsModal';

export default class DiscordSettingsModal extends SettingsModal {
  className() {
    return 'DiscordSettingsModal --small';
  }

  title() {
    return app.translator.trans('pcnnet-flarum-auth-discord.admin.discord_settings.title');
  }

  form() {
    return [
      <div className="Form-group">
        <label>{app.translator.trans('pcnnet-flarum-auth-discord.admin.discord_settings.client_id_label')}</label>
        <input className="FormControl" bidi={this.setting('pcnnet.flarum-auth-discord.client_id')}/>
      </div>,

      <div className="Form-group">
        <label>{app.translator.trans('pcnnet-flarum-auth-discord.admin.discord_settings.client_secret_label')}</label>
        <input className="FormControl" bidi={this.setting('pcnnet.flarum-auth-discord.client_secret')}/>
      </div>
    ];
  }
}
