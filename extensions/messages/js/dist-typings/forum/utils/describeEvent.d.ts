import type Mithril from 'mithril';
import type DialogMessage from '../../common/models/DialogMessage';
/**
 * What a notice in a conversation says happened: who left it, who named it.
 * In plain words for a preview, otherwise with a link to the member.
 */
export default function describeEvent(message: DialogMessage, plain: true): string;
export default function describeEvent(message: DialogMessage, plain?: false): Mithril.Children;
