import app from 'flarum/admin/app';
import Component, { type ComponentAttrs } from 'flarum/common/Component';
import Alert from 'flarum/common/components/Alert';
import Icon from 'flarum/common/components/Icon';
import LoadingIndicator from 'flarum/common/components/LoadingIndicator';
import extractText from 'flarum/common/utils/extractText';
import type Mithril from 'mithril';
import type { StreamArrival, StreamEvent } from 'flarum/common/utils/readEventStream';
import { streamingCheck } from 'flarum/admin/utils/streamingCheck';
import { formatTokens } from '../utils/settings';

/**
 * A connection as the modal sends it to be tested before it's saved.
 */
export interface CandidateConnection {
  driver: string;
  url?: string;
  region?: string;
  model?: string;
  guardrail_id?: string;
  guardrail_version?: string;
  key?: string;
  removeKey?: boolean;
}

/**
 * Runs a test of a connection and holds what it has found so far, so the
 * test carries on while the component showing it is redrawn.
 */
export class ConnectionTestState {
  status: 'idle' | 'running' | 'passed' | 'failed' = 'idle';
  text = '';
  arrivals: StreamArrival[] = [];
  done: StreamEvent | null = null;
  error: StreamEvent | null = null;
  /** Why the server refused to start the test, such as a field it found wrong. */
  refused: string | null = null;
  buffering: 'streamed' | 'buffered' | 'unknown' = 'unknown';
  /** Each run's number, so a run that's been replaced stops updating. */
  protected run = 0;

  start(name: string, candidate?: CandidateConnection): Promise<void> {
    const run = ++this.run;

    this.status = 'running';
    this.text = '';
    this.arrivals = [];
    this.done = this.error = null;
    this.refused = null;
    this.buffering = 'unknown';
    m.redraw();

    const body = candidate ? { name, connection: candidate } : { name };
    let streamBuffering: ((arrivals: StreamArrival[]) => 'streamed' | 'buffered' | 'unknown') | null = null;

    return import('flarum/common/utils/readEventStream')
      .then((module) => {
        streamBuffering = module.streamBuffering;

        return module.default(app.forum.attribute('apiUrl') + '/ai/connections/test', { method: 'POST', body }, (event, arrival) => {
          if (run !== this.run) return;

          this.arrivals.push(arrival);

          if (event.type === 'text') this.text += String(event.delta ?? '');
          if (event.type === 'done') this.done = event;
          if (event.type === 'error') this.error = event;

          m.redraw();
        });
      })
      .catch((error) => {
        if (run !== this.run) return;

        // Refused before the test started, such as for a field the server found wrong.
        const detail = error?.errors?.[0]?.detail;

        if (typeof detail === 'string') this.refused = detail;
      })
      .finally(() => {
        if (run !== this.run) return;

        this.status = this.done ? 'passed' : 'failed';

        if (this.done && streamBuffering) {
          this.buffering = streamBuffering(this.arrivals);
          streamingCheck.note(this.buffering);
        }

        m.redraw();
      });
  }

  /**
   * Milliseconds from the test starting on the server to its end.
   */
  totalMs(): number | null {
    const last = this.arrivals[this.arrivals.length - 1];

    return last ? last.event.ms : null;
  }
}

function extractMessage(key: string, params: Record<string, string> = {}): string {
  return extractText(app.translator.trans(key, params));
}

/**
 * A duration as the admin reads it: milliseconds under a second, then seconds.
 */
export function formatMs(ms: number): string {
  return ms < 1000
    ? extractMessage('flarum-ai.admin.test.ms_text', { ms: String(ms) })
    : extractMessage('flarum-ai.admin.test.seconds_text', { seconds: (ms / 1000).toFixed(1) });
}

export interface ConnectionTestAttrs extends ComponentAttrs {
  state: ConnectionTestState;
}

/**
 * What a connection test has found: the reply as it's written, then whether
 * the connection works, how long it took, and whether the reply streamed.
 */
export default class ConnectionTest extends Component<ConnectionTestAttrs> {
  view(): Mithril.Children {
    const state = this.attrs.state;

    if (state.status === 'idle') return null;

    return (
      <div className={'AiConnectionTest AiConnectionTest--' + state.status} aria-live="polite">
        {(state.status === 'running' || !!state.text) && (
          <pre className="AiConnectionTest-reply" aria-label={extractMessage('flarum-ai.admin.test.reply_label')}>
            {state.text}
            {state.status === 'running' && <span className="AiConnectionTest-caret" aria-hidden="true" />}
          </pre>
        )}
        {state.status === 'running' && !state.text && (
          <p className="AiConnectionTest-waiting">
            <LoadingIndicator display="inline" size="small" /> {app.translator.trans('flarum-ai.admin.test.waiting_text')}
          </p>
        )}
        {state.status === 'passed' && this.passed()}
        {state.status === 'failed' && this.failed()}
        {state.status === 'passed' && this.streamingNote()}
      </div>
    );
  }

  passed(): Mithril.Children {
    const state = this.attrs.state;
    const done = state.done!;
    const tokens = Number(done.inputTokens || 0) + Number(done.outputTokens || 0);
    const model = String(done.model || extractMessage('flarum-ai.admin.test.default_model_text'));

    return (
      <p className="AiConnectionTest-result">
        <Icon name="fas fa-circle-check" />{' '}
        {state.text.trim()
          ? app.translator.trans('flarum-ai.admin.test.passed_text', {
              model,
              first: formatMs(Number(done.firstTextMs ?? done.ms)),
              total: formatMs(state.totalMs() ?? 0),
              tokens: formatTokens(tokens),
            })
          : app.translator.trans('flarum-ai.admin.test.passed_empty_text', { model, tokens: formatTokens(tokens) })}
      </p>
    );
  }

  failed(): Mithril.Children {
    const state = this.attrs.state;

    if (state.refused) {
      return (
        <Alert type="error" dismissible={false}>
          {state.refused}
        </Alert>
      );
    }

    const error = state.error;
    // A dropped connection ends without an error; an uncaught one has no code.
    const code = error ? String(error.code || 'failed') : 'lost';
    const known = ['auth', 'model', 'unreachable', 'rate_limited', 'credits', 'overloaded', 'limit', 'not_configured', 'failed', 'lost'];

    return (
      <Alert type="error" dismissible={false}>
        <p>
          {app.translator.trans(`flarum-ai.admin.test.error.${known.includes(code) ? code : 'failed'}_text`, {
            model: String(error?.model || ''),
            tokens: formatTokens(Number(error?.tokens || 0)),
            date: error?.resetsAt ? dayjs(String(error.resetsAt)).format('ll') : '',
          })}
        </p>
        {!!error?.message && (
          <p className="AiConnectionTest-providerMessage">
            {error.status
              ? app.translator.trans('flarum-ai.admin.test.provider_status_text', { status: String(error.status), message: String(error.message) })
              : app.translator.trans('flarum-ai.admin.test.provider_text', { message: String(error.message) })}
          </p>
        )}
      </Alert>
    );
  }

  streamingNote(): Mithril.Children {
    const verdict = this.attrs.state.buffering;

    if (verdict === 'buffered') {
      return (
        <Alert type="warning" dismissible={false} className="AiConnectionTest-buffered">
          {app.translator.trans('flarum-ai.admin.streaming.buffered_text')}
        </Alert>
      );
    }

    return (
      <p className="helpText AiConnectionTest-streaming">
        {app.translator.trans(verdict === 'streamed' ? 'flarum-ai.admin.test.streams_text' : 'flarum-ai.admin.test.too_quick_text')}
      </p>
    );
  }
}
