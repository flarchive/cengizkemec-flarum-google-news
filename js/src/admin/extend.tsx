import app from 'flarum/admin/app';
import Extend from 'flarum/common/extenders';
import GoogleNewsPage from './GoogleNewsPage';
import { settings, sections, t } from './settings';

const admin = new Extend.Admin();

for (const section of sections) {
  admin.customSetting(
    () => <h2 className="GoogleNewsSectionTitle">{t(section.label)}</h2>,
    section.priority
  );
}

for (const definition of settings) {
  admin.setting(() => definition.config as any, definition.priority);
}

admin
  .page(GoogleNewsPage as any)
  .permission(
    () => ({
      icon: 'fas fa-newspaper',
      label: t('permissions.manage'),
      permission: 'googleNews.manage',
    }),
    'moderate',
    50
  );

export default [admin];
