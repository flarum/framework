import IExtender, { IExtensionModule } from './IExtender';
import type ForumApplication from '../../forum/ForumApplication';
export default class PostTypes implements IExtender<ForumApplication> {
    private postComponents;
    /**
     * Register a new post component type.
     * Usually used for event posts.
     *
     * @param name The name of the post type.
     * @param component The component class to render the post.
     */
    add(name: string, component: any): PostTypes;
    extend(app: ForumApplication, extension: IExtensionModule): void;
}
