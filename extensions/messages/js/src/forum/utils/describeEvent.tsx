import app from 'flarum/forum/app';
import type Mithril from 'mithril';
import Link from 'flarum/common/components/Link';
import username from 'flarum/common/helpers/username';
import extractText from 'flarum/common/utils/extractText';
import type DialogMessage from '../../common/models/DialogMessage';

/**
 * What a notice in a conversation says happened: who left it, who named it.
 * In plain words for a preview, otherwise with a link to the member.
 */
export default function describeEvent(message: DialogMessage, plain: true): string;
export default function describeEvent(message: DialogMessage, plain?: false): Mithril.Children;
export default function describeEvent(message: DialogMessage, plain: boolean = false): Mithril.Children {
  const user = message.user() || null;
  const data = message.eventData();
  const name = username(user);

  const params = {
    username: plain || !user ? name : <Link href={app.route.user(user)}>{name}</Link>,
    name: data.name,
  };

  let key: string;

  switch (message.type()) {
    case 'userLeft':
      key = 'flarum-messages.forum.messages_page.stream.user_left_text';
      break;
    case 'renamed':
      key = data.name ? 'flarum-messages.forum.messages_page.stream.renamed_text' : 'flarum-messages.forum.messages_page.stream.name_removed_text';
      break;
    default:
      return '';
  }

  const description = app.translator.trans(key, params);

  return plain ? extractText(description) : description;
}
