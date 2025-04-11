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
    $artifact = new Artifact($config['git_remote'], $config['directory'], $config['template_map'], $config['git_remote_base_branch'], $config['prefix']);

    $artifact->setIO($event->getIO());

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

  public static function processConfig($config_from_composer) {
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

    return $config;
  }

  public static function processArgs($arguments) {
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

  protected string $gitRemote;
  protected string $directory;
  protected array $templateMap;
  protected string $baseBranch;
  protected string $prefix;

  protected SkeletonRepository $artifactRepository;
  protected SkeletonRepository $sourceRepository;
  protected SkeletonGit $git;
  protected bool $buildDirty = FALSE;
  protected string $buildBranch;
  protected IOInterface $io;

  protected string $resultAction;

  public function __construct(string $gitRemote, string $directory, array $templateMap, string $baseBranch = 'main', string $prefix = 'artifact') {
    $this->gitRemote = $gitRemote;
    $this->directory = $directory;
    $this->templateMap = $templateMap;
    $this->baseBranch = $baseBranch;
    $this->prefix = $prefix;

    $this->git = new SkeletonGit();
    $this->sourceRepository = $this->git->open(getcwd());

    $this->buildBranch = $this->prefix . '-' . $this->sourceRepository->getCurrentBranchName();
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
   * Push the artifact to a specific branch instead of 'artifact-CURRENT-BRANCH'.
   *
   * @param string $branch
   *   The name of the branch to push to.
   */
  public function setBuildBranch(string $branch): void {
    $this->buildBranch = $branch;
  }

  /**
   * Provide an IO object.
   *
   * @param IOInterface $io
   */
  public function setIO(IOInterface $io): void {
    $this->io = $io;
  }

  /**
   * Output messages about the status of the artifact build.
   */
  protected function writeIO($message) {
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
  protected function setResultAction(string $action) {
    if (in_array($action, ['push', 'keep', 'discard'])) {
      $this->resultAction = $action;
    }
    else {
      throw new \Exception("Action must be 'push', 'keep', or 'discard'.");
    }
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
      $this->writeIO($e->getMessage());
      return;
    }

    // Fetch the latest artifact and set up build branches.
    $this->setupBranches();

    // Copy files from the source repository to the artifact repository.
    $this->removeArtifactFiles();
    $this->copySource();
    $this->copyTemplates();

    // Run build steps.
    $this->build();
    // @todo allow running other composer scripts as part of the build steps.

    // Commit the changes to the artifact repository.
    $this->commit();

    // Prompt the user to push the changes to the remote branch or cancel.
    if (empty($this->resultAction) && $this->io) {
      $this->io->select(
        "Push artifact changes to the '{$this->buildBranch}' branch?",
        ['push' => 'push (default)', 'keep' => 'keep', 'discard' => 'discard'], 'push');
    }
    else {
      throw new \Exception("Missing required action argument 'push', 'keep', or 'discard'.");
    }

    if ($this->resultAction === 'push') {
      $this->writeIO("Pushing artifact.");
      $this->push();
      $this->resetState();
    }
    elseif ($this->resultAction === 'keep') {
      $this->cleanupTag();
      $this->writeIO("Artifact changes are in the temporary branch '{$this->getTemporaryBranch()}'");
    }
    else {
      $this->cleanupTag();
      $this->resetState();
      $this->writeIO("Artifact changes have been discarded.");
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
   *
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
   *
   */
  public function setupBranches(): void {
    $artifactRepo = $this->getArtifactRepository();

    // Ensure the build branch exists on the remote repository.
    if (!$artifactRepo->hasRemoteBranch($this->buildBranch, 'origin')) {
      // Check out the latest upstream version of the base branch.
      $this->syncBranch($this->baseBranch);

      $artifactRepo->createBranch($this->buildBranch, TRUE);
      $artifactRepo->push(['origin', $this->buildBranch]);
      // @todo pass messages without "print"
      print "created remote branch: origin/{$this->buildBranch}\n";
    }

    // Check out the latest upstream version of the build branch.
    $this->syncBranch($this->buildBranch);

    if ($artifactRepo->hasLocalBranch($this->getTemporaryBranch())) {
      $artifactRepo->forceRemoveBranch($this->getTemporaryBranch());
    }

    $artifactRepo->createBranch($this->getTemporaryBranch(), TRUE);
    print "created temporary local branch: {$this->getTemporaryBranch()}\n";
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
    // Initialize Composer Installer.
    $composer = new Application();
    $composer->setAutoExit(FALSE);

    // Run the install command.
    $input = new ArrayInput([
      'command' => 'install',
      '--no-interaction' => TRUE,
      '--no-dev' => TRUE,
      '--ignore-platform-reqs' => TRUE,
      '--working-dir' => $this->getArtifactRepository()->getRepositoryPath(),
    ]);
    $output = new ConsoleOutput();

    $result = $composer->run($input, $output);

    // Check if the command was successful.
    if ($result !== 0) {
      throw new \RuntimeException("Failed to run composer install in {$this->getArtifactRepository()->getRepositoryPath()}.");
    }

    // Output the result.
    print "Composer install completed successfully in {$this->getArtifactRepository()->getRepositoryPath()}.\n";
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
    // @todo Handle the case when this command outputs "HEAD".
    // This happens when building a repo from a detatched head state (e.g.
    // you've checked out a tag), and it causes pushing the artifact to fail
    // because "HEAD" is not a branch you can push to.
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
    $this->getArtifactRepository()->push(['origin', "{$this->getTemporaryBranch()}:{$this->buildBranch}"]);
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
