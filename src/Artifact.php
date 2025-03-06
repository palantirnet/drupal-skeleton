<?php

namespace Skeleton;

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
    $artifactGitRemote = $extra['artifact']['git_remote'];

    $artifactDirectory = $extra['artifact']['directory'];
    $artifactPrefix = $extra['artifact']['prefix'];
    $artifactGitRemoteBaseBranch = $extra['artifact']['git_remote_base_branch'];
    $artifactGitRemoteName = $extra['artifact']['git_remote_name'];
    $artifactTemplateMap = $extra['artifact']['template_map'];

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
    $artifact = new Artifact($config['git_remote'], $config['directory'], $config['template_map'], $config['git_remote_base_branch'], $config['prefix'], $config['build_dirty']);

    // Don't build if the repository has uncommitted changes.
    try {
      $artifact->safeToBuild();
    }
    catch (\Exception $e) {
      $event->getIO()->write($e->getMessage());
      return;
    }

    $artifact->setupBranches();
    $artifact->removeArtifactFiles();
    $artifact->copySource();
    $artifact->copyTemplates();
    $artifact->build();
    $artifact->commit();
    return;


    // Get the current commit, branch, message, and tag so that they can be used
    // to label the resulting artifact and reset the repository after the
    // artifact is built.
    $artifactGitCommit = $source_repository->getLastCommit()->getId();
    // @todo Handle the case when this command outputs "HEAD".
    // This happens when building a repo from a detatched head state (e.g.
    // you've checked out a tag), and it causes pushing the artifact to fail
    // because "HEAD" is not a branch you can push to.
    $artifactGitBranch = $source_repository->getCurrentBranchName();
    $artifactGitCommitMessage = $source_repository->getLastCommit()->getSubject();
    $artifactGitTag = $source_repository->getCurrentTag();

    // Create a temporary branch name based on the commit for building the
    // artifact, to avoid branch conflicts.
    $artifactGitTemporaryBranch = 'artifact-' . $artifactGitCommit;
    // Prefix the repository tag so that we're not using the exact same tag on
    // the artifact and on the repository, to avoid confusion, especially when
    // the artifact is built on a branch of the development repository.
    $artifactGitArtifactTag = $artifactGitTag ? $artifactPrefix . $artifactGitTag : '';
    // If the remote branch isn't configured, use a remote branch based on the
    // name of the current branch. This won't overwrite the property value if it
    // is already set.
    $artifactGitRemoteBranch = $artifactPrefix . $artifactGitBranch;

    $event->getIO()->write('artifactGitCommit: ' . $artifactGitCommit);
    $event->getIO()->write('artifactGitBranch: ' . $artifactGitBranch);
    $event->getIO()->write('artifactGitCommitMessage: ' . $artifactGitCommitMessage);
    $event->getIO()->write('artifactGitTag: ' . print_r($artifactGitTag, TRUE));
    $event->getIO()->write('artifactGitTemporaryBranch: ' . $artifactGitTemporaryBranch);
    $event->getIO()->write('artifactGitArtifactTag: ' . $artifactGitArtifactTag);
    $event->getIO()->write('artifactGitRemoteBranch: ' . $artifactGitRemoteBranch);

    self::artifactCommit($artifact_repository, $source_repository, $artifactGitArtifactTag);
        //<phingcall target="artifact-finish" />

    // prompt the user to push the changes to the remote branch or cancel
    // ask for user input
    //         <selectone list="push,keep,discard" propertyName="artifact.result" message="Push artifact changes to the '${artifact.git.remote_branch}' branch?" />

    $arguments = $event->getArguments();
    $argumentAction = array_intersect(['push', 'keep', 'discard'], $arguments);
    if (count($argumentAction) == 1) {
      $artifactResult = $arguments[0];
    }
    else {
      $artifactResult = $event->getIO()->select(
        "Push artifact changes to the '{$artifactGitRemoteBranch}' branch?",
        ['push' => 'push (default)', 'keep' => 'keep', 'discard' => 'discard'], 'push');
    }

    if ($artifactResult === 'push') {
      self::artifactPush($artifact_repository, $artifactGitRemoteName, $artifactGitTemporaryBranch, $artifactGitRemoteBranch, $artifactGitArtifactTag, $artifactGitRemoteBaseBranch);
    }
    elseif ($artifactResult === 'keep') {
      self::artifactKeep($artifact_repository, $artifactGitArtifactTag);
    }
    else {
      self::artifactDiscard($artifact_repository, $artifactGitArtifactTag, $artifactGitRemoteBaseBranch, $artifactGitTemporaryBranch);
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

  public function getSourceRepository(): SkeletonRepository {
    return $this->sourceRepository;
  }

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



/*
    <target name="artifact-setupBranch" hidden="true">
        <exec dir="${artifact.directory}" command="git fetch ${artifact.git.remote_name} ${artifact.git.remote_branch}" returnProperty="remote_fetch.error" />
        <if>
            <not><equals arg1="${remote_fetch.error}" arg2="0" /></not>
            <then>
                <echo>Remote branch does not exist. Creating '${artifact.git.remote_branch}' on remote repository...</echo>
                <exec dir="${artifact.directory}" command="git fetch ${artifact.git.remote_name} ${artifact.git.remote_base_branch}" returnProperty="remote_fetch_base.error" />
                <if>
                    <not><equals arg1="${remote_fetch_base.error}" arg2="0" /></not>
                    <then><fail msg="Failed to create remote branch: base branch '${artifact.git.remote_base_branch}' could not be fetched." /></then>
                </if>

                <!-- The 'remote branch' should only exist on the remote, so we create, push, then delete the local copy. -->
                <exec dir="${artifact.directory}" command="git branch ${artifact.git.remote_branch} ${artifact.git.remote_name}/${artifact.git.remote_base_branch}" checkreturn="true" />
                <exec dir="${artifact.directory}" command="git push ${artifact.git.remote_name} ${artifact.git.remote_branch}:${artifact.git.remote_branch}" checkreturn="true" />
                <exec dir="${artifact.directory}" command="git branch -d ${artifact.git.remote_branch}" checkreturn="true" />
                <echo>Remote branch '${artifact.git.remote_name}/${artifact.git.remote_branch}' created.</echo>
            </then>
        </if>

        <!-- At this point, we should have the latest work from the remote destination
             branch available locally. Now create a fresh, temporary branch for our
             artifact work, to avoid naming conflicts and allow clean rebuilds. -->
        <!-- If the build failed previously, there might be an abandoned branch for this build. -->
        <exec dir="${artifact.directory}" command="git branch | grep ${artifact.git.temporary_branch} &amp;&amp; git branch -D ${artifact.git.temporary_branch}" logoutput="true" />
        <exec dir="${artifact.directory}" command="git branch ${artifact.git.temporary_branch} ${artifact.git.remote_name}/${artifact.git.remote_branch}" checkreturn="true" logoutput="true" />
        <exec dir="${artifact.directory}" command="git checkout ${artifact.git.temporary_branch}" checkreturn="true" />
        <echo>Building on temporary branch '${artifact.git.temporary_branch}'.</echo>
    </target>
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

  public function getTemporaryBranch(): string {
    return $this->prefix . '-' . $this->sourceRepository->getLastCommit()->getId();
  }

  public function getBuildBranch(): string {
    return $this->prefix . '-' . $this->sourceRepository->getCurrentBranchName();
  }

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

  /*

      <target name="artifact-updateCode" hidden="true">
          <!-- Sometimes files have read-only permissions that will cause issues when we
               attempt to remove them. -->
          <exec dir="${artifact.directory}" command="chmod -R 750 ." checkreturn="true" logoutput="true" />
          <!-- Remove all the existing files so that we can install cleanly. -->
          <delete includeemptydirs="true">
              <fileset dir="${artifact.directory}" defaultexcludes="false" excludes=".git,.git/**" includes="** /*,** /.git,** /.git/**" />
        </delete>

        <!-- List all files that are checked in to git, then use the list to copy them all
             into the artifact. -->
        <tempfile property="tmpfile" destdir="${build.dir}/artifacts" />
        <exec command="git ls-files" dir="${build.dir}" output="${tmpfile}" />

        <copy todir="${artifact.directory}" overwrite="true" haltonerror="true">
            <filelist dir="${build.dir}" listfile="${tmpfile}" />
        </copy>
        <delete file="${tmpfile}" />

        <!-- Copy a template .gitignore specific to the artifact. -->
        <copy file="${artifact.gitignore_template}" tofile="${artifact.directory}/.gitignore" overwrite="true">
            <filterchain>
                <expandproperties />
            </filterchain>
        </copy>

        <!-- Copy a template README into the artifact. -->
        <copy file="${artifact.readme_template}" tofile="${artifact.directory}/README.md" overwrite="true">
            <filterchain>
                <expandproperties />
            </filterchain>
        </copy>
    </target>

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

  /*

    <target name="artifact-build" hidden="true">
        <echo>Installing composer dependencies in the artifact...</echo>
        <composer command="install" composer="${composer.composer}">
            <arg line="--no-interaction --no-dev --ignore-platform-reqs --working-dir=${artifact.directory}" />
        </composer>

        <echo>Deleting .git subdirectories added by Composer...</echo>
        <delete includeemptydirs="true">
            <fileset dir="${artifact.directory}" defaultexcludes="false" excludes=".gitignore,.git/,.git/**" includes="** /.gitignore,** /.git,** /.git/**" />
        </delete>

        <echo>Running 'phing build' in the artifact...</echo>
        <!-- Run the build target for each drupal.sites.* property key. -->
        <foreachkey prefix="drupal.sites" omitKeys="_defaults" target="artifact-build-one" keyParam="site_key" prefixParam="prefix" />
    </target>
*/

  /**
   * Build the artifact.
   *
   * @throws \Exception
   */
  public function build(): void {
    // Initialize Composer Installer.
    $composer = new \Composer\Console\Application();
    $composer->setAutoExit(false);

    // Run the install command.
    $input = new \Symfony\Component\Console\Input\ArrayInput([
      'command' => 'install',
      '--no-interaction' => true,
      '--no-dev' => true,
      '--ignore-platform-reqs' => true,
      '--working-dir' => $this->getArtifactRepository()->getRepositoryPath(),
    ]);
    $output = new \Symfony\Component\Console\Output\ConsoleOutput();

    $result = $composer->run($input, $output);

    // Check if the command was successful.
    if ($result !== 0) {
      throw new \RuntimeException("Failed to run composer install in {$this->getArtifactRepository()->getRepositoryPath()}.");
    }

    // Output the result.
    print "Composer install completed successfully in {$this->getArtifactRepository()->getRepositoryPath()}.\n";
  }

/*


    <target name="artifact-commit" hidden="true">
        <!-- The 'allFiles' flag on the GitCommitTask does not add files that aren't already tracked by git. -->
        <exec command="git add --all" dir="${artifact.directory}" />

        <!-- Commit all changes to the artifact repository. -->
        <gitcommit repository="${artifact.directory}" message="Drupal artifact build of ${artifact.git.commit_message}" allFiles="true" />

        <!-- If this is a build of a tag, tag the artifact. -->
        <if>
            <equals arg1="${artifact.git.no_tag}" arg2="0" />
            <then>
                <gittag repository="${artifact.directory}" name="${artifact.git.artifact_tag}" annotate="true" message="Drupal artifact build of tag ${artifact.git.tag}." />
            </then>
        </if>
    </target>
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

/*
        <if>
            <equals arg1="${artifact.result}" arg2="push" />
            <then>
                <!-- Push the changes, delete the temporary branch, and keep any tag that was created. -->
                <phingcall target="artifact-push" />
                <phingcall target="artifact-resetState" />
            </then>
        </if>

    <target name="artifact-push" hidden="true">
        <echo>Pushing changes.</echo>
        <exec dir="${artifact.directory}" command="git push ${artifact.git.remote_name} ${artifact.git.temporary_branch}:${artifact.git.remote_branch}" checkreturn="true" />

        <!-- If this is a build of a tag, push the new artifact tag. -->
        <if>
            <equals arg1="${artifact.git.no_tag}" arg2="0" />
            <then>
                <exec dir="${artifact.directory}" command="git push ${artifact.git.remote_name} ${artifact.git.artifact_tag}" checkreturn="true" />
            </then>
        </if>
    </target>

*/

  protected static function artifactPush(SkeletonRepository $artifactRepository, string $remote, string $temporaryBranch, string $remoteBranch, string $artifactTag, string $remoteBaseBranch): void {
    print "Pushing changes.\n";
    try {
      $artifactRepository->push([$remote, "{$temporaryBranch}:{$remoteBranch}"]);
    }
    catch (\Exception $e) {
      print $e->getMessage() . "\n";
    }

    if ($artifactTag) {
      $artifactRepository->push([$remote, $artifactTag]);
    }

    self::artifactResetState($artifactRepository, $remoteBaseBranch, $temporaryBranch);
  }

  /*
          <if>
              <equals arg1="${artifact.result}" arg2="keep" />
              <then>
                  <!-- Keep the temporary branch, but delete any tag that was created, since if we run the artifact
                       generation again, the tag should be re-created against the regenerated artifact. -->
                  <phingcall target="artifact-cleanupTag" />
                  <echo>Artifact changes are in the temporary branch '${artifact.git.temporary_branch}'</echo>
              </then>
          </if>
  */

  protected static function artifactKeep(SkeletonRepository $artifactRepository, string $artifactTag): void {
    self::artifactCleanupTag($artifactRepository, $artifactTag);
    print "Artifact changes are in the temporary branch '{$artifactRepository->getCurrentBranchName()}'\n";
  }

  /*
        <if>
            <equals arg1="${artifact.result}" arg2="discard" />
            <then>
                <!-- Delete the temporary branch and any tag that was created. -->
                <phingcall target="artifact-cleanupTag" />
                <phingcall target="artifact-resetState" />
            </then>
        </if>
    </target>

*/

  protected static function artifactDiscard(SkeletonRepository $artifactRepository, string $artifactTag, string $remoteBaseBranch, string $temporaryBranch): void {
    self::artifactCleanupTag($artifactRepository, $artifactTag);
    self::artifactResetState($artifactRepository, $remoteBaseBranch, $temporaryBranch);
  }


/*


    <target name="artifact-cleanupTag" hidden="true">
        <if>
            <equals arg1="${artifact.git.no_tag}" arg2="0" />
            <then>
                <exec dir="${artifact.directory}" command="git tag -d ${artifact.git.artifact_tag}" checkreturn="true" logoutput="true" />
            </then>
        </if>
    </target>
*/
  protected static function artifactCleanupTag(SkeletonRepository $artifactRepository, string $artifactTag): void {
    if ($artifactTag) {
      $artifactRepository->removeTag($artifactTag);
    }
  }
/*


    <target name="artifact-resetState" hidden="true">
        <exec dir="${artifact.directory}" command="chmod -R 750 ." checkreturn="true" logoutput="true" />
        <exec dir="${artifact.directory}" command="git reset --hard HEAD" checkreturn="true" logoutput="true" />
        <exec dir="${artifact.directory}" command="git checkout ${artifact.git.remote_base_branch}" checkreturn="true" logoutput="true" />
        <exec dir="${artifact.directory}" command="git clean -ffd" checkreturn="true" />
        <exec dir="${artifact.directory}" command="git branch -D ${artifact.git.temporary_branch}" checkreturn="true" logoutput="true" />
    </target>
    */
  protected static function artifactResetState(SkeletonRepository $artifactRepository, string $remoteBaseBranch, string $temporaryBranch): void {
    // make the whole thing writable

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

    // reset to HEAD
    $artifactRepository->reset();

    // checkout the remote base branch
    $artifactRepository->checkout($remoteBaseBranch);

    // clean the working directory
    $artifactRepository->clean();

    // delete the temporary branch
    $artifactRepository->forceRemoveBranch($temporaryBranch);
  }

}
