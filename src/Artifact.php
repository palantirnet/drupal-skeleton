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
   */
  public static function createArtifact(Event $event): void {
    // Get artifact configuration options from the composer.json file.
    $composer = $event->getComposer();
    $extra = $composer->getPackage()->getExtra();


/*
        <!-- This property MUST be provided. -->
        <fail unless="artifact.git.remote" message="The remote git repository must be configured in the 'artifact.git.remote' property." />

        <!-- Defaults are set in defaults.yml -->
        <fail unless="artifact.directory" />
        <fail unless="artifact.prefix" />
        <fail unless="artifact.git.remote_base_branch" />
        <fail unless="artifact.git.remote_name" />
        <fail unless="artifact.gitignore_template" />
        <fail unless="artifact.readme_template" />

*/

    $artifactGitRemote = $extra['artifact']['git_remote'];
    $artifactDirectory = $extra['artifact']['directory'];
    $artifactPrefix = $extra['artifact']['prefix'];
    $artifactGitRemoteBaseBranch = $extra['artifact']['git_remote_base_branch'];
    $artifactGitRemoteName = $extra['artifact']['git_remote_name'];
    $artifactTemplateMap = $extra['artifact']['template_map'];

    $git = new SkeletonGit();
    $source_repository = $git->open(getcwd());

    //<phingcall target="artifact-safeToBuild" />
    self::safeToBuild($source_repository);

    /*

            <!-- Get the current commit, branch, message, and tag so that they can be used to label the
                 resulting artifact and reset the repository after the artifact is built. -->
            <exec command="git rev-parse HEAD" outputProperty="artifact.git.commit" checkreturn="true" />
            <!-- @TODO Handle the case when this command outputs "HEAD". This happens when
                 building a repo from a detatched head state (e.g. you've checked out a tag),
                 and it causes pushing the artifact to fail because "HEAD" is not a branch you
                 can push to. -->
            <exec command="git rev-parse --abbrev-ref HEAD" outputProperty="artifact.git.branch" checkreturn="true" />
            <exec command="git log -1 --oneline" outputProperty="artifact.git.commit_message" />
            <exec command="git describe --tags --exact-match" outputProperty="artifact.git.tag" returnProperty="artifact.git.no_tag" />

            <!-- Create a temporary branch name based on the commit for building the artifact,
                 to avoid branch conflicts. -->
            <property name="artifact.git.temporary_branch" value="artifact-${artifact.git.commit}" override="true" />
            <!-- Prefix the repository tag so that we're not using the exact same tag on the
                 artifact and on the repository, to avoid confusion, especially when the
                 artifact is built on a branch of the development repository. -->
            <property name="artifact.git.artifact_tag" value="${artifact.prefix}${artifact.git.tag}" override="true" />

            <!-- If the remote branch isn't configured, use a remote branch based on the name
                 of the current branch. This won't overwrite the property value if it is
                 already set. -->
            <property name="artifact.git.remote_branch" value="${artifact.prefix}${artifact.git.branch}" />
    */

    $artifactGitCommit = $source_repository->getLastCommit()->getId();
    $artifactGitBranch = $source_repository->getCurrentBranchName();
    $artifactGitCommitMessage = $source_repository->getLastCommit()->getSubject();
    $artifactGitTag = $source_repository->getCurrentTag();

    $artifactGitTemporaryBranch = 'artifact-' . $artifactGitCommit;
    $artifactGitArtifactTag = $artifactPrefix . $artifactGitTag;
    $artifactGitRemoteBranch = $artifactPrefix . $artifactGitBranch;

    $event->getIO()->write('artifactGitCommit: ' . $artifactGitCommit);
    $event->getIO()->write('artifactGitBranch: ' . $artifactGitBranch);
    $event->getIO()->write('artifactGitCommitMessage: ' . $artifactGitCommitMessage);
    $event->getIO()->write('artifactGitTag: ' . print_r($artifactGitTag, TRUE));
    $event->getIO()->write('artifactGitTemporaryBranch: ' . $artifactGitTemporaryBranch);
    $event->getIO()->write('artifactGitArtifactTag: ' . $artifactGitArtifactTag);
    $event->getIO()->write('artifactGitRemoteBranch: ' . $artifactGitRemoteBranch);



        //<phingcall target="artifact-initializeRepository" />
    $artifact_repository = $git->openOrClone($artifactDirectory, $artifactGitRemote);
    // reset artifact repository to remote base branch

        //<phingcall target="artifact-setupBranch" />
    self::setupBranch($artifact_repository, $artifactGitRemoteName, $artifactGitRemoteBranch, $artifactGitTemporaryBranch);
        //<phingcall target="artifact-updateCode" />
        //<phingcall target="artifact-build" />
        //<phingcall target="artifact-commit" />
        //<phingcall target="artifact-finish" />
  }

  /**
   *
   */
  protected static function safeToBuild(SkeletonRepository $repository): bool {
    if ($repository->hasChanges()) {
      print "Repository status:    dirty\n\n";
      print "  * You have changes which must be committed before you may build an artifact.\n\n";
      print "  * Modified files:\n      ";
      print implode("\n      ", $repository->showChanges());
      print "\n\n";
      return FALSE;
    }

    print "Repository status:    clean\n";
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

  protected static function setupBranch(SkeletonRepository $repository, string $remote, string $remoteBranch, $temporaryBranch): void {
    if (!$repository->hasRemoteBranch($remoteBranch, $remote)) {
      $repository->createBranch($remoteBranch);
      $repository->push([$remote, $remoteBranch]);
      $repository->removeBranch($remoteBranch);
      print "created remote branch: $remote/$remoteBranch\n";
    }

    try {
      $repository->createBranch($temporaryBranch);
    }
    catch (\Exception $e) {
      $repository->removeBranch($temporaryBranch);
      $repository->createBranch($temporaryBranch);
    }

    $repository->checkout($temporaryBranch);
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
  protected static function updateCode() {}

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
/*


    <target name="artifact-build-one" hidden="true">
        <!--
            - Override the Drupal root so that it is within the artifact directory.
            - Override the "artifact mode" so that files managed with the
              <includeresource /> task are copied into the artifact instead of symlinked.
            -->
        <phing target="build" phingfile="${phing.file}" inheritAll="false" dir="." haltonfailure="true">
            <property name="build.site" value="${site_key}" />
            <property name="drupal.root" value="${artifact.directory}/${drupal.root}" override="true" />
            <property name="includeresource.mode" value="copy" override="true" />
        </phing>
    </target>
*/
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
/*


    <target name="artifact-finish" hidden="true">
        <selectone list="push,keep,discard" propertyName="artifact.result" message="Push artifact changes to the '${artifact.git.remote_branch}' branch?" />

        <if>
            <equals arg1="${artifact.result}" arg2="push" />
            <then>
                <!-- Push the changes, delete the temporary branch, and keep any tag that was created. -->
                <phingcall target="artifact-push" />
                <phingcall target="artifact-resetState" />
            </then>
        </if>

        <if>
            <equals arg1="${artifact.result}" arg2="keep" />
            <then>
                <!-- Keep the temporary branch, but delete any tag that was created, since if we run the artifact
                     generation again, the tag should be re-created against the regenerated artifact. -->
                <phingcall target="artifact-cleanupTag" />
                <echo>Artifact changes are in the temporary branch '${artifact.git.temporary_branch}'</echo>
            </then>
        </if>

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
/*

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
/*


    <target name="artifact-resetState" hidden="true">
        <exec dir="${artifact.directory}" command="chmod -R 750 ." checkreturn="true" logoutput="true" />
        <exec dir="${artifact.directory}" command="git reset --hard HEAD" checkreturn="true" logoutput="true" />
        <exec dir="${artifact.directory}" command="git checkout ${artifact.git.remote_base_branch}" checkreturn="true" logoutput="true" />
        <exec dir="${artifact.directory}" command="git clean -ffd" checkreturn="true" />
        <exec dir="${artifact.directory}" command="git branch -D ${artifact.git.temporary_branch}" checkreturn="true" logoutput="true" />
    </target>
    */

}
