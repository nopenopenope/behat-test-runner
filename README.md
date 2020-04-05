Behat Test Runner
=========================
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/elvetemedve/behat-test-runner/badges/quality-score.png?b=master)](https://scrutinizer-ci.com/g/elvetemedve/behat-test-runner/?branch=master)
[![Build Status](https://scrutinizer-ci.com/g/elvetemedve/behat-test-runner/badges/build.png?b=master)](https://scrutinizer-ci.com/g/elvetemedve/behat-test-runner/build-status/master)
[![Build Status](https://travis-ci.org/elvetemedve/behat-test-runner.svg?branch=master)](https://travis-ci.org/elvetemedve/behat-test-runner)

Behat Test Runner is essentially a Behat context class which provides steps for testing a Behat extension.
You can put together a feature file and behat.yml configuration, than the test runner will start a second
Behat process to evaluate the created feature file.

Installation
------------

Install by adding to your `composer.json`:

```bash
composer require --dev bex/behat-test-runner
```

Configuration
-------------

Include the context file in `behat.yml` like this:

```yml
default:
  suites:
    default:
      contexts:
        - Bex\Behat\Context\TestRunnerContext
```

You can configure the test web browser to be used for opening the pages, like this:
```yml
default:
  suites:
    default:
      contexts:
        - Bex\Behat\Context\TestRunnerContext:
            browserCommand: %paths.base%/bin/phantomjs --webdriver=4444
```

You can configure the working directory like this:
```yml
default:
  suites:
    default:
      contexts:
        - Bex\Behat\Context\TestRunnerContext:
            workingDirectory: path/to/your/working/dir # default: /tmp/behat-test-runner
```

Usage
-----

Simply use the necessary steps from the context file to put together your feature.

An example:
```feature
Feature: Visiting a page on the website
    In order to demonstrate how to use test runner
    As a developer
    I should open a page and verify the content of it

    Scenario: Visiting the index.html page
        Given I have the file "index.html" in document root:
            """
            <!DOCTYPE html>
            <html>
              <head>
                  <meta charset="UTF-8">
                  <title>Test page</title>
              </head>
              <body>
                  <h1>Lorem ipsum dolor amet.</h1>
              </body>
            </html>
            """
        And I have a web server running on host "localhost" and port "8080"
        And I have the feature:
            """
            Feature: Test runner demo feature
                Scenario:
                    Given I open the index page
                    Then I should see the content "Lorem ipsum" on the page
            """
        And I have the context:
            """
            <?php
            use Behat\MinkExtension\Context\RawMinkContext;
            class FeatureContext extends RawMinkContext
            {
                /**
                 * @Given I open the index page
                 */
                function firstStep()
                {
                    $this->visitPath('index.html');
                }
                /**
                 * @Then I should see the content :content on the page
                 */
                function secondStep($content)
                {
                   $this->getMink()->assertElementContains('h1', $content);
                }
            }
            """
        When I run Behat
        Then I should not see a failing test
```

