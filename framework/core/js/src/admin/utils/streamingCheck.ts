import app from '../app';
import type { StreamArrival } from '../../common/utils/readEventStream';

export type StreamingStatus = 'idle' | 'checking' | 'streamed' | 'buffered' | 'unknown' | 'failed';

const STORAGE_KEY = 'flarum.streamingCheck';

/**
 * Whether this server lets a streamed response reach the browser as it's
 * written. The dashboard checks once per browser session and warns when it's
 * held back. Anything else that streams, and can tell, can report what it saw.
 */
export const streamingCheck = {
  status: readStored() as StreamingStatus,

  /** The last conclusive answer, kept while the check runs again. */
  verdict: readStored() === 'idle' ? null : (readStored() as 'streamed' | 'buffered' | null),

  /**
   * Asks the server for a few events a moment apart, and sees whether they
   * arrive apart too.
   */
  run(): Promise<void> {
    const arrivals: StreamArrival[] = [];

    this.status = 'checking';
    m.redraw();

    return import('../../common/utils/readEventStream')
      .then(({ default: readEventStream, streamBuffering }) =>
        readEventStream(app.forum.attribute('apiUrl') + '/streaming-check', {}, (event, arrival) => arrivals.push(arrival)).then(() =>
          this.note(streamBuffering(arrivals))
        )
      )
      .catch((error) => {
        console.error(error);
        this.status = 'failed';
      })
      .finally(() => m.redraw());
  },

  /**
   * Runs the check, unless it has already run in this browser session.
   */
  runOnce(): Promise<void> {
    return this.status === 'idle' ? this.run() : Promise.resolve();
  },

  /**
   * What another stream saw. One too quick to tell changes nothing.
   */
  note(verdict: 'streamed' | 'buffered' | 'unknown') {
    if (verdict === 'unknown') {
      if (this.status === 'checking') this.status = 'unknown';

      return;
    }

    this.status = this.verdict = verdict;

    try {
      sessionStorage.setItem(STORAGE_KEY, verdict);
    } catch (e) {
      // Without storage, the next page load checks again.
    }
  },
};

function readStored(): StreamingStatus {
  try {
    const stored = sessionStorage.getItem(STORAGE_KEY);

    return stored === 'streamed' || stored === 'buffered' ? stored : 'idle';
  } catch (e) {
    return 'idle';
  }
}
