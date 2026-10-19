import app from 'flarum/forum/app';
import Page, { type IPageAttrs } from 'flarum/common/components/Page';
import PageStructure from 'flarum/forum/components/PageStructure';
import IndexSidebar from 'flarum/forum/components/IndexSidebar';
import Button from 'flarum/common/components/Button';
import Dropdown from 'flarum/common/components/Dropdown';
import Icon from 'flarum/common/components/Icon';
import Link from 'flarum/common/components/Link';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import classList from 'flarum/common/utils/classList';
import extractText from 'flarum/common/utils/extractText';
import type Discussion from 'flarum/common/models/Discussion';
import type Mithril from 'mithril';

import VoteButton from './VoteButton';
import GithubLinks from './GithubLinks';
import BoardDrag from '../drag';
import { COLUMNS, ICONS, SETTABLE, statusLabel } from '../status';

const PAGE = 10;

type Column = { items: Discussion[]; loading: boolean; more: boolean };

type TagLike = { name(): string; color(): string | null };

/** The list view's sorts, as the API names them. */
const SORTS: Record<string, string> = { votes: '-ideationVotes', newest: '-createdAt', latest: '-lastPostedAt' };

/** The index sidebar, with "Post an idea" where "Start a discussion" was. */
class IdeationSidebar extends IndexSidebar {
  items() {
    const items = super.items();
    const canPost = !app.session.user || app.forum.attribute('ideationCanPost');

    items.setContent(
      'newDiscussion',
      <Button
        icon="fas fa-lightbulb"
        className="Button Button--primary IndexPage-newDiscussion"
        itemClassName="App-primaryControl"
        disabled={!canPost}
        onclick={() => postIdea()}
      >
        {app.translator.trans(canPost ? 'flarum-ideation.forum.post_idea' : 'flarum-ideation.forum.cannot_post_idea')}
      </Button>
    );

    return items;
  }
}

function postIdea() {
  if (!app.session.user) {
    app.modal.show(() => import('flarum/forum/components/LogInModal'));
    return;
  }

  app.composer
    .load(() => import('flarum/forum/components/DiscussionComposer'), {
      user: app.session.user,
      ideationIdea: true,
      titlePlaceholder: app.translator.trans('flarum-ideation.forum.composer.title_placeholder', {}, true),
      placeholder: app.translator.trans('flarum-ideation.forum.composer.body_placeholder', {}, true),
      submitLabel: app.translator.trans('flarum-ideation.forum.composer.submit'),
    })
    .then(() => app.composer.show());
}

export default class IdeationPage extends Page {
  mode: 'board' | 'list' = 'board';
  status = 'all';
  sort = 'votes';
  q = '';
  columns: Record<string, Column> = {};
  over: string | null = null;
  drag: BoardDrag | null = null;
  searchTimer: number | undefined;

  oninit(vnode: Mithril.Vnode<IPageAttrs, this>) {
    super.oninit(vnode);

    this.bodyClass = 'App--index';
    this.mode = m.route.param('view') === 'list' ? 'list' : 'board';
    this.status = SETTABLE.includes(m.route.param('status')) ? m.route.param('status') : 'all';
    this.sort = ['newest', 'latest'].includes(m.route.param('sort')) ? m.route.param('sort') : 'votes';
    this.q = m.route.param('q') || '';

    this.load();

    app.history.push('ideation', app.translator.trans('flarum-ideation.forum.title', {}, true));
  }

  oncreate(vnode: Mithril.VnodeDOM<IPageAttrs, this>) {
    super.oncreate(vnode);
    app.setTitle(app.translator.trans('flarum-ideation.forum.title', {}, true));
    app.setTitleCount(0);
  }

  onremove(vnode: Mithril.VnodeDOM<IPageAttrs, this>) {
    super.onremove(vnode);
    this.drag?.destroy();
  }

  view() {
    return (
      <PageStructure className="IndexPage IdeationPage" hero={() => this.hero()} sidebar={() => <IdeationSidebar />}>
        <div className="IdeationPage-toolbar">{this.toolbar()}</div>
        {this.mode === 'board' ? this.board() : this.listView()}
      </PageStructure>
    );
  }

  hero() {
    return (
      <header className="Hero IdeationHero">
        <div className="container">
          <div className="containerNarrow">
            <h1 className="Hero-title">{app.translator.trans('flarum-ideation.forum.title')}</h1>
            <div className="Hero-subtitle">{app.translator.trans('flarum-ideation.forum.subtitle')}</div>
          </div>
        </div>
      </header>
    );
  }

