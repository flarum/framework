import type { RollbackPayload, Run } from './types';
/**
 * Asks before undoing a run, and says whether the database goes back too.
 *
 * Every way to undo a run comes through here: a finished run, a failed one,
 * and the History tab. Undoing a FAILED run skipped it at first, so the one
 * case where migrations may have half run was the one with no warning that
 * they would be reversed.
 */
export declare function confirmRollback(run: Run, onconfirm: () => void): void;
/** Undoes the latest run, the only one the endpoint acts on. A refusal is shown as the server's words. */
export declare function requestRollback(): Promise<RollbackPayload>;
