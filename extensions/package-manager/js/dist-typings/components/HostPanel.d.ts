import Component, { type ComponentAttrs } from 'flarum/common/Component';
import type { HostReport } from '../types';
export interface HostPanelAttrs extends ComponentAttrs {
    host: HostReport | null;
    onchange?: () => void;
}
/**
 * What this host will and will not let the Extension Manager do — shown before anything is
 * pressed, which is the entire point.
 *
 * Every row says what it MEANS, not just what it is. "memory_limit: 128M" is
 * a fact; "Composer cannot resolve dependencies here at all — ask your host for
 * 256 MB" is something somebody can act on. A panel of facts without
 * consequences is the spinner problem wearing a different hat.
 *
 * Rows that arrive as keys (the PHP diagnosis) are translated here; the older
 * rows still arrive as text.
 */
export default class HostPanel extends Component<HostPanelAttrs> {
    phpPath: string | null;
    saving: boolean;
    view(): JSX.Element | null;
    /** A row's status: an icon for the eye, its name for a screen reader. */
    mark(status: 'ok' | 'warn' | 'fail'): JSX.Element;
    /** Where the admin can point the Extension Manager at the command-line PHP itself. */
    phpSetting(onchange?: () => void): JSX.Element;
    save(path: string, onchange?: () => void): void;
}
