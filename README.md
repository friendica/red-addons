red-addons
==========

These are addons for RedMatrix sites (see https://github.com/friendica/red )
red-addons

Notes:

In order to merge cleanly without conflict, it is recommended when making changes that one checkin the source changes changes, and then merge from upstream **before** updating the package (.tgz) files (by running 'make' on Unix systems). If you update the packages before merging and somebody else has updated that particular package file, you will get a conflict that is very difficult to resolve; as these are binary files. Once the changes are checked in and merged with upstream, update the packages (run make in the top-level addon directory) and check that in. Push all of the changes upstream or submit a pull request to the project as soon as possible after doing this.

If you do get a merge conflict on a package file, stash all your source changes elsewhere and completely revert your commit containing the package files; then recover your source changes and follow the instructions just given. You may be best off to not update the package files at all and leave this to a project contributor with repository access.   