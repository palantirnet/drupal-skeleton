<?php

namespace Skeleton;

use CzProject\GitPhp\Git;
use Composer\Script\Event;
use CzProject\GitPhp\GitRepository;

/**
 * Generate deployment artifacts for the project.
 */
class Artifact {

  /**
   * Create an artifact.
   *
   * @param \Composer\Script\Event $event
   */
  public static function createArtifact(Event $event): void {
    // Get artifact configuration options from the composer.json file.
    $composer = $event->getComposer();
    $extra = $composer->getPackage()->getExtra();

    // Git configuration for storing the artifact.
    $remoteGit = $extra['artifact']['remote_git'];
    $remoteBaseBranch = $extra['artifact']['remote_base_branch'];
    $branchPrefix = $extra['artifact']['git_branch_prefix'];
    $tagPrefix = $extra['artifact']['git_tag_prefix'];
    // Where to build the artifact.
    $buildDirectory = $extra['artifact']['build_directory'];
    // Template files to copy into the artifact.
    $templates = $extra['artifact']['templates'];

    // Output configuration options.
    $event->getIO()->write('Artifact configuration:');
    $event->getIO()->write('  remote_git: ' . $remoteGit);
    $event->getIO()->write('  remote_base_branch: ' . $remoteBaseBranch);
    $event->getIO()->write('  git_branch_prefix: ' . $branchPrefix);
    $event->getIO()->write('  git_tag_prefix: ' . $tagPrefix);
    $event->getIO()->write('  build_directory: ' . $buildDirectory);
    $event->getIO()->write('  templates: ' . implode(', ', $templates));
    $event->getIO()->write('  current dir: ' . getcwd());

    $git = new Git();
    $source_repository = $git->open(getcwd());
    $artifact_repository = self::initializeRepository($buildDirectory, $remoteGit, $remoteBaseBranch);

    self::safeToBuild($source_repository);

    $temporaryBranch = 'artifact-' . $source_repository->getLastCommit()->getId();
    $event->getIO()->write('Creating temporary branch: ' . $temporaryBranch);
    if (in_array($temporaryBranch, $artifact_repository->getBranches())) {
      $artifact_repository->removeBranch($temporaryBranch);
    }
    $artifact_repository->createBranch($temporaryBranch, TRUE);

  }

  /**
   *
   */
  protected static function safeToBuild(GitRepository $repository): bool {
    $result = $repository->run('status', ['--porcelain']);

    if ($result->hasOutput()) {
      print "Repository status:    dirty\n";
      print "  * You have changes which must be committed before you may build an artifact.\n";
      print "  * Modified files:\n";
      print $result->getOutputAsString();
      return FALSE;
    }

    print "Repository status:    clean\n";
    return TRUE;
  }

  /**
   * Initialize the artifact repository.
   */
  private static function initializeRepository(string $directory, string $remoteGit, string $remoteBaseBranch): GitRepository {
    $git = new Git();

    if (!is_dir($directory)) {
      // Clone the artifact repository if it doesn't exist.
      $repository = $git->cloneRepository($remoteGit, $directory);
    }
    else {
      // Update the artifact repository if it already exists.
      $repository = $git->open($directory);
    }

    // Reset the repository to the base branch.
    // @todo FOR LATER: this will need some error handling / and/or to force the checkout.
    $repository->checkout($remoteBaseBranch, ['--force']);
    $repository->pull();

    return $repository;
  }

}
