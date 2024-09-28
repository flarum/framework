import app from 'flarum/forum/app';
import { initialize } from '../common/index';

app.initializers.add('flarum-markdown', initialize);
