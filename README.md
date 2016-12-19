Dorgflow: git workflow for drupal.org patches
=============================================

Dorgflow is a set of commands that streamline your work with patches for issues
on drupal.org. With Dorgflow, you don't need to download and apply patches, and
creating patches and interdiffs is simplified. The only thing that Dorgflow
doesn't handle is posting your files back to an issue for review.

## Usage

### Starting work

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

WARNING: This feature is not yet fully developed. Your commits will remain, but
new patches will come after them on the branch. You will need to retrieve your
work, possibly with cherry-picking to another branch and then merging.

### Contributing your work

When you are ready to make a patch, just do:

    $ dorgflow

This will create a patch with a systematic filename.

WARNING: This does not yet make an interdiff from the most recent patch.
