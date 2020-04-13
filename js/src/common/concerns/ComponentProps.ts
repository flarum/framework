import { ComponentProps } from '../Component';
import Discussion from '../models/Discussion';
import Post from '../models/Post';
import User from '../models/User';

export interface DiscussionProp extends ComponentProps {
    discussion: Discussion;
}

export interface PostProp extends ComponentProps {
    post: Post;
}

export interface UserProp extends ComponentProps {
    user: User;
}
