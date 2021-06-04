<?php

namespace Friendica\Module\Api;

use Friendica\Module\BaseApi;

/**
 * Mastodon API-specific methods
 */
class BaseMastodon extends BaseApi
{
	/**
	 * Get current application from the Bearer token
	 *
	 * This Mastodon-specific Application is unrelated with the local Friendica application model.
	 *
	 * @return array token
	 */
	protected static function getCurrentApplication(): array
	{
		return self::getCachedTokenByBearer();
	}
}