  toolbar() {
    const menu = (label: Mithril.Children, values: string[], current: string, labelOf: (v: string) => Mithril.Children, set: (v: string) => void) => (
      <Dropdown buttonClassName="Button" label={label} accessibleToggleLabel={extractText(label)}>
        {values.map((v) => (
          <Button icon={current === v ? 'fas fa-check' : true} active={current === v} onclick={() => set(v)}>
            {labelOf(v)}
          </Button>
        ))}
      </Dropdown>
    );

    return [
      <ul className="IdeationPage-toolbar-view">
        <li>
          <div className="ButtonGroup IdeationPage-mode" role="group" aria-label={app.translator.trans('flarum-ideation.forum.view.label', {}, true)}>
            {(['board', 'list'] as const).map((mode) => (
              <Button
                className={classList('Button', this.mode === mode && 'active')}
                icon={mode === 'board' ? 'fas fa-columns' : 'fas fa-list'}
                aria-pressed={this.mode === mode ? 'true' : 'false'}
                onclick={() => this.change({ mode })}
              >
                {app.translator.trans('flarum-ideation.forum.view.' + mode)}
              </Button>
            ))}
          </div>
        </li>
        {this.mode === 'list' ? (
          <li>
            {menu(
              this.status === 'all' ? app.translator.trans('flarum-ideation.forum.filter.all') : statusLabel(this.status),
              ['all', ...SETTABLE],
              this.status,
              (v) => (v === 'all' ? app.translator.trans('flarum-ideation.forum.filter.all') : statusLabel(v)),
              (status) => this.change({ status })
            )}
          </li>
        ) : null}
        {this.mode === 'list' ? (
          <li>
            {menu(
              app.translator.trans('flarum-ideation.forum.sort.' + this.sort),
              ['votes', 'newest', 'latest'],
              this.sort,
              (v) => app.translator.trans('flarum-ideation.forum.sort.' + v),
              (sort) => this.change({ sort })
            )}
          </li>
        ) : null}
      </ul>,
      <ul className="IdeationPage-toolbar-action">
        <li>
          <input
            className="FormControl IdeationPage-search"
            type="search"
            value={this.q}
            placeholder={app.translator.trans('flarum-ideation.forum.search_placeholder', {}, true)}
            aria-label={app.translator.trans('flarum-ideation.forum.search_placeholder', {}, true)}
            oninput={(e: InputEvent) => {
              const q = (e.target as HTMLInputElement).value;
              window.clearTimeout(this.searchTimer);
              this.searchTimer = window.setTimeout(() => this.change({ q }), 300);
            }}
          />
        </li>
      </ul>,
    ];
  }

  board() {
    const moderating = !!app.forum.attribute('ideationCanModerate');

    return (
      <div
        className={classList('IdeationBoard', moderating && 'IdeationBoard--draggable')}
        oncreate={(vnode: Mithril.VnodeDOM) => {
          if (moderating) {
            this.drag = new BoardDrag(
              vnode.dom as HTMLElement,
              (over) => {
                this.over = over;
                m.redraw();
              },
              (id, column) => this.move(id, column)
            );
          }
        }}
        onremove={() => {
          this.drag?.destroy();
          this.drag = null;
        }}
      >
        {COLUMNS.map((status) => this.column(status))}
      </div>
    );
  }

  column(status: string) {
    const column = this.columns[status];

    return (
      <section
        className={classList('IdeationColumn', 'IdeationStatus--' + status, this.over === status && 'IdeationColumn--over')}
        data-ideation-column={status}
        key={status}
      >
        <h2 className="IdeationColumn-title">
          <Icon name={ICONS[status]} />
          <span>{statusLabel(status)}</span>
        </h2>
        <div className="IdeationColumn-cards">
          {column.items.map((discussion) => this.card(discussion))}
          {!column.loading && !column.items.length ? (
            <p className="IdeationColumn-empty">{app.translator.trans('flarum-ideation.forum.empty_column')}</p>
          ) : null}
        </div>
        {column.loading ? (
          <LoadingIndicator />
        ) : column.more ? (
          <Button className="Button Button--block IdeationColumn-more" onclick={() => this.loadColumn(status, true)}>
            {app.translator.trans('flarum-ideation.forum.load_more')}
          </Button>
        ) : null}
      </section>
    );
  }

