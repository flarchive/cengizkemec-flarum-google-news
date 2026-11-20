import Model from 'flarum/common/Model';
import Discussion from 'flarum/common/models/Discussion';

export default class GoogleNewsMeta extends Model {
  discussion(): false | Discussion | null {
    return Model.hasOne<Discussion>('discussion').call(this);
  }

  isNews(): boolean {
    return Model.attribute<boolean>('isNews').call(this);
  }

  enableStructuredData(): boolean {
    return Model.attribute<boolean>('enableStructuredData').call(this);
  }

  includeInSitemap(): boolean {
    return Model.attribute<boolean>('includeInSitemap').call(this);
  }

  includeInFeed(): boolean {
    return Model.attribute<boolean>('includeInFeed').call(this);
  }

  headline(): string | null {
    return Model.attribute<string | null>('headline').call(this);
  }

  description(): string | null {
    return Model.attribute<string | null>('description').call(this);
  }

  category(): string | null {
    return Model.attribute<string | null>('category').call(this);
  }

  language(): string | null {
    return Model.attribute<string | null>('language').call(this);
  }

  coverImage(): string | null {
    return Model.attribute<string | null>('coverImage').call(this);
  }

  authorName(): string | null {
    return Model.attribute<string | null>('authorName').call(this);
  }

  authorUrl(): string | null {
    return Model.attribute<string | null>('authorUrl').call(this);
  }
}
