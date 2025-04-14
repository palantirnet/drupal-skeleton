<?php

namespace Skeleton;

use Composer\IO\IOInterface;
use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Composer\Console\Application;
use Composer\Script\Event;

/**
 * Generate deployment artifacts for the project.
 *
 * Run 'composer create-artifact'
 *
 * Options:
 *   --build-branch=BRANCH_NAME
 *     Push the artifact to a specific remote branch. Without this argument, the
 *     branch will be named 'artifact-CURRENT_BRANCH'.
 *   --build-dirty
 *     Allow building an artifact from a repository with un-committed changes.
 *     This option is for debugging the build process, not for production
 *     builds.
 *   push | keep | discard
 *     What to do with a successful artifact build. If this is not provided as
 *     an argument, the user will be prompted interactively.
 *
 * Examples:
 *   composer create-artifact push
 *   composer create-artifact -- --build-branch=main push
 *   composer create-artifact -- --build-dirty keep
 *
 * The artifact can be configured in the 'extra' section of composer.json. Full
 * configuration options are:
 *
 * @code
 *  "artifact": {
 *    "git_remote": "../example-artifact-repo",      # REQUIRED: a git URL
 *    "directory": "artifacts/build",                # REQUIRED: path where the artifact should be built
 *    "prefix": "artifact",                          # Optional: prefix for artifact branch and tag names
 *    "git_remote_base_branch": "main",              # Optional: base branch to branch off of
 *    "git_remote_name": "origin",                   # @todo unused?
 *    "template_map": {                              # Optional: Array of files to copy in to the artifact, destination => source
 *      ".gitignore": "vendor/palantirnet/the-build/defaults/artifact/gitignore",
 *      "README.md": "vendor/palantirnet/the-build/defaults/artifact/README.md"
 *    },
 *    "extra_build_steps": [                         # Optional: array of extra composer scripts/commands to run as build steps. These are run after "composer install --no-dev".
 *      {
 *        "command": "outdated",                      # (Example: specify the command)
 *        "--direct": true                            # (Example: include flags as key => value)
 *      }
 *    ]
 *  }
 * @endcode
 *
 * The minimum configuration in composer.json is:
 * @code
 *  "artifact": {
 *    "git_remote": "../example-artifact-repo",
 *    "directory": "artifacts/build"
 *  }
 * @endcode
 */
class Artifact {

  /**
   * Create an artifact.
   *
   * @param \Composer\Script\Event $event
   *   The Composer event.
   */
  public static function create(Event $event): void {
    // Get artifact configuration options from the composer.json file.
    $composer = $event->getComposer();
    $extra = $composer->getPackage()->getExtra();
    $config = self::processConfig($extra['artifact'] ?? []);

    // Construct the artifact object.
    $artifact = new Artifact($config['git_remote'], $config['directory'], $config['template_map'], $config['git_remote_base_branch'], $config['prefix'], $config['build_steps']);

    $artifact->setIo($event->getIO());

    // Apply command line arguments.
    $arguments = self::processArgs($event->getArguments());
    if (isset($arguments['build-branch'])) {
      // Build to a specific branch, e.g. `main`, instead of `artifact-main`.
      $artifact->setBuildBranch($arguments['build-branch']);
    }
    if (isset($arguments['build-dirty'])) {
      // Allow building with un-committed local changes.
      $artifact->allowDirtyBuild(TRUE);
    }

    // Discard, keep, or push a successful artifact. If this argument is not
    // provided, the user will be prompted.
    if (isset($arguments['discard'])) {
      $artifact->setResultAction('discard');
    }
    elseif (isset($arguments['keep'])) {
      $artifact->setResultAction('keep');
    }
    elseif (isset($arguments['push'])) {
      $artifact->setResultAction('push');
    }

    $artifact->run();
  }

