import compat from '../common/compat';

import addTagFilter from './addTagFilter';
import addTagControl from './addTagControl';
import TagHero from './components/TagHero';
import TagDiscussionModal from './components/TagDiscussionModal';
import TagsPage from './components/TagsPage';
import DiscussionTaggedPost from './components/DiscussionTaggedPost';
import TagLinkButton from './components/TagLinkButton';
import addTagList from './addTagList';
import addTagLabels from './addTagLabels';
import addTagComposer from './addTagComposer';

export default Object.assign(compat, {
  'addTagFilter': addTagFilter,
  'addTagControl': addTagControl,
  'components/TagHero': TagHero,
  'components/TagDiscussionModal': TagDiscussionModal,
  'components/TagsPage': TagsPage,
  'components/DiscussionTaggedPost': DiscussionTaggedPost,
  'components/TagLinkButton': TagLinkButton,
  'addTagList': addTagList,
  'addTagLabels': addTagLabels,
  'addTagComposer': addTagComposer,
});
