import { extend } from 'flarum/extend';
import app from 'flarum/app';

import PusherSettingsModal from './components/PusherSettingsModal';

app.initializers.add('flarum-pusher', app => {
  app.extensionSettings['flarum-pusher'] = () => app.modal.show(new PusherSettingsModal());
});
