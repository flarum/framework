import app from 'flarum/admin/app';
import { initialize } from '../common/index';

app.initializers.add('flarum-markdown', initialize);
