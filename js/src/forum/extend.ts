import Extend from 'flarum/common/extenders';
import Discussion from 'flarum/common/models/Discussion';
import GoogleNewsMeta from '../common/GoogleNewsMeta';

export default [
  new Extend.Store().add('googleNewsMeta', GoogleNewsMeta),

  new Extend.Model(Discussion).hasOne<GoogleNewsMeta>('googleNewsMeta'),
];
