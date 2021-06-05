<?php
/**
 * @copyright Copyright (C) 2010-2021, the Friendica project
 *
 * @license GNU AGPL version 3 or any later version
 *
 * This program is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as
 * published by the Free Software Foundation, either version 3 of the
 * License, or (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU Affero General Public License for more details.
 *
 * You should have received a copy of the GNU Affero General Public License
 * along with this program.  If not, see <https://www.gnu.org/licenses/>.
 *
 */

namespace Friendica\Module\Api\Mastodon\Timelines;

use Friendica\Core\Protocol;
use Friendica\Core\System;
use Friendica\Database\DBA;
use Friendica\DI;
use Friendica\Model\Post;
use Friendica\Module\Api\BaseMastodon;
use Friendica\Network\HTTPException;

/**
 * @see https://docs.joinmastodon.org/methods/timelines/
 */
class Tag extends BaseMastodon
{
	/**
	 * @param array $parameters
	 * @throws HTTPException\InternalServerErrorException
	 */
	public static function rawContent(array $parameters = [])
	{
		self::checkAllowedScope(self::SCOPE_READ);
		$uid = self::getCachedCurrentUserIdFromRequest();

		if (empty($parameters['hashtag'])) {
			DI::mstdnError()->UnprocessableEntity();
		}

		$request = self::getRequest([
			'local'           => false, // If true, return only local statuses. Defaults to false.
			'remote'          => false, // Show only remote statuses? Defaults to false.
			'only_media'      => false, // If true, return only statuses with media attachments. Defaults to false.
			'max_id'          => 0,     // Return results older than this ID.
			'since_id'        => 0,     // Return results newer than this ID.
			'min_id'          => 0,     // Return results immediately newer than this ID.
			'limit'           => 20,    // Maximum number of results to return. Defaults to 20.
			'with_muted'      => false, // Pleroma extension: return activities by muted (not by blocked!) users.
			'exclude_replies' => false, // Don't show comments
		]);

		$params = ['order' => ['uri-id' => true], 'limit' => $request['limit']];

		$condition = ["`name` = ? AND (`uid` = ? OR (`uid` = ? AND NOT `global`))
			AND (`network` IN (?, ?, ?, ?) OR (`uid` = ? AND `uid` != ?))",
			$parameters['hashtag'], 0, $uid, Protocol::ACTIVITYPUB, Protocol::DFRN, Protocol::DIASPORA, Protocol::OSTATUS, $uid, 0];

		if ($request['local']) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `origin`)"]);
		}

		if ($request['remote']) {
			$condition = DBA::mergeConditions($condition, ["NOT `uri-id` IN (SELECT `uri-id` FROM `post-user` WHERE `origin`)"]);
		}

		if ($request['only_media']) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` IN (SELECT `uri-id` FROM `post-media` WHERE `type` IN (?, ?, ?))",
				Post\Media::AUDIO, Post\Media::IMAGE, Post\Media::VIDEO]);
		}

		if ($request['exclude_replies']) {
			$condition = DBA::mergeConditions($condition, ['gravity' => GRAVITY_PARENT]);
		}

		if (!empty($request['max_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` < ?", $request['max_id']]);
		}

		if (!empty($request['since_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $request['since_id']]);
		}

		if (!empty($request['min_id'])) {
			$condition = DBA::mergeConditions($condition, ["`uri-id` > ?", $request['min_id']]);

			$params['order'] = ['uri-id'];
		}

		$items = DBA::select('tag-search-view', ['uri-id'], $condition, $params);

		$statuses = [];
		while ($item = Post::fetch($items)) {
			$statuses[] = DI::mstdnStatus()->createFromUriId($item['uri-id'], $uid);
		}
		DBA::close($items);

		if (!empty($request['min_id'])) {
			array_reverse($statuses);
		}

		System::jsonExit($statuses);
	}
}
