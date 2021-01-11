<?php
declare(strict_types=1);

namespace ListProjectSortingByModified;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Symfony\Component\Finder\Finder;

final class Project
{
    private \SplFileInfo $info;

    private ?CarbonImmutable $modified;

    public function __construct(\SplFileInfo $info)
    {
        if (!$info->isDir()) {
            throw new \InvalidArgumentException('Project must be dir.');
        }
        $this->info = $info;
    }

    public static function fromPath(string $dirPath): self
    {
        $info = new \SplFileInfo($dirPath);
        return new self($info);
    }

    public function getInfo(): \SplFileInfo
    {
       return $this->info;
    }

    public function getModified($cache = true): ?CarbonInterface
    {
        if ($cache && isset($this->modified)) {
            return $this->modified;
        }

        if (!$this->isGit()) {
            return $this->getModifiedWithoutGit();
        }

        $modified = $this->getUncommitLastModified();
        if (!is_null($modified)) {
            return $modified;
        }
        return $modified = $this->getLastCommitModified();
    }

    private function isGit(): bool
    {
        $out = $this->git("status 2>/dev/null");
        return !is_null($out);
    }

    private function getModifiedWithoutGit(): ?CarbonImmutable
    {
        $finder = new Finder();
        $finder
            ->in($this->info->getRealPath())
            ->sortByModifiedTime();
        /** @var \SplFileInfo $fileInfo */
        $fileInfo = $finder->getIterator()->current();
        if (empty($fileInfo)) {
            return null;
        }

        $modified = $fileInfo->getMTime();
        return CarbonImmutable::createFromTimestamp($modified);
    }

    /**
     * @return null|CarbonInterface last author date
     */
    private function getUncommitLastModified(): ?CarbonInterface
    {
        // ls options
        // -p ディレクトリの末尾に/を付ける
        // -d ディレクトリを再帰的に検索しない
        // -t 更新日でソート(新しい順)
        $out = $this->git(<<<EOF
          ls-files --exclude-standard --other --modified \
            | command ls -pd | grep -v '/$' \
            | head -n 1
        EOF
        );
        if (is_null($out)) {
            return null;
        }
        $out = trim($out);
        if (empty($out)) {
            return null;
        }

        $out = $this->git('log -1 --pretty=%ad --date=short');
        return new CarbonImmutable(trim($out));
    }

    /**
     * git initされていてもコミットがまだの場合を考慮して、戻り値がnullable。
     */
    private function getLastCommitModified(): ?CarbonImmutable
    {
        $out = $this->git('log -1 --pretty=%ad --date=short');
        if (is_null($out)) {
            return null;
        }
        return new CarbonImmutable(trim($out));
    }

    private function git($command): ?string
    {
        return shell_exec("git -C {$this->info->getRealPath()} $command");
    }
}

