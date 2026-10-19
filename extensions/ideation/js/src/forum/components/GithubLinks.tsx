import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Icon from 'flarum/common/components/Icon';
import classList from 'flarum/common/utils/classList';
import extractText from 'flarum/common/utils/extractText';
import { t } from '../t';

export type GithubLink = {
  id: number;
  type: 'issue' | 'pull' | 'milestone';
  repo: string;
  number: number;
  title: string;
  state: string;
  openIssues: number | null;
  closedIssues: number | null;
  url: string;
};

export interface GithubLinksAttrs extends ComponentAttrs {
  links: GithubLink[];
  /** The board card's one-line summary instead of the sidebar list. */
  compact?: boolean;
  onunlink?: (link: GithubLink) => void;
}

const ICONS: Record<string, string> = {
  issue: 'far fa-circle-dot',
  pull: 'fas fa-code-pull-request',
  milestone: 'fas fa-flag',
};

export function progress(link: GithubLink): number | null {
  const total = (link.openIssues ?? 0) + (link.closedIssues ?? 0);

  return link.type === 'milestone' && total ? Math.round(((link.closedIssues ?? 0) / total) * 100) : null;
}

/** Where on GitHub the work for an idea is, as GitHub last told us. */
export default class GithubLinks extends Component<GithubLinksAttrs> {
  view() {
    const links = this.attrs.links || [];
    if (!links.length) return null;

    if (this.attrs.compact) {
      return (
        <span className="IdeationGithub IdeationGithub--compact">
          {links.slice(0, 2).map((link) => (
            <span className={classList('IdeationGithub-chip', 'IdeationGithub--' + link.type, 'IdeationGithub--' + link.state)} title={link.title}>
              <Icon name={ICONS[link.type]} /> {link.type === 'milestone' ? `${link.title} · ${progress(link) ?? 0}%` : `#${link.number}`}
            </span>
          ))}
          {links.length > 2 ? <span className="IdeationGithub-chip">+{links.length - 2}</span> : null}
        </span>
      );
    }

    return (
      <ul className="IdeationGithub">
        {links.map((link) => {
          const pct = progress(link);

          return (
            <li className={classList('IdeationGithub-item', 'IdeationGithub--' + link.type, 'IdeationGithub--' + link.state)}>
              <a href={link.url} target="_blank" rel="noopener noreferrer nofollow" className="IdeationGithub-link">
                <Icon name={ICONS[link.type]} className="IdeationGithub-icon" />
                <span className="IdeationGithub-text">
                  <span className="IdeationGithub-title">{link.title}</span>
                  <span className="IdeationGithub-meta">
                    {link.type === 'milestone' ? link.repo : `${link.repo}#${link.number}`} · {t('github.state.' + link.type + '.' + link.state)}
                  </span>
                  {pct !== null ? (
                    <span className="IdeationGithub-progress" role="progressbar" aria-valuenow={pct} aria-valuemin={0} aria-valuemax={100}>
                      <span style={{ width: pct + '%' }} />
                    </span>
                  ) : null}
                </span>
              </a>
              {this.attrs.onunlink ? (
                <button
                  type="button"
                  className="Button Button--icon Button--link IdeationGithub-remove"
                  aria-label={extractText(t('github.remove'))}
                  onclick={() => this.attrs.onunlink!(link)}
                >
                  <Icon name="fas fa-times" />
                </button>
              ) : null}
            </li>
          );
        })}
      </ul>
    );
  }
}
