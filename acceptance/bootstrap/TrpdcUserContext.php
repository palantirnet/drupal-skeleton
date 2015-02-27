<?php

use Drupal\DrupalExtension\Context\DrupalSubContextInterface;
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
 *  User context.
 */
class TrpdcUserContext extends BehatContext implements DrupalSubContextInterface
{

    /**
     * Copy of the users array from the DrupalContext.
     *
     * @var array
     */
    protected $users;

    /**
     * Sets the alias of this Context.
     *
     * @return string
     *   Returns alias of context.
     */
public static function getAlias()
    {
        return 'user';
    }

    /**
     * Get Mink session from MinkContext
     */
    public function getSession($name = NULL) {
        return $this->getMainContext()->getSession($name);
    }

    /**
     * Creates user with Apigee required fields.
     *
     * @param string $role
     *   The name of the role to assign to the user.
     *
     * @return object
     *   Returns user object with random details.
     */
    public function trpdcCreateUser($role = '')
    {
        // Create user with first and last name to satisfy Apigee.
        $user = (object) array(
          'field_first_name' => array(
            LANGUAGE_NONE => array(
              0 => array(
                'value' => Random::name(8),
              ),
            ),
          ),
          'field_last_name' => array(
            LANGUAGE_NONE => array(
              0 => array(
                'value' => Random::name(8),
              ),
            ),
          ),
          'name' => Random::name(8),
          'pass' => Random::name(16),
          'role' => $role,
        );
        $user->mail = "{$user->name}@example.com";

        // Create a new user.
        $this->getMainContext()->getDriver()->userCreate($user);
        $this->users[$user->name] = $this->getMainContext()->user = $user;
        return $user;
    }

    /**
     * Logs the user out to ensure they are a guest user.
     *
     * @Given /^I am a guest user$/
     */
    public function assertGuestUser()
    {
        $this->getMainContext()->assertAnonymousUser();
    }

    /**
     * Creates and authenticates a user with the given role.
     *
     * @Given /^I am logged in as a trpdc user with the "(?P<role>[^"]*)" role$/
     */
    public function loginUserWithRole($role)
    {
        // Check if a user with this role is already logged in.
        if ($this->getMainContext()->loggedIn()
          && $this->getMainContext()->user
          && isset($this->getMainContext()->user->role)
          && $this->getMainContext()->user->role == $role) {
            return TRUE;
        }
        // Create user.
        $user = $this->trpdcCreateUser($role);

        // Add role.
        if ($role != 'authenticated user') {
            $this->getMainContext()->getDriver()->userAddRole($user, $role);
        }

        // Login.
        $this->getMainContext()->login();

        return TRUE;
    }

    /**
     * Creates and authenticates a user with the given permission.
     *
     * @Given /^I am logged in as a trpdc user with the "(?P<permission>[^"]*)" permission(?:|s)$/
     */
    public function loginWithUserPermission($permissions)
    {
        $permissions = explode(',', $permissions);

        $rid = $this->getMainContext()->getDriver()->roleCreate($permissions);
        if (!$rid) {
            return FALSE;
        }

        // Create user with first and last name to satisfy Apigee.
        $user = (object) array(
          'field_first_name' => array(
            LANGUAGE_NONE => array(
              0 => array(
                'value' => Random::name(8),
              ),
            ),
          ),
          'field_last_name' => array(
            LANGUAGE_NONE => array(
              0 => array(
                'value' => Random::name(8),
              ),
            ),
          ),
          'name' => Random::name(8),
          'pass' => Random::name(16),
          'roles' => array($rid),
        );
        $user->mail = "{$user->name}@example.com";

        // Create a new user.
        $this->getMainContext()->getDriver()->userCreate($user);
        $this->users[$user->name] = $this->getMainContext()->user = $user;

        // Login.
        $this->getMainContext()->login();

        return TRUE;
    }

