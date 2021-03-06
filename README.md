[![Latest Stable Version](https://poser.pugx.org/thecodingmachine/washingmachine/v/stable)](https://packagist.org/packages/thecodingmachine/washingmachine)
[![Total Downloads](https://poser.pugx.org/thecodingmachine/washingmachine/downloads)](https://packagist.org/packages/thecodingmachine/washingmachine)
[![Latest Unstable Version](https://poser.pugx.org/thecodingmachine/washingmachine/v/unstable)](https://packagist.org/packages/thecodingmachine/washingmachine)
[![License](https://poser.pugx.org/thecodingmachine/washingmachine/license)](https://packagist.org/packages/thecodingmachine/washingmachine)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/thecodingmachine/washingmachine/badges/quality-score.png?b=1.2)](https://scrutinizer-ci.com/g/thecodingmachine/washingmachine/?branch=1.2)
[![Build Status](https://travis-ci.org/thecodingmachine/washingmachine.svg?branch=1.2)](https://travis-ci.org/thecodingmachine/washingmachine)
[![Coverage Status](https://coveralls.io/repos/thecodingmachine/washingmachine/badge.svg?branch=1.2&service=github)](https://coveralls.io/github/thecodingmachine/washingmachine?branch=1.2)


# Washing machine

The *washing machine* is a tool that helps you writing cleaner code by integrating PHPUnit with Gitlab CI.

As a result, when you perform a merge request in Gitlab, the washing machine will add meaningful information about your code quality.

## Usage

### Enable Gitlab CI for your project

First, you need a Gitlab project with continuous integration enabled (so a project with a `.gitlab-ci.yml` file).

### Create a personal access token

Then, you need a [Gitlab API personal access token](https://docs.gitlab.com/ce/api/README.html#personal-access-tokens).

Got it?

### Add a secret variable

Now, we need to add this token as a "secret variable" of your project (so the CI script can modify the merge request comments):

Go to your project page in Gitlab: **Settings ➔ Variables ➔ Add variable**

- Key: `GITLAB_API_TOKEN`
- Value: the token you just received in previous step

### Configure PHPUnit to dump a "clover" test file


Let's configure PHPUnit. Go to your `phpunit.xml.dist` file and add:

```
<phpunit>
    <logging>
        <log type="coverage-clover" target="clover.xml"/>
    </logging>
</phpunit>
```

Note: the "clover.xml" file must be written at the root of your GIT repository, so if your `phpunit.xml.dist` sits in a subdirectory, the correct path will be something like "../../clover.xml".

Alternatively, washing-machine also knows how to read Crap4J files. Crap4J files contain Crap score, but not code coverage score so you will get slightly less data from Crap4J. The expected file name is "crap4j.xml".

### Configure Gitlab CI yml file

Now, we need to install the *washingmachine*, and get it to run.

`.gitlab-ci.yml`
```
before_script:
 - composer global require 'thecodingmachine/washingmachine'
 
script:
 - phpdbg -qrr vendor/bin/phpunit
 
after_script:
 - /root/.composer/vendor/bin/washingmachine run -v
```

Notice that we need to make sure the PHPDbg extension for PHP is installed. Also, make sure that Xdebug is NOT enabled on your Docker instance. Xdebug can also return code coverage data but is less accurate than PHPDbg, leading to wrong CRAP score results.

### Adding extra data in the comment

When the *washingmachine* adds a comment in your merge-request, you can ask it to add additional text.
This text must be stored in a file.

You simply do:

```
washingmachine run -f file_to_added_to_comments.txt
```

Of course, this file might be the output of a CI tool.

The *washingmachine* will only display the first 50 lines of the file. If the file is longer, a link to download the file is added at the end of the comment.

You can also add several files by repeating the "-f" option:

```
washingmachine run -f file1.txt -f file2.txt
```

### Opening an issue

When a merge request is open, the *washingmachine* will post comments directly in the merge request.

If no merge request exists, the *washingmachine* can open an issue in your Gitlab project.

To open an issue, use the `--open-issue` option:

```
washingmachine run --open-issue
```

Tip: you typically want to add the `--open-issue` tag conditionally if a build fails. Also, the `--open-issue` is ignored if a merge request matches the build.
