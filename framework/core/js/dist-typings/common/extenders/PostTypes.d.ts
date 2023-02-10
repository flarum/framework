import IExtender, { IExtensionModule } from './IExtender';
import Application from '../Application';
export default class PostTypes implements IExtender {
    private postComponents;
    /**
     * Register a new post component type.
     * Usually used for event posts.
     *
     * @param name The name of the post type.
     * @param component The component class to render the post.
     */
    add(name: string, component: any): PostTypes;
    extend(app: Application, extension: IExtensionModule): void;
}
