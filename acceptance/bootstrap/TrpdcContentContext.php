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
 * Content context.
 */
class TrpdcContentContext extends BehatContext implements DrupalSubContextInterface
{

    /**
     * Sets the alias of this Context.
     *
     * @return string
     *   Returns alias of context.
     */
    public static function getAlias()
    {
        return 'content';
    }

    /**
     * @Given /^I have a future event node "(?P<event>[^"]*)"$/
     *
     * @TODO Can we accomplish this with a transform?
     */
    public function iHaveAFutureEventNode($event)
    {
        $eventsTable = new TableNode();
        $eventsTable->addRow(
          [
            'title',
            'field_event_date',
            'field_summary',
            'field_description',
            'field_event_location',
            'field_archived_event',
            'promote',
            'status',
            'field_event_owner'
          ]
        );
        $eventsTable->addRow(
          [
            $event,
            date('Y-m-d H:i:s', time() + (24 * 60 * 60)),
            'I am a summary',
            'This is an event description',
            'I am a Location',
            '0',
            '1',
            '1',
            '1'
          ]
        );
        $this->getMainContext()->createNodes('event', $eventsTable);
    }

    /**
     * @Given /^I have a past event node "(?P<event>[^"]*)"$/
     *
     * @TODO Can we accomplish this with a transform?
     */
    public function iHaveAPastEventNode($event)
    {
        $eventsTable = new TableNode();
        $eventsTable->addRow(
          [
            'title',
            'field_event_date',
            'field_summary',
            'field_description',
            'field_event_location',
            'field_archived_event',
            'promote',
            'status',
            'field_event_owner'
          ]
        );
        $eventsTable->addRow(
          [
            $event,
            date('Y-m-d H:i:s', time() - (24 * 60 * 60)),
            'I am a past summary',
            'This is a past event description',
            'I am a past Location',
            '0',
            '1',
            '1',
            '1'
          ]
        );
        $this->getMainContext()->createNodes('event', $eventsTable);
    }

    /**
     * @Then /^I should not see Authoring information and Sticky at top of lists options$/
     */
    public function iShouldNotSeeNodeEditOptions()
    {
        return array(
          new Then('I should not see the link "Authoring information"'),
            // The options below are under "Publishing options", collapsed by js
            // in the UI. This test does not use the browser emulator.
          new Then('I should not see the text "Sticky at top of lists"'),
        );
    }


    /**
     * @Given /^I am (?:a|an) "(?P<group_role>[^"]*)" of the Proposition group "(?P<og>[^"]*)"$/
     */
    public function assignOrganicGroupRole($group_role, $og)
    {
        $groupID = $this->getLastNodeID('proposition', $og);
        $group = node_load($groupID);
        $this->getMainContext()->getSubcontext('user')->addMembertoGroup(
          $group_role,
          $group
        );
        //$path = $this->getSession()->getCurrentUrl();
        //$this->getSession()->visit($path);
    }

    public function getNodeByName($title) {
        $query = new EntityFieldQuery();

        $entities = $query->entityCondition('entity_type', 'node')
          ->propertyCondition('title', $title)
          ->execute();

        if (!empty($entities['node'])) {
            $node = end($entities['node']);
        }

        return $node->nid;
    }

    /**
     *  Helper Function to return the nid of a node by title and type.
     *
     * @param string $type
     *   The node bundle
     *
     * @param string $title
     *   The title of the node
     *
     * @return int
     *   Returns the nid.
     */
    public function getLastNodeID($type, $title)
    {
        $query = new EntityFieldQuery();

        $entities = $query->entityCondition('entity_type', 'node')
          ->propertyCondition('type', $type)
          ->propertyCondition('title', $title)
          ->execute();

        if (!empty($entities['node'])) {
            $node = end($entities['node']);
        }

        return $node->nid;
    }

    /**
     * @Given /^A "(?P<type>[^"]*)" node:$/
     *
     * @param TableNode $nodesTable
     *   The table with the node info.
     *
     * @todo rename this method.
     *
     * Use the createNodes to create nodes with references found via their title.
     */
    public function createAllOfTheNodes($type, $nodesTable = null)
    {
        foreach ($nodesTable->getHash() as $nodeHash) {
            // Create a node with default settings.
            $settings = $this->getNodeDefaultProperties();
            $node = (object) $settings;

            $node->type = $type;
            $node = $this->setNodeProperties($node, $settings, $nodeHash);

            foreach($nodeHash as $field => $value) {
                $node->$field = $value;
            }

            $node = $this->expandEntityFields($node);

            // Save the node and set the moderation state.
            $saved = $this->saveNode($node);

            if(!empty($nodeHash['workbench_moderation_state'])) {
                workbench_moderation_moderate($saved, $nodeHash['workbench_moderation_state']);
            }

        }
    }

