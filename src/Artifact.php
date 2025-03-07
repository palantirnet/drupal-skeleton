<?php

namespace Skeleton;

use Symfony\Component\Console\Output\ConsoleOutput;
use Symfony\Component\Console\Input\ArrayInput;
use Composer\Console\Application;
use Composer\Script\Event;

/**
 * Generate deployment artifacts for the project.
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

    // @todo error handling for missing configuration options.
    $defaults = [
      'git_remote' => '',
      'directory' => '',
      'template_map' => [
        ".gitignore" => "vendor/palantirnet/the-build/defaults/artifact/gitignore",
        "README.md" => "vendor/palantirnet/the-build/defaults/artifact/README.md",
      ],
      'git_remote_base_branch' => 'main',
      'prefix' => 'artifact',
      // Temporary flag to allow building artifacts with uncommitted changes.
      // @todo pass this in as a command line option.
      'build_dirty' => TRUE,
    ];
    // Merge the defaults with the configuration options.
    $config = array_merge($defaults, $extra['artifact']);

    if (empty($config['git_remote'])) {
      throw new \Exception("The artifact 'git_remote' configuration option is required.");
    }
    if (empty($config['directory'])) {
      throw new \Exception("The artifact 'directory' configuration option is required.");
    }

    $artifact = new Artifact($config['git_remote'], $config['directory'], $config['template_map'], $config['git_remote_base_branch'], $config['prefix'], $config['build_dirty']);

    // Don't build if the repository has uncommitted changes.
    try {
      $artifact->safeToBuild();
    }
    catch (\Exception $e) {
      $event->getIO()->write($e->getMessage());
      return;
    }

    // Fetch the latest artifact and set up build branches.
    $artifact->setupBranches();

    // Copy files from the source repository to the artifact repository.
    $artifact->removeArtifactFiles();
    $artifact->copySource();
    $artifact->copyTemplates();

    // Run build steps.
    $artifact->build();

    // Commit the changes to the artifact repository.
    $artifact->commit();

    // Prompt the user to push the changes to the remote branch or cancel.
    $arguments = $event->getArguments();
    $argumentAction = array_intersect(['push', 'keep', 'discard'], $arguments);
    if (count($argumentAction) == 1) {
      $artifactResult = $arguments[0];
    }
    else {
      $artifactResult = $event->getIO()->select(
        "Push artifact changes to the '{$artifact->getBuildBranch()}' branch?",
        ['push' => 'push (default)', 'keep' => 'keep', 'discard' => 'discard'], 'push');
    }

    if ($artifactResult === 'push') {
      $event->getIO()->write("Pushing artifact.");
      $artifact->push();
    }
    elseif ($artifactResult === 'keep') {
      $artifact->keep();
      $event->getIO()->write("Artifact changes are in the temporary branch '{$artifact->getTemporaryBranch()}'");
    }
    else {
      $artifact->discard();
      $event->getIO()->write("Artifact changes have been discarded.");
    }
  }

  protected string $gitRemote;
  protected string $directory;
  protected array $templateMap;
  protected string $baseBranch;
  protected string $prefix;

  protected SkeletonRepository $artifactRepository;
  protected SkeletonRepository $sourceRepository;
  protected SkeletonGit $git;
  protected bool $buildDirty;

  public function __construct(string $gitRemote, string $directory, array $templateMap, string $baseBranch = 'main', string $prefix = 'artifact', bool $buildDirty = FALSE) {
    $this->gitRemote = $gitRemote;
    $this->directory = $directory;
    $this->templateMap = $templateMap;
    $this->baseBranch = $baseBranch;
    $this->prefix = $prefix;
    $this->buildDirty = $buildDirty;

    $this->git = new SkeletonGit();
    $this->sourceRepository = $this->git->open(getcwd());
  }

  /**
   *
   */
  public function getSourceRepository(): SkeletonRepository {
    return $this->sourceRepository;
  }

  /**
   *
   */
  public function getArtifactRepository(): SkeletonRepository {
    if (!isset($this->artifactRepository)) {
      $git = new SkeletonGit();
      $this->artifactRepository = $git->openOrClone($this->directory, $this->gitRemote);
    }
    return $this->artifactRepository;
  }

  /**
   *
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
   * // Create a temporary branch name based on the commit for building the
   * // artifact, to avoid branch conflicts.
   */
  public function getTemporaryBranch(): string {
    return $this->prefix . '-' . $this->sourceRepository->getLastCommit()->getId();
  }

  /**
   * // If the remote branch isn't configured, use a remote branch based on the
   * // name of the current branch. This won't overwrite the property value if it
   * // is already set.
   */
  public function getBuildBranch(): string {
    return $this->prefix . '-' . $this->sourceRepository->getCurrentBranchName();
  }

  /**
   *
   */
  public function setupBranches(): void {
    $artifactRepo = $this->getArtifactRepository();

    // Ensure the build branch exists on the remote repository.
    if (!$artifactRepo->hasRemoteBranch($this->getBuildBranch(), 'origin')) {
      // Check out the latest upstream version of the base branch.
      $this->syncBranch($this->baseBranch);

      $artifactRepo->createBranch($this->getBuildBranch(), TRUE);
      $artifactRepo->push(['origin', $this->getBuildBranch()]);
      print "created remote branch: origin/{$this->getBuildBranch()}\n";
    }

    // Check out the latest upstream version of the build branch.
    $this->syncBranch($this->getBuildBranch());

    if ($artifactRepo->hasLocalBranch($this->getTemporaryBranch())) {
      $artifactRepo->forceRemoveBranch($this->getTemporaryBranch());
    }

    $artifactRepo->createBranch($this->getTemporaryBranch(), TRUE);
    print "created temporary local branch: {$this->getTemporaryBranch()}\n";
  }

  /**
   *
   */
  protected function removeArtifactFiles() {
    // Callback to remove the root .git directory from the files to be deleted.
    $filter = function ($current, $key, $iterator) {
      $subpath = substr($current->getPathname(), strlen($iterator->getPath()) + 1);
      if (empty($subpath) || strpos($subpath, '.git/') === 0 || $subpath === '.git') {
        return FALSE;
      }
      return TRUE;
    };

    // Iterator for all of the files in the artifact.
    $innerIterator = new \RecursiveDirectoryIterator($this->artifactRepository->getRepositoryPath(), \RecursiveDirectoryIterator::SKIP_DOTS);
    $filterIterator = new \RecursiveCallbackFilterIterator($innerIterator, $filter);

    // Iterator for all of the files in the artifact EXCEPT the .git directory.
    $iterator = new \RecursiveIteratorIterator($filterIterator, \RecursiveIteratorIterator::CHILD_FIRST);

    // Remove all files in the artifact directory.
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
   *
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
   *
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
   * // Prefix the repository tag so that we're not using the exact same tag on
   * // the artifact and on the repository, to avoid confusion, especially when
   * // the artifact is built on a branch of the development repository.
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
   *
   */
  public function push() {
    $this->getArtifactRepository()->push(['origin', "{$this->getTemporaryBranch()}:{$this->getBuildBranch()}"]);
    if ($this->getTag()) {
      $this->getArtifactRepository()->push(['origin', $this->getTag()]);
    }

    $this->resetState();
  }

  /**
   *
   */
  public function keep() {
    $this->cleanupTag();
  }

  /**
   *
   */
  public function discard(): void {
    $this->cleanupTag();
    $this->resetState();
  }

  /**
   *
   */
  protected function cleanupTag() {
    $tag = $this->getTag();
    if ($tag) {
      $this->getArtifactRepository()->removeTag($tag);
    }
  }

  /**
   *
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
