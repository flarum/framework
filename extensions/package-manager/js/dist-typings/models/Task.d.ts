import Model from 'flarum/common/Model';
export declare type TaskOperations = 'extension_install' | 'extension_remove' | 'extension_update' | 'update_global' | 'update_minor' | 'update_major' | 'update_check' | 'why_not';
export default class Task extends Model {
    status(): any;
    operation(): any;
    command(): any;
    package(): any;
    output(): any;
    createdAt(): any;
    startedAt(): any;
    finishedAt(): any;
    peakMemoryUsed(): string;
}
