import Model from '../Model';
import computed from '../utils/computed';
import { getPlainContent } from '../utils/string';

import Discussion from './Discussion';
import User from './User';

export default class Post extends Model {
    number = Model.attribute('number') as () => number;
    discussion = Model.hasOne('discussion') as () => Discussion;

    createdAt = Model.attribute('createdAt', Model.transformDate) as () => Date;
    user = Model.hasOne('user') as () => User;
    contentType = Model.attribute('contentType') as () => string;
    content = Model.attribute('content') as () => string;
    contentHtml = Model.attribute('contentHtml') as () => string;
    contentPlain = computed('contentHtml', getPlainContent) as () => string;

    editedAt = Model.attribute('editedAt', Model.transformDate) as () => Date;
    editedUser = Model.hasOne('editedUser') as () => User;
    isEdited = computed('editedAt', editedAt => !!editedAt) as () => boolean;

    hiddenAt = Model.attribute('hiddenAt', Model.transformDate) as () => Date;
    hiddenUser = Model.hasOne('hiddenUser') as () => User;
    isHidden = computed('hiddenAt', hiddenAt => !!hiddenAt) as () => boolean;

    canEdit = Model.attribute('canEdit') as () => boolean;
    canHide = Model.attribute('canHide') as () => boolean;
    canDelete = Model.attribute('canDelete') as () => boolean;
}
