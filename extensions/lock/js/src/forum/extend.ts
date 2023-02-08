import Extend from 'flarum/common/extenders';
import DiscussionLockedPost from './components/DiscussionLockedPost';

export default [new Extend.PostTypes().add('discussionLocked', DiscussionLockedPost)];
