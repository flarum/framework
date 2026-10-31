import app from 'flarum/forum/app';

import TagListState from '../common/states/TagListState';

import addTagList from './addTagList';
import addTagFilter from './addTagFilter';
import addTagLabels from './addTagLabels';
import addTagControl from './addTagControl';
import addTagComposer from './addTagComposer';
import extendRealtime from './extendRealtime';

export { default as extend } from './extend';

app.initializers.add('flarum-tags', () => {
  app.tagList = new TagListState();

  addTagList();
  addTagFilter();
  addTagLabels();
  addTagControl();
  addTagComposer();

  // Register a discussion stream update event with flarum/realtime when enabled.
  // Tag changes will trigger a DiscussionPage stream reload for other users.
  if ('flarum-realtime' in flarum.extensions) {
    extendRealtime();
  }
});

import './forum';
