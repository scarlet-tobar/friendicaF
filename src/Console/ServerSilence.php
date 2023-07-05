<?php
/**
 * @copyright Copyright (C) 2010-2023, the Friendica project
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

namespace Friendica\Console;

use Asika\SimpleConsole\CommandArgsException;
use Asika\SimpleConsole\Console;
use Friendica\Model\Contact;

/**
 * Manage Silenceed servers
 *
 * With this tool, you can list the current Silenceed servers
 * or you can add / remove a Silenced server from the list
 */
class ServerSilence extends Console
{
	protected $helpOptions = ['h', 'help', '?', ''];


	protected function getHelp(): string
	{
		return <<<HELP
console serverSilence - Manage Silenceed server domain patterns
Usage
    bin/console serversilence [-h|--help|-?] [-v]
    bin/console serversilence add <pattern> <reason> [-h|--help|-?] [-v]
    bin/console serversilence remove <pattern> [-h|--help|-?] [-v]

Description
    With this tool, you can list the current silenced server domain patterns
    or you can add / remove a Silenceed server domain pattern from the list.

Options
    -h|--help|-? Show help information
    -v           Show more debug information.
HELP;
	}

	protected function doExecute(): int
	{
		if (count($this->args) == 0) {
			return 0;
		}

		switch ($this->getArgument(0)) {
			case 'add':
				return $this->addSilencedServer();
			case 'remove':
				return $this->removeSilencedServer();
			default:
				throw new CommandArgsException('Unknown command.');
		}
	}


	/**
	 * Silences all contacts with a specific domain.
	 * 
	 * @return int The return code (0 = success, 1 = failed)
	 */
	private function addSilencedServer(): int
	{
		if (count($this->args) != 2) {
			throw new CommandArgsException('Add needs a domain.');
		}
		$domain = $this->getArgument(1);
		Contact::update(['hidden'=> true], ['baseurl' => "https://" . $domain]);
		$this->out('The domain has been successfully silenced from the global community page.');
		return 0;
	}

	/**
	 * Removes silence from all contacts with a specific domain.
	 *
	 * @return int The return code (0 = success, 1 = failed)
	 */
	private function removeSilencedServer(): int
	{
		if (count($this->args) !== 2) {
			throw new CommandArgsException('Remove needs a domain.');
		}
		$domain = $this->getArgument(1);
		Contact::update(['hidden' => false], ['baseurl' => "https://" . $domain]);
		$this->out('The domain has been successfully unsilenced from the global community page.');
		return 0;
	}
}
