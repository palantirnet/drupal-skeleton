<?php

use Drupal\Drupal;
use Drupal\DrupalExtension\Context\DrupalContext;
use Drupal\Component\Utility\Random;
use Drupal\DrupalExtension\Event\EntityEvent;
use Behat\Behat\Context\ClosuredContextInterface,
  Behat\Behat\Context\TranslatedContextInterface,
  Behat\Behat\Context\BehatContext,
  Behat\Behat\Context\Step\Given,
  Behat\Behat\Context\Step\Then,
  Behat\Behat\Context\Step\When,
  Behat\Behat\Event\ScenarioEvent,
  Behat\Behat\Exception\PendingException;
use Behat\Gherkin\Node\PyStringNode,
  Behat\Gherkin\Node\TableNode;

/**
 * Features context.
 */
class FeatureContext extends DrupalContext
{

    public $dispatcher;

    /**
     * Initializes context.
     *
     * @param array $parameters
     *   context parameters (set them up through behat.yml)
     */
    public function __construct(array $parameters) {
        // Tell DrupalContext about our subcontexts.
        $this->useContext('gateway', new GatewayContext());
        $this->useContext('migrate', new MigrationSubContext());
        $this->useContext('content', new TrpdcContentContext());
        $this->useContext('user', new TrpdcUserContext());
    }

    /**
     * Asserts that a given module exists and is enabled.
     *
     * @Given /^the "([^"]*)" module is installed$/
     *
     * @param string $module
     *   The name of the module that is being checked.
     *
     * @throws \Exception if the module is not available and enabled.
     *
     * @return bool
     *   Returns TRUE is the module is installed.
     */
    public function assertModuleExists($module)
    {
        if (module_exists($module)) {
            return TRUE;
        }
        $message = sprintf('Module "%s" is not installed.', $module);
        throw new \Exception($message);
    }

    /**
     * Asserts that a given theme exists.
     *
     * @Given /^the "([^"]*)" theme exists$/
     *
     * @param string $theme
     *   The name of the module that is being checked.
     *
     * @throws \Exception if the theme is not available and enabled.
     *
     * @return bool
     *   Returns TRUE is the theme is installed.
     */
    public function assertThemeExists($theme)
    {
        if (drupal_get_path('theme', $theme)) {
            return TRUE;
        }
        $message = sprintf('Theme "%s" does not exist.', $theme);
        throw new \Exception($message);
    }

    /**
     * Asserts that a given theme is set as default.
     *
     * @Then /^the "([^"]*)" theme is set as default\.$/
     *
     * @throws /Exception if the migration did not complete.
     */
    public function theThemeIsSetAsDefault($theme)
    {
        if (variable_get('theme_default') == $theme) {
            return TRUE;
        }
        $message = sprintf('Theme "%s" is not set as default.', $theme);
        throw new \Exception($message);
    }

    /**
     * Wait for the link before continuing with the step.
     *
     * @Transform /^(?P<link>[^"]*)"$/
     */
    public function waitForLink($link)
    {
        $this->getSession()->wait(10000, 'jQuery("#autocomplete").length === 0');
        return $link;
    }

    /**
     * Overrides DrupalContext LoggedIn.
     *
     * @Todo There has to be a better way to do this.
     *
     * @{inheritdoc}
     */
    public function loggedIn()
    {
        $session = $this->getSession();
        $session->visit($this->locatePath('/'));

        // If a logout link is found, we are logged in. While not perfect, this is
        // how Drupal SimpleTests currently work as well.
        $element = $session->getPage();
        return $element->findLink('Log Out');
    }

    /**
     * Gets a private property from the parent scope.
     *
     * This is needed when defining what it means to have a user login,
     * and testing that they are indeed logged in to the site.
     *
     * @param string $property
     *   The name of the property to get from the parent class.
     *
     * @return mixed
     *   The property returned from the parent.
     */
    protected function getPrivateProperty($property)
    {
        $getProperty = function (DrupalContext $drupalContext) use ($property) {
            return $drupalContext->$property;
        };
        $getProperty = Closure::bind($getProperty, NULL, new DrupalContext());
        return $getProperty($this);
    }

    /**
     * @When /^I click "([^"]*)" and wait$/
     */
    public function iClickAndWait($link)
    {
        $session = $this->getMainContext()->getSession();
        $element = $session->getPage();
        $link_element = $element->findLink($link);
        if (is_object($link_element))
        {
            $link_element->click();
        }
        else
        {
            $message = sprintf("The link $link was not found");
            throw new \Exception($message);
        }
        $this->getSession()->wait(10000);
    }

    /**
     * @Then /^I should see the image "([^"]*)"$/
     */
    public function assertImagePresent($image)
    {
        $imageFound = false;
        $session = $this->getSession();
        $imgs = $session->getPage()->findAll('css', 'img');

        // Loop through all the images on the page to see if the image is
        // present.
        foreach ($imgs as $img) {
          $src = $img->getAttribute('src');
          $srcExplode = explode('/', $src);
          if (array_search($image, $srcExplode)) {
            $imageFound = true;
          }
        }

        if(!$imageFound) {
            throw new \Exception(sprintf('The image "%s" was not found on the page %s.', $image, $session->getCurrentUrl()));
        }
    }
    /**
     * @Then /^I should see a "([^"]*)" element in the "([^"]*)" region$/
     */
    public function assertElementInTheRegion($element, $region)
    {
        $session = $this->getSession();
        $regionObj = $session->getPage()->find('region', $region);
        if (!$regionObj)
        {
            throw new \Exception(sprintf('No region "%s" found on the page %s.', $region, $session->getCurrentUrl()));
        }

        if(!($regionObj->find('css', $element))) {
            throw new \Exception(sprintf('The element "%s" does not exist in the  region "%s" found on the page %s.', $element, $region, $session->getCurrentUrl()));
        }
    }

    /**
     * @Then /^I should see "([^"]*)" in the "([^"]*)" element in the "([^"]*)" region$/
     */
    public function assertInTheElementInTheRegion($text, $element, $region)
    {
        $session = $this->getSession();
        $regionObj = $session->getPage()->find('region', $region);
        if (!$regionObj)
        {
            throw new \Exception(sprintf('No region "%s" found on the page %s.', $region, $session->getCurrentUrl()));
        }

        $test_elements = $regionObj->findAll('css', $element);
        if (!empty($test_elements))
        {
            foreach ($test_elements as $test_element)
            {
                if (stripos(trim($test_element->getText()), $text) !== false)
                {
                    return;
                }
            }
        }

        throw new \Exception(sprintf('The text "%s" was not found in the "%s" element in the %s" region on the page %s', $text, $element, $region, $this->getSession()->getCurrentUrl()));
    }

    /**
     * Generated a random alphanumeric string.
     *
     * @param int $length
     *  The number of characters that string will use.
     *
     * @return string
     */
    public function randomName($length = 8)
    {
        return user_password($length);
    }
}
