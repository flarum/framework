import Extend from 'flarum/common/extenders';
import DiscussionStickiedPost from "./components/DiscussionStickiedPost";

export default [
  new Extend.PostTypes()
    .add('discussionStickied', DiscussionStickiedPost),
];
