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
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Filesystem\Filesystem;

class InitCommand extends Command
{
    protected $tmplDir = __DIR__ . '/../../../tmpl';
    /**
     * @var QuestionHelper $question
     */
    protected $questionHelper;

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
    protected function initialize(InputInterface $input, OutputInterface $output)
    {
        $this->questionHelper = $this->getHelper('question');
        
        parent::initialize($input, $output);
    }
    
    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('<comment>This command must be run from root directory of project</comment>');
        
        $question = new ConfirmationQuestion(
            '' . getcwd() . ' - is root directory of project? [Y/n] ',
            true,
            '/^(y|j)/i'
        );

        if (!$this->questionHelper->ask($input, $output, $question))
        {
            $output->writeln('<info>Run this command from root directory of project</info>');
            return;
        }
        
        $output->writeln('<info>Install Console Jedi application</info>');
        
        $this->createEnvironmentsDir($input, $output);
        $this->createApplicationFile($input, $output);
    }
    
    protected function createEnvironmentsDir(InputInterface $input, OutputInterface $output)
    {
        $targetDir = getcwd() . '/environments';
        $tmplDir = $this->tmplDir . '/environments';
        
        $output->writeln('  - Environment settings');
        
        if (file_exists($targetDir))
        {
            $question = new ConfirmationQuestion(
                '    <error>Directory ' . $targetDir . ' already exists</error>' . PHP_EOL 
                . '    <info>Overwrite? [Y/n]</info> ',
                true,
                '/^(y|j)/i'
            );
            
            if (!$this->questionHelper->ask($input, $output, $question))
            {
                return;
            }
        }
                
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

        $output->writeln('  - Application');

        if (file_exists($path))
        {
            $question = new ConfirmationQuestion(
                '    <error>File ' . $path . ' already exists</error>' . PHP_EOL
                . '    <info>Overwrite? [Y/n]</info> ',
                true,
                '/^(y|j)/i'
            );

            if (!$this->questionHelper->ask($input, $output, $question))
            {
                return;
            }
        }
        
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