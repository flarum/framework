import compat from '../common/compat';

import addTagFilter from './addTagFilter';
import addTagControl from './addTagControl';
import TagHero from './components/TagHero';
import TagDiscussionModal from './components/TagDiscussionModal';
import TagsPage from './components/TagsPage';
import ToggleButton from './components/ToggleButton';
import DiscussionTaggedPost from './components/DiscussionTaggedPost';
import TagLinkButton from './components/TagLinkButton';
import addTagList from './addTagList';
import addTagLabels from './addTagLabels';
import addTagComposer from './addTagComposer';
import getSelectableTags from './utils/getSelectableTags';

export default Object.assign(compat, {
  'tags/addTagFilter': addTagFilter,
  'tags/addTagControl': addTagControl,
  'tags/components/TagHero': TagHero,
  'tags/components/TagDiscussionModal': TagDiscussionModal,
  'tags/components/TagsPage': TagsPage,
  'tags/components/ToggleButton': ToggleButton,
  'tags/components/DiscussionTaggedPost': DiscussionTaggedPost,
  'tags/components/TagLinkButton': TagLinkButton,
  'tags/addTagList': addTagList,
  'tags/addTagLabels': addTagLabels,
  'tags/addTagComposer': addTagComposer,
  'tags/utils/getSelectableTags': getSelectableTags,
});
