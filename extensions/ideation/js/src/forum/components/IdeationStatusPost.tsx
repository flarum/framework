import app from 'flarum/forum/app';
import EventPost from 'flarum/forum/components/EventPost';
import Link from 'flarum/common/components/Link';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';
import type { IPostAttrs } from 'flarum/forum/components/Post';
import { ICONS, statusLabel } from '../status';

/** [old status, new status, id of the idea it was merged into, GitHub item that moved it]. */
type StatusPostContent = [string | null, string | null, number | null, string | null];

/**
 * "Ernest moved this to Planned", in the idea's discussion.
 * Content: [old, new, mergedIntoId].
 *
 * The idea it was merged into is stored by id only, so its title is loaded
 * through the API, which only answers someone allowed to see it.
 */
export default class IdeationStatusPost extends EventPost {
  target: Discussion | null = null;

  oninit(vnode: Mithril.Vnode<IPostAttrs, this>) {
    super.oninit(vnode);

    const id = this.parts()[2];
    if (!id) return;

    this.target = app.store.getById<Discussion>('discussions', String(id)) || null;

    // Posts only redraw when their subtree says something changed.
    this.subtree.check(() => this.target);

    if (!this.target) {
      app.store
        .find<Discussion>('discussions', String(id))
        .then((discussion) => (this.target = discussion))
        .catch(() => {})
        .finally(() => m.redraw());
    }
  }

  parts(): StatusPostContent | [] {
    return (this.attrs.post.content() as unknown as StatusPostContent | null) || [];
  }

  icon() {
    return ICONS[this.parts()[1] ?? ''] || 'fas fa-map-signs';
  }

  description(data: Record<string, unknown>) {
    const [old, next, , via] = this.parts();
    const key = via ? 'github_text' : next === 'merged' ? 'merged_text' : next === null ? 'removed_text' : old === null ? 'added_text' : 'moved_text';

    return <span>{app.translator.trans('flarum-ideation.forum.post_stream.' + key, data)}</span>;
  }

  descriptionData() {
    const [, next, targetId, via] = this.parts();

    return {
      via: via ? <strong>{via}</strong> : '',
      status: <strong className={'IdeationStatusPost-status IdeationStatus--' + next}>{next ? statusLabel(next) : ''}</strong>,
      target: targetId ? (
        <Link href={app.route('discussion', { id: String(targetId) })}>
          {this.target ? this.target.title() : app.translator.trans('flarum-ideation.forum.post_stream.another_idea')}
        </Link>
      ) : (
        ''
      ),
    };
  }
}
