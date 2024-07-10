import GroupMentionedNotification from './components/GroupMentionedNotification';
import MentionedByModal from './components/MentionedByModal';
import MentionsDropdownItem from './components/MentionsDropdownItem';
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
import GroupMention from './mentionables/GroupMention';
import MentionableModel from './mentionables/MentionableModel';
import MentionableModels from './mentionables/MentionableModels';
import PostMention from './mentionables/PostMention';
import TagMention from './mentionables/TagMention';
import UserMention from './mentionables/UserMention';
import AtMentionFormat from './mentionables/formats/AtMentionFormat';
import HashMentionFormat from './mentionables/formats/HashMentionFormat';
import MentionFormat from './mentionables/formats/MentionFormat';
import MentionFormats from './mentionables/formats/MentionFormats';
import Mentionables from './extenders/Mentionables';
import MentionedByModalState from './state/MentionedByModalState';

export default {
  'mentions/components/MentionsUserPage': MentionsUserPage,
  'mentions/components/PostMentionedNotification': PostMentionedNotification,
  'mentions/components/MentionedByModal': MentionedByModal,
  'mentions/components/MentionsDropdownItem': MentionsDropdownItem,
  'mentions/components/UserMentionedNotification': UserMentionedNotification,
  'mentions/components/GroupMentionedNotification': GroupMentionedNotification,
  'mentions/fragments/AutocompleteDropdown': AutocompleteDropdown,
  'mentions/fragments/PostQuoteButton': PostQuoteButton,
  'mentions/utils/getCleanDisplayName': getCleanDisplayName,
  'mentions/utils/getMentionText': getMentionText,
  'mentions/utils/reply': reply,
  'mentions/utils/selectedText': selectedText,
  'mentions/utils/textFormatter': textFormatter,
  'mentions/mentionables/GroupMention': GroupMention,
  'mentions/mentionables/MentionableModel': MentionableModel,
  'mentions/mentionables/MentionableModels': MentionableModels,
  'mentions/mentionables/PostMention': PostMention,
  'mentions/mentionables/TagMention': TagMention,
  'mentions/mentionables/UserMention': UserMention,
  'mentions/mentionables/formats/AtMentionFormat': AtMentionFormat,
  'mentions/mentionables/formats/HashMentionFormat': HashMentionFormat,
  'mentions/mentionables/formats/MentionFormat': MentionFormat,
  'mentions/mentionables/formats/MentionFormats': MentionFormats,
  'mentions/extenders/Mentionables': Mentionables,
  'mentions/state/MentionedByModalState': MentionedByModalState,
};
