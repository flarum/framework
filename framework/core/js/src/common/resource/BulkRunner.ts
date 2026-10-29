import type Model from '../Model';
import type ResourceListState from './ResourceListState';

/**
 * The outcome of running one operation over many records: the records it succeeded for, in input
 * order, and the ones it failed for with their errors.
 */
export interface BulkResult<M> {
  ok: M[];
  failed: { model: M; error: unknown }[];
}

/**
 * A bulk result plus the selected records the action left out (e.g. no `canDelete`).
 */
export interface BulkSummary<M> extends BulkResult<M> {
  skipped: M[];
}

/**
 * Called after each operation settles, with the number settled so far and the total.
 */
export type BulkProgress = (done: number, total: number) => void;

/**
 * What a definition's bulk actions get from the view that renders them.
 */
export interface BulkActionContext<M extends Model = Model> {
  /** The list the selection belongs to; refreshed after an action. */
  list: ResourceListState<M>;
  runner: ConcurrentBulkRunner;
  /** Show the outcome of an action; the admin `ResourceTable` opens `BulkResultModal`. */
  showResult(summary: BulkSummary<M>): void;
}

/**
 * Runs one operation per record, at most `concurrency` at a time (3 by default, to stay well inside
 * browser and server connection limits), and collects the outcome. One failure never stops the
 * others.
 */
export class ConcurrentBulkRunner {
  constructor(readonly concurrency: number = 3) {}

  async run<M>(models: M[], op: (model: M) => Promise<unknown>, onProgress?: BulkProgress): Promise<BulkResult<M>> {
    const outcomes: ({ ok: true } | { ok: false; error: unknown })[] = new Array(models.length);
    let next = 0;
    let done = 0;

    const worker = async (): Promise<void> => {
      while (next < models.length) {
        const index = next++;

        try {
          await op(models[index]);
          outcomes[index] = { ok: true };
        } catch (error) {
          outcomes[index] = { ok: false, error };
        }

        onProgress?.(++done, models.length);
      }
    };

    await Promise.all(Array.from({ length: Math.max(1, Math.min(this.concurrency, models.length)) }, worker));

    const result: BulkResult<M> = { ok: [], failed: [] };

    outcomes.forEach((outcome, index) => {
      if (outcome.ok) result.ok.push(models[index]);
      else result.failed.push({ model: models[index], error: outcome.error });
    });

    return result;
  }
}
