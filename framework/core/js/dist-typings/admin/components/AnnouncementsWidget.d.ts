import DashboardWidget from './DashboardWidget';
import type { AnnouncementData } from './AnnouncementItem';
import type { IDashboardWidgetAttrs } from './DashboardWidget';
import type Mithril from 'mithril';
export default class AnnouncementsWidget extends DashboardWidget {
    announcements: AnnouncementData[] | null;
    loadError: boolean;
    loading: boolean;
    hidden: boolean;
    oninit(vnode: Mithril.Vnode<IDashboardWidgetAttrs, this>): void;
    className(): string;
    load(bust?: boolean): Promise<void>;
    toggleHidden(): void;
    content(): JSX.Element;
}
