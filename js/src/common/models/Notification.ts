import Model from '../Model';
import User from './User';

export default class Notification extends Model {
    static ADMINISTRATOR_ID = '1';
    static GUEST_ID = '2';
    static MEMBER_ID = '3';

    contentType = Model.attribute('contentType') as () => string;
    content = Model.attribute('content') as () => string;
    createdAt = Model.attribute('createdAt', Model.transformDate) as () => Date;

    isRead = Model.attribute('isRead') as () => boolean;

    user = Model.hasOne('user') as () => User;
    fromUser = Model.hasOne('fromUser') as () => User;
    subject = Model.hasOne('subhect') as () => any;
}
