import AdminPage from './AdminPage';
import type { IPageAttrs } from '../../common/components/Page';
import type Mithril from 'mithril';
import ItemList from '../../common/utils/ItemList';
import type { AccessTokenLifetime } from '../AdminApplication';
export default class AdvancedPage<CustomAttrs extends IPageAttrs = IPageAttrs> extends AdminPage<CustomAttrs> {
    searchDriverOptions: Record<string, Record<string, string>>;
    urlRequestedModalHasBeenShown: boolean;
    oninit(vnode: Mithril.Vnode<CustomAttrs, this>): void;
    headerInfo(): {
        className: string;
        icon: string;
        title: string | any[];
        description: string | any[];
    };
    content(): JSX.Element[];
    driverLocale(): Record<string, Record<string, string>>;
    sectionItems(): ItemList<Mithril.Children>;
    sessions(): JSX.Element;
    sessionItems(): ItemList<Mithril.Children>;
    sessionConfigOverride(): JSX.Element;
    sessionLifetime(): JSX.Element;
    /**
     * One control per registered token type, so a type added by an extension is
     * configured here alongside the ones core ships.
     */
    sessionTokenLifetimes(): JSX.Element | null;
    tokenLifetimeControl(token: AccessTokenLifetime): JSX.Element;
    sessionCookieControl(): JSX.Element;
    /**
     * A name for a token type. Types core knows about are named properly; one
     * added by an extension falls back to its own identifier rather than going
     * unlabelled.
     */
    tokenTypeLabel(type: string): string;
    tokenTypeHelp(type: string): Mithril.Children;
    durationLabel(seconds: number): string;
    searchDrivers(): JSX.Element;
    maintenance(): JSX.Element;
    queue(): JSX.Element;
    queueItems(): ItemList<Mithril.Children>;
    queuePauseControl(): JSX.Element;
    queueSyncContent(): JSX.Element;
    queueDatabaseContent(): JSX.Element;
    queueDatabaseSettings(): ItemList<Mithril.Children>;
    queueCustomContent(): JSX.Element;
    pgsqlSettings(): JSX.Element;
    fontAwesome(): JSX.Element;
    static register(): void;
}
