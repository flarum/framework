import app from 'flarum/admin/app';
import Modal, { type IInternalModalAttrs } from 'flarum/common/components/Modal';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import apiUrl from '../apiUrl';
import t from '../t';
import type Mithril from 'mithril';

declare const m: any;

export interface IReleaseNotesModalAttrs extends IInternalModalAttrs {
  name: string;
  package: string;
  from: string;
  to: string;
}

interface Release {
  name: string;
  tag: string;
  url: string;
  publishedAt: string | null;
  html: string;
}

/**
 * What an update gives you: every GitHub release between the installed
 * version and the one on offer, fetched by the server when this opens.
 */
export default class ReleaseNotesModal extends Modal<IReleaseNotesModalAttrs> {
  loading = true;
  releases: Release[] = [];
  url: string | null = null;
  failed = false;

  oninit(vnode: Mithril.Vnode<IReleaseNotesModalAttrs, this>) {
    super.oninit(vnode);

    app
      .request<any>({ method: 'GET', url: apiUrl() + '/extension-manager/release-notes', params: { package: this.attrs.package } })
      .then((data) => {
        this.releases = data.releases || [];
        this.url = data.url || null;
      })
      .catch(() => (this.failed = true))
      .finally(() => {
        this.loading = false;
        m.redraw();
      });
  }

  className() {
    return 'Modal--large ExtensionManager-notesModal';
  }

  title() {
    return t('notes_title', { name: this.attrs.name, from: this.attrs.from, to: this.attrs.to });
  }

  content() {
    return <div className="Modal-body">{this.body()}</div>;
  }

  body(): Mithril.Children {
    if (this.loading) return <LoadingIndicator />;

    const link = this.url ? (
      <p className="ExtensionManager-notesLink">
        <a href={this.url} target="_blank" rel="noopener noreferrer">
          {t('notes_on_github')} <i className="fas fa-external-link-alt" aria-hidden="true" />
        </a>
      </p>
    ) : null;

    const releases =
      this.failed || this.releases.length === 0 ? (
        <p>{this.failed ? t('notes_failed') : t('notes_none')}</p>
      ) : (
        <div className="ExtensionManager-releases">
          {this.releases.map((r) => (
            <section className="ExtensionManager-release" key={r.tag}>
              <h4>
                <a href={r.url} target="_blank" rel="noopener noreferrer">
                  {r.name}
                </a>
                {r.publishedAt ? <time dateTime={r.publishedAt}>{new Date(r.publishedAt).toLocaleDateString()}</time> : null}
              </h4>
              {/* Cut down to a short list of tags on the server (ReleaseNotes::clean). */}
              {r.html ? (
                <div className="ExtensionManager-releaseBody">{m.trust(r.html)}</div>
              ) : (
                <p className="ExtensionManager-releaseEmpty">{t('notes_empty_release')}</p>
              )}
            </section>
          ))}
        </div>
      );

    return (
      <div>
        {releases}
        {link}
      </div>
    );
  }
}
