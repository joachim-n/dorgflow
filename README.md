Dorgflow: git workflow for drupal.org patches
=============================================

Dorgflow is a set of commands that streamline your work with patches for issues
on drupal.org. With Dorgflow, you don't need to download and apply patches, and
creating patches and interdiffs is simplified. The only thing that Dorgflow
doesn't handle is posting your files back to an issue for review.

NOTICE: This is currently in heavy development. Things work, but there are more
features to come, and lots of clean-up and refactoring is needed, as well as
tests. I'm also writing this while on paternity leave and so intensely
sleep-deprived ;)

## Installation

Install dependencies with Composer:

    $ composer install

Then either:
  - symlink the file dorgflow into a folder that's in your path.
  - set the root folder of this repository into your path.

## Usage

### Starting work on an issue

Start with your local git clone clean and up to date on a release branch, e.g.
8.3.x (for core) or 8.x-1.x (for contrib). We'll call this the *master branch*.

To start working on an issue, simply do:

    $ dorgflow https://www.drupal.org/node/12345

You can also just give the issue number. And it's fine to have anchor links from
the URL you're copy-pasting too, thus https://www.drupal.org/node/12345#new.

This creates a new git branch for you to work on. If there are patches on the
issue, it will also download them and make commits for them. So you'll have
something like this:

      * (12345-fix-bug) Patch from Drupal.org. File: 12345-4.fix-bug.patch; fid 99999. Automatic commit by dorgflow.
      * Patch from Drupal.org. File: 12345-1.fix-bug.permissions-author.patch; fid 88888. Automatic commit by dorgflow.
     /
    * (8.3.x) Issue 11111 by whoever.
    * Issue 22222 by whoever.

The branch name is formed from the issue number and title: 12345-fix-bug. Each
automatic patch commit gives you the patch filename and file entity ID (the
file's comment index is not yet available from drupal.org's REST API; this is on
the roadmap!).

You can now start work on your own fix to the issue!

### Working on an issue

Commit your work to the feature branch, as you would normally. Make as many
commits as you want, with whatever message you want.

### Updating your feature branch

If there are new patches on the issue on drupal.org, do:

    $ dorgflow update

This will create commits for the new patches on your branch.

In the situation that the feature branch has your own commits at the tip of it,
which have not been posted as a patch, these are moved to a separate branch.

This situation:

      * My commit.
      * (12345-fix-bug) Patch 2 from Drupal.org.
      * Patch 1 from Drupal.org.
     /
    * (8.3.x) Issue 11111 by whoever.
    * Issue 22222 by whoever.

becomes this:

      * (12345-fix-bug) Patch 3 from Drupal.org.
      | * (12345-fix-bug-forked-TIMESTAMP) My commit.
      |/
      * Patch 2 from Drupal.org.
      * Patch 1 from Drupal.org.
     /
    * (8.3.x) Issue 11111 by whoever.
    * Issue 22222 by whoever.

You should then manually merge the forked branch back into the feature branch to
preserve your work.

### Contributing your work

When you are ready to make a patch, just do:

    $ dorgflow

This will create a patch with a systematic filename. You can then upload this to
the issue node on drupal.org.

WARNING: This does not yet make an interdiff from the most recent patch.

### Committing a patch (maintainers only)

If an issue is set to RTBC, and the corresponding feature branch is up to date
with the most recent patch, you can apply the changes to the master branch ready
to be committed:

    $ dorgflow apply

This puts git back on the master branch, and performs a squash merge so that all
the changes from the feature branch are applied and staged.

All you now need to is perform the git commit, using the command suggested by
the issue node's credit and committing section.
