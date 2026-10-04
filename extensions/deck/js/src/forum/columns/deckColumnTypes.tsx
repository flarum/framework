import app from 'flarum/forum/app';
import ItemList from 'flarum/common/utils/ItemList';
import Avatar from 'flarum/common/components/Avatar';
import highlight from 'flarum/common/helpers/highlight';
import type User from 'flarum/common/models/User';
import extractText from 'flarum/common/utils/extractText';
import type Discussion from 'flarum/common/models/Discussion';
import AuthorDiscussionsSource from './AuthorDiscussionsSource';
import AuthorPostsSource from './AuthorPostsSource';
import GroupPostsSource from './GroupPostsSource';
import GroupBadge from 'flarum/common/components/GroupBadge';
import GroupSelector from 'flarum/common/components/GroupSelector';
import Group from 'flarum/common/models/Group';
import DiscussionListSource from './DiscussionListSource';
import DiscussionPostsSource from './DiscussionPostsSource';
import NotificationsSource from './NotificationsSource';
import TypingActivitySource from './TypingActivitySource';
import type { DeckColumnConfig, DeckColumnField, DeckColumnType } from './DeckColumnType';

/**
 * Every kind of column a member can add, keyed by the `type` stored in their
 * layout. Extensions register theirs with the DeckColumns extender.
 */
const deckColumnTypes = new ItemList<DeckColumnType>();

export default deckColumnTypes;

const isEnabled = (extension: string): boolean => extension in flarum.extensions;

/**
 * A filter column's query as API filters. Only gambits are allowed: free text
 * would be a full-text search re-run on every refresh, so it's rejected when
 * the column is added, and dropped here.
 */
export const gambitFilters = (query: string): Record<string, unknown> => {
  const filter = app.search.gambits.apply('discussions', { q: query });

  delete filter.q;

  return filter;
};

/** Shared by the member columns: pick a member by searching, store their id. */
const memberField = (): DeckColumnField => ({
  key: 'user',
  label: app.translator.trans('flarum-deck.forum.add_column.author_label'),
  placeholder: extractText(app.translator.trans('flarum-deck.forum.add_column.author_placeholder')),
  search: {
    find: (query: string) => app.store.find<User[]>('users', { filter: { q: query }, page: { limit: 6 } }),
    display: (user: User, query: string) => [<Avatar user={user} />, ' ', highlight(user.displayName(), query)],
    label: (user: User) => user.displayName(),
    params: (user: User) => ({ userId: user.id()!, name: user.displayName() }),
  },
});

const memberName = (config: DeckColumnConfig): string =>
  app.store.getById<User>('users', String(config.params.userId))?.displayName() ?? String(config.params.name);

const onlyGambits = (query: string): string | null => {
  const leftover = app.search.gambits.match('discussions', query, () => {});

  return query && !leftover ? query : null;
};

