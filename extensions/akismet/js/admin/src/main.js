import { extend } from 'flarum/extend';
import app from 'flarum/app';

import AkismetSettingsModal from 'akismet/components/AkismetSettingsModal';

app.initializers.add('akismet', () => {
  app.extensionSettings.akismet = () => app.modal.show(new AkismetSettingsModal());
});
