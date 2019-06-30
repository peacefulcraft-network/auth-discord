import { extend } from 'flarum/extend';
import app from 'flarum/app';
import LogInButtons from 'flarum/components/LogInButtons';
import LogInButton from 'flarum/components/LogInButton';

app.initializers.add('pcnnet-auth-discord', () => {
  extend(LogInButtons.prototype, 'items', function(items) {
    items.add('discord',
      <LogInButton
        className="Button LogInButton--discord"
        icon="fab fa-discord"
        path="/auth/discord">
        {app.translator.trans('pcnnet-flarum-auth-discord.forum.log_in.with_discord_button')}
      </LogInButton>
    );
  });
});
