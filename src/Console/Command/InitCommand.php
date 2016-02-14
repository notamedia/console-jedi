<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Console\Command;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;

class InitCommand extends Command
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('init')
            ->setDescription('Initialize the Console Jedi');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->createEnvironmentDir($input, $output);
        $this->createApplicationFile($input, $output);
    }
    
    protected function createEnvironmentDir(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var QuestionHelper $question
         */
        $questionHelper = $this->getHelper('question');

        $question = new ConfirmationQuestion(
            'Create a directories for environment settings? [Y/n]',
            true,
            '/^(y|j)/i'
        );

        if ($questionHelper->ask($input, $output, $question))
        {
            
        }
    }
    
    protected function createApplicationFile(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var QuestionHelper $question
         */
        $questionHelper = $this->getHelper('question');
        
        $path = $this->getApplication()->getRoot() . '/jedi';
        $question = new Question('Enter path for creating file for run Console Jedi application::' . PHP_EOL
            . '  Default path: <info>' . $path . '</info>' . PHP_EOL, $path);

        $path = $questionHelper->ask($input, $output, $question);
        echo $path;
    }
}