import app from 'flarum/forum/app';
import FormModal, { type IFormModalAttrs } from 'flarum/common/components/FormModal';
import Form from 'flarum/common/components/Form';
import Button from 'flarum/common/components/Button';
import Switch from 'flarum/common/components/Switch';
import Stream from 'flarum/common/utils/Stream';
import ItemList from 'flarum/common/utils/ItemList';
import type Discussion from 'flarum/common/models/Discussion';
import type RequestError from 'flarum/common/utils/RequestError';
import type Mithril from 'mithril';

import GoogleNewsMeta from '../common/GoogleNewsMeta';

type DiscussionWithNews = Discussion & {
  googleNewsMeta?: () => GoogleNewsMeta | false | null;
};

export interface GoogleNewsSettingsModalAttrs extends IFormModalAttrs {
  discussion: DiscussionWithNews;
}

export default class GoogleNewsSettingsModal extends FormModal<GoogleNewsSettingsModalAttrs> {
  meta!: GoogleNewsMeta;
  isNew!: boolean;

  isNews!: Stream<boolean>;
  enableStructuredData!: Stream<boolean>;
  includeInSitemap!: Stream<boolean>;
  includeInFeed!: Stream<boolean>;
  headline!: Stream<string>;
  description!: Stream<string>;
  category!: Stream<string>;
  language!: Stream<string>;
  coverImage!: Stream<string>;
  authorName!: Stream<string>;
  authorUrl!: Stream<string>;

  oninit(vnode: Mithril.Vnode<GoogleNewsSettingsModalAttrs, this>) {
    super.oninit(vnode);

    const existing = this.attrs.discussion.googleNewsMeta?.();
    this.meta = existing ? (existing as GoogleNewsMeta) : app.store.createRecord<GoogleNewsMeta>('googleNewsMeta');
    this.isNew = !this.meta.exists;

    const defaults = (app.forum.attribute<Record<string, any>>('googleNewsDefaults') || {}) as Record<string, any>;

    this.isNews = Stream(this.isNew ? !!defaults.isNews : !!this.meta.isNews());
    this.enableStructuredData = Stream(this.isNew ? !!defaults.enableStructuredData : !!this.meta.enableStructuredData());
    this.includeInSitemap = Stream(this.isNew ? !!defaults.includeInSitemap : !!this.meta.includeInSitemap());
    this.includeInFeed = Stream(this.isNew ? defaults.includeInFeed !== false : this.meta.includeInFeed() !== false);
    this.headline = Stream(this.meta.headline() || '');
    this.description = Stream(this.meta.description() || '');
    this.category = Stream(this.meta.category() || '');
    this.language = Stream(this.meta.language() || defaults.language || '');
    this.coverImage = Stream(this.meta.coverImage() || '');
    this.authorName = Stream(this.meta.authorName() || '');
    this.authorUrl = Stream(this.meta.authorUrl() || '');
  }

  className() {
    return 'Modal--small GoogleNewsSettingsModal';
  }

  title() {
    return app.translator.trans('cengizkemec-google-news.forum.modal.title');
  }

  content() {
    return (
      <div className="Modal-body">
        <p className="helpText GoogleNewsModal-intro">{app.translator.trans('cengizkemec-google-news.forum.modal.intro')}</p>
        <Form>{this.fields().toArray()}</Form>
      </div>
    );
  }

