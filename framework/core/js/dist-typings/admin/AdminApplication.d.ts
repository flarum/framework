import { AdminRoutes } from './routes';
import Application, { ApplicationData } from '../common/Application';
import AdminRegistry from './utils/AdminRegistry';
import IHistory from '../common/IHistory';
import SearchManager from '../common/SearchManager';
import SearchState from '../common/states/SearchState';
import GeneralSearchIndex from './states/GeneralSearchIndex';
export interface Extension {
    id: string;
    name: string;
    version: string;
    description?: string;
    icon?: {
        name: string;
        [key: string]: string;
    };
    links: {
        authors?: {
            name?: string;
            link?: string;
        }[];
        discuss?: string;
        documentation?: string;
        support?: string;
        website?: string;
        donate?: string;
        source?: string;
    };
    extra: {
        'flarum-extension': {
            title: string;
            category?: string;
            'database-support'?: string[];
        };
    };
    require?: Record<string, string>;
}
export declare enum DatabaseDriver {
    MySQL = "MySQL",
    PostgreSQL = "PostgreSQL",
    SQLite = "SQLite"
}
export interface AdminApplicationData extends ApplicationData {
    extensions: Record<string, Extension>;
    settings: Record<string, string>;
    modelStatistics: Record<string, {
        total: number;
    }>;
    displayNameDrivers: string[];
    slugDrivers: Record<string, string[]>;
    searchDrivers: Record<string, string[]>;
    permissions: Record<string, string[]>;
    maintenanceByConfig: boolean;
    safeModeExtensions?: string[] | null;
    safeModeExtensionsConfig?: string[] | null;
    fontawesomeByConfig: boolean;
    fontawesomeConfig?: {
        source: string;
        cdn_url: string | null;
        kit_url: string | null;
    };
    dbDriver: DatabaseDriver;
    dbVersion: string;
    dbOptions: Record<string, string>;
    phpVersion: string;
    queueDriver: string;
    schedulerStatus: string;
    sessionDriver: string;
}
export default class AdminApplication extends Application {
    /**
     * Stores the available settings, permissions, and custom pages of the app.
     * Allows the global search to find these items.
     *
     * @internal
     */
    registry: AdminRegistry;
    extensionCategories: Record<string, number>;
    history: IHistory;
    search: SearchManager<SearchState>;
    /**
     * Custom settings and custom permissions do not go through the registry.
     * The general index is used to manually add these items to be picked up by the search.
     */
    generalIndex: GeneralSearchIndex;
    /**
     * Settings are serialized to the admin dashboard as strings.
     * Additional encoding/decoding is possible, but must take
     * place on the client side.
     *
     * @inheritdoc
     */
    data: AdminApplicationData;
    route: typeof Application.prototype.route & AdminRoutes;
    constructor();
    protected runBeforeMount(): void;
    /**
     * @inheritdoc
     */
    mount(): void;
    getRequiredPermissions(permission: string): string[];
}
