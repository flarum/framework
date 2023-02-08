import app from 'flarum/forum/app';
import Model from 'flarum/common/Model';
import Discussion from 'flarum/common/models/Discussion';

import TagListState from '../common/states/TagListState';
import Tag from '../common/models/Tag';

import addTagList from './addTagList';
import addTagFilter from './addTagFilter';
import addTagLabels from './addTagLabels';
import addTagControl from './addTagControl';
import addTagComposer from './addTagComposer';

export { default as extend } from './extend';

app.initializers.add('flarum-tags', function () {
  app.store.models.tags = Tag;

  app.tagList = new TagListState();

  Discussion.prototype.tags = Model.hasMany<Tag>('tags');
  Discussion.prototype.canTag = Model.attribute<boolean>('canTag');

  addTagList();
  addTagFilter();
  addTagLabels();
  addTagControl();
  addTagComposer();
});

// Expose compat API
import tagsCompat from './compat';
import { compat } from '@flarum/core/forum';

Object.assign(compat, tagsCompat);