    /**
     * @When /^I go to the node url$/
     */
    public function iGoToTheNodeUrl()
    {
        $node = $this->getLastNode();
        $alias = drupal_get_path_alias("node/$node->nid");
        $session = $this->getMainContext()->getSession();
        $session->visit($this->getMainContext()->locatePath($alias));
    }


    /**
     * Gets the last created node.
     *
     * @throws Exception
     *
     * @return stdClass
     *   Last created node object.
     */
    protected function getLastNode()
    {
        $nodes = $this->getMainContext()->nodes;

        if (empty($nodes)) {
            $message = sprintf('No nodes have been created yet.');
            throw new \Exception($message);
        }
        $node = end($nodes);

        return $node;
    }

    /**
     * @When /^The "(?P<type>[^"]*)" node with the title "(?P<title>[^"]*)" has a new moderation state of "(?P<state>[^"]*)"$/
     */
    public function changeNodeModerationState($type, $title, $state)
    {
        // Get the entity wrapper for the last created node.
        $node = node_load($this->getLastNodeID($type, $title));
        workbench_moderation_moderate($node, $state);
    }

    /**
     * Create drupal node.
     */
    public function nodeCreate(\stdClass $node) {
        // Set original if not set.
        if (!isset($node->original)) {
            $node->original = clone $node;
        }

        // Assign authorship if none exists and `author` is passed.
        if (!isset($node->uid) && !empty($node->author) && ($user = user_load_by_name($node->author))) {
            $node->uid = $user->uid;
        }

        // Set defaults that haven't already been set.
        $defaults = clone $node;
        node_object_prepare($defaults);
        $node = (object) array_merge((array) $defaults, (array) $node);

        node_save($node);
        return $node;
    }

    /**
     * Given a node object, expand fields to match the format expected by node_save().
     *
     * @param stdClass $entity
     *   Entity object.
     *
     * @param string $entityType
     *   Entity type, defaults to node.
     *
     * @param string $bundle
     *   Entity bundle.
     *
     * @throws Exception if a taxonomy term value doesn't exist on a term reference field .
     *
     * @return stdClass
     *   An entity object with newly set field values.
     */
    public function expandEntityFields(\stdClass $entity, $entityType = 'node', $bundle = '') {
        if ($entityType === 'node' && !$bundle) {
            $bundle = $entity->type;
        }
        $new_entity = clone $entity;
        foreach ($entity as $param => $value) {
            if ($info = field_info_field($param)) {
                foreach ($info['bundles'] as $type => $bundles) {
                    if ($type == $entityType) {
                        foreach ($bundles as $target_bundle) {
                            if ($bundle === $target_bundle) {
                                unset($new_entity->{$param});
                                // Use the first defined column. @todo probably breaks things.
                                $column_names = array_keys($info['columns']);
                                $column = array_shift($column_names);
                                // Special handling for date fields (start/end).
                                // @todo generalize this
                                if ('date' === $info['module']) {
                                    // Dates passed in separated by a comma are start/end dates.
                                    $dates = explode(',', $value);
                                    $value = trim($dates[0]);
                                    if (!empty($dates[1])) {
                                        $column2 = array_shift($column_names);
                                        $new_entity->{$param}[LANGUAGE_NONE][0][$column2] = trim($dates[1]);
                                    }
                                    $new_entity->{$param}[LANGUAGE_NONE][0][$column] = $value;
                                }
                                // Special handling for term references.
                                elseif ('taxonomy' === $info['module']) {
                                    $terms = explode(',', $value);
                                    $i = 0;
                                    foreach ($terms as $term) {
                                        $tid = taxonomy_get_term_by_name($term);
                                        if (!$tid) {
                                            throw new \Exception(sprintf("No term '%s' exists.", $term));
                                        }
                                        $new_entity->{$param}[LANGUAGE_NONE][$i][$column] = array_shift($tid)->tid;
                                        $i++;
                                    }
                                }
                                elseif ('entityreference' == $info['module']) {
                                    $references = explode(',', $value);
                                    // @TODO add check for node reference and submit patch.
                                    if ('taxonomy_term' == $info['settings']['target_type']) {
                                        $i = 0;
                                        foreach ($references as $term) {
                                            $tid = taxonomy_get_term_by_name($term);
                                            if (!$tid) {
                                                throw new \Exception(sprintf("No term '%s' exists.", $term));
                                            }
                                            $new_entity->{$param}[LANGUAGE_NONE][$i][$column] = array_shift($tid)->tid;
                                            $i++;
                                        }
                                    }
                                    elseif ('node' == $info['settings']['target_type']) {
                                        $i = 0;
                                        foreach ($references as $node_title) {
                                            $nid = $this->getNodeByName($node_title);
                                            if (!$nid) {
                                                throw new \Exception(sprintf("No node '%s' exists.", $node_title));
                                            }
                                            $new_entity->{$param}[LANGUAGE_NONE][$i][$column] = $nid;
                                            $i++;
                                        }
                                    }
                                }
                                elseif (is_array($value)) {
                                    foreach ($value as $key => $data) {
                                        if (is_int($key) && (isset($value[$key+1]) || isset($value[$key-1]))) {
                                            $new_entity->{$param}[LANGUAGE_NONE][$key] = $data;
                                        } else {
                                            $new_entity->{$param}[LANGUAGE_NONE][0][$key] = $data;
                                        }
                                    }
                                }
                                else {
                                    $new_entity->{$param}[LANGUAGE_NONE][0][$column] = $value;
                                }
                            }
                        }
                    }
                }
            }
        }
        return $new_entity;
    }

