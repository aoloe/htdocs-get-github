# htdocs-get-github


Synchronize a local directory with a GitHub repository.

Uses the GitHub API to retrieve the information about the files in the repository and HTTP -- using curl in PHP and the RAW view on GitHub -- to get the files from the git repository.

No git is needed on the server (and thanks to the GitHub web based editor, you don't need git either to create and edit the file... but this does not make much sense...).

It's not possible to synchronize the files the other way round: You can't put changes from your server into the GitHub repository.

This script is not GitHub specific and can work with any Git repository that:

- provides an API returning a list of files and their hashes and
- lets you download individual files per HTTP.

##Copyright and Credits

copyright (c) 2013, Ale Rimoldi, except where otherwise mentioned.
All rights reserved.

Redistribution and use in source and binary forms, with or without modification, are permitted provided that the following conditions are met:

1. Redistributions of source code must retain the above copyright notice, this list of conditions and the following disclaimer.
2. Redistributions in binary form must reproduce the above copyright notice, this list of conditions and the following disclaimer in the documentation and/or other materials provided with the distribution.

THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE COPYRIGHT HOLDER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.

- Uses a modified version of [MyCurl](http://www.phpclasses.org/package/3588-PHP-Pure-PHP-implementation-of-the-cURL-library.html)
- Uses a modifield versino of [simplejson](http://code.google.com/p/simplejson-php/)

## TODO

- force the download of files that are missing locally.
- delete locally files that have been deleted in the GitHub repository.
- allow other services on top of GitHub.
