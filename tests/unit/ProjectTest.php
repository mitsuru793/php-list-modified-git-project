<?php
declare(strict_types=1);

namespace UnitTest;

use ListProjectSortingByModified\Project;
use Mitsuru793\FileConstructor\FileConstructor;
use Symfony\Component\Filesystem\Filesystem;
use TestHelper\TestCase;

class ProjectTest extends TestCase
{
    private FileConstructor $fc;

    public function setUp(): void
    {
        parent::setUp();
        $this->fc = FileConstructor::inTempDir();
    }

    public function tearDown(): void
    {
        parent::tearDown();
        $fs = new Filesystem();
        $fs->remove($this->fc->root());
    }

    public function testConstructor()
    {
        $this->fc->append([
            'dir' => [],
            'file' => '',
        ]);

        $info = new \SplFileInfo($this->fc->root() . '/dir');
        $got = new Project($info);
        $this->assertInstanceOf(Project::class, $got);

        $this->expectException(\InvalidArgumentException::class);
        $info = new \SplFileInfo($this->fc->root() . '/file');
        new Project($info);
    }

    /**
     * @todo gitリポジトリのテスト
     */
    public function testGetModified()
    {
        $fs = new Filesystem();
        $this->fc->append([
            'plain-empty' => [],
            'git-empty' => [
                '.git' => [],
            ],
            'plain' => [
                'f1' => '',
                'f2' => '',
                'f3' => '',
            ],
            'git' => [
                'f1' => '',
                'f2' => '',
            ]
        ]);

        $p = Project::fromPath($this->fc->root() . '/plain-empty');
        $this->assertNull($p->getModified());

        $p = Project::fromPath($this->fc->root() . '/git-empty');
        $this->assertNull($p->getModified());

        $root = $this->fc->root();
        $fs->touch($root . '/plain/f2', time() - 1000);
        $fs->touch($root . '/plain/f1', time() - 2000);
        $fs->touch($root . '/plain/f3', $f3Time = time() - 3000);
        $p = Project::fromPath($this->fc->root() . '/plain');
        $this->assertSame($f3Time, $p->getModified()->getTimestamp());
    }
}
