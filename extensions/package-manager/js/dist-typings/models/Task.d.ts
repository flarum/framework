import Model from 'flarum/common/Model';
export declare type TaskOperations = 'extension_install' | 'extension_remove' | 'extension_update' | 'update_global' | 'update_minor' | 'update_major' | 'update_check' | 'why_not';
export default class Task extends Model {
    status(): "pending" | "running" | "failure" | "success";
    operation(): TaskOperations;
    command(): string;
    package(): string;
    output(): string;
    guessedCause(): string;
    createdAt(): Date | null | undefined;
    startedAt(): Date;
    finishedAt(): Date;
    peakMemoryUsed(): string;
}
