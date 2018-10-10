# tide_webform
Webform configuration for Tide distribution - Test with current version

[![CircleCI](https://circleci.com/gh/dpc-sdp/tide_webform.svg?style=svg&circle-token=0fbcdc200c4f27982721057e322e270786c87d44)](https://circleci.com/gh/dpc-sdp/tide_webform)
## Purpose
- Webform configuration
- Content Rating form


# CONTENTS OF THIS FILE

* Introduction
* Requirements
* Recommended Modules
* Installation

# INTRODUCTION
The Tide Webform module provides the Content Rating form. 
It also provides the `Show Content Rating?` field for Tide Landing Page and Tide Page.

# REQUIREMENTS
* [Tide Core](https://github.com/dpc-sdp/tide_core)

# RECOMMENDED MODULES
* [Tide Page](https://github.com/dpc-sdp/tide_page)
* [Tide Landing Page](https://github.com/dpc-sdp/tide_landing_page)

# INSTALLATION
Include the Tide Landing Page module in your composer.json file

```bash
composer require dpc-sdp/tide_webform
```

# Caveats

Tide Webform is on the alpha release, use with caution. APIs are likely to change before the stable version, that there will be breaking changes and that we're not supporting it for external production sites at the moment.
