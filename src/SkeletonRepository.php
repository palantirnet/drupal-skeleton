<?php

namespace Skeleton;

use CzProject\GitPhp\GitException;
use CzProject\GitPhp\GitRepository;

/**
 * Add methods to the repository class.
 */
class SkeletonRepository extends GitRepository {

  /**
   * Get the tag of the current commit.
   *
   * @return string
   *   The tag name.
   */
  public function getCurrentTag(): string {
    try {
      $result = $this->run('describe', '--tags', '--exact-match');
      if ($result->hasOutput()) {
        print_r($result->getOutput());
        $tag = $result->getOutput()[0];
      }
    }
    catch (\Exception $e) {
      $tag = '';
    }

    return $tag;
  }

  /**
   * Overrides GitRepository::getCurrentBranchName().
   *
   * This uses git's --show-current instead of grabbing the first value the list
   * from 'git branch', because the latter doesn't handle a detached head.
   *
   * @return string
   *   The branch name.
   *
   * @throws \CzProject\GitPhp\GitException
   *   When no branch is checked out.
   */
  public function getCurrentBranchName() {
    try {
      $branch = $this->extractFromCommand(['branch', '--show-current', '--no-color'], 'trim');

      if (is_array($branch)) {
        return $branch[0];
      }

    }
    catch (GitException $e) {
      // Nothing.
    }

    throw new GitException('Getting of current branch name failed.');
  }

  /**
   * Get un-committed changes to the repository.
   *
   * @return mixed|string
   *   A list of changed files.
   *
   * @throws \CzProject\GitPhp\GitException
   */
  public function showChanges(): array {
    $result = $this->run('status', '--porcelain');
    return $result->getOutput();
  }

  /**
   * Check whether a remote branch exists.
   *
   * @param string $branch
   *   Branch name.
   * @param string $remote
   *   Remote name.
   *
   * @return bool
   *   True if the branch exists.
   *
   * @throws \CzProject\GitPhp\GitException
   */
  public function hasRemoteBranch(string $branch, string $remote): bool {
    $branches = $this->getRemoteBranches();
    return in_array("{$remote}/{$branch}", $branches);
  }

  /**
   * Check whether a local branch exists.
   *
   * @return bool
   *   True if the branch exists.
   *
   * @throws \CzProject\GitPhp\GitException
   */
  public function hasLocalBranch(string $branch): bool {
    $branches = $this->getLocalBranches();
    return in_array($branch, $branches);
  }

  /**
   * Get the list of files checked in to git.
   *
   * @return array
   *   Array of files.
   *
   * @throws \CzProject\GitPhp\GitException
   */
  public function listFiles(): array {
    $result = $this->run('ls-files');
    return $result->getOutput();
  }

  /**
   * Reset the current HEAD to the last commit, discarding local changes.
   *
   * @throws \CzProject\GitPhp\GitException
   */
  public function reset(): void {
    $this->run('reset', '--hard');
  }

  /**
   * Remove untracked files and directories.
   *
   * @throws \CzProject\GitPhp\GitException
   */
  public function clean(): void {
    $this->run('clean', '-ffd');
  }

  /**
   * Remove a branch, even if it has unmerged changes.
   *
   * @param string $branch
   *   The branch to remove.
   *
   * @throws \CzProject\GitPhp\GitException
   */
  public function forceRemoveBranch(string $branch): void {
    $this->run('branch', ['-D' => $branch]);
  }

}