export function registerDefaultColumnTypes(): void {
  deckColumnTypes.add(
    'all',
    {
      icon: 'far fa-comments',
      label: () => extractText(app.translator.trans('flarum-deck.forum.column_types.all')),
      title: () => extractText(app.translator.trans('flarum-deck.forum.column_types.all')),
      isAvailable: () => true,
      createSource: () => new DiscussionListSource({}),
    },
    90
  );

  deckColumnTypes.add(
    'unread',
    {
      icon: 'fas fa-circle-dot',
      label: () => extractText(app.translator.trans('flarum-deck.forum.column_types.unread')),
      title: () => extractText(app.translator.trans('flarum-deck.forum.column_types.unread')),
      isAvailable: () => true,
      createSource: () => new DiscussionListSource({ filter: { unread: true } }),
    },
    80
  );

  deckColumnTypes.add(
    'notifications',
    {
      icon: 'fas fa-bell',
      label: () => extractText(app.translator.trans('core.forum.notifications.title')),
      title: () => extractText(app.translator.trans('core.forum.notifications.title')),
      isAvailable: () => true,
      createSource: () => new NotificationsSource(),
    },
    60
  );

  deckColumnTypes.add(
    'author',
    {
      icon: 'fas fa-user',
      label: () => extractText(app.translator.trans('flarum-deck.forum.column_types.author')),
      title: (config) => extractText(app.translator.trans('flarum-deck.forum.column_types.author_title', { username: memberName(config) })),
      isAvailable: () => true,
      fields: () => [memberField()],
      createSource: (config) => new AuthorDiscussionsSource(String(config.params.userId)),
    },
    40
  );

  deckColumnTypes.add(
    'authorPosts',
    {
      icon: 'far fa-comment-dots',
      label: () => extractText(app.translator.trans('flarum-deck.forum.column_types.author_posts')),
      title: (config) => extractText(app.translator.trans('flarum-deck.forum.column_types.author_posts_title', { username: memberName(config) })),
      isAvailable: () => true,
      fields: () => [memberField()],
      createSource: (config) => new AuthorPostsSource(String(config.params.userId)),
    },
    38
  );

  deckColumnTypes.add(
    'groupPosts',
    {
      icon: 'fas fa-users',
      label: () => extractText(app.translator.trans('flarum-deck.forum.column_types.group_posts')),
      title: (config) => {
        const group = app.store.getById<Group>('groups', String(config.params.groupId));

        return group
          ? extractText(app.translator.trans('flarum-deck.forum.column_types.group_posts_title', { group: group.namePlural() }))
          : extractText(app.translator.trans('flarum-deck.forum.column_types.group_posts'));
      },
      badge: (config) => {
        const group = app.store.getById<Group>('groups', String(config.params.groupId));

        return group && <GroupBadge group={group} label={null} className="DeckColumn-badge" />;
      },
      isAvailable: () => true,
      fields: () => [
        {
          key: 'groupId',
          label: app.translator.trans('flarum-deck.forum.add_column.group_label'),
          // Guests can't post, and Members is everyone, no different from all posts.
          input: ({ id, value, onchange }) => <GroupSelector id={id} value={value} onchange={onchange} exclude={[Group.GUEST_ID, Group.MEMBER_ID]} />,
          invalidText: app.translator.trans('flarum-deck.forum.add_column.group_invalid_message'),
        },
      ],
      createSource: (config) => new GroupPostsSource(String(config.params.groupId)),
    },
    36
  );

  deckColumnTypes.add(
    'discussion',
    {
      icon: 'fas fa-comment',
      label: () => extractText(app.translator.trans('flarum-deck.forum.column_types.discussion')),
      title: (config) =>
        app.store.getById<Discussion>('discussions', String(config.params.id))?.title() ??
        String(config.params.title ?? extractText(app.translator.trans('flarum-deck.forum.column_types.discussion'))),
      isAvailable: () => true,
      fields: () => [
        {
          key: 'discussion',
          label: app.translator.trans('flarum-deck.forum.add_column.discussion_label'),
          placeholder: extractText(app.translator.trans('flarum-deck.forum.add_column.discussion_placeholder')),
          // Searches only while the member is typing here, never on refresh.
          search: {
            find: (query: string) => app.store.find<Discussion[]>('discussions', { filter: { q: query }, page: { limit: 6 } }),
            display: (discussion: Discussion, query: string) => highlight(discussion.title(), query),
            label: (discussion: Discussion) => discussion.title(),
            params: (discussion: Discussion) => ({ id: discussion.id()!, title: discussion.title() }),
          },
        },
      ],
      createSource: (config) => new DiscussionPostsSource(String(config.params.id)),
    },
    30
  );

  deckColumnTypes.add(
    'typing',
    {
      icon: 'fas fa-keyboard',
      label: () => extractText(app.translator.trans('flarum-deck.forum.column_types.typing')),
      title: () => extractText(app.translator.trans('flarum-deck.forum.column_types.typing')),
      // flarum/realtime sends the feed; it also decides who may subscribe.
      isAvailable: () => isEnabled('flarum-realtime') && !!app.session.user?.attribute<boolean>('canViewAllTyping'),
      createSource: () => new TypingActivitySource(),
    },
    20
  );

  deckColumnTypes.add(
    'filter',
    {
      icon: 'fas fa-filter',
      label: () => extractText(app.translator.trans('flarum-deck.forum.column_types.filter')),
      title: (config) => String(config.params.query),
      isAvailable: () => true,
      fields: () => [
        {
          key: 'query',
          label: app.translator.trans('flarum-deck.forum.add_column.filter_label'),
          placeholder: extractText(app.translator.trans('flarum-deck.forum.add_column.filter_placeholder')),
          help: app.translator.trans('flarum-deck.forum.add_column.filter_help'),
          // Every extension's registered discussion gambits, as in the search modal.
          gambits: 'discussions',
          parse: onlyGambits,
          invalidText: app.translator.trans('flarum-deck.forum.add_column.filter_invalid_message'),
        },
      ],
      createSource: (config) => new DiscussionListSource({ filter: gambitFilters(String(config.params.query)) }),
    },
    35
  );
}
