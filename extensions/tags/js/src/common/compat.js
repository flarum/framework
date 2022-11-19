import sortTags from './utils/sortTags';
import Tag from './models/Tag';
import tagsLabel from './helpers/tagsLabel';
import tagIcon from './helpers/tagIcon';
import tagLabel from './helpers/tagLabel';
import TagSelectionModal from './components/TagSelectionModal';
import TagListState from './states/TagListState';

export default {
  'tags/utils/sortTags': sortTags,
  'tags/models/Tag': Tag,
  'tags/helpers/tagsLabel': tagsLabel,
  'tags/helpers/tagIcon': tagIcon,
  'tags/helpers/tagLabel': tagLabel,
  'tags/components/TagSelectionModal': TagSelectionModal,
  'tags/states/TagListState': TagListState,
};