  /*
   * The list is drawn with this extension's own rows rather than core's
   * DiscussionList: themes replace discussion list items wholesale (Bespoke's
   * card layout does), and the vote and the status went with them.
   */
  listView() {
    const list = this.columns.list;

    return (
      <div className="IdeationList">
        {list.items.map((discussion) => this.card(discussion, true))}
        {!list.loading && !list.items.length ? (
          <p className="IdeationColumn-empty">{app.translator.trans('flarum-ideation.forum.empty_list')}</p>
        ) : null}
        {list.loading ? (
          <LoadingIndicator />
        ) : list.more ? (
          <Button className="Button Button--block IdeationColumn-more" onclick={() => this.loadColumn('list', true)}>
            {app.translator.trans('flarum-ideation.forum.load_more')}
          </Button>
        ) : null}
      </div>
    );
  }

  card(discussion: Discussion, withStatus = false) {
    const status = discussion.ideationStatus() || '';

    // Tags are an optional dependency, so their model is not imported.
    const tags = ((discussion as Discussion & { tags?: () => (TagLike | undefined)[] | false }).tags?.() || []).filter(
      (tag): tag is TagLike => !!tag
    );

    return (
      <article
        className="IdeationCard"
        data-ideation-idea={discussion.id()}
        data-ideation-movable={discussion.canIdeationModerate() ? '' : undefined}
        key={discussion.id()}
      >
        <VoteButton discussion={discussion} />
        <div className="IdeationCard-body">
          <Link className="IdeationCard-title" href={app.route.discussion(discussion)}>
            {discussion.title()}
          </Link>
          <div className="IdeationCard-meta">
            {withStatus ? (
              <span className={'IdeationCard-status IdeationStatus--' + status}>
                <Icon name={ICONS[status]} /> {statusLabel(status)}
              </span>
            ) : null}
            {tags.length ? (
              <span className="TagsLabel">
                {tags.map((tag) => (
                  <span className={classList('TagLabel', tag.color() && 'colored')} style={tag.color() ? { '--tag-bg': tag.color() } : undefined}>
                    <span className="TagLabel-text">{tag.name()}</span>
                  </span>
                ))}
              </span>
            ) : null}
            <GithubLinks links={discussion.ideationGithub() || []} compact />
            <span
              className="IdeationCard-replies"
              title={app.translator.trans('flarum-ideation.forum.replies', { count: discussion.replyCount() }, true)}
            >
              <Icon name="far fa-comment" /> {discussion.replyCount()}
            </span>
          </div>
        </div>
      </article>
    );
  }

  change(next: { mode?: 'board' | 'list'; status?: string; sort?: string; q?: string }) {
    Object.assign(this, next);

    // The URL follows the view, so it can be shared and survives a reload.
    const params: Record<string, string> = {};
    if (this.mode === 'list') params.view = 'list';
    if (this.mode === 'list' && this.status !== 'all') params.status = this.status;
    if (this.mode === 'list' && this.sort !== 'votes') params.sort = this.sort;
    if (this.q) params.q = this.q;
    window.history.replaceState(null, '', app.route('ideation', params));

    this.load();
  }

  load() {
    (this.mode === 'list' ? ['list'] : COLUMNS).forEach((key) => this.loadColumn(key));
  }

  /** One board column, or (key `list`) the list view. */
  loadColumn(key: string, more = false) {
    const column = (this.columns[key] ??= { items: [], loading: false, more: false });
    const list = key === 'list';
    column.loading = true;

    const filter: Record<string, string> = { ideation: list ? this.status : key };
    if (this.q) filter.q = this.q;

    app.store
      .find<Discussion[]>('discussions', {
        filter,
        sort: list ? SORTS[this.sort] : key === 'shipped' ? '-ideationStatusChangedAt' : '-ideationVotes',
        page: { offset: more ? column.items.length : 0, limit: list ? PAGE * 2 : PAGE },
        include: app.initializers.has('flarum-tags') ? 'user,tags' : 'user',
      })
      .then((results) => {
        column.items = more ? [...column.items, ...results] : results;
        column.more = !!results.payload?.links?.next;
      })
      .finally(() => {
        column.loading = false;
        m.redraw();
      });
  }

  /** A card dropped on another column: move it now, keep it if the server agrees. */
  move(id: string, status: string) {
    const discussion = app.store.getById<Discussion>('discussions', id);
    const from = discussion?.ideationStatus();

    if (!discussion || !from || from === status) return;

    this.columns[from].items = this.columns[from].items.filter((d) => d !== discussion);
    this.columns[status].items = [discussion, ...this.columns[status].items];
    m.redraw();

    discussion.save({ ideationStatus: status }).catch(() => {
      this.loadColumn(from);
      this.loadColumn(status);
    });
  }
}
