import IExtender, { IExtensionModule } from './IExtender';
import type Component from '../Component';
import type ForumApplication from '../../forum/ForumApplication';
import type { NewComponent } from '../Application';

export default class Notification implements IExtender<ForumApplication> {
  private notificationComponents: Record<string, new () => Component> = {};

  /**
   * Register a new notification component type.
   *
   * @param name The name of the notification type.
   * @param component The component class to render the notification.
   */
  add(name: string, component: NewComponent<any>): Notification {
    this.notificationComponents[name] = component;

    return this;
  }

  extend(app: ForumApplication, extension: IExtensionModule): void {
    Object.assign(app.notificationComponents, this.notificationComponents);
  }
}
