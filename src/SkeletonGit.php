<?php

namespace Skeleton;

use CzProject\GitPhp\Git;

/**
 * Use our own repository class.
 */
class SkeletonGit extends Git {

  /**
   * {@inheritdoc}
   */
  public function open($directory) {
    return new SkeletonRepository($directory, $this->runner);
  }

  /**
   * Get or clone a repository.
   *
   * @param $directory
   * @param $url
   * @return \CzProject\GitPhp\GitRepository|SkeletonRepository
   * @throws \CzProject\GitPhp\GitException
   */
  public function openOrClone($directory, $url) {
    try {
      $repository = $this->open($directory);
    }
    catch (\Exception $e) {
      $repository = $this->cloneRepository($url, $directory);
    }

    return $repository;
  }

}
