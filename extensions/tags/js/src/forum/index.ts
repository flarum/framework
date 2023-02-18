import app from 'flarum/forum/app';

import TagListState from '../common/states/TagListState';

import addTagList from './addTagList';
import addTagFilter from './addTagFilter';
import addTagLabels from './addTagLabels';
import addTagControl from './addTagControl';
import addTagComposer from './addTagComposer';

export { default as extend } from './extend';

app.initializers.add('flarum-tags', function () {
  app.tagList = new TagListState();

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
