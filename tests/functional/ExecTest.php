<?php
namespace Robo;

use PHPUnit\Framework\TestCase;
use Robo\Traits\TestTasksTrait;

class ExecTest extends TestCase
{
    use TestTasksTrait;
    use Task\Base\loadTasks;

    public function setup()
    {
        $this->initTestTasksTrait();
    }

    public function testExecLsCommand()
    {
        $command = strncasecmp(PHP_OS, 'WIN', 3) == 0 ? 'dir' : 'ls';
        $res = $this->taskExec($command)->interactive(false)->run();
        $this->assertTrue($res->wasSuccessful());
        $this->assertContains(
            'src',
            $res->getMessage());
        $this->assertContains(
            'codeception.yml',
            $res->getMessage());
    }

    public function testMultipleEnvVars()
    {
        $task = $this->taskExec('env')->interactive(false);
        $task->env('FOO', 'BAR');
        $task->env('BAR', 'BAZ');
        $result = $task->run();
        $this->assertTrue($result->wasSuccessful());
        // Verify that the text contains our environment variable.
        $this->assertContains(
            'FOO=BAR',
            $result->getMessage());
        $this->assertContains(
            'BAR=BAZ',
            $result->getMessage());

        // Now verify that we can reset a value that was previously set.
        $task = $this->taskExec('env')->interactive(false);
        $task->env('FOO', 'BAR');
        $task->env('FOO', 'BAZ');
        $result = $task->run();
        $this->assertTrue($result->wasSuccessful());
        // Verify that the text contains the most recent environment variable.
        $this->assertContains(
            'FOO=BAZ',
            $result->getMessage());
    }

    public function testInheritEnv()
    {
        // Symfony < 3.2.1 does not inherit environment variables, so there's
        // nothing to test if the function doesn't exist.
        if (!method_exists('Symfony\Component\Process\Process', 'inheritEnvironmentVariables')) {
            throw new \PHPUnit_Framework_SkippedTestError(
                'Inheriting of environment variables is not supported.'
            );
        }
        // With no environment variables set, count how many environment
        // variables are present.
        $task = $this->taskExec('env | wc -l')->interactive(false);
        $result = $task->run();
        $this->assertTrue($result->wasSuccessful());
        $start_count = (int) $result->getMessage();
        $this->assertGreaterThan(0, $start_count);

        // Verify that we get the same amount of environment variables with
        // another exec call.
        $task = $this->taskExec('env | wc -l')->interactive(false);
        $result = $task->run();
        $this->assertTrue($result->wasSuccessful());
        $this->assertEquals(
            $start_count,
            (int) $result->getMessage());

        // Now run the same command, but this time add another environment
        // variable, and see if our count increases by one.
        $task = $this->taskExec('env | wc -l')->interactive(false);
        $task->env('FOO', 'BAR');
        $result = $task->run();
        $this->assertTrue($result->wasSuccessful());
        $this->assertEquals($start_count + 1, (int) $result->getMessage());
    }
}
