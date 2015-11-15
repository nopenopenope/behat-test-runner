<?php

namespace spec\Bex\Behat\Context;

use Behat\Gherkin\Node\PyStringNode;
use Bex\Behat\Context\Services\ProcessFactory;
use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;

/**
 * Unit test of TestRunnerContext
 *
 * @package spec\Bex\Behat\Context
 *
 * @author Geza Buza <bghome@gmail.com>
 * @license http://opensource.org/licenses/MIT The MIT License
 */
class TestRunnerContextSpec extends ObjectBehavior
{
    function let(Filesystem $filesystem, ProcessFactory $processFactory)
    {
        $this->beConstructedWith('bin/phantomjs', $filesystem, $processFactory);
        $this->initFilesystemDouble($filesystem);

        defined('BEHAT_BIN_PATH') or define('BEHAT_BIN_PATH', 'bin/behat');
    }

    function it_is_initializable()
    {
        $this->shouldHaveType('Bex\Behat\Context\TestRunnerContext');
    }

    function it_creates_temporary_working_directory(Filesystem $filesystem)
    {
        $tempDir = sys_get_temp_dir() .'/behat-test-runner';

        $filesystem->mkdir(Argument::containingString($tempDir), 0770)->shouldBeCalled();

        $this->createWorkingDirectory();
    }

    function it_removes_temporary_working_directory(Filesystem $filesystem)
    {
        $tempDir = sys_get_temp_dir() .'/behat-test-runner';

        $filesystem->remove(Argument::containingString($tempDir))->shouldBeCalled();

        $this->createWorkingDirectory();
        $this->clearWorkingDirectory();
    }

    function it_saves_behat_configuration(PyStringNode $config, Filesystem $filesystem)
    {
        $config->getRaw()->willReturn('default:');
        $filesystem->dumpFile(Argument::containingString('/behat.yml'), 'default:')->shouldBeCalled();

        $this->createWorkingDirectory();
        $this->iHaveTheConfiguration($config);
    }

    function it_saves_behat_feature_file(PyStringNode $config, Filesystem $filesystem)
    {
        $config->getRaw()->willReturn('Feature: test');
        $filesystem->dumpFile(
            Argument::containingString('/features/feature.feature'), 'Feature: test'
        )->shouldBeCalled();

        $this->createWorkingDirectory();
        $this->iHaveTheFeature($config);
    }

    function it_saves_files_in_document_root_directory(PyStringNode $content, Filesystem $filesystem)
    {
        $filesystem->dumpFile(Argument::containingString('index.html'), $content)->shouldBeCalled();
        $filesystem->dumpFile(Argument::containingString('/document_root'), $content)->shouldBeCalled();

        $this->createWorkingDirectory();
        $this->iHaveTheFileInDocumentRoot('index.html', $content);
    }

    function it_runs_behat_in_working_directory(ProcessFactory $processFactory, Process $process)
    {
        $processFactory->createBehatProcess(
            Argument::containingString(sys_get_temp_dir() .'/behat-test-runner'),
            Argument::any()
        )->shouldBeCalled()->willReturn($process);
        $process->run()->shouldBeCalled();

        $this->createWorkingDirectory();
        $this->iRunBehat();
    }

    function it_starts_the_webserver_and_the_browser(
        ProcessFactory $processFactory,
        Process $webServerProcess,
        Process $browserProcess
    ) {
        $processFactory->createWebServerProcess(
            Argument::containingString('/document_root'), 'localhost', '8080'
        )->shouldBeCalled()->willReturn($webServerProcess);

        $processFactory->createBrowserProcess(
            Argument::containingString('bin/phantomjs'),
            Argument::containingString(sys_get_temp_dir() .'/behat-test-runner')
        )->shouldBeCalled()->willReturn($browserProcess);

        $webServerProcess->start()->shouldBeCalled();
        $browserProcess->start()->shouldBeCalled();

        $this->createWorkingDirectory();
        $this->iHaveAWebServerRunningOnAddressAndPort('localhost', '8080');
    }

    function it_can_detect_when_there_was_no_failing_tests_but_expected(
        ProcessFactory $processFactory,
        Process $process
    ) {
        $processFactory->createBehatProcess(Argument::any(), Argument::any())->willReturn($process);
        $this->createWorkingDirectory();
        $this->iRunBehat();

        $process->getExitCode()->willReturn(0);
        $this->shouldThrow(
            new \RuntimeException('Behat did not find any failing scenario.')
        )->duringIShouldSeeAFailingTest();

        $process->getExitCode()->willReturn(255);
        $this->shouldNotThrow('\RuntimeException')->duringIShouldSeeAFailingTest();
    }

    function it_does_not_run_browser_when_broser_binary_is_not_set(
        Filesystem $filesystem,
        ProcessFactory $processFactory,
        Process $webServerProcess,
        Process $browserProcess
    ) {
        $processFactory->createWebServerProcess(
            Argument::any(),
            Argument::any(),
            Argument::any()
        )->willReturn($webServerProcess);

        $processFactory->createBrowserProcess(
            Argument::any(),
            Argument::any()
        )->willReturn($browserProcess);

        $this->beConstructedWith(null, $filesystem, $processFactory);

        $webServerProcess->start()->shouldBeCalled();
        $browserProcess->start()->shouldNotBeCalled();

        $this->createWorkingDirectory();
        $this->iHaveAWebServerRunningOnAddressAndPort(Argument::any(), Argument::any());
    }

    private function initFilesystemDouble($filesystem)
    {
        $filesystem->remove(Argument::type('string'))->willReturn(null);
        $filesystem->mkdir(Argument::type('string'), Argument::type('int'))->willReturn(null);
    }
}
