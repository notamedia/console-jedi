<?php
/**
 * For the full copyright and license information, please view the LICENSE.md
 * file that was distributed with this source code.
 */

namespace Notamedia\ConsoleJedi\Console\Command;

use Symfony\Component\Console\Helper\QuestionHelper;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Filesystem\Filesystem;

class InitCommand extends Command
{
    protected $tmplDir = __DIR__ . '/../../../tmpl';
    
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this->setName('init')
            ->setDescription('Initialize the Console Jedi')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Override an existing files');
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->createEnvironmentsDir($input, $output);
        $this->createApplicationFile($input, $output);
    }
    
    protected function createEnvironmentsDir(InputInterface $input, OutputInterface $output)
    {
        $targetDir = getcwd() . '/environments2';
        $tmplDir = $this->tmplDir . '/environments';
        
        /**
         * @var QuestionHelper $question
         */
        $questionHelper = $this->getHelper('question');
        $question = new Question(
            'Enter path for creating directories for the environment settings:' . PHP_EOL
            . '  Default path: <info>' . $targetDir . '</info>' . PHP_EOL,
            $targetDir
        );
        $question->setValidator(function ($answer) use ($input) {
            if (is_dir($answer) && !$input->getOption('force')) {
                throw new \RuntimeException(
                    'Directory "' . $answer . '" already exist'
                );
            }
            return $answer;
        });

        $targetDir = $questionHelper->ask($input, $output, $question);
        
        $fs = new Filesystem();
        $tmplIterator = new \RecursiveDirectoryIterator($tmplDir, \RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new \RecursiveIteratorIterator($tmplIterator, \RecursiveIteratorIterator::SELF_FIRST);

        foreach ($iterator as $item)
        {
            $itemPath = $targetDir . '/' . $iterator->getSubPathName();

            if ($item->isDir())
            {
                $fs->mkdir($itemPath);
            }
            else
            {
                $fs->copy($item, $itemPath, true);
            }
        }
    }
    
    protected function createApplicationFile(InputInterface $input, OutputInterface $output)
    {
        $path = $this->getApplication()->getRoot() . '/jedi';

        /**
         * @var QuestionHelper $question
         */
        $questionHelper = $this->getHelper('question');
        $question = new Question(
            'Enter path for creating file for run Console Jedi application:' . PHP_EOL
                . '  Default path: <info>' . $path . '</info>' . PHP_EOL, 
            $path
        );
        $question->setValidator(function ($answer) use ($input) {
            if (file_exists($answer) && !$input->getOption('force')) {
                throw new \RuntimeException(
                    'File "' . $answer . '" already exist'
                );
            }
            return $answer;
        });

        $path = $questionHelper->ask($input, $output, $question);

        $fs = new Filesystem();

        $content = file_get_contents($this->tmplDir . '/jedi');
        $content = str_replace(
            [
                '%vendor-dir%',
                '%web-dir%'
            ],
            [
                
            ], 
            $content
        );
        
        $fs->dumpFile($path, $content);
    }
}