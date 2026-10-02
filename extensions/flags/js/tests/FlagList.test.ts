import { jest } from '@jest/globals';
import mq from 'mithril-query';
import m from 'mithril';
import app from 'flarum/forum/app';
import FlagList from '../src/forum/components/FlagList';
import HeaderList from 'flarum/forum/components/HeaderList';
import { bootFlags, resources, flag, listState } from './fixtures';

beforeEach(() => {
  // A thrown Mithril view leaves this sentinel set. Reset it so a regression
  // in one case cannot make later renders silently return an empty component.
  delete (FlagList as any).$$reentrantLock$$;
  bootFlags();
  // HeaderList's scroll listeners measure layout through a separate JSDOM
  // window in the shared harness. Layout is unrelated to target rendering.
  jest.spyOn(HeaderList.prototype, 'oncreate').mockImplementation(() => {});
  jest.spyOn(HeaderList.prototype, 'onremove').mockImplementation(() => {});
});
afterEach(() => jest.restoreAllMocks());

describe('mixed post and account moderation list', () => {
  it('renders an account report without a post and routes to the reported account, not the reporter', () => {
    const { targetUser, reporter } = resources();
    const accountFlag = flag('101', { post: { data: null }, targetUser: { data: { type: 'users', id: '5' } } });

    const list = mq(FlagList, { state: listState([accountFlag]) });

    expect(list).toContainRaw('Reported account');
    expect(list).toContainRaw('Account report detail');
    expect(list.first('a.Flag--user').getAttribute('href')).toBe(m.route.prefix + app.route.user(targetUser));
    expect(list.first('a.Flag--user').getAttribute('href')).not.toBe(m.route.prefix + app.route.user(reporter));
  });

  it('preserves post routing and excerpts alongside an account report in the same page', () => {
    const { post, targetUser } = resources();
    // JSDOM lacks innerText used by Post.contentPlain(); its HTML-to-text
    // utility is separate from choosing the report target and excerpt.
    jest.spyOn(post, 'contentPlain').mockReturnValue('Post excerpt');
    const postFlag = flag('102', { post: { data: { type: 'posts', id: '11' } }, targetUser: { data: null } }, 'Post report detail');
    const accountFlag = flag('103', { post: { data: null }, targetUser: { data: { type: 'users', id: '5' } } });

    const list = mq(FlagList, { state: listState([postFlag, accountFlag]) });

    expect(list).toHaveElement(`a[href="${m.route.prefix + app.route.post(post)}"]`);
    expect(list).toHaveElement(`a[href="${m.route.prefix + app.route.user(targetUser)}"]`);
    expect(list).toContainRaw('Post discussion');
    expect(list).toContainRaw('Post excerpt');
    expect(list).toContainRaw('Account report detail');
  });

  it('tolerates a report whose deleted post and deleted target account are both unavailable', () => {
    resources();
    const staleFlag = flag('104', { post: { data: null }, targetUser: { data: null } });

    expect(() => mq(FlagList, { state: listState([staleFlag]) })).not.toThrow();
  });

  it('does not dereference a missing discussion on a surviving post report', () => {
    const { post } = resources();
    post.pushData({ relationships: { discussion: { data: null } } });
    const postFlag = flag('105', { post: { data: { type: 'posts', id: '11' } }, targetUser: { data: null } });

    expect(() => mq(FlagList, { state: listState([postFlag]) })).not.toThrow();
  });

  it('groups repeated reports about an account without conflating the same ID in the post namespace', () => {
    const { post, targetUser } = resources();
    const collidingPost = app.store.pushPayload({ data: { ...post.data, id: '5' } });
    jest.spyOn(collidingPost, 'contentPlain').mockReturnValue('Post excerpt');
    const first = flag('106', { post: { data: null }, targetUser: { data: { type: 'users', id: '5' } } });
    const second = flag('107', { post: { data: null }, targetUser: { data: { type: 'users', id: '5' } } });
    const postFlag = flag('108', { post: { data: { type: 'posts', id: '5' } }, targetUser: { data: null } });

    const list = mq(FlagList, { state: listState([first, second, postFlag]) });

    expect(list.find('li').length).toBe(2);
    expect(list.find('a.Flag--user').length).toBe(1);
    expect(list).toHaveElement(`a[href="${m.route.prefix + app.route.user(targetUser)}"]`);
    expect(list).toHaveElement(`a[href="${m.route.prefix + app.route.post(collidingPost)}"]`);
  });

  it('skips an ambiguous report with both targets while continuing to render an unambiguous account report', () => {
    const { post, targetUser } = resources();
    jest.spyOn(post, 'contentPlain').mockReturnValue('Post excerpt');
    const ambiguous = flag('109', { post: { data: { type: 'posts', id: '11' } }, targetUser: { data: { type: 'users', id: '5' } } });
    const accountFlag = flag('110', { post: { data: null }, targetUser: { data: { type: 'users', id: '5' } } });

    const list = mq(FlagList, { state: listState([ambiguous, accountFlag]) });

    expect(list.find('li').length).toBe(1);
    expect(list.first('a.Flag--user').getAttribute('href')).toBe(m.route.prefix + app.route.user(targetUser));
  });
});