  /**
   * Extract command line arguments and apply default values.
   *
   * @param array $config_from_composer
   *   Array of configuration from Composer 'extras'.
   *
   * @return array
   *   Configuration array with defaults applied.
   *
   * @throws \Exception
   *   When required values are missing.
   */
  public static function processConfig(array $config_from_composer) {
    $defaults = [
      'git_remote' => '',
      'directory' => '',
      'template_map' => [
        ".gitignore" => "vendor/palantirnet/the-build/defaults/artifact/gitignore",
        "README.md" => "vendor/palantirnet/the-build/defaults/artifact/README.md",
      ],
      'git_remote_base_branch' => 'main',
      'prefix' => 'artifact',
    ];

    // Merge the defaults with the configuration options.
    $config = array_merge($defaults, $config_from_composer);

    // Check that the required configuration options are set.
    foreach ($defaults as $key => $value) {
      if (empty($config[$key])) {
        throw new \Exception("The artifact '{$key}' configuration option is required.");
      }
    }

    // Optional configuration values.
    // This default build step should always be present.
    $config['build_steps'] = [
      [
        "command" => "install",
        "--no-dev" => TRUE,
        "--ignore-platform-reqs" => TRUE,
      ],
    ];

    // Merge in extra build steps.
    if (isset($config['extra_build_steps'])) {
      $config['build_steps'] = array_merge($config['build_steps'], $config['extra_build_steps']);
    }

    return $config;
  }

  /**
   * Process command line arguments.
   *
   * @param array $arguments
   *   Array of arguments from the Composer event.
   *
   * @return array|false[]
   *   Array of arguments.
   */
  public static function processArgs(array $arguments) {
    $arguments = array_map(function ($arg) {
      return trim($arg, '-');
    }, $arguments);

    $keys = array_map(function ($arg) {
      return current(explode('=', $arg, 2));
    }, $arguments);

    $values = array_map(function ($arg) {
      $parts = explode('=', $arg, 2);
      return end($parts);
    }, $arguments);

    return array_combine($keys, $values);
  }

  /**
   * Git URL for the artifact repository.
   *
   * @var string
   */
  protected string $gitRemote;

  /**
   * Local directory where the artifact git repository should live.
   *
   * @var string
   */
  protected string $directory;

  /**
   * Map of templates that should be copied into the artifact.
   *
   * Keys are the template destination path within the artifact, values are the
   * template source paths relative to the source repository root.
   *
   * @var array
   */
  protected array $templateMap;

  /**
   * The branch of the artifact repository to use as the base for the build.
   *
   * @var string
   */
  protected string $baseBranch;

  /**
   * Prefix for the artifact branch and tag names.
   *
   * @var string
   */
  protected string $prefix;

  /**
   * Array of build steps to run.
   *
   * @var array
   */
  protected array $buildSteps;

  /**
   * Git repository object for the artifact repository.
   *
   * @var SkeletonRepository
   */
  protected SkeletonRepository $artifactRepository;

  /**
   * Git repository object for the source repository.
   *
   * @var SkeletonRepository
   */
  protected SkeletonRepository $sourceRepository;

  /**
   * Class for interacting with git.
   *
   * @var SkeletonGit
   */
  protected SkeletonGit $git;

  /**
   * Whether to allow building a source repository with local changes.
   *
   * @var bool
   */
  protected bool $buildDirty = FALSE;

  /**
   * The branch to push the artifact to.
   *
   * This will be set using the $prefix and the current source branch name, or
   * it can be overridden by passing --build-branch=BRANCH_NAME.
   *
   * @var string
   */
  protected string $buildBranch;

  /**
   * IO interface from the composer event.
   *
   * @var \Composer\IO\IOInterface
   */
  protected IOInterface $io;

  /**
   * Action to perform with a successful artifact build.
   *
   * This may be 'push', 'keep', or 'discard'.
   *
   * @var string
   */
  protected string $resultAction;

  public function __construct(string $gitRemote, string $directory, array $templateMap, string $baseBranch = 'main', string $prefix = 'artifact', array $buildSteps) {
    $this->gitRemote = $gitRemote;
    $this->directory = $directory;
    $this->templateMap = $templateMap;
    $this->baseBranch = $baseBranch;
    $this->prefix = $prefix;
    $this->buildSteps = $buildSteps;

    $this->git = new SkeletonGit();
    $this->sourceRepository = $this->git->open(getcwd());
  }

  /**
   * Allow building an artifact from a repository with un-committed changes.
   *
   * This option is for debugging the build process, not for production builds.
   *
   * @param bool $allow
   *   Whether to allow building with un-committed changes.
   */
  public function allowDirtyBuild(bool $allow): void {
    $this->buildDirty = $allow;
  }

  /**
   * Push the artifact to a given branch instead of 'artifact-CURRENT-BRANCH'.
   *
   * @param string $branch
   *   The name of the branch to push to.
   */
  public function setBuildBranch(string $branch): void {
    $this->buildBranch = $branch;
  }

