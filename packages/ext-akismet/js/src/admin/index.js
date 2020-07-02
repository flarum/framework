import app from 'flarum/app';

import AkismetSettingsModal from './components/AkismetSettingsModal';

app.initializers.add('flarum-akismet', () => {
  app.extensionSettings['flarum-akismet'] = () => app.modal.show(AkismetSettingsModal);
});
