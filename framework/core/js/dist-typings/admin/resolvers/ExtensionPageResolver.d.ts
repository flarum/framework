import DefaultResolver from '../../common/resolvers/DefaultResolver';
import ExtensionPage, { ExtensionPageAttrs } from '../components/ExtensionPage';
/**
 * A custom route resolver for ExtensionPage that generates handles routes
 * to default extension pages or a page provided by an extension.
 */
export default class ExtensionPageResolver<Attrs extends ExtensionPageAttrs = ExtensionPageAttrs, RouteArgs extends Record<string, unknown> = {}> extends DefaultResolver<Attrs, ExtensionPage<Attrs>, RouteArgs> {
    static extension: string | null;
    /**
     * Switching tabs (`?tab=`) keeps the page, and any unsaved changes on it.
     */
    makeKey(): string;
    onmatch(args: Attrs & RouteArgs, requestedPath: string, route: string): Promise<import("../../common/Application").NewComponent<ExtensionPage<Attrs>>>;
}