  /**
   * Get the branch to push the artifact to.
   *
   * If this hasn't been set by the user, it will be generated based on the
   * prefix + the current branch of the source repository.
   *
   * If the source repository is being built from a tag or detached head commit,
   * this will throw an exception -- the user MUST pass a build branch in this
   * case.
   *
   * @return string
   *   The build branch name.
   *
   * @throws \Exception
   */
  public function getBuildBranch(): string {
    if (empty($this->buildBranch)) {
      try {
        $this->buildBranch = $this->prefix . '-' . $this->sourceRepository->getCurrentBranchName();
      }
      catch (\Exception $e) {
        throw new \Exception('Building from a tag or detached head. Please provide a destination build branch with --build-branch=BRANCH_NAME');
      }
    }

    return $this->buildBranch;
  }

  /**
   * Provide an IO object.
   *
   * @param \Composer\IO\IOInterface $io
   *   The IO object from the Composer event.
   */
  public function setIo(IOInterface $io): void {
    $this->io = $io;
  }

  /**
   * Output messages about the status of the artifact build.
   */
  protected function writeIo(string $message) {
    if (isset($this->io)) {
      $this->io->write($message);
    }
  }

  /**
   * What to do with a successful artifact build.
   *
   * @param string $action
   *   The action to take with a successful artifact build: "push", "keep", or
   *   "discard".
   */
  public function setResultAction(string $action) {
    if (in_array($action, ['push', 'keep', 'discard'])) {
      $this->resultAction = $action;
    }
    else {
      throw new \Exception("Action must be 'push', 'keep', or 'discard'.");
    }
  }

  /**
   * Get the result action, or prompt the user if one is not set.
   *
   * @return string
   *   Either 'push', 'keep', or 'discard'.
   *
   * @throws \Exception
   */
  protected function getResultAction(): string {
    if (empty($this->resultAction) && $this->io) {
      $this->resultAction = $this->io->select(
        "Push artifact changes to the '{$this->getBuildBranch()}' branch?",
        ['push' => 'push (default)', 'keep' => 'keep', 'discard' => 'discard'], 'push');
    }
    else {
      throw new \Exception("Missing required action argument 'push', 'keep', or 'discard'.");
    }

    return $this->resultAction;
  }

  /**
   * Run the artifact process.
   */
  public function run() {
    // Don't build if the repository has uncommitted changes.
    try {
      $this->safeToBuild();
    }
    catch (\Exception $e) {
      $this->writeIo($e->getMessage());
      return;
    }

    // Fetch the latest artifact and set up build branches.
    $this->setupBuildBranch();
    $this->setupLocalBranch();

    // Copy files from the source repository to the artifact repository.
    $this->removeArtifactFiles();
    $this->copySource();
    $this->copyTemplates();

    // Run build steps.
    $this->build();

    // Commit the changes to the artifact repository.
    $this->commit();

    // Prompt the user to push the changes to the remote branch or cancel.
    $action = $this->getResultAction();

    if ($action === 'push') {
      $this->writeIo("Pushing artifact.");
      $this->push();
      $this->resetState();
    }
    elseif ($action === 'keep') {
      $this->cleanupTag();
      $this->writeIo("Artifact changes are in the temporary branch '{$this->getTemporaryBranch()}'");
    }
    else {
      $this->cleanupTag();
      $this->resetState();
      $this->writeIo("Artifact changes have been discarded.");
    }
  }

  /**
   * Get the source repository.
   */
  public function getSourceRepository(): SkeletonRepository {
    return $this->sourceRepository;
  }

  /**
   * Get the artifact repository, cloning if it does not yet exist.
   */
  public function getArtifactRepository(): SkeletonRepository {
    if (!isset($this->artifactRepository)) {
      $git = new SkeletonGit();
      $this->artifactRepository = $git->openOrClone($this->directory, $this->gitRemote);
    }
    return $this->artifactRepository;
  }

  /**
   * Check for un-committed changes to the source repository.
   */
  public function safeToBuild(): bool {
    if (!$this->buildDirty && $this->sourceRepository->hasChanges()) {
      $message = "You have changes which must be committed before you may build an artifact.\n";
      $message .= "Modified files:\n  ";
      $message .= implode("\n  ", $this->sourceRepository->showChanges());

      throw new \Exception($message);
    }

    return TRUE;
  }