  /**
   * Adds a member to an organic group with the specified role.
   *
   * @param string $group_role
   *   The machine name of the group role.
   *
   * @param object $group
   *   The group node.
   *
   * @param string $group_type
   *   (optional) The group's entity type.
   *
   * @throws \Exception
   */
  public function addMembertoGroup($group_role, $group, $group_type = 'node') {
    $user = $this->getMainContext()->user;
    list($gid, $vid, $bundle) = entity_extract_ids($group_type, $group);

    $membership = og_group($group_type, $gid, array(
      "entity type" => 'user',
      "entity" => $user,
    ));

    if (!$membership) {
      throw new \Exception("The Organic Group membership could not be created.");
    }

    // Add role for membership.
    $roles = og_roles($group_type, $group->type, $gid);
    $rid = array_search($group_role, $roles);

    if (!$rid) {
      throw new \Exception("'$group_role' is not a valid group role.");
    }

    og_role_grant($group_type, $gid, $user->uid, $rid);

  }

    /**
     * @Given /^I am the author of the created "(?P<type>[^"]*)" node with the title "(?P<title>[^"]*)"$/
     */
    public function IAmNodeAuthor($type, $title)
    {
        // Get the drupal user object for the currently logged in trpdc user.
        $user = $this->getLastCreatedTrpdcUser();

        // Get the entity wrapper for the last created node.
        $wrapper = entity_metadata_wrapper('node', $this->getMainContext()->getSubcontext('content')->getLastNodeID($type, $title));

        $wrapper->author = $user->uid;
        $wrapper->revision->set(1);
        $wrapper->save();
    }

    /**
     * Loads a user object for the last created trpdc user.
     * @see trpdcCreateUser()
     *
     * By loading the drupal user object and not relying just on $this->user we
     * can ensure that the object we're seeing exists in drupal and is not
     * a representation of an object that should be a drupal object. This also
     * gives access to the user's uid.
     *
     * @return stdClass A fully-loaded $user object.
     *
     * @throws \Exception if a user hasn't been created yet, or can not load the
     * drupal user object for the last created trpdc user.
     */
    protected function getLastCreatedTrpdcUser()
    {
        if (empty($this->getMainContext()->user)) {
            throw new \Exception(sprintf('A TRPDC User has not been created yet.'));
        }

        $user = user_load_by_name($this->getMainContext()->user->name);

        if($user) {
            return $user;
        } else {
            throw new \Exception(sprintf('Could not load the user %s by name', $this->getMainContext()->user->name));
        }
    }

    /**
     * @Then /^I have the "(?P<role>[^"]*)" group role for the created "(?P<type>[^"]*)" group node with the title "(?P<title>[^"]*)"$/
     */
    public function assertUserHasGroupRoleForCreatedNode($role, $type, $title)
    {
        $user = $this->getLastCreatedTrpdcUser();
        $wrapper = entity_metadata_wrapper('node', $this->getMainContext()->getSubcontext('content')->getLastNodeID($type, $title));

        $availableGroupRoles = og_roles('node', $type);

        if (!in_array($role, $availableGroupRoles)) {
            throw new \Exception((sprintf("The role %s does not exist for the %s bundle", $role, $type)));
        }

        // Get the user group roles for the group.
        $userRoles = og_get_user_roles('node', $wrapper->getIdentifier(), $user->uid);

        if (!in_array($role, $userRoles)) {
            throw new \Exception(sprintf("The user %s does not have the %s role for the %s group node", $user->uid, $role, $wrapper->title->value()));
        }
    }

    /**
     * @When /^I edit my profile$/
     */
    public function iEditMyProfile() {
      // Get the drupal user object for the currently logged in trpdc user.
      $user = $this->getLastCreatedTrpdcUser();

      $steps = array();

      $steps[] = new When('I am at "user/'. $user->uid . '/edit"');

      return $steps;
    }

    /**
     * @When /^I view my profile$/
     */
    public function iViewMyProfile() {
      // Get the drupal user object for the currently logged in trpdc user.
      $user = $this->getLastCreatedTrpdcUser();

      $steps = array();

      $steps[] = new When('I am at "users/'. $user->name . '"');

      return $steps;
    }