  fields() {
    const t = (key: string) => app.translator.trans(`cengizkemec-google-news.forum.modal.fields.${key}`);

    const items = new ItemList<any>();

    items.add(
      'isNews',
      <div className="Form-group">
        <Switch state={this.isNews()} onchange={this.isNews}>
          <b>{t('is_news')}</b>
        </Switch>
        <div className="helpText">{t('is_news_help')}</div>
      </div>,
      100
    );

    items.add(
      'structuredData',
      <div className="Form-group">
        <Switch state={this.enableStructuredData()} onchange={this.enableStructuredData}>
          <b>{t('structured_data')}</b>
        </Switch>
        <div className="helpText">{t('structured_data_help')}</div>
      </div>,
      95
    );

    items.add(
      'sitemap',
      <div className="Form-group">
        <Switch state={this.includeInSitemap()} onchange={this.includeInSitemap}>
          <b>{t('sitemap')}</b>
        </Switch>
        <div className="helpText">{t('sitemap_help')}</div>
      </div>,
      90
    );

    items.add(
      'feed',
      <div className="Form-group">
        <Switch state={this.includeInFeed()} onchange={this.includeInFeed}>
          <b>{t('feed')}</b>
        </Switch>
        <div className="helpText">{t('feed_help')}</div>
      </div>,
      85
    );

    items.add(
      'headline',
      <div className="Form-group">
        <label>{t('headline')}</label>
        <input className="FormControl" maxlength={200} bidi={this.headline} />
        <div className="helpText">{t('headline_help')}</div>
      </div>,
      80
    );

    items.add(
      'description',
      <div className="Form-group">
        <label>{t('description')}</label>
        <textarea className="FormControl" maxlength={1000} rows={4} bidi={this.description} />
        <div className="helpText">{t('description_help')}</div>
      </div>,
      75
    );

    items.add(
      'category',
      <div className="Form-group">
        <label>{t('category')}</label>
        <input className="FormControl" maxlength={150} bidi={this.category} />
        <div className="helpText">{t('category_help')}</div>
      </div>,
      70
    );

    items.add(
      'language',
      <div className="Form-group">
        <label>{t('language')}</label>
        <input className="FormControl" maxlength={20} placeholder="en" bidi={this.language} />
        <div className="helpText">{t('language_help')}</div>
      </div>,
      65
    );

    items.add(
      'coverImage',
      <div className="Form-group">
        <label>{t('cover_image')}</label>
        <input className="FormControl" type="url" maxlength={2048} placeholder="https://" bidi={this.coverImage} />
        <div className="helpText">{t('cover_image_help')}</div>
      </div>,
      60
    );

    items.add(
      'authorName',
      <div className="Form-group">
        <label>{t('author_name')}</label>
        <input className="FormControl" maxlength={150} bidi={this.authorName} />
        <div className="helpText">{t('author_name_help')}</div>
      </div>,
      55
    );

    items.add(
      'authorUrl',
      <div className="Form-group">
        <label>{t('author_url')}</label>
        <input className="FormControl" type="url" maxlength={2048} placeholder="https://" bidi={this.authorUrl} />
        <div className="helpText">{t('author_url_help')}</div>
      </div>,
      50
    );

    items.add(
      'submit',
      <div className="Form-group">
        <Button type="submit" className="Button Button--primary" loading={this.loading}>
          {app.translator.trans('cengizkemec-google-news.forum.modal.save')}
        </Button>
      </div>,
      -10
    );

    return items;
  }

  onsubmit(e: SubmitEvent) {
    e.preventDefault();

    if (this.loading) return;

    this.loading = true;

    const attributes: Record<string, any> = {
      isNews: !!this.isNews(),
      enableStructuredData: !!this.enableStructuredData(),
      includeInSitemap: !!this.includeInSitemap(),
      includeInFeed: !!this.includeInFeed(),
      headline: this.headline() || null,
      description: this.description() || null,
      category: this.category() || null,
      language: this.language() || null,
      coverImage: this.coverImage() || null,
      authorName: this.authorName() || null,
      authorUrl: this.authorUrl() || null,
    };

    const data: Record<string, any> = { ...attributes };

    if (this.isNew) {
      data.relationships = {
        discussion: this.attrs.discussion,
      };
    }

    this.meta.save(data).then(
      () => {
        this.attrs.discussion.pushData({
          relationships: {
            googleNewsMeta: this.meta,
          },
        });

        app.alerts.show({ type: 'success' }, app.translator.trans('cengizkemec-google-news.forum.modal.saved'));

        this.hide();
        m.redraw();
      },
      (error: RequestError) => {
        this.loaded();
        this.onerror(error);
      }
    );
  }
}