    /**
     * Sets the node properties from those defined in the nodeTable
     *
     * @param stdClass $node
     *   A node object.
     *
     * @param array $settings
     *   An array of default node properties.
     *
     * @param array $nodeHash
     *   A row from the node table provided in the feature.
     *
     * @return stdClass
     *   A node object.
     */
    protected function setNodeProperties($node, $settings, &$nodeHash)
    {
        $overrideProperties = array_intersect_key($nodeHash, $settings);

        foreach ($overrideProperties as $property => $value) {
            $node->$property = $value;
            unset($nodeHash[$property]);
        }


        return $node;
    }


    /**
     * Creates the node, and add the events to allow for create and tear down events after scenario.
     *
     * @param stdClass $node
     *   A Drupal node object.
     *
     * @return stdClass
     *   A saved Drupal node object.
     */
    protected function saveNode($node)
    {
        $this->getMainContext()->dispatcher->dispatch('beforeNodeCreate', new EntityEvent($this, $node));
        $saved = $this->nodeCreate($node);
        $this->getMainContext()->dispatcher->dispatch('afterNodeCreate', new EntityEvent($this, $saved));
        $this->getMainContext()->nodes[] = $saved;

        return $saved;
    }

    /**
     * Default settings of created nodes.
     *
     * @return array
     */
    protected function getNodeDefaultProperties()
    {
        // Grab the uid of the last created user, or set the uid to the anonymous user.
        $uid = (!empty($this->getMainContext()->user)) ? $this->getMainContext()->user->uid : 0;

        return array(
          'title' => $this->getMainContext()->randomName(8),
          'changed' => REQUEST_TIME,
          'language' => LANGUAGE_NONE,
          'comment' => 0,
          'sticky' => 0,
          'promote' => 0,
          'revision' => 1,
          'revisions' => null,
          'moderate' => 0,
          'status' => 1,
          'uid' => $uid,
          'body' => Random::string(255),
        );
    }

    /**
    * @TODO make this function far more useful.
    *
    * @afterscenario @carousel-content
    */
    public function removeContent()
    {
        $query = new entityFieldQuery();

        // Get the dummy carousel node that we created.
        $results = $query
          ->entityCondition('entity_type', 'node')
          ->propertyCondition('title', 'Carousel slide 1')
          ->propertyCondition('status', NODE_PUBLISHED)
          ->fieldCondition('field_promo_type', 'value', 'carousel')
          ->range(0, 20)
          ->execute();

        // Delete any of the test data that was created during testing.
        if (!empty($results['node'])) {
            foreach ($results['node'] as $node) {
                node_delete($node->nid);
            }
        }
    }
}
