import { extend } from 'flarum/extend';
import app from 'flarum/app';

import PusherSettingsModal from 'pusher/components/PusherSettingsModal';

app.initializers.add('pusher', app => {
  app.extensionSettings.pusher = () => app.modal.show(new PusherSettingsModal());
});
