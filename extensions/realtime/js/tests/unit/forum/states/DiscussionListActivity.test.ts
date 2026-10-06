import { jest } from '@jest/globals';
import bootstrapForum from '@flarum/jest-config/src/bootstrap/forum';
import { dirname, resolve } from 'path';
import { fileURLToPath } from 'url';
import app from 'flarum/forum/app';
import Discussion from 'flarum/common/models/Discussion';
import DiscussionListState from 'flarum/forum/states/DiscussionListState';

import DiscussionListActivity from '../../../../src/forum/states/DiscussionListActivity';

const testDir = dirname(fileURLToPath(import.meta.url));
const coreJsDir = resolve(testDir, '../../../../../../../framework/core/js');

beforeAll(() => {
  const cwd = process.cwd();

  try {
    // bootstrap reads core's locale file relative to the working directory.
    process.chdir(coreJsDir);
    bootstrapForum();
  } finally {
    process.chdir(cwd);
  }
});

afterEach(() => {
  jest.useRealTimers();
});

/** Stands in for a Pusher channel. */
class FakeChannel {
  handlers = new Map<string, Array<(data: unknown) => void>>();

  bind(event: string, handler: (data: unknown) => void) {
    this.handlers.set(event, [...(this.handlers.get(event) ?? []), handler]);
  }

  unbind(event: string, handler: (data: unknown) => void) {
    this.handlers.set(
      event,
      (this.handlers.get(event) ?? []).filter((h) => h !== handler)
    );
  }

  emit(event: string, data: unknown) {
    (this.handlers.get(event) ?? []).forEach((handler) => handler(data));
  }
}

/** 0 releases only when the reader clicks the button. */
function releaseInterval(seconds: number) {
  app.store.pushPayload({
    data: { type: 'forums', id: '1', attributes: { 'flarum-realtime.release-discussion-updates-interval': seconds } },
  } as any);
  app.forum = app.store.getById('forums', '1')!;
}

function discussion(id: string, lastPostedAt: string): Discussion {
  return app.store.pushPayload({ data: { type: 'discussions', id, attributes: { title: `Discussion ${id}`, lastPostedAt } } } as any) as any;
}

/** A reply as realtime delivers it: the post, with its discussion included. */
function reply(postId: string, discussionId: string, lastPostedAt: string) {
  return {
    data: {
      type: 'posts',
      id: postId,
      attributes: { contentType: 'comment', createdAt: lastPostedAt },
      relationships: { discussion: { data: { type: 'discussions', id: discussionId } } },
    },
    included: [{ type: 'discussions', id: discussionId, attributes: { lastPostedAt } }],
  };
}

function list(params: Record<string, unknown>, ...discussions: Discussion[]): DiscussionListState {
  const state = new DiscussionListState(params as any);
  (state as any).pages = [{ number: 1, items: discussions, hasPrev: false, hasNext: false }];
  app.discussions = state;

  return state;
}

const listedIds = () => app.discussions.getPages().flatMap((page) => page.items.map((d) => d.id()));

const POSTED = 'Flarum\\Post\\Event\\Posted';