    /**
     * @Then /^I can see the value for "([^"]*)" in the "([^"]*)" element but do not have acess to edit "([^"]*)"$/
     */
    public function assertTheValueInTheElementButDoNotHaveAcessToEdit($locator, $csselement, $field_name)
    {
        $session = $this->getSession();
        $user = $this->getLastCreatedTrpdcUser();
        $content = $user->{$field_name}[LANGUAGE_NONE][0]['value'];
        $field = $session->getPage()->findField($locator);
        $regionObj = $session->getPage()->find('region', 'Profile edit');

        $test_elements = $regionObj->findAll('css', $csselement);
        if (!empty($test_elements))
        {
            foreach ($test_elements as $test_element)
            {
                if (stripos(trim($test_element->getText()), $content) !== false)
                {
                    if (null === $field)
                    {
                        return;
                    }
                    else
                    {
                        throw new \Exception(sprintf("The field %s exists on the page and the user has access to it", $locator));
                    }
                }
            }
        }
    }

    /**
     * @Then /^I can change my screen name$/
     */
    public function assertChangeMyScreenName()
    {

      $steps = array();

      $steps[] = new When('I fill in "Screen name" with "'. Random::name(8) .'"');
      $steps[] = new When('I press "Save"');
      $steps[] = new Then('I should see the text "The changes have been saved."');

      return $steps;
    }

    /**
     * @Then /^I am unable to change my Drupal password$/
     */
    public function assertUnableToChangeMyDrupalPassword()
    {
        $steps = array();

        $steps[] = new Then('I should not see the text "Password" in the "Profile edit" region');
        $steps[] = new Then('I should not see the text "Confirm password" in the "Profile edit" region');

        return $steps;
    }

    /**
     * @Then /^I can cancel my changes$/
     */
    public function assertCancelMyChanges()
    {
        $user = $this->getLastCreatedTrpdcUser();

        $profile_url = url('user/' . $user->uid);

        $steps = array();

        $steps[] = new When('I click "Cancel changes"');
        $steps[] = new Then('I am at "'. $profile_url .'"');

        return $steps;
    }

    /**
     * @Then /^I can change my first name$/
     */
    public function assertChangeMyFirstName()
    {

        $steps = array();

        $steps[] = new Given('I fill in "First Name" with "'. Random::name(8) .'"');
        $steps[] = new When('I press "Save"');
        $steps[] = new Then('I should see the text "The changes have been saved."');

        return $steps;
    }

    /**
     * @Then /^I can change my last name$/
     */
    public function assertChangeMyLastName()
    {

        $steps = array();

        $steps[] = new Given('I fill in "Last Name" with "'. Random::name(8) .'"');
        $steps[] = new When('I press "Save"');
        $steps[] = new Then('I should see the text "The changes have been saved."');

        return $steps;
    }

    /**
     * @Then /^I can choose to become a member$/
     */
    public function assertChooseToBecomeAMember() {
        $user = $this->getLastCreatedTrpdcUser();

        $become_member_url = url('user/' . $user->uid . '/become-member');

        $steps = array();

        $steps[] = new When('I click "Become a member"');
        $steps[] = new Then('I am at "'. $become_member_url .'"');

        return $steps;
        throw new PendingException();
    }

    /**
     * @Then /^I am able to change my Drupal password$/
     */
    public function assertAbleToChangeMyDrupalPassword()
    {
        $steps = array();

        $steps[] = new Then('I should see the text "Current password" in the "Profile edit" region');
        $steps[] = new Then('I should see the text "Password" in the "Profile edit" region');
        $steps[] = new Then('I should see the text "Confirm password" in the "Profile edit" region');

        return $steps;
    }

    /**
     * @Then /^I can see my screen name$/
     */
    public function assertSeeMyScreenName()
    {
        $user = $this->getLastCreatedTrpdcUser();
        $name = $user->name;

        $steps = array();

        $steps[] = new Then('I should see the text "Screen Name: '. $name .'"');

      return $steps;
    }

    /**
     * @Then /^I can see my memborship information$/
     */
    public function assertSeeMyMemborshipInformation()
    {
        $user = $this->getLastCreatedTrpdcUser();

        $membership_date = date('m/Y', $user->created);

        $steps = array();

        $steps[] = new Then('I should see the text "Member since: '. $membership_date .'"');

      return $steps;
    }

    /**
     * @Then /^I should not see any memborship information$/
     */
    public function assertNotSeeAnyMemborshipInformation()
    {
        $user = $this->getLastCreatedTrpdcUser();

        $membership_date = date('m/Y', $user->created);

        $steps = array();

        $steps[] = new Then('I should not see the text "Member since: '. $membership_date .'"');

      return $steps;
    }
}
