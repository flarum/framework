import sortTags from './utils/sortTags';
import Tag from './models/Tag';
import tagsLabel from './helpers/tagsLabel';
import tagIcon from './helpers/tagIcon';
import tagLabel from './helpers/tagLabel';

export default {
  'tags/utils/sortTags': sortTags,
  'tags/models/Tag': Tag,
  'tags/helpers/tagsLabel': tagsLabel,
  'tags/helpers/tagIcon': tagIcon,
  'tags/helpers/tagLabel': tagLabel
};