describe('DiscussionListActivity', () => {
  let channel: FakeChannel;
  let activity: DiscussionListActivity;
  let d1: Discussion, d2: Discussion, d3: Discussion;

  beforeEach(() => {
    releaseInterval(10);

    d1 = discussion('1', '2026-10-06T10:03:00Z');
    d2 = discussion('2', '2026-10-06T10:02:00Z');
    d3 = discussion('3', '2026-10-06T10:01:00Z');
    list({}, d1, d2, d3);

    channel = new FakeChannel();
    activity = new DiscussionListActivity();
    activity.bind(channel as any);
  });

  afterEach(() => {
    activity.updates.stopTimer();
  });

  it('keeps activity that arrives away from the list, and puts it on top before the list renders again', () => {
    activity.showList('index');
    activity.hideList();

    channel.emit(POSTED, reply('31', '3', '2026-10-06T10:10:00Z'));

    // Nothing moves while the reader is elsewhere.
    expect(listedIds()).toEqual(['1', '2', '3']);
    expect(activity.updates.length()).toBe(1);

    activity.showList('index');

    expect(listedIds()).toEqual(['3', '1', '2']);
    expect(activity.updates.isEmpty()).toBe(true);
  });

  it('moves the discussions with activity and leaves every other one in place', () => {
    activity.hideList();
    channel.emit(POSTED, reply('31', '3', '2026-10-06T10:10:00Z'));

    const page = (app.discussions as any).pages[0];
    activity.showList('index');

    // The same page and the same models: nothing was cleared and loaded again.
    expect((app.discussions as any).pages[0]).toBe(page);
    expect(page.items).toEqual([d1, d2]);
    expect(page.items[0]).toBe(d1);
  });

  it('puts the most recent activity on top', () => {
    activity.hideList();
    channel.emit(POSTED, reply('21', '2', '2026-10-06T10:20:00Z'));
    channel.emit(POSTED, reply('31', '3', '2026-10-06T10:10:00Z'));

    activity.showList('index');

    expect(listedIds()).toEqual(['2', '3', '1']);
  });

  it('with manual release, keeps the activity waiting for the button', () => {
    releaseInterval(0);
    activity.hideList();
    channel.emit(POSTED, reply('31', '3', '2026-10-06T10:10:00Z'));

    activity.showList('index');

    expect(listedIds()).toEqual(['1', '2', '3']);
    expect(activity.updates.length()).toBe(1);

    activity.release();

    expect(listedIds()).toEqual(['3', '1', '2']);
    expect(activity.updates.isEmpty()).toBe(true);
  });

  it('drops what it collected when the list is reloaded', () => {
    activity.hideList();
    channel.emit(POSTED, reply('31', '3', '2026-10-06T10:10:00Z'));

    activity.listReloaded();
    activity.showList('index');

    expect(listedIds()).toEqual(['1', '2', '3']);
  });

  it('ignores activity for the discussion already at the top', () => {
    activity.hideList();
    channel.emit(POSTED, reply('11', '1', '2026-10-06T10:10:00Z'));

    expect(activity.updates.isEmpty()).toBe(true);
  });

  it('breaks a tie in the same second by which activity arrived last', () => {
    activity.hideList();
    channel.emit(POSTED, reply('31', '3', '2026-10-06T10:10:00Z'));
    channel.emit(POSTED, reply('21', '2', '2026-10-06T10:10:00Z'));

    activity.showList('index');

    expect(listedIds()).toEqual(['2', '3', '1']);
  });

  it('keeps the top discussion on top when its activity is the most recent', () => {
    activity.hideList();
    channel.emit(POSTED, reply('31', '3', '2026-10-06T10:10:00Z'));
    channel.emit(POSTED, reply('11', '1', '2026-10-06T10:20:00Z'));

    activity.showList('index');

    expect(listedIds()).toEqual(['1', '3', '2']);
  });

  it.each([[{ q: 'search terms' }], [{ sort: 'oldest' }], [{ filter: { author: 'admin' } }]])(
    'ignores activity while the list is %j, which realtime cannot place',
    (params) => {
      list(params, d1, d2, d3);
      activity.hideList();
      channel.emit(POSTED, reply('31', '3', '2026-10-06T10:10:00Z'));

      expect(activity.updates.isEmpty()).toBe(true);
    }
  );

  it('binds a channel once, however often it is handed the same one', () => {
    activity.bind(channel as any);
    activity.bind(channel as any);

    expect(channel.handlers.get(POSTED)).toHaveLength(1);
  });

  it('listens on the channels a reconnect creates', () => {
    const reconnected = new FakeChannel();
    activity.bind(reconnected as any);
    activity.hideList();

    reconnected.emit(POSTED, reply('31', '3', '2026-10-06T10:10:00Z'));

    expect(activity.updates.length()).toBe(1);
  });

  it('counts activity in the title only while the list is shown', () => {
    const setTitleCount = jest.spyOn(app, 'setTitleCount');

    activity.hideList();
    channel.emit(POSTED, reply('31', '3', '2026-10-06T10:10:00Z'));
    expect(setTitleCount).not.toHaveBeenCalled();

    releaseInterval(0);
    activity.showList('index');
    expect(setTitleCount).toHaveBeenLastCalledWith(1);

    channel.emit(POSTED, reply('21', '2', '2026-10-06T10:20:00Z'));
    expect(setTitleCount).toHaveBeenLastCalledWith(2);

    setTitleCount.mockRestore();
  });

  it('stops the countdown when the list is left', () => {
    jest.useFakeTimers();
    activity.showList('index');
    channel.emit(POSTED, reply('31', '3', '2026-10-06T10:10:00Z'));
    activity.updates.startTimer();

    expect(activity.updates.isTimerRunning()).toBe(true);

    activity.hideList();

    expect(activity.updates.isTimerRunning()).toBe(false);
  });

  it('runs the countdown down to zero once', () => {
    jest.useFakeTimers();
    const ticks: number[] = [];
    activity.updates.onTimer((second) => ticks.push(second));
    activity.updates.startTimer();

    jest.advanceTimersByTime(15000);

    expect(ticks).toEqual([9, 8, 7, 6, 5, 4, 3, 2, 1, 0]);
    expect(activity.updates.isTimerRunning()).toBe(false);
  });
});
