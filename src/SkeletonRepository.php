<?php

namespace Skeleton;

use CzProject\GitPhp\GitRepository;

/**
 * Add methods to the repository class.
 */
class SkeletonRepository extends GitRepository {

  /**
   * Get the tag of the current commit.
   *
   * @return string
   */
  public function getCurrentTag(): string {
    try {
      $result = $this->run('describe', '--tags', '--exact-match');
      if ($result->hasOutput()) {
        $tag = $result->getOutput()[0];
      }
    }
    catch (\Exception $e) {
      $tag = '';
    }

    return $tag;
  }

  /**
   * Get un-committed changes to the repository.
   *
   * @return mixed|string
   * @throws \CzProject\GitPhp\GitException
   */
  public function showChanges(): array {
    $result = $this->run('status', '--porcelain');
    return $result->getOutput();
  }

  public function hasRemoteBranch($branch, $remote): bool {
    $branches = $this->getRemoteBranches();
    return in_array("{$remote}/{$branch}", $branches);
  }


}
