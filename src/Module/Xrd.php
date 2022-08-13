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

use Friendica\BaseModule;
use Friendica\Core\Hook;
use Friendica\Core\Renderer;
use Friendica\Core\System;
use Friendica\DI;
use Friendica\Model\Photo;
use Friendica\Model\User;
use Friendica\Network\HTTPException\NotFoundException;
use Friendica\Protocol\ActivityNamespace;
use Friendica\Protocol\Salmon;

/**
 * Prints responses to /.well-known/webfinger  or /xrd requests
 */
class Xrd extends BaseModule
{
	protected function rawContent(array $request = [])
	{
		// @TODO: Replace with parameter from router
		if (DI::args()->getArgv()[0] == 'xrd') {
			if (empty($_GET['uri'])) {
				return;
			}

			$uri = urldecode(trim($_GET['uri']));
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/jrd+json') !== false)  {
				$mode = Response::TYPE_JSON;
			} else {
				$mode = Response::TYPE_XML;
			}
		} else {
			if (empty($_GET['resource'])) {
				return;
			}

			$uri = urldecode(trim($_GET['resource']));
			if (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/xrd+xml') !== false)  {
				$mode = Response::TYPE_XML;
			} else {
				$mode = Response::TYPE_JSON;
			}
		}

		if (substr($uri, 0, 4) === 'http') {
			$name = ltrim(basename($uri), '~');
		} else {
			$local = str_replace('acct:', '', $uri);
			if (substr($local, 0, 2) == '//') {
				$local = substr($local, 2);
			}

			$name = substr($local, 0, strpos($local, '@'));
		}

		if ($name == User::getActorName()) {
			$owner = User::getSystemAccount();
			if (empty($owner)) {
				throw new NotFoundException('System account was not found. Please setup your Friendica installation properly.');
			}
			$this->printSystemJSON($owner);
		} else {
			$user = User::getByNickname($name);
			if (empty($user)) {
				throw new NotFoundException('User was not found for name=' . $name);
			}

			$owner = User::getOwnerDataById($user['uid']);
			if (empty($owner)) {
				DI::logger()->warning('No owner data for user id', ['uri' => $uri, 'name' => $name, 'user' => $user]);
				throw new NotFoundException('Owner was not found for user->uid=' . $user['uid']);
			}

			$alias = str_replace('/profile/', '/~', $owner['url']);

			$avatar = Photo::selectFirst(['type'], ['uid' => $owner['uid'], 'profile' => true]);
		}

		if (empty($avatar)) {
			$avatar = ['type' => 'image/jpeg'];
		}

