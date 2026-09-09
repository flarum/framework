import app from '../../admin/app';
import Modal, { IInternalModalAttrs } from '../../common/components/Modal';
import Button from '../../common/components/Button';
import LoadingIndicator from '../../common/components/LoadingIndicator';
import Icon from '../../common/components/Icon';
import type Mithril from 'mithril';

/**
 * One thing the server did, as it reported it.
 *
 * Every step says what happened rather than describing it, so the wording here
 * comes from the translations — a sentence assembled on the server could only
 * ever have been in English.
 */
interface Step {
  type: 'cleared' | 'rebuilt' | 'failed' | 'done';
  name?: string;
  files?: number | null;
  frontend?: string;
  locale?: string | null;
  localeName?: string | null;
  bundle?: string;
  state?: 'unchanged' | 'rebuilt' | 'rewritten' | 'empty' | 'chunks';
  changed?: number | null;
  milliseconds?: number;
  step?: string;
  message?: string;
}

export interface IClearCacheModalAttrs extends IInternalModalAttrs {}

/**
 * Clears the cache, showing each step as the server finishes it.
 *
 * The work recompiles every asset bundle for every frontend and locale, which
 * runs for tens of seconds where the assets live on remote storage. A spinner
 * alone gives no way to tell that from a request that has died, so the steps
 * are read from the response as they arrive.
 *
 * Nothing reloads on its own at the end: the page is still running on the
 * assets it booted with, and an admin who has just watched a rebuild should
 * decide for themselves when to pick up the new ones.
 */
export default class ClearCacheModal<ModalAttrs extends IClearCacheModalAttrs = IClearCacheModalAttrs> extends Modal<ModalAttrs> {
  protected steps: Step[] = [];
  protected finished = false;

  protected static readonly isDismissibleViaCloseButton: boolean = false;
  protected static readonly isDismissibleViaEscKey: boolean = false;
  protected static readonly isDismissibleViaBackdropClick: boolean = false;

  oninit(vnode: Mithril.Vnode<ModalAttrs, this>) {
    super.oninit(vnode);

    this.clear();
  }

  className() {
    return 'ClearCacheModal Modal--medium';
  }

  title() {
    return app.translator.trans('core.admin.dashboard.clear_cache_modal.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <ol className="ClearCacheModal-steps">
          {this.steps
            .filter((step) => step.type !== 'done')
            .map((step, i, steps) => (
              <li className={'ClearCacheModal-step ClearCacheModal-step--' + this.appearance(step)}>
                <Icon name={this.icon(step)} className="ClearCacheModal-step-icon" />
                <span className="ClearCacheModal-step-label">{this.describe(step)}</span>
                {this.time(step, steps[i - 1])}
              </li>
            ))}
        </ol>

        <div className="ClearCacheModal-footer">
          {this.finished ? (
            <Button className="Button Button--primary" icon="fas fa-sync" onclick={() => window.location.reload()}>
              {app.translator.trans('core.admin.dashboard.clear_cache_modal.reload_button')}
            </Button>
          ) : (
            <p className="ClearCacheModal-working">
              <LoadingIndicator display="inline" size="small" /> {app.translator.trans('core.admin.dashboard.clear_cache_modal.working')}
            </p>
          )}
        </div>
      </div>
    );
  }

  /**
   * How much of the reader's attention a step deserves.
   *
   * Most of a clear is bundles that turned out not to need rebuilding, and on a
   * forum with several locales those rows are the majority. They are worth
   * listing — they are how you know nothing was missed — but not worth reading,
   * so they recede and the handful of things that actually changed stand out.
   */
  protected appearance(step: Step): string {
    if (step.type === 'failed') return 'failed';
    if (step.state === 'unchanged' || step.state === 'empty') return 'quiet';
    if (step.state === 'chunks' && !step.changed) return 'quiet';

    return 'changed';
  }

  /**
   * How long a step took, where that is worth knowing.
   *
   * Bundles compiled together are timed together, so the figure belongs to the
   * frontend rather than to each of its bundles — repeating it down every row
   * of a group would read as several slow steps instead of one. Shown against
   * the first row of each group only, as the console table does it.
   */
  protected time(step: Step, previous?: Step): Mithril.Children {
    if (step.milliseconds === undefined || step.milliseconds < 100) return null;

    const group = (s?: Step) => (s === undefined ? null : `${s.frontend}\u0000${s.locale}`);

    if (group(step) === group(previous)) return null;

    return (
      <span className="ClearCacheModal-step-detail">
        {app.translator.trans('core.admin.dashboard.clear_cache_modal.took', { time: step.milliseconds })}
      </span>
    );
  }

  protected icon(step: Step): string {
    return {
      failed: 'fas fa-exclamation-triangle',
      quiet: 'fas fa-minus',
      changed: 'fas fa-check',
    }[this.appearance(step)]!;
  }

  /**
   * Puts words to a step. Which words depend only on what the server said
   * happened, never on a string it sent.
   */
  protected describe(step: Step): Mithril.Children {
    const key = (name: string) => `core.admin.dashboard.clear_cache_modal.${name}`;

    switch (step.type) {
      case 'cleared':
        return step.files === null || step.files === undefined
          ? app.translator.trans(key('cleared'), { name: step.name })
          : app.translator.trans(key('cleared_files'), { name: step.name, count: step.files });

      case 'failed':
        return app.translator.trans(key('failed'), { step: step.step, message: step.message });

      case 'rebuilt':
        if (step.state === 'chunks') {
          return app.translator.trans(key('chunks'), { frontend: step.frontend, count: step.changed });
        }

        // The bundle's own name is the useful label — `forum-de.js` says both
        // which frontend and which locale it belongs to.
        return app.translator.trans(key(step.state ?? 'rebuilt'), { bundle: step.bundle });

      // `done` is the stream's terminator rather than a step, and is filtered
      // out before rendering. A type added server-side without a case here
      // lands as a blank row instead of breaking the list.
      default:
        return null;
    }
  }

  /**
   * Reads the response as it is written.
   *
   * `app.request` cannot be used here: it hands back the body once, complete,
   * so nothing could be shown until the work had finished. Each line is a whole
   * JSON object, so a step can be rendered the moment its line arrives.
   */
  protected async clear() {
    try {
      const response = await fetch(app.forum.attribute('apiUrl') + '/cache', {
        method: 'DELETE',
        headers: {
          'X-CSRF-Token': app.session.csrfToken!,
        },
        credentials: 'same-origin',
      });

      if (!response.ok || !response.body) {
        throw new Error(String(response.status));
      }

      const reader = response.body.getReader();
      const decoder = new TextDecoder();
      let buffered = '';

      for (;;) {
        const { done, value } = await reader.read();

        if (done) break;

        buffered += decoder.decode(value, { stream: true });

        // Anything up to the last newline is complete; whatever follows it is
        // half a line and waits for the next chunk.
        const lines = buffered.split('\n');
        buffered = lines.pop() ?? '';

        for (const line of lines) {
          if (line.trim() === '') continue;

          this.steps.push(JSON.parse(line));
        }

        m.redraw();
      }
    } catch (e) {
      app.alerts.show({ type: 'error' }, app.translator.trans('core.admin.dashboard.io_error_message'));
    }

    // Either way the caches are gone, so the page is running on assets that may
    // no longer exist — offer the reload rather than leaving a spinner.
    this.finished = true;

    m.redraw();
  }
}
