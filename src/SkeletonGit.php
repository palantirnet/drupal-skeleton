<?php

namespace Skeleton;

use CzProject\GitPhp\Git;

/**
 * Override in order to use our own repository class.
 */
class SkeletonGit extends Git {

  /**
   * {@inheritdoc}
   */
  public function open($directory) {
    return new SkeletonRepository($directory, $this->runner);
  }

  /**
   * Open or clone a repository.
   *
   * @param string $directory
   *   The directory where the repository should live.
   * @param string $url
   *   The git URL.
   *
   * @return \CzProject\GitPhp\GitRepository|SkeletonRepository
   *   The repository object.
   *
   * @throws \CzProject\GitPhp\GitException
   */
  public function openOrClone(string $directory, string $url) {
    try {
      $repository = $this->open($directory);
    }
    catch (\Exception $e) {
      $repository = $this->cloneRepository($url, $directory);
    }

    return $repository;
  }

  /**
   * {@inheritdoc}
   *
   * Ensure that this returns our repository class.
   */
  public function cloneRepository($url, $directory = NULL, array $params = NULL) {
    $repository = parent::cloneRepository($url, $directory, $params);
    return $this->open($repository->getRepositoryPath());
  }

}