		if ($mode == Response::TYPE_XML) {
			$this->printXML($alias, $user, $owner, $avatar);
		} else {
			$this->printJSON($alias, $owner, $avatar);
		}
	}

	private function printSystemJSON(array $owner)
	{
		$baseURL = $this->baseUrl->get();
		$json = [
			'subject' => 'acct:' . $owner['addr'],
			'aliases' => [$owner['url']],
			'links'   => [
				[
					'rel'  => 'http://webfinger.net/rel/profile-page',
					'type' => 'text/html',
					'href' => $owner['url'],
				],
				[
					'rel'  => 'self',
					'type' => 'application/activity+json',
					'href' => $owner['url'],
				],
				[
					'rel'      => 'http://ostatus.org/schema/1.0/subscribe',
					'template' => $baseURL . '/follow?url={uri}',
				],
				[
					'rel'  => ActivityNamespace::FEED,
					'type' => 'application/atom+xml',
					'href' => $owner['poll'] ?? $baseURL,
				],
				[
					'rel'  => 'salmon',
					'href' => $baseURL . '/salmon/' . $owner['nickname'],
				],
				[
					'rel'  => 'http://microformats.org/profile/hcard',
					'type' => 'text/html',
					'href' => $baseURL . '/hcard/' . $owner['nickname'],
				],
				[
					'rel'  => 'http://joindiaspora.com/seed_location',
					'type' => 'text/html',
					'href' => $baseURL,
				],
			]
		];
		header('Access-Control-Allow-Origin: *');
		System::jsonExit($json, 'application/jrd+json; charset=utf-8');
	}

	private function printJSON(string $alias, array $owner, array $avatar)
	{
		$baseURL = $this->baseUrl->get();
		$salmon_key = Salmon::salmonKey($owner['spubkey']);

		$json = [
			'subject' => 'acct:' . $owner['addr'],
			'aliases' => [
				$alias,
				$owner['url'],
			],
			'links'   => [
				[
					'rel'  => ActivityNamespace::DFRN ,
					'href' => $owner['url'],
				],
				[
					'rel'  => ActivityNamespace::FEED,
					'type' => 'application/atom+xml',
					'href' => $owner['poll'],
				],
				[
					'rel'  => 'http://webfinger.net/rel/profile-page',
					'type' => 'text/html',
					'href' => $owner['url'],
				],
				[
					'rel'  => 'self',
					'type' => 'application/activity+json',
					'href' => $owner['url'],
				],
				[
					'rel'  => 'http://microformats.org/profile/hcard',
					'type' => 'text/html',
					'href' => $baseURL . '/hcard/' . $owner['nickname'],
				],
				[
					'rel'  => ActivityNamespace::POCO,
					'href' => $owner['poco'],
				],
				[
					'rel'  => 'http://webfinger.net/rel/avatar',
					'type' => $avatar['type'],
					'href' => User::getAvatarUrl($owner),
				],
				[
					'rel'  => 'http://joindiaspora.com/seed_location',
					'type' => 'text/html',
					'href' => $baseURL,
				],
				[
					'rel'  => 'salmon',
					'href' => $baseURL . '/salmon/' . $owner['nickname'],
				],
				[
					'rel'  => 'http://salmon-protocol.org/ns/salmon-replies',
					'href' => $baseURL . '/salmon/' . $owner['nickname'],
				],
				[
					'rel'  => 'http://salmon-protocol.org/ns/salmon-mention',
					'href' => $baseURL . '/salmon/' . $owner['nickname'] . '/mention',
				],
				[
					'rel'      => 'http://ostatus.org/schema/1.0/subscribe',
					'template' => $baseURL . '/follow?url={uri}',
				],
				[
					'rel'  => 'magic-public-key',
					'href' => 'data:application/magic-public-key,' . $salmon_key,
				],
				[
					'rel'  => 'http://purl.org/openwebauth/v1',
					'type' => 'application/x-zot+json',
					'href' => $baseURL . '/owa',
				],
			],
		];

		header('Access-Control-Allow-Origin: *');
		System::jsonExit($json, 'application/jrd+json; charset=utf-8');
	}

	private function printXML(string $alias, array $user, array $owner, array $avatar)
	{
		$baseURL = $this->baseUrl->get();
		$salmon_key = Salmon::salmonKey($owner['spubkey']);

		$tpl = Renderer::getMarkupTemplate('xrd_person.tpl');

		$o = Renderer::replaceMacros($tpl, [
			'$nick'        => $owner['nickname'],
			'$accturi'     => 'acct:' . $owner['addr'],
			'$alias'       => $alias,
			'$profile_url' => $owner['url'],
			'$hcard_url'   => $baseURL . '/hcard/' . $owner['nickname'],
			'$atom'        => $owner['poll'],
			'$poco_url'    => $owner['poco'],
			'$photo'       => User::getAvatarUrl($owner),
			'$type'        => $avatar['type'],
			'$salmon'      => $baseURL . '/salmon/' . $owner['nickname'],
			'$salmen'      => $baseURL . '/salmon/' . $owner['nickname'] . '/mention',
			'$subscribe'   => $baseURL . '/follow?url={uri}',
			'$openwebauth' => $baseURL . '/owa',
			'$modexp'      => 'data:application/magic-public-key,' . $salmon_key
		]);

		$arr = ['user' => $user, 'xml' => $o];
		Hook::callAll('personal_xrd', $arr);

		header('Access-Control-Allow-Origin: *');

		System::httpExit($arr['xml'], Response::TYPE_XML, 'application/xrd+xml');
	}
}
