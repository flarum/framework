import IExtender, { IExtensionModule } from './IExtender';
import type Application from '../Application';
import type { NewComponent } from '../Application';
export default class Notification implements IExtender {
    private notificationComponents;
    /**
     * Register a new notification component type.
     *
     * @param name The name of the notification type.
     * @param component The component class to render the notification.
     */
    add(name: string, component: NewComponent<any>): Notification;
    extend(app: Application, extension: IExtensionModule): void;
}
