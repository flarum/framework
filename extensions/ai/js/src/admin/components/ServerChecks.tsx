import app from 'flarum/admin/app';
import Component from 'flarum/common/Component';
import Button from 'flarum/common/components/Button';
import Icon from 'flarum/common/components/Icon';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import type Mithril from 'mithril';
import { aiPayload } from '../utils/settings';
import { streamingCheck } from 'flarum/admin/utils/streamingCheck';

type CheckState = 'ok' | 'warning' | 'pending';

/**
 * How long after the scheduler last ran it's still taken to be running, as
 * core's dashboard has it.
 */
const SCHEDULER_GRACE_MINUTES = 5;

/**
 * What the server needs for AI features to work well, checked where it can be:
 * whether streamed replies reach the browser as they're written, whether
 * background work has a queue to run on, and whether the scheduler runs the
 * daily checks.
 */
export default class ServerChecks extends Component {
  oncreate(vnode: Mithril.VnodeDOM<{}, this>) {
    super.oncreate(vnode);

    // Once per visit to the admin page, not every time the tab is shown.
    streamingCheck.runOnce();
  }

  view(): Mithril.Children {
    return <ul className="AiServerChecks">{[this.streamingCheck(), this.queueCheck(), this.schedulerCheck()]}</ul>;
  }

  check(key: string, state: CheckState, label: string, text: Mithril.Children, control?: Mithril.Children): Mithril.Children {
    return (
      <li key={key} className={'AiServerChecks-item AiServerChecks-item--' + state}>
        <span className="AiServerChecks-icon">
          {state === 'pending' ? (
            <LoadingIndicator display="inline" size="small" />
          ) : (
            <Icon name={state === 'ok' ? 'fas fa-circle-check' : 'fas fa-triangle-exclamation'} />
          )}
        </span>
        <span className="AiServerChecks-body">
          <strong>{app.translator.trans(label)}</strong> {text}
          {control}
        </span>
      </li>
    );
  }

  streamingCheck(): Mithril.Children {
    const { status, verdict } = streamingCheck;
    // While checking again, the last answer stands.
    const shown = status === 'checking' && verdict ? verdict : status;
    const state: CheckState = shown === 'streamed' || shown === 'unknown' ? 'ok' : shown === 'idle' || shown === 'checking' ? 'pending' : 'warning';
    const text =
      shown === 'idle' || shown === 'checking'
        ? 'flarum-ai.admin.streaming.checking_text'
        : shown === 'streamed'
          ? 'flarum-ai.admin.streaming.streams_text'
          : shown === 'buffered'
            ? 'flarum-ai.admin.streaming.buffered_text'
            : shown === 'failed'
              ? 'flarum-ai.admin.streaming.failed_text'
              : 'flarum-ai.admin.streaming.unknown_text';

    return this.check(
      'streaming',
      state,
      'flarum-ai.admin.streaming.label',
      app.translator.trans(text),
      state !== 'pending' && (
        <Button className="Button Button--link AiServerChecks-again" loading={status === 'checking'} onclick={() => streamingCheck.run()}>
          {app.translator.trans('flarum-ai.admin.streaming.again_button')}
        </Button>
      )
    );
  }

  queueCheck(): Mithril.Children {
    const driver = String(app.data.queueDriver || 'sync');

    return this.check(
      'queue',
      driver === 'sync' ? 'warning' : 'ok',
      'flarum-ai.admin.server.queue_label',
      driver === 'sync'
        ? app.translator.trans('flarum-ai.admin.server.queue_sync_text')
        : app.translator.trans('flarum-ai.admin.server.queue_text', { driver })
    );
  }

  schedulerCheck(): Mithril.Children {
    const lastRun = aiPayload().schedulerLastRun;
    const running = !!lastRun && dayjs(lastRun).isAfter(dayjs().subtract(SCHEDULER_GRACE_MINUTES, 'minute'));

    return this.check(
      'scheduler',
      running ? 'ok' : 'warning',
      'flarum-ai.admin.server.scheduler_label',
      running
        ? app.translator.trans('flarum-ai.admin.server.scheduler_text')
        : lastRun
          ? app.translator.trans('flarum-ai.admin.server.scheduler_stopped_text', { date: dayjs(lastRun).format('lll') })
          : app.translator.trans('flarum-ai.admin.server.scheduler_never_text')
    );
  }
}
