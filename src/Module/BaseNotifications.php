<?php
/**
 * @copyright Copyright (C) 2010-2022, the Friendica project
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

namespace Friendica\Module;

use Exception;
use Friendica\App;
use Friendica\App\Arguments;
use Friendica\BaseModule;
use Friendica\Content\Pager;
use Friendica\Core\L10n;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\Navigation\Notifications\ValueObject\FormattedNotify;
use Friendica\Network\HTTPException\ForbiddenException;
use Friendica\Util\Profiler;
use Psr\Log\LoggerInterface;

/**
 * Base Module for each tab of the notification display
 *
 * General possibility to print it as JSON as well
 */
abstract class BaseNotifications extends BaseModule
{
	/** @var array Array of URL parameters */
	const URL_TYPES = [
		FormattedNotify::NETWORK  => 'network',
		FormattedNotify::SYSTEM   => 'system',
		FormattedNotify::HOME     => 'home',
		FormattedNotify::PERSONAL => 'personal',
		FormattedNotify::INTRO    => 'intros',
	];

	/** @var array Array of the allowed notifications and their printable name */
	const PRINT_TYPES = [
		FormattedNotify::NETWORK  => 'Network',
		FormattedNotify::SYSTEM   => 'System',
		FormattedNotify::HOME     => 'Home',
		FormattedNotify::PERSONAL => 'Personal',
		FormattedNotify::INTRO    => 'Introductions',
	];

	/** @var array The array of access keys for notification pages */
	const ACCESS_KEYS = [
		FormattedNotify::NETWORK  => 'w',
		FormattedNotify::SYSTEM   => 'y',
		FormattedNotify::HOME     => 'h',
		FormattedNotify::PERSONAL => 'r',
		FormattedNotify::INTRO    => 'i',
	];

	/** @var int The default count of items per page */
	const ITEMS_PER_PAGE = 30;
	/** @var int The default limit of notifications per page */
	const DEFAULT_PAGE_LIMIT = 80;

	/**
	 * Shows the printable result of notifications for a specific tab
	 *
	 * @param string $header        The notification header
	 * @param string $noContent     The string in case there are no notifications
	 * @param array  $notifications The array with the notifications
	 * @param int    $totalCount
	 *
	 * @return string The rendered output
	 *
	 * @throws \Friendica\Network\HTTPException\InternalServerErrorException
	 * @throws \Friendica\Network\HTTPException\ServiceUnavailableException
	 */
	protected function printContent(string $header, string $noContent, array $notifications, int $totalCount): string
	{
		// Get the nav tabs for the notification pages
		$tabs = $this->getTabs();

		// Set the pager
		$pager = new Pager($this->l10n, $this->args->getQueryString(), self::ITEMS_PER_PAGE);

		$notif_tpl = Renderer::getMarkupTemplate('notifications/notifications.tpl');
		return Renderer::replaceMacros($notif_tpl, [
			'$l10n' => [
				'title' => $header ?: $this->t('Notifications'),
				'noContent' => $noContent,
			],
			'$tabs'          => $tabs,
			'$notifications' => $notifications,
			'$pager'         => $pager->renderFull($totalCount),
		]);
	}

	/**
	 * List of pages for the Notifications TabBar
	 *
	 * @return array with notifications TabBar data
	 * @throws Exception
	 */
	private function getTabs()
	{
		$selected = $this->args->get(1, '');

		$tabs = [];

		foreach (self::URL_TYPES as $type => $url) {
			$tabs[] = [
				'label'     => $this->t(self::PRINT_TYPES[$type]),
				'url'       => 'notifications/' . $url,
				'sel'       => (($selected == $url) ? 'active' : ''),
				'id'        => $type . '-tab',
				'accesskey' => self::ACCESS_KEYS[$type],
			];
		}

		return $tabs;
	}
}
