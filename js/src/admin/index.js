import app from 'flarum/app';

import DiscordSettingsModal from './components/DiscordSettingsModal';

app.initializers.add('pcnnet-auth-discord', () => {
  app.extensionSettings['pcnnet-auth-discord'] = () => app.modal.show(new DiscordSettingsModal());
});
