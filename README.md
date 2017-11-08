# Secret Santa

This is a plugin to run a Holiday Gift Exchange among users of a given blog.

To use, just add a shortcode to your post! `[holiday-gift-exchange event="holidays-2017" state="1"]`

You can then change the `state` param to advance the exchange.

State 1: Collection of signups
State 2: Signups closed, waiting on assignments
State 3: Assignments public, participants can message their senders and recipients, without spoiling who is who.
State 4: The big reveal!  Participants can see who was sending to them, to send their thanks.

## Extensible

We use this internally at Automattic with special filters in place to pre-populate user mailing addresses and whatnot.

We also short-circuit any `wp_mail` calls in the plugin to message folks via Slack instead (I think, it's been about a year since I looked at that bit of the code)

# Building Styles

Until I integrate a build tool, styles can be compiled via:

```
sass admin-page.scss > admin-page.css
sass gutenblock.scss > gutenblock.css
```

