import DefaultResolver from '../../common/resolvers/DefaultResolver';
/**
 * A custom route resolver for ExtensionPage that generates handles routes
 * to default extension pages or a page provided by an extension.
 */
export default class ExtensionPageResolver extends DefaultResolver {
    static extension: string | null;
    onmatch(args: any, requestedPath: any, route: any): any;
}
