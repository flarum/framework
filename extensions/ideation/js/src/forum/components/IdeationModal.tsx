import app from 'flarum/forum/app';
import Modal, { type IInternalModalAttrs } from 'flarum/common/components/Modal';
import Button from 'flarum/common/components/Button';
import classList from 'flarum/common/utils/classList';
import extractText from 'flarum/common/utils/extractText';
import type Discussion from 'flarum/common/models/Discussion';
import { t } from '../t';
import { ICONS, SETTABLE, statusLabel } from '../status';
import GithubLinks, { type GithubLink } from './GithubLinks';

export interface IdeationModalAttrs extends IInternalModalAttrs {
  discussion: Discussion;
}

/** Move an idea, take a discussion on or off the roadmap, or merge a duplicate. */
export default class IdeationModal extends Modal<IdeationModalAttrs> {
  query = '';
  results: Discussion[] = [];
  target: Discussion | null = null;
  searching = false;
  timer: number | undefined;
  githubRef = '';
  linking = false;

  className() {
    return 'IdeationModal Modal--small';
  }

  title() {
    return t('modal.title');
  }

  content() {
    const discussion = this.attrs.discussion;
    const current = discussion.attribute<string | null>('ideationStatus');

    return (
      <div className="Modal-body">
        <div className="Form">
          <div className="Form-group">
            <label>{t('modal.status_label')}</label>
            <div className="IdeationModal-statuses">
              {SETTABLE.map((status) => (
                <Button
                  className={classList('Button', 'IdeationStatus--' + status, current === status && 'active')}
                  icon={ICONS[status]}
                  aria-pressed={current === status ? 'true' : 'false'}
                  disabled={this.loading}
                  onclick={() => this.save({ ideationStatus: status })}
                >
                  {statusLabel(status)}
                </Button>
              ))}
            </div>
            {current ? (
              <Button
                className="Button Button--link IdeationModal-remove"
                disabled={this.loading}
                onclick={() => this.save({ ideationStatus: null })}
              >
                {t('modal.remove')}
              </Button>
            ) : null}
          </div>

          {current && current !== 'merged' && app.forum.attribute('ideationGithub') ? this.githubForm() : null}
          {current && current !== 'merged' ? this.mergeForm() : null}
        </div>
      </div>
    );
  }

  githubForm() {
    const discussion = this.attrs.discussion;

    return (
      <div className="Form-group IdeationModal-github">
        <label for="ideation-github">{t('modal.github_label')}</label>
        <GithubLinks
          links={discussion.attribute<GithubLink[]>('ideationGithub') || []}
          onunlink={(link: GithubLink) => this.saveGithub({ ideationGithubUnlink: link.id })}
        />
        <div className="IdeationModal-githubAdd">
          <input
            id="ideation-github"
            className="FormControl"
            type="text"
            value={this.githubRef}
            placeholder={extractText(t('modal.github_placeholder'))}
            oninput={(e: InputEvent) => (this.githubRef = (e.target as HTMLInputElement).value)}
            onkeydown={(e: KeyboardEvent) => {
              if (e.key === 'Enter') {
                e.preventDefault();
                this.linkGithub();
              }
            }}
          />
          <Button className="Button" icon="fab fa-github" loading={this.linking} disabled={!this.githubRef.trim()} onclick={() => this.linkGithub()}>
            {t('modal.github_link')}
          </Button>
        </div>
      </div>
    );
  }

  linkGithub() {
    if (!this.githubRef.trim()) return;

    this.saveGithub({ ideationGithubLink: this.githubRef.trim() }).then(() => (this.githubRef = ''));
  }

  /** Links stay in the dialog: a moderator usually adds several. */
  saveGithub(attributes: Record<string, unknown>): Promise<unknown> {
    this.linking = true;

    return this.attrs.discussion
      .save(attributes)
      .then(() => (app.current.get('stream') as any)?.update?.())
      .catch(() => {})
      .finally(() => {
        this.linking = false;
        m.redraw();
      });
  }

  mergeForm() {
    return (
      <div className="Form-group IdeationModal-merge">
        <label for="ideation-merge">{t('modal.merge_label')}</label>
        <p className="helpText">{t('modal.merge_help')}</p>
        <input
          id="ideation-merge"
          className="FormControl"
          type="search"
          value={this.query}
          placeholder={extractText(t('modal.merge_placeholder'))}
          oninput={(e: InputEvent) => this.search((e.target as HTMLInputElement).value)}
        />
        <ul className="IdeationModal-results" role="listbox">
          {this.results.map((idea) => (
            <li>
              <Button
                className={classList('Button Button--block', this.target === idea && 'active')}
                aria-selected={this.target === idea ? 'true' : 'false'}
                onclick={() => (this.target = idea)}
              >
                <span className="IdeationModal-result-title">{idea.title()}</span>
                <span className="IdeationModal-result-votes">{t('votes', { count: idea.attribute<number>('ideationVotes') || 0 })}</span>
              </Button>
            </li>
          ))}
        </ul>
        <Button
          className="Button Button--primary Button--block"
          disabled={!this.target || this.loading}
          loading={this.loading}
          onclick={() => this.save({ ideationMergedIntoId: Number(this.target!.id()) })}
        >
          {this.target ? t('modal.merge_into', { title: this.target.title() }) : t('modal.merge_button')}
        </Button>
      </div>
    );
  }

  search(query: string) {
    this.query = query;
    this.target = null;
    window.clearTimeout(this.timer);

    if (query.trim().length < 2) {
      this.results = [];
      return;
    }

    this.timer = window.setTimeout(() => {
      app.store.find<Discussion[]>('discussions', { filter: { q: query, ideation: 'all' }, page: { limit: 6 } }).then((results) => {
        this.results = results.filter((d) => d !== this.attrs.discussion);
        m.redraw();
      });
    }, 250);
  }

  save(attributes: Record<string, unknown>) {
    this.loading = true;

    this.attrs.discussion
      .save(attributes)
      .then(() => {
        // The move is an event post in the discussion: show it.
        (app.current.get('stream') as any)?.update?.();
        this.hide();
      })
      .catch(() => {})
      .finally(() => {
        this.loading = false;
        m.redraw();
      });
  }
}