  /**
   * Ensure a branch is up to date with the remote.
   *
   * @param string $branchName
   *   The branch to sync.
   */
  public function syncBranch($branchName) {
    // Get the latest changes to the base branch.
    $artifactRepo = $this->getArtifactRepository();
    $artifactRepo->fetch(['origin', $branchName]);
    $artifactRepo->checkout("origin/{$branchName}");

    if ($artifactRepo->hasLocalBranch($branchName)) {
      $artifactRepo->forceRemoveBranch($branchName);
    }
    $artifactRepo->createBranch($branchName, TRUE);
  }

  /**
   * Create a temporary branch name based on the commit hash.
   *
   * This branch name is used to avoid conflicts while building the artifact.
   */
  public function getTemporaryBranch(): string {
    return $this->prefix . '-' . $this->sourceRepository->getLastCommit()->getId();
  }

  /**
   * Make sure the build branch exists on the remote repository.
   *
   * @throws \CzProject\GitPhp\GitException
   */
  public function setupBuildBranch(): void {
    $artifactRepo = $this->getArtifactRepository();

    // Ensure the build branch exists on the remote repository.
    if (!$artifactRepo->hasRemoteBranch($this->getBuildBranch(), 'origin')) {
      // Check out the latest upstream version of the base branch.
      $this->syncBranch($this->baseBranch);

      $artifactRepo->createBranch($this->getBuildBranch(), TRUE);
      $artifactRepo->push(['origin', $this->getBuildBranch()]);
      $this->writeIo("Created remote branch: origin/{$this->getBuildBranch()}\n");
    }

    // Check out the latest upstream version of the build branch.
    $this->syncBranch($this->getBuildBranch());
  }

  /**
   * Set up the temporary local branch for this artifact build.
   *
   * Assumes that Artifact::setupBuildBranch() has already been called.
   *
   * @throws \CzProject\GitPhp\GitException
   */
  public function setupLocalBranch(): void {
    $artifactRepo = $this->getArtifactRepository();

    // If a previous build failed or had the result action 'keep', the temporary
    // branch may already exist.
    if ($artifactRepo->hasLocalBranch($this->getTemporaryBranch())) {
      $artifactRepo->forceRemoveBranch($this->getTemporaryBranch());
    }

    $artifactRepo->createBranch($this->getTemporaryBranch(), TRUE);
    $this->writeIo("Created temporary local branch: {$this->getTemporaryBranch()}\n");
  }

  /**
   * Empty the artifact directory.
   *
   * This is used in preparation for copying files from the source repository.
   */
  protected function removeArtifactFiles() {
    // Callback to remove the root .git directory from the files to be deleted.
    $filter = function ($current, $key, $iterator) {
      $subpath = substr($current->getPathname(), strlen($iterator->getPath()) + 1);
      if (empty($subpath) || str_starts_with($subpath, '.git/') || $subpath === '.git') {
        return FALSE;
      }
      return TRUE;
    };

    // Iterator for all the files in the artifact.
    $innerIterator = new \RecursiveDirectoryIterator($this->artifactRepository->getRepositoryPath(), \RecursiveDirectoryIterator::SKIP_DOTS);
    $filterIterator = new \RecursiveCallbackFilterIterator($innerIterator, $filter);

    // Iterator for all the files in the artifact EXCEPT the .git directory.
    $iterator = new \RecursiveIteratorIterator($filterIterator, \RecursiveIteratorIterator::CHILD_FIRST);

    // Remove each file, symlink, or directory.
    /** @var \SplFileInfo $item */
    foreach ($iterator as $item) {
      if ($item->isDir()) {
        rmdir($item->getPathname());
      }
      elseif ($item->isFile() || $item->isLink()) {
        unlink($item->getPathname());
      }
    }
  }

  /**
   * Copy files checked in to the source repository to the artifact repository.
   */
  public function copySource() {
    $files = $this->getSourceRepository()->listFiles();
    $this->copyFileMap(array_combine($files, $files));
  }

  /**
   * Copy templates into the artifact.
   */
  public function copyTemplates() {
    $this->copyFileMap($this->templateMap);
  }

