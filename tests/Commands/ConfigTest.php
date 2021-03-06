<?php


namespace TheCodingMachine\WashingMachine\Commands;


use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Input\InputDefinition;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\StringInput;

class ConfigTest extends \PHPUnit_Framework_TestCase
{
    private function getInputDefinition()
    {
        return new InputDefinition([
            new InputOption('clover',
                'c',
                InputOption::VALUE_REQUIRED,
                'The path to the clover file generated by PHPUnit.',
                'clover.xml'),
            new InputOption('gitlab-api-token',
                't',
                InputOption::VALUE_REQUIRED,
                'The Gitlab API token. If not specified, it is fetched from the GITLAB_API_TOKEN environment variable.',
                null),
            new InputOption('gitlab-url',
                'u',
                InputOption::VALUE_REQUIRED,
                'The Gitlab URL. If not specified, it is deduced from the CI_BUILD_REPO environment variable.',
                null),
            new InputOption('gitlab-project-name',
                'p',
                InputOption::VALUE_REQUIRED,
                'The Gitlab project name (in the form "group/name"). If not specified, it is deduced from the CI_PROJECT_DIR environment variable.',
                null),
            new InputOption('commit-sha',
                'r',
                InputOption::VALUE_REQUIRED,
                'The commit SHA. If not specified, it is deduced from the CI_COMMIT_SHA environment variable.',
                null),
            new InputOption('gitlab-job-id',
                'b',
                InputOption::VALUE_REQUIRED,
                'The Gitlab CI build id. If not specified, it is deduced from the CI_BUILD_ID environment variable.',
                null),
            new InputOption('file',
                'f',
                InputOption::VALUE_IS_ARRAY | InputOption::VALUE_REQUIRED,
                'Text file to be sent in the merge request comments (can be used multiple times).',
                []),
            new InputOption('open-issue',
                'i',
                InputOption::VALUE_NONE,
                'Opens an issue (if the build is not part of a merge request)')
        ]);
    }

    public function testCloverTest()
    {
        $input = new ArrayInput(array('--clover' => 'clovertest.xml'), $this->getInputDefinition());

        $config = new Config($input);
        $this->assertSame('clovertest.xml', $config->getCloverFilePath());
    }

    public function testGitlabApiTokenFromEnv()
    {
        putenv('GITLAB_API_TOKEN=DEADBEEF');
        $input = new ArrayInput([], $this->getInputDefinition());
        $config = new Config($input);
        $this->assertSame('DEADBEEF', $config->getGitlabApiToken());
    }

    public function testGitlabApiTokenFromParam()
    {
        $input = new ArrayInput(array('--gitlab-api-token' => '1234'), $this->getInputDefinition());

        $config = new Config($input);
        $this->assertSame('1234', $config->getGitlabApiToken());
    }

    public function testNoGitlabApiToken()
    {
        putenv('GITLAB_API_TOKEN');
        $input = new ArrayInput([], $this->getInputDefinition());

        $config = new Config($input);
        $this->expectException(\RuntimeException::class);
        $config->getGitlabApiToken();
    }

    public function testGitlabUrlFromEnv()
    {
        putenv('CI_BUILD_REPO=http://gitlab-ci-token:xxxxxx@git.example.com/mouf/test.git');
        $input = new ArrayInput([], $this->getInputDefinition());
        $config = new Config($input);
        $this->assertSame('http://git.example.com', $config->getGitlabUrl());
    }

    public function testGitlabUrlFromParam()
    {
        $input = new ArrayInput(array('--gitlab-url' => 'http://git.example2.com/'), $this->getInputDefinition());

        $config = new Config($input);
        $this->assertSame('http://git.example2.com', $config->getGitlabUrl());
    }

    public function testGitlabApiUrlFromParam()
    {
        $input = new ArrayInput(array('--gitlab-url' => 'http://git.example2.com/'), $this->getInputDefinition());

        $config = new Config($input);
        $this->assertSame('http://git.example2.com/api/v3/', $config->getGitlabApiUrl());
    }

    public function testNoGitlabUrl()
    {
        putenv('CI_BUILD_REPO');
        $input = new ArrayInput([], $this->getInputDefinition());

        $config = new Config($input);
        $this->expectException(\RuntimeException::class);
        $config->getGitlabUrl();
    }

