import type Mithril from 'mithril';
import { ClassComponent, Vnode } from 'mithril';
import app from 'flarum/common/app';
import Badge from 'flarum/common/components/Badge';
import Button from 'flarum/common/components/Button';
import Dropdown from 'flarum/common/components/Dropdown';
import GroupBadge from 'flarum/common/components/GroupBadge';
import IPAddress from 'flarum/common/components/IPAddress';
import Link from 'flarum/common/components/Link';
import Avatar from 'flarum/common/components/Avatar';
import Icon from 'flarum/common/components/Icon';
import humanTime from 'flarum/common/helpers/humanTime';
import username from 'flarum/common/helpers/username';
import Group from 'flarum/common/models/Group';
import extractText from 'flarum/common/utils/extractText';
import ItemList from 'flarum/common/utils/ItemList';
import AuditLog from '../models/AuditLog';
import routes from '../routeHelpers';

const translationPrefix = 'flarum-audit.lib.browser.';

function formatGroups(groupIds: string[] | null | undefined, emptyIsAdmin = false) {
  let groupIdsCopy: string[] = groupIds ? JSON.parse(JSON.stringify(groupIds)) : [];

  if (!groupIdsCopy.length) {
    if (emptyIsAdmin) {
      groupIdsCopy.push(Group.ADMINISTRATOR_ID);
    } else {
      return <em>{app.translator.trans(translationPrefix + 'noValue')}</em>;
    }
  }

  return groupIdsCopy
    .map((groupId) => {
      if (groupId + '' === Group.GUEST_ID) {
        return <Badge icon="fas fa-globe" label={app.translator.trans(translationPrefix + 'permissionGroup.everyone')} />;
      }

      if (groupId + '' === Group.MEMBER_ID) {
        return <Badge icon="fas fa-user" label={app.translator.trans(translationPrefix + 'permissionGroup.members')} />;
      }

      const group = app.store.getById('groups', groupId);

      if (group) {
        return <GroupBadge group={group} />;
      }

      return (
        <Badge
          icon="fas fa-question"
          label={extractText(
            app.translator.trans(translationPrefix + 'deletedResource.group', {
              id: groupId,
            })
          )}
        />
      );
    })
    .map((vnode, index) => [index > 0 ? ', ' : null, vnode]);
}

function formatTags(tagSlugs: string[] | null | undefined) {
  let tagSlugsCopy = tagSlugs || [];

  return tagSlugsCopy.map((slug, index) => [index > 0 ? ', ' : null, <code>{slug}</code>]);
}

interface AuditItemAttrs {
  log: AuditLog;
  changeQuery: (q: string) => void;
}

export default class AuditItem implements ClassComponent<AuditItemAttrs> {
  showRaw: boolean = false;

