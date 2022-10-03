import IExtender, { IExtensionModule } from './IExtender';
import Application from '../Application';
import ForumApplication from '../../forum/ForumApplication';

export default class PostTypes implements IExtender {
  private postComponents: Record<string, any> = {};

  /**
   * Register a new post component type.
   * Usually used for event posts.
   *
   * @param name The name of the post type.
   * @param component The component class to render the post.
   */
  add(name: string, component: any): PostTypes {
    this.postComponents[name] = component;

    return this;
  }

  extend(app: Application, extension: IExtensionModule): void {
    Object.assign((app as unknown as ForumApplication).postComponents, this.postComponents);
  }
}