  /**
   * Copy an array of files into place.
   *
   * @param array $map
   *   An associative array of destination => source file paths.
   */
  protected function copyFileMap($map) {
    $source_path = $this->sourceRepository->getRepositoryPath();
    $destination_path = $this->artifactRepository->getRepositoryPath();

    foreach ($map as $d => $s) {
      $destination = "{$destination_path}/{$d}";
      $source = "{$source_path}/{$s}";

      $destinationDir = dirname($destination);
      if (!is_dir($destinationDir)) {
        mkdir($destinationDir, 0750, TRUE);
      }

      copy($source, $destination);
    }
  }

  /**
   * Build the artifact.
   *
   * @throws \Exception
   */
  public function build(): void {
    // Initialize Composer.
    $composer = new Application();
    $composer->setAutoExit(FALSE);

    foreach ($this->buildSteps as $step) {
      if (isset($step['command'])) {
        $step['--no-interaction'] = TRUE;
        $step['--working-dir'] = $this->getArtifactRepository()->getRepositoryPath();

        $input = new ArrayInput($step);
        $output = new ConsoleOutput();

        $result = $composer->run($input, $output);

        if ($result !== 0) {
          throw new \RuntimeException("Failed to run composer {$step['command']} in {$this->getArtifactRepository()->getRepositoryPath()}.");
        }

        $this->writeIo("Composer {$step['command']} completed successfully in {$this->getArtifactRepository()->getRepositoryPath()}.\n");

      }
    }
  }

  /**
   * Commit changes to the artifact repository.
   */
  public function commit(): void {
    $commit = $this->getSourceRepository()->getLastCommit();
    $message = "Drupal artifact build of {$commit->getId()}";

    $this->getArtifactRepository()->addAllChanges();
    $this->getArtifactRepository()->commit($message);

    $tag = $this->getSourceRepository()->getCurrentTag();
    if ($tag) {
      $this->getArtifactRepository()->createTag("{$this->prefix}-{$tag}");
    }
  }

  /**
   * Get a tag for the artifact.
   *
   * Prefix the repository tag so that we're not using the exact same tag on
   * the artifact and on the repository, to avoid confusion, especially when
   * the artifact is built on a branch of the development repository.
   */
  public function getTag(): string {
    $currentTag = $this->sourceRepository->getCurrentTag();

    if ($currentTag) {
      return ($this->prefix ? "{$this->prefix}-" : '') . $currentTag;
    }

    return '';
  }

  /**
   * Push the built artifact to the remote repository.
   */
  public function push() {
    $this->getArtifactRepository()->push(['origin', "{$this->getTemporaryBranch()}:{$this->getBuildBranch()}"]);
    if ($this->getTag()) {
      $this->getArtifactRepository()->push(['origin', $this->getTag()]);
    }
  }

  /**
   * Delete the tag if we're not pushing the build.
   */
  protected function cleanupTag() {
    $tag = $this->getTag();
    if ($tag) {
      $this->getArtifactRepository()->removeTag($tag);
    }
  }

  /**
   * Reset the artifact repository to the base branch.
   */
  public function resetState() {
    $artifactRepository = $this->getArtifactRepository();

    // Make the whole thing writable.
    // Callback to ignore the root .git directory.
    $filter = function ($current, $key, $iterator) {
      $subpath = substr($current->getPathname(), strlen($iterator->getPath()) + 1);
      if (empty($subpath) || strpos($subpath, '.git/') === 0 || $subpath === '.git') {
        return FALSE;
      }
      return TRUE;
    };

    // Iterator for all the files in the artifact.
    $innerIterator = new \RecursiveDirectoryIterator($artifactRepository->getRepositoryPath(), \RecursiveDirectoryIterator::SKIP_DOTS);
    $filterIterator = new \RecursiveCallbackFilterIterator($innerIterator, $filter);

    // Iterator for all the files in the artifact EXCEPT the .git directory.
    $iterator = new \RecursiveIteratorIterator($filterIterator, \RecursiveIteratorIterator::CHILD_FIRST);

    // Update permissions for all files in the artifact directory.
    /** @var \SplFileInfo $item */
    foreach ($iterator as $item) {
      chmod($item->getPathname(), 0750);
    }

    // Reset to HEAD.
    $artifactRepository->reset();

    // Checkout the remote base branch.
    $artifactRepository->checkout($this->baseBranch);

    // Clean the working directory.
    $artifactRepository->clean();

    // Delete the temporary branch.
    $artifactRepository->forceRemoveBranch($this->getTemporaryBranch());
  }

}
