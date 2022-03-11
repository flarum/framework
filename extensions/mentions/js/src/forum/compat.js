import MentionsUserPage from './components/MentionsUserPage';
import PostMentionedNotification from './components/PostMentionedNotification';
import UserMentionedNotification from './components/UserMentionedNotification';
import AutocompleteDropdown from './fragments/AutocompleteDropdown';
import PostQuoteButton from './fragments/PostQuoteButton';
import getCleanDisplayName from './utils/getCleanDisplayName';
import getMentionText from './utils/getMentionText';
import * as reply from './utils/reply';
import selectedText from './utils/selectedText';
import * as textFormatter from './utils/textFormatter';

export default {
  'mentions/components/MentionsUserPage': MentionsUserPage,
  'mentions/components/PostMentionedNotification': PostMentionedNotification,
  'mentions/components/UserMentionedNotification': UserMentionedNotification,
  'mentions/fragments/AutocompleteDropdown': AutocompleteDropdown,
  'mentions/fragments/PostQuoteButton': PostQuoteButton,
  'mentions/utils/getCleanDisplayName': getCleanDisplayName,
  'mentions/utils/getMentionText': getMentionText,
  'mentions/utils/reply': reply,
  'mentions/utils/selectedText': selectedText,
  'mentions/utils/textFormatter': textFormatter,
};
