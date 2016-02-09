<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Command;

use Symfony\Component\Console\Helper\DialogHelper;
use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ConfirmationQuestion;

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
        /**
         * @var QuestionHelper $question
         */
        $questionHelper = $this->getHelper('question');
                
        $question = new ConfirmationQuestion(
            '<question>Create a directories for environment settings?</question> [Y/n]', 
            true, 
            '/^(y|j)/i'
        );

        if ($questionHelper->ask($input, $output, $question)) {
            $this->createEnvironmentDir($input, $output);
        }

        $question = new ConfirmationQuestion(
            '<question>Create file for run Console Jedi application?</question> [Y/n]',
            true,
            '/^(y|j)/i'
        );

        if ($questionHelper->ask($input, $output, $question)) {
            $this->createApplicationFile($input, $output);
        }
    }
    
    protected function createEnvironmentDir(InputInterface $input, OutputInterface $output)
    {
        
    }
    
    protected function createApplicationFile(InputInterface $input, OutputInterface $output)
    {
        /**
         * @var DialogHelper $dialogHelper
         */
        $dialogHelper = $this->getHelper('dialog');
        
        $path = getcwd() . '/jedi';

        $pathAnswer = $dialogHelper->select(
            $output,
            'Create application file in ' . $path,
            ['Yes', 'Another path', 'Cancel'],
            0
        );

        if ($pathAnswer === '1' || $pathAnswer == 0)
        {
            $path = $dialogHelper->askAndValidate(
                $output,
                'Enter the path to file: ',
                function ($answer) {
                    if (!$answer) {
                        throw new \RuntimeException(
                            'Path "' . $answer . '" is invalid"'
                        );
                    }

                    return $answer;
                }
            );
            
            echo 'CREATED';
        }
    }
}