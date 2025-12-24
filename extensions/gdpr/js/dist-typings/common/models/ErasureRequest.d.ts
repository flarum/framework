import Model from 'flarum/common/Model';
import User from 'flarum/common/models/User';
export default class ErasureRequest extends Model {
    status(): string;
    reason(): string;
    createdAt(): Date | null | undefined;
    userConfirmedAt(): Date | null | undefined;
    processedAt(): Date | null | undefined;
    processorComment(): string;
    processedMode(): string;
    user(): false | User;
    processedBy(): false | User;
}
