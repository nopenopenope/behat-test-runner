<?php

declare(strict_types=1);

namespace SEEC\BehatTestRunner\Tests\Services;

use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use SEEC\BehatTestRunner\Context\Services\WorkingDirectoryService;
use SEEC\BehatTestRunner\Context\Services\WorkingDirectoryServiceInterface;
use SEEC\PhpUnit\Helper\ConsecutiveParams;
use Symfony\Component\Filesystem\Filesystem;

final class WorkingDirectoryServiceTest extends TestCase
{
    use ConsecutiveParams;

    private WorkingDirectoryServiceInterface $directoryService;

    /** @var object|MockObject|Filesystem */
    private object $fileSystem;

    public function setUp(): void
    {
        $this->fileSystem = $this->createMock(Filesystem::class);
        $this->directoryService = new WorkingDirectoryService(
            '/var/www/html/test',
            $this->fileSystem
        );
    }

    public function test_it_will_return_the_working_directory_correctly(): void
    {
        $this->directoryService->setWorkingDirectory('bla');
        $this->assertSame('bla', $this->directoryService->getWorkingDirectory());
    }

    public function test_it_will_return_the_default_document_root_correctly(): void
    {
        $this->assertNull($this->directoryService->getDocumentRoot());
    }

    public function test_it_will_return_the_default_feature_directory_correctly(): void
    {
        $this->assertNull($this->directoryService->getFeatureDirectory());
    }

    public function test_it_will_return_correctly_negative_response_if_directory_is_not_initialized_yet(): void
    {
        $this->assertFalse($this->directoryService->isInitialized());
    }

    public function test_it_can_correctly_create_the_working_folders_and_initialize_the_class(): void
    {
        $this->fileSystem->expects($this->exactly(3))
            ->method('mkdir')
            ->with(...self::withConsecutive(
                ['/var/www/html/test', 504],
                ['/var/www/html/test/document_root', 504],
                ['/var/www/html/test/features/bootstrap', 504]
            ));
        $this->fileSystem->expects($this->exactly(3))
            ->method('exists')
            ->with(...self::withConsecutive(
                ['/var/www/html/test'],
                ['/var/www/html/test/document_root'],
                ['/var/www/html/test/features/bootstrap'],
            ))
            ->willReturn(false);

        $this->directoryService->createWorkingDirectory();
    }

    public function test_it_will_use_the_current_working_directory_when_nothing_is_specified(): void
    {
        $service = new WorkingDirectoryService(null, $this->fileSystem);
        $cwd = getcwd();
        $this->fileSystem->expects($this->exactly(3))
            ->method('mkdir')
            ->with(...self::withConsecutive(
                [$cwd, 504],
                [sprintf('%s/document_root', $cwd), 504],
                [sprintf('%s/features/bootstrap', $cwd), 504]
            ));
        $this->fileSystem->expects($this->exactly(3))
            ->method('exists')
            ->with(...self::withConsecutive(
                [$cwd],
                [sprintf('%s/document_root', $cwd)],
                [sprintf('%s/features/bootstrap', $cwd)],
            ))
            ->willReturn(false);

        $service->createWorkingDirectory();
        $service->createWorkingDirectory();
    }

    public function test_it_can_clear_out_the_working_directory(): void
    {
        $this->fileSystem->expects($this->once())
            ->method('remove')
            ->with('/var/www/html/test');

        $this->directoryService->clearWorkingDirectory();
    }

    public function test_it_will_does_not_do_anything_if_workdir_already_exists(): void
    {
        $this->fileSystem->expects($this->exactly(3))
            ->method('exists')
            ->with(...$this->withConsecutive(
                ['/var/www/html/test'],
                ['/var/www/html/test/document_root'],
                ['/var/www/html/test/features/bootstrap'],
            ))
            ->willReturn(true);
        $this->fileSystem->expects($this->never())
            ->method('mkdir');

        $this->directoryService->createWorkingDirectory();
    }
}