  view(vnode: Vnode<AuditItemAttrs>) {
    const { log, changeQuery } = vnode.attrs;

    const actor = log.actor();
    const payload = log.payload() || {};
    const discussion = log.discussion();
    const newDiscussion = log.newDiscussion();
    const post = log.post();
    const postUser = post ? post.user() : null;
    const tag = log.tag();
    const user = log.user();

    const clientRow = [];

    if (log.ipAddress()) {
      // Core's IPAddress component renders the IP; fof/geoip (when installed) extends it to add
      // the country flag, tooltip and lookup. Click-to-filter by IP lives in the controls dropdown.
      clientRow.push(<IPAddress ip={log.ipAddress()} />);
    }

    if (log.client() !== 'session' && log.client() !== 'cli') {
      clientRow.push(
        <Button className="Button Button--link" onclick={() => changeQuery('client:' + log.client())}>
          {app.translator.trans(translationPrefix + 'client.' + log.client())}
        </Button>
      );
    }

    clientRow.push(humanTime(log.createdAt()!));

    let avatarElement;

    if (log.client() === 'cli') {
      avatarElement = <Icon name="fas fa-terminal" />;
    } else if (log.actorId() === null) {
      avatarElement = <Icon name="fas fa-user-secret" />;
    } else if (actor) {
      avatarElement = (
        <Link external href={routes().user(actor)}>
          <Avatar user={actor} />
        </Link>
      );
    } else {
      // In this case actorId isn't null but actor is, which means the user was deleted
      avatarElement = <Avatar user={null} />;
    }

    let usernameElement;

    if (log.client() === 'cli') {
      usernameElement = (
        <Button className="Button Button--link" onclick={() => changeQuery('client:cli')}>
          {app.translator.trans(translationPrefix + 'client.cli')}
        </Button>
      );
    } else if (log.actorId() === null) {
      usernameElement = (
        <Button className="Button Button--link" onclick={() => changeQuery('actor:guest')}>
          {app.translator.trans(translationPrefix + 'withoutActor')}
        </Button>
      );
    } else if (actor) {
      usernameElement = (
        <Button className="Button Button--link" onclick={() => changeQuery('actor:' + actor.username())}>
          {username(actor)}
        </Button>
      );
    } else {
      // In this case actorId isn't null but actor is, which means the user was deleted
      usernameElement = username(actor);
    }

    let formattedPayload;

    let translationKeyForPayload = translationPrefix + log.action();

    if (log.action() === 'setting_changed' && payload.hasOwnProperty('new_value')) {
      translationKeyForPayload = translationPrefix + 'setting_changed_with_values';
    }

    // A reset attempt for an email with no matching account uses a distinct message.
    if (log.action() === 'user.password_reset_attempted' && !payload.matched) {
      translationKeyForPayload = translationPrefix + 'user.password_reset_attempted_unmatched';
    }

    // In Flarum 2.x a stored translation may be a plain string OR a `Translation` object
    // ({ message }) — `trans()` rewrites the entry to the object form on first use. So we
    // can't probe with `typeof === 'string'` (it flips to false after the first render and
    // we'd fall back to raw JSON). Treat any present entry as "a message exists".
    if (app.translator.translations[translationKeyForPayload]) {
      const parameters = {
        // We can't call this translation parameter {user} because it's reserved by Flarum
        username: (
          <Link external href={user ? routes().user(user) : '#'}>
            {user
              ? username(user)
              : app.translator.trans(translationPrefix + 'deletedResource.user', {
                  id: payload.user_id,
                })}
          </Link>
        ),

        discussion: (
          <Link external href={discussion ? routes().discussion(discussion) : '#'}>
            {discussion
              ? discussion.title()
              : app.translator.trans(translationPrefix + 'deletedResource.discussion', {
                  id: payload.discussion_id,
                })}
          </Link>
        ),

        new_discussion: (
          <Link external href={newDiscussion ? routes().discussion(newDiscussion) : '#'}>
            {newDiscussion
              ? newDiscussion.title()
              : app.translator.trans(translationPrefix + 'deletedResource.discussion', {
                  id: payload.new_discussion_id,
                })}
          </Link>
        ),

        tag: (
          <Link external href={tag ? routes().tag(tag) : '#'}>
            {tag
              ? tag.name()
              : app.translator.trans(translationPrefix + 'deletedResource.tag', {
                  id: payload.tag_id,
                })}
          </Link>
        ),

        post: (
          <Link external href={post && post.discussion() ? routes().post(post) : '#'}>
            {post
              ? app.translator.trans(translationPrefix + 'genericResource.' + (post.contentType() === 'comment' ? 'comment' : 'post'))
              : app.translator.trans(translationPrefix + 'deletedResource.post', {
                  id: payload.post_id,
                })}
          </Link>
        ),

        postuser: (
          <Link external href={postUser ? routes().user(postUser) : '#'}>
            {username(postUser || null)}
          </Link>
        ),

        until: payload.until ? dayjs(payload.until).format('LLLL') : '?',

        old_title: <em>{payload.old_title}</em>,

        new_title:
          payload.new_title && discussion ? (
            <Link external href={routes().discussion(discussion)}>
              {payload.new_title}
            </Link>
          ) : (
            payload.new_title
          ),

        package: <code>{payload.package}</code>,
        provider: <code>{payload.provider}</code>,
        ip: <code>{payload.ip}</code>,

        name: <code>{payload.name}</code>,
        old_name: <code>{payload.old_name}</code>,
        new_name: <code>{payload.new_name}</code>,
        title: <code>{payload.title}</code>,
        extension: payload.extension ? <code>{payload.extension}</code> : <em>{app.translator.trans(translationPrefix + 'core')}</em>,
        keys: <code>{Array.isArray(payload.keys) ? payload.keys.join(', ') : payload.keys}</code>,

        key: <code>{payload.key}</code>,
        permission: <code>{payload.permission}</code>,
        old_value: payload.old_value ? <code>{payload.old_value}</code> : <em>{app.translator.trans(translationPrefix + 'noValue')}</em>,
        new_value: payload.new_value ? <code>{payload.new_value}</code> : <em>{app.translator.trans(translationPrefix + 'noValue')}</em>,

        old_groups: formatGroups(payload.old_group_ids, log.action() === 'permission_changed'),
        new_groups: formatGroups(payload.new_group_ids, log.action() === 'permission_changed'),

        old_username: <code>{payload.old_username}</code>,
        new_username: <code>{payload.new_username}</code>,

        old_nickname: payload.old_nickname ? <code>{payload.old_nickname}</code> : <em>{app.translator.trans(translationPrefix + 'noValue')}</em>,
        new_nickname: payload.new_nickname ? <code>{payload.new_nickname}</code> : <em>{app.translator.trans(translationPrefix + 'noValue')}</em>,

        old_email: <code>{payload.old_email}</code>,
        new_email: <code>{payload.new_email}</code>,
        email: <code>{payload.email}</code>,

        old_tags: formatTags(payload.old_tags),
        new_tags: formatTags(payload.new_tags),

        original_discussion_ids_count: Array.isArray(payload.original_discussion_ids) ? (
          payload.original_discussion_ids.length
        ) : (
          <em>{app.translator.trans(translationPrefix + 'noValue')}</em>
        ),
        post_count: payload.post_count,

        old_user: payload.old_user_id ? (
          app.translator.trans(translationPrefix + 'deletedResource.user', {
            id: payload.old_user_id,
          })
        ) : (
          <em>{app.translator.trans(translationPrefix + 'noValue')}</em>
        ),
        new_user: payload.new_user_id ? (
          app.translator.trans(translationPrefix + 'deletedResource.user', {
            id: payload.new_user_id,
          })
        ) : (
          <em>{app.translator.trans(translationPrefix + 'noValue')}</em>
        ),

        old_date: payload.old_date ? dayjs(payload.old_date).format('LLLL') : <em>{app.translator.trans(translationPrefix + 'noValue')}</em>,
        new_date: payload.new_date ? dayjs(payload.new_date).format('LLLL') : <em>{app.translator.trans(translationPrefix + 'noValue')}</em>,

        reason: payload.reason ? <code>{payload.reason}</code> : <em>{app.translator.trans(translationPrefix + 'noReason')}</em>,

        deleted_count: payload.deleted_count,
      };

      formattedPayload = app.translator.trans(translationKeyForPayload, parameters);

      if (this.showRaw) {
        formattedPayload = [formattedPayload, <pre>{JSON.stringify(payload, null, 2)}</pre>];
      }
    } else {
      formattedPayload = JSON.stringify(payload);
    }

    const controls = new ItemList<Mithril.Children>();

    controls.add(
      'raw',
      <Button onclick={() => (this.showRaw = !this.showRaw)}>
        {app.translator.trans(translationPrefix + 'controls.' + (this.showRaw ? 'hideRaw' : 'showRaw'))}
      </Button>
    );

    if (actor) {
      controls.add(
        'actor',
        <Button onclick={() => changeQuery('actor:' + actor.username())}>{app.translator.trans(translationPrefix + 'controls.filterActor')}</Button>
      );
    }

    if (log.ipAddress()) {
      controls.add(
        'ip',
        <Button onclick={() => changeQuery('ip:' + log.ipAddress())}>{app.translator.trans(translationPrefix + 'controls.filterIp')}</Button>
      );
    }

    controls.add(
      'client',
      <Button onclick={() => changeQuery('client:' + log.client())}>{app.translator.trans(translationPrefix + 'controls.filterClient')}</Button>
    );

    controls.add(
      'action',
      <Button onclick={() => changeQuery('action:' + log.action())}>{app.translator.trans(translationPrefix + 'controls.filterAction')}</Button>
    );

    if (user) {
      controls.add(
        'user',
        <Button onclick={() => changeQuery('user:' + user.username())}>{app.translator.trans(translationPrefix + 'controls.filterUser')}</Button>
      );
    }

    if (payload.discussion_id) {
      controls.add(
        'discussion',
        <Button onclick={() => changeQuery('discussion:' + payload.discussion_id)}>
          {app.translator.trans(translationPrefix + 'controls.filterDiscussion')}
        </Button>
      );
    }

    return (
      <div className="AuditItem">
        <div className="AuditItemAvatar">{avatarElement}</div>
        <div className="AuditItemData">
          <Dropdown
            menuClassName="Dropdown-menu--right"
            buttonClassName="Button Button--icon Button--flat"
            label={app.translator.trans(translationPrefix + 'controls.title')}
            icon="fas fa-ellipsis-v"
          >
            {controls.toArray()}
          </Dropdown>
          <div className="AuditItemRow">
            {usernameElement}
            {' - '}
            <Button className="Button Button--link" onclick={() => changeQuery('action:' + log.action())}>
              {log.action()}
            </Button>
          </div>
          <div className="AuditItemRow">{formattedPayload}</div>
          <div className="AuditItemRow">{clientRow.map((text, i) => [i === 0 ? null : ' - ', text])}</div>
        </div>
      </div>
    );
  }
}
