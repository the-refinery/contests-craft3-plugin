# CraftCMS Contests plugin for Craft CMS 3.x

Create contests with voting on CraftCMS entries.

![Screenshot](src/icon.svg)


Table of Contents
=================
* [Requirements](#requirements)
* [Installation](#installation)
  * [Development](#development)
  * [Non-development](#non-development)
* [CraftCMS Contests Overview](#craftcms-contests-overview)
* [Configuring CraftCMS Contests](#configuring-craftcms-contests)
* [Using CraftCMS Contests](#using-craftcms-contests)
  * [Asynchronously](#asynchronously)
  * [Traditional Form POST](#traditional-form-post)
* [CraftCMS Contests Roadmap](#craftcms-contests-roadmap)

## Requirements

This plugin requires Craft CMS 3.0.0-beta.23 or later.

## Installation

### Development

If you would like to install this plugin for development or debugging purposes:

1. Create a subfolder in your CraftCMS project that Craft has access to. For example: `<craft-root>/plugins/therefinery/craftcmscontests`
1. Go into that directory and clone this repo:  `git clone git@github.com:the-refinery/contests-craft3-plugin.git .` or use a forked URL if you have forked this project
1. Make sure to `.gitignore` the `plugins/therefinery/craftcmscontests` directory (even if it's temporary) as it will already have it's own git setup and you do not want to include these files with your main Craft project's git repo.
1. In `composer.json` add a path repository in the `repositories` block, such as:

        {
         "type": "path",
         "url": "plugins/therefinery/craftcmscontests"
        }
1. In `composer.json` add the requirement within your `require` block:

        "therefinery/craft-cms-contests": "^1.0"
1. Run `composer require therefinery/craft-cms-contests`
1. Log into the CraftCMS admin panel and install the plugin like you would for any other plugin.

### Non-development

1.  In `composer.json` add a vcs repository in the `repositories` block:

        {
          "type": "vcs",
          "url": "https://github.com/the-refinery/contests-craft3-plugin"
        }
1. In `composer.json` add the requirement within your `require` block:

        "therefinery/craft-cms-contests": "^1.0"
1. Run `composer require therefinery/craft-cms-contests`
1. Log into the CraftCMS admin panel and install the plug like you would for any other plugin.

[Back to Table of Contents &uarr;](#table-of-contents)

## CraftCMS Contests Overview

The idea behind CraftCMS Contests is to simply create contests which allow users to vote on specific entries belonging to certain categories. The vote tallies can be seen in real-time in the CraftCMS admin control panel or through a custom-built template.

There are some very basic security measures in place, such as CSRF tokens and enabling the ability to prevent the same user from voting more than once within a specific time period. Optionally, Google reCaptcha (v2) can be set up and utilized.

[Back to Table of Contents &uarr;](#table-of-contents)

## Configuring CraftCMS Contests

Once you have the plugin installed and enabled, you will need to determine a
few things before you can create your first contest:

* What do you want to name your contest?
* What categories (new or existing) do you want your users to vote on?
* What kind of lockout period do you want? One vote every 5 minutes? Every 24 hours?
* Do you want to use reCaptcha?

Next, you will want to log into the CraftCMS admin control panel if you are not there already. If you do not have the categories in which you want users to vote on, you will want to create those first. Next, in the left hand admin menu, click on "CraftCMS Contests > Contests". On the CraftCMS contests listing page, click the "Create a Contest" button.

* Set the contest to Enabled if you prefer to make it live right away. It defaults to disabled.
* Give the contest a name
* Give the contest a good handle (you will need this later on)
* Choose at least one category in which to vote
* Set a lockout length. For example, if you wanted to prevent people from voting more than once every 5 minutes for the same category, set 5 for the "Voting Lockout Length" and "minutes" from the "Voting Lockout Frequency"
* Optionally add a reCaptcha secret key

With your new contest created, the next step is to create the entries to vote on. These entries ordinary CraftCMS entries so creating those is beyond the scope of this document. Generally the best way to set them up is to create a unique section for your votable entries. The key point, however, is to ensure that your entries belong to the votable categories. When voting on an entry, you're actually voting on an entry that belongs to a category, where that same category belongs to a contest. Without that link from entry > category > contest, the votes will not be valid.

With a contest set up, the final step is to set up the front end in which to cast votes.

[Back to Table of Contents &uarr;](#table-of-contents)

## Using CraftCMS Contests

### Asynchronously

CraftCMS Contests allows for votes to be cast in an asynchronous fashion using AJAX. In order to use this functionality, you will need to collect several pieces of _required_ information before processing the request:

* A CSRF token
* The Contest ID
* The Entry ID
* The Category ID
* An email address

The folowing is a very simple example of a Twig template that could be used to gather such information. Granted each project is unique in how it is structured and designed, but this is to simply demonstrate what pieces of information you will need and how to get them in order to make a successful vote:

```twig
<script src="https://ajax.googleapis.com/ajax/libs/jquery/3.5.1/jquery.min.js"></script>

<form id="food-vote" method="post" action="" accept-charset="UTF-8">
    {# Generate a CSRF token #}
    {{ csrfInput() }}

    {# Get the Contest by handle so that you can obtain the ID #}
    {% set contest = craft.craftcmsContests.getContestByHandle("foodVotingContest2021") %}
    <input type="hidden" name="contestId" id="contestId" value="{{ contest.id }}">

    {#
      The form inputs down below can be built dynamically, but for this short example they're going to be hard-coded.
      In the env, the Javascript needs to collect the category ID and entry ID for each type of vote (example below).
    #}
    <h1>Dishes</h1>

    <div>
      <input data-category-id="26233" data-entry-id="26238" type="radio" id="burger" name="dishes" value="burger" checked>
      <label for="burger">Burger</label>
    </div>

    <div>
      <input data-category-id="26233" data-entry-id="26242" type="radio" id="spaghetti" name="dishes" value="spaghetti">
      <label for="spaghetti">Spaghetti</label>
    </div>

    <div>
      <input data-category-id="26233" data-entry-id="26245" type="radio" id="pizza" name="dishes" value="pizza">
      <label for="pizza">Pizza</label>
    </div>

    <h1>Sides</h1>

    <div>
      <input data-category-id="26234" data-entry-id="26248" type="radio" id="french-fries" name="sides" value="french-fries" checked>
      <label for="burger">French Fries</label>
    </div>

    <div>
      <input data-category-id="26234" data-entry-id="26251" type="radio" id="rice" name="sides" value="rice">
      <label for="rice">Rice</label>
    </div>

    <div>
      <input data-category-id="26234" data-entry-id="26254" type="radio" id="beans" name="sides" value="beans">
      <label for="beans">Beans</label>
    </div>

    {# Get an email address #}
    <label for="email">Email</label>
    <input type="email" name="email" id="email" />

    <input class="btn submit" type="submit" value="{{ 'Submit'|t }}">
</form>

// ----------------------------------------------------------------------

<script defer>
// Submit the form
$("#food-vote").submit(function(e){
    e.preventDefault();

    /*
    The structure of the vote data being POSTed will need to look like the following:
    ---

    {
        "data":
        [
            {
                "email": "test@test.com",
                "entryId": "1",
                "categoryId": "1"
            },
            {
                "email": "test@test.com",
                "entryId": "4",
                "categoryId": "5"
            },
            {
                "email": "test@test.com",
                "entryId": "10",
                "categoryId": "16"
            }
        ],
        "contestId": "1",
        "csrfToken": "A9023489i_09402hiodsnfio230n0fsfs",
        "recaptchaResponse": "tkkldLKLl3kkljasd_i23opikpladfjklajdasf" # if applicable
    }
    */

    // Build the data structure
    var voteData = {"data": []};

    // csrfInput above in the form generates a hidden form input with a name
    // of CRAFT_CSRF_TOKEN. Extract it and include it as part of the payload.
    voteData["csrfToken"] = $('input[name="CRAFT_CSRF_TOKEN"]').val();

    // Need the contest ID for the submission
    voteData["contestId"] = $("#contestId").val();

    // Build a vote for the dishes category
    var selectedDishInput = $('input[name="dishes"]:checked');
    var vote = {
        "email": $("#email").val(),
        "entryId": selectedDishInput.data('entry-id'),
        "categoryId": selectedDishInput.data('category-id'),
    };

    voteData["data"].push(vote);

    // Build a vote for the sides category
    var selectedSidesInput = $('input[name="sides"]:checked');
    var vote = {
        "email": $("#email").val(),
        "entryId": selectedSidesInput.data('entry-id'),
        "categoryId": selectedSidesInput.data('category-id'),
    };

    voteData["data"].push(vote);

    // Make the AJAX call.
    var voteAjax = $.ajax({
        url: '/craft-cms-contests/votes/saveVoteAsync',
        type: 'POST',
        contentType: 'application/json',
        data: JSON.stringify(voteData),
        dataType: 'json',
    })
    .done(function(data) {
        console.log("Success");
    })
    .fail(function(data) {
        console.log("Fail");
    });
});
</script>
```


Upon a successful vote cast, the following is returned along with an HTTP/200:

```javascript
{"status":"success","message":"Votes successfully cast."}
```

Upon a failure, an error message is returned. For invalid votes an HTTP/400 is returned. Internal server errors are returned as an HTTP/500. Here is an example error message:

```javascript
{
  "status": "error",
  "errors":[
    {
      "detail":"You can only vote once every 24 hours for category 'Burger Vote'. Please try again soon."
    }
  ]
}
```

[Back to Table of Contents &uarr;](#table-of-contents)

### Traditional form POST

Coming soon.

[Back to Table of Contents &uarr;](#table-of-contents)

## CraftCMS Contests Roadmap

Some things to do, and ideas for potential features:

- [ ] Release it

Brought to you by [The Refinery](https://the-refinery.io/)

[Back to Table of Contents &uarr;](#table-of-contents)
