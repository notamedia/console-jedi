<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Module\Command;

use Bitrix\Main\NotImplementedException;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Notamedia\ConsoleJedi\Module\Exception\ModuleException;

/**
 * Command for module installation/register
 *
 * @author Marat Shamshutdinov <m.shamshutdinov@gmail.com>
 */
class UpdateCommand extends ModuleCommand
{
	/**
	 * {@inheritdoc}
	 */
	protected function configure()
	{
		$this->setName('module:update')
			->setDescription('Load module updates from Marketplace or SiteUpdate');

		parent::configure();
	}

	/**
	 * {@inheritdoc}
	 */
	protected function execute(InputInterface $input, OutputInterface $output)
	{
		try
		{
			// @todo Implement module:update
			throw new NotImplementedException('module:update is not implemented');
		}
		catch (ModuleException $e)
		{
			$output->writeln('<error>' . $e->getMessage() . '</error>');

			return 1;
		}
	}
}