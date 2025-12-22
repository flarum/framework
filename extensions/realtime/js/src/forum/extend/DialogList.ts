import NewActivity from './DialogList/NewActivity';
import TypingIndicator from './DialogList/TypingIndicator';

export default function DialogList() {
  if ('flarum-messages' in flarum.extensions) {
    NewActivity();
    TypingIndicator();
  }
}
