import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { makeUser, makeDiscussion } from '@flarum/jest-config/factory';
import app from 'flarum/forum/app';
import FlagListState from '../src/forum/states/FlagListState';
import extension from '../src/forum/extend';
import fs from 'fs';
import path from 'path';
import flatten from 'flat';
import yaml from 'js-yaml';

export function bootFlags() {
  // Registry and model extensions persist between app.boot() calls; payloads
  // must not, or one test can inherit another account's permissions/reports.
  (app.store as any).data = {};
  bootstrapForum();
  app.boot();
  if (!app.store.models.flags) app.bootExtensions({ 'flarum-flags': { extend: extension } });
  app.translator.addTranslations(flatten(yaml.load(fs.readFileSync(path.resolve(__dirname, '../../locale/en.yml'), 'utf8'))));
  app.forum.pushAttributes({ apiUrl: 'https://forum.example/api' });
}

export function resources() {
  app.store.pushPayload({
    data: [
      makeUser({ id: '5', attributes: { displayName: 'Reported account', username: 'reported', slug: 'reported', canFlagUser: true } }),
      makeUser({ id: '7', attributes: { displayName: 'Reporter', username: 'reporter', slug: 'reporter' } }),
      makeDiscussion({ id: '13', attributes: { title: 'Post discussion', slug: '13-post-discussion' } }),
      {
        type: 'posts',
        id: '11',
        attributes: { contentType: 'comment', contentHtml: 'Post excerpt', number: 2, canFlag: true },
        relationships: { user: { data: { type: 'users', id: '7' } }, discussion: { data: { type: 'discussions', id: '13' } } },
      },
    ],
  });

  return {
    targetUser: app.store.getById('users', '5')!,
    reporter: app.store.getById('users', '7')!,
    post: app.store.getById('posts', '11')!,
  };
}

export function flag(id: string, relationships: Record<string, any>, detail = 'Account report detail') {
  return app.store.pushPayload({
    data: {
      type: 'flags',
      id,
      attributes: { type: 'user', reason: 'spam', reasonDetail: detail, createdAt: '2026-10-01T12:00:00Z' },
      relationships: { user: { data: { type: 'users', id: '7' } }, ...relationships },
    },
  });
}

export function listState(flags: any[]) {
  const state = new FlagListState(app);
  (state as any).pages = [{ number: 1, items: flags }];
  return state;
}

export async function settle() {
  // The submit handler intentionally does not return its promise. Drain its
  // save -> success/error -> loaded chain without timers or network activity.
  for (let i = 0; i < 6; i++) await Promise.resolve();
}
