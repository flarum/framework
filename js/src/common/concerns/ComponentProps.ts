import { ComponentProps } from '../Component';
import Discussion from '../models/Discussion';
import Post from '../models/Post';

export interface DiscussionProp extends ComponentProps {
    discussion: Discussion;
}

export interface PostProp extends ComponentProps {
    post: Post;
}
