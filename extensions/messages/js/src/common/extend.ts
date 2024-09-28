import DialogMessage from './models/DialogMessage';
import Dialog from './models/Dialog';
import Extend from 'flarum/common/extenders';

export default [
  new Extend.Store()
    .add('dialogs', Dialog) //
    .add('dialog-messages', DialogMessage), //
];
