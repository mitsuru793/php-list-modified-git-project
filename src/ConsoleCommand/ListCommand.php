<?php
declare(strict_types=1);

namespace ListProjectSortingByModified\ConsoleCommand;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use ListProjectSortingByModified\Project;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Finder\Finder;

final class ListCommand extends Command
{
    protected static $defaultName = 'list';

    protected function configure()
    {
        $this
            ->setDescription('list projects sorting by modified.')
            ->addArgument('path', InputArgument::REQUIRED, 'Root path has some projects.');
    }

    public function execute(InputInterface $input, OutputInterface $output)
    {
        $rank = 1;
        $finder = new Finder();
        $dirs = $finder->in($input->getArgument('path'))->depth(0)->directories();
        collect($dirs)
            ->map(fn(string $dir) => Project::fromPath($dir))
            ->sort(function (Project $a, Project $b) use (&$modifiedCache) {
                $modifiedA = $a->getModified() ?? CarbonImmutable::minValue();
                $modifiedB = $b->getModified() ?? CarbonImmutable::minValue();

                if ($modifiedA->greaterThan($modifiedB)) return 1;
                if ($modifiedA->equalTo($modifiedB)) return 0;
                return -1;
            })
            ->map(function (Project $p) use (&$rank) {
                return sprintf('%s %s %s',
                    $rank++,
                    $p->getInfo()->getFilename(),
                    $p->getModified()->toDateString()
                );
            })
            ->pipe(function (Collection $lines) use ($output) {
                $output->writeln($lines);
            })
        ;

        return Command::SUCCESS;
    }
}