    public function testGitlabProjectNameFromEnv()
    {
        putenv('CI_PROJECT_DIR=/builds/foo/bar');
        $input = new ArrayInput([], $this->getInputDefinition());
        $config = new Config($input);
        $this->assertSame('foo/bar', $config->getGitlabProjectName());
    }

    public function testGitlabProjectNameFromParam()
    {
        $input = new ArrayInput(array('--gitlab-project-name' => 'foo/bar'), $this->getInputDefinition());

        $config = new Config($input);
        $this->assertSame('foo/bar', $config->getGitlabProjectName());
    }

    public function testNoGitlabProjectName()
    {
        putenv('CI_PROJECT_DIR');
        $input = new ArrayInput([], $this->getInputDefinition());

        $config = new Config($input);
        $this->expectException(\RuntimeException::class);
        $config->getGitlabProjectName();
    }

    public function testGitlabBuildRefFromEnv()
    {
        putenv('CI_BUILD_REF=DEADBEEFDEADBEEF');
        $input = new ArrayInput([], $this->getInputDefinition());
        $config = new Config($input);
        $this->assertSame('DEADBEEFDEADBEEF', $config->getCommitSha());
    }

    public function testGitlab9BuildRefFromEnv()
    {
        putenv('CI_BUILD_REF');
        putenv('CI_COMMIT_SHA=DEADBEEFDEADBEEF');
        $input = new ArrayInput([], $this->getInputDefinition());
        $config = new Config($input);
        $this->assertSame('DEADBEEFDEADBEEF', $config->getCommitSha());
    }

    public function testGitlabBuildRefFromParam()
    {
        putenv('CI_BUILD_REF');
        putenv('CI_COMMIT_SHA');
        $input = new ArrayInput(array('--commit-sha' => 'DEADBEEFDEADBEEF2'), $this->getInputDefinition());

        $config = new Config($input);
        $this->assertSame('DEADBEEFDEADBEEF2', $config->getCommitSha());
    }

    public function testNoGitlabBuildRef()
    {
        putenv('CI_BUILD_REF');
        putenv('CI_COMMIT_SHA');
        $input = new ArrayInput([], $this->getInputDefinition());

        $config = new Config($input);
        $this->expectException(\RuntimeException::class);
        $config->getCommitSha();
    }

    public function testGitlabBuildIdFromEnv()
    {
        putenv('CI_BUILD_ID=42');
        $input = new ArrayInput([], $this->getInputDefinition());
        $config = new Config($input);
        $this->assertSame(42, $config->getGitlabBuildId());
    }

    public function testGitlabJobIdFromEnv()
    {
        putenv('CI_BUILD_ID');
        putenv('CI_JOB_ID=42');
        $input = new ArrayInput([], $this->getInputDefinition());
        $config = new Config($input);
        $this->assertSame(42, $config->getGitlabBuildId());
    }

    public function testGitlabBuildIdFromParam()
    {
        putenv('CI_BUILD_ID');
        putenv('CI_JOB_ID');

        $input = new ArrayInput(array('--gitlab-job-id' => '42'), $this->getInputDefinition());

        $config = new Config($input);
        $this->assertSame(42, $config->getGitlabBuildId());
    }

    public function testNoGitlabBuildId()
    {
        putenv('CI_BUILD_ID');
        $input = new ArrayInput([], $this->getInputDefinition());

        $config = new Config($input);
        $this->expectException(\RuntimeException::class);
        $config->getGitlabBuildId();
    }


    public function testFile()
    {
        $input = new ArrayInput(array('--file' => ['foo.txt']), $this->getInputDefinition());

        $config = new Config($input);
        $this->assertSame(['foo.txt'], $config->getFiles());
    }

    public function testOpenIssueTrue()
    {
        $input = new ArrayInput(array('--open-issue' => true), $this->getInputDefinition());

        $config = new Config($input);
        $this->assertTrue($config->isOpenIssue());
    }

    public function testOpenIssueFalse()
    {
        $input = new ArrayInput(array(), $this->getInputDefinition());

        $config = new Config($input);
        $this->assertFalse($config->isOpenIssue());
    }
}
