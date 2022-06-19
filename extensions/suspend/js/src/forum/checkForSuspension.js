import app from 'flarum/forum/app';
import SuspensionInfoModal from './components/SuspensionInfoModal';
import { localStorageKey } from './helpers/suspensionHelper';

export default function () {
  return setTimeout(() => {
    if (app.session.user) {
      const message = app.session.user.suspendMessage();
      const until = app.session.user.suspendedUntil();
      const isSuspended = message && until && new Date() < until;
      const alreadyDisplayed = localStorage.getItem(localStorageKey()) === until?.getTime().toString();

      if (isSuspended && !alreadyDisplayed) {
        app.modal.show(SuspensionInfoModal, { message, until });
      } else if (localStorage.getItem(localStorageKey())) {
        localStorage.removeItem(localStorageKey());
      }
    }
  }, 0);
}
