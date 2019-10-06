import compat from '../common/compat';

import PostControls from './utils/PostControls';
import KeyboardNavigatable from './utils/KeyboardNavigatable';
import slidable from './utils/slidable';
import affixSidebar from './utils/affixSidebar';
import History from './utils/History';
import DiscussionControls from './utils/DiscussionControls';
import alertEmailConfirmation from './utils/alertEmailConfirmation';
import UserControls from './utils/UserControls';
import Pane from './utils/Pane';
import DiscussionPage from './components/DiscussionPage';
import LogInModal from './components/LogInModal';
import ComposerBody from './components/ComposerBody';
import ForgotPasswordModal from './components/ForgotPasswordModal';
import Notification from './components/Notification';
import LogInButton from './components/LogInButton';
import DiscussionsUserPage from './components/DiscussionsUserPage';
import Composer from './components/Composer';
import SessionDropdown from './components/SessionDropdown';
import HeaderPrimary from './components/HeaderPrimary';
import PostEdited from './components/PostEdited';
import PostStream from './components/PostStream';
import ChangePasswordModal from './components/ChangePasswordModal';
import IndexPage from './components/IndexPage';
import Page from './components/Page';
import DiscussionRenamedNotification from './components/DiscussionRenamedNotification';
import DiscussionsSearchSource from './components/DiscussionsSearchSource';
import HeaderSecondary from './components/HeaderSecondary';
import ComposerButton from './components/ComposerButton';
import DiscussionList from './components/DiscussionList';
import ReplyPlaceholder from './components/ReplyPlaceholder';
import TextEditor from './components/TextEditor';
import TextEditorButton from './components/TextEditorButton';
import AvatarEditor from './components/AvatarEditor';
import Post from './components/Post';
import SettingsPage from './components/SettingsPage';
import TerminalPost from './components/TerminalPost';
import ChangeEmailModal from './components/ChangeEmailModal';
import NotificationsDropdown from './components/NotificationsDropdown';
import UserPage from './components/UserPage';
import PostUser from './components/PostUser';
import UserCard from './components/UserCard';
import UsersSearchSource from './components/UsersSearchSource';
import NotificationGrid from './components/NotificationGrid';
import PostPreview from './components/PostPreview';
import EventPost from './components/EventPost';
import DiscussionHero from './components/DiscussionHero';
import PostMeta from './components/PostMeta';
import EditUserModal from './components/EditUserModal';
import SearchSource from './components/SearchSource';
import DiscussionRenamedPost from './components/DiscussionRenamedPost';
import DiscussionComposer from './components/DiscussionComposer';
import LogInButtons from './components/LogInButtons';
import NotificationList from './components/NotificationList';
import WelcomeHero from './components/WelcomeHero';
import SignUpModal from './components/SignUpModal';
import CommentPost from './components/CommentPost';
import ReplyComposer from './components/ReplyComposer';
import NotificationsPage from './components/NotificationsPage';
import PostStreamScrubber from './components/PostStreamScrubber';
import EditPostComposer from './components/EditPostComposer';
import RenameDiscussionModal from './components/RenameDiscussionModal';
import Search from './components/Search';
import DiscussionListItem from './components/DiscussionListItem';
import LoadingPost from './components/LoadingPost';
import PostsUserPage from './components/PostsUserPage';
import routes from './routes';
import ForumApplication from './ForumApplication';

export default Object.assign(compat, {
  'utils/PostControls': PostControls,
  'utils/KeyboardNavigatable': KeyboardNavigatable,
  'utils/slidable': slidable,
  'utils/affixSidebar': affixSidebar,
  'utils/History': History,
  'utils/DiscussionControls': DiscussionControls,
  'utils/alertEmailConfirmation': alertEmailConfirmation,
  'utils/UserControls': UserControls,
  'utils/Pane': Pane,
  'components/DiscussionPage': DiscussionPage,
  'components/LogInModal': LogInModal,
  'components/ComposerBody': ComposerBody,
  'components/ForgotPasswordModal': ForgotPasswordModal,
  'components/Notification': Notification,
  'components/LogInButton': LogInButton,
  'components/DiscussionsUserPage': DiscussionsUserPage,
  'components/Composer': Composer,
  'components/SessionDropdown': SessionDropdown,
  'components/HeaderPrimary': HeaderPrimary,
  'components/PostEdited': PostEdited,
  'components/PostStream': PostStream,
  'components/ChangePasswordModal': ChangePasswordModal,
  'components/IndexPage': IndexPage,
  'components/Page': Page,
  'components/DiscussionRenamedNotification': DiscussionRenamedNotification,
  'components/DiscussionsSearchSource': DiscussionsSearchSource,
  'components/HeaderSecondary': HeaderSecondary,
  'components/ComposerButton': ComposerButton,
  'components/DiscussionList': DiscussionList,
  'components/ReplyPlaceholder': ReplyPlaceholder,
  'components/TextEditor': TextEditor,
  'components/TextEditorButton': TextEditorButton,
  'components/AvatarEditor': AvatarEditor,
  'components/Post': Post,
  'components/SettingsPage': SettingsPage,
  'components/TerminalPost': TerminalPost,
  'components/ChangeEmailModal': ChangeEmailModal,
  'components/NotificationsDropdown': NotificationsDropdown,
  'components/UserPage': UserPage,
  'components/PostUser': PostUser,
  'components/UserCard': UserCard,
  'components/UsersSearchSource': UsersSearchSource,
  'components/NotificationGrid': NotificationGrid,
  'components/PostPreview': PostPreview,
  'components/EventPost': EventPost,
  'components/DiscussionHero': DiscussionHero,
  'components/PostMeta': PostMeta,
  'components/EditUserModal': EditUserModal,
  'components/SearchSource': SearchSource,
  'components/DiscussionRenamedPost': DiscussionRenamedPost,
  'components/DiscussionComposer': DiscussionComposer,
  'components/LogInButtons': LogInButtons,
  'components/NotificationList': NotificationList,
  'components/WelcomeHero': WelcomeHero,
  'components/SignUpModal': SignUpModal,
  'components/CommentPost': CommentPost,
  'components/ReplyComposer': ReplyComposer,
  'components/NotificationsPage': NotificationsPage,
  'components/PostStreamScrubber': PostStreamScrubber,
  'components/EditPostComposer': EditPostComposer,
  'components/RenameDiscussionModal': RenameDiscussionModal,
  'components/Search': Search,
  'components/DiscussionListItem': DiscussionListItem,
  'components/LoadingPost': LoadingPost,
  'components/PostsUserPage': PostsUserPage,
  'routes': routes,
  'ForumApplication': ForumApplication
});
