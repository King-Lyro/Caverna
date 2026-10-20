# Themes and site appearance

## Deployment

Deploy the application changes, then run:

```powershell
php artisan migrate --force
npm run build
```

The public storage disk must be writable and accessible through `public/storage`.
On a new deployment, create the storage link with `php artisan storage:link` if it
does not already exist. PHP's `upload_max_filesize` must allow 5 MB uploads, and
`post_max_size` must accommodate the combined files in a presentation form.
For large box collections, raise `max_input_vars` as needed (for example 5000).
The editor detects truncated presentation forms and refuses to save partial data.

The migration adds theme and schedule tables and preserves the selected seasonal
theme. Spring, Summer, Fall, and Winter remain editable, protected built-in
presets. Fall remains the fallback. No new queue worker or cron task is needed
for theme switching.

## Editing

Open **Staff CP -> Themes & appearance** as an administrator.

- **Your themes** shows the live theme and your saved collection. Choose
  **Customize** to edit a theme, or **Preview** to see its saved appearance.
  The card thumbnails illustrate colors and header artwork, not full page previews.
- **Create a theme** has two steps: pick an existing theme to start with, then
  give your copy a name. Colors, images, and text are copied; dates and activation
  are not. Your selected active theme is the suggested starting point.
- **Use this theme** selects your active theme. **More options** on a theme
  card contains **Make a copy** and (for custom themes) **Delete theme**.
- In the editor, choose **Header & season**, **Homepage**, **Sidebars**, or
  **Individual pages** in the navigation. Individual pages can be searched.
  **Name & colors** changes the theme name and palette, with a live color sample.
- Each area is divided into expandable sections describing where its content
  appears. Open only the section you need. Save each area separately.
- Select **Custom** in an item's dropdown to show its text inputs,
  image uploads, or content cards. These controls stay hidden for **Use site default**
  and **Hide this item**. The editor marks edited items **Custom**.
  **Use site default** removes that theme's change and restores the shared value,
  or original content, while hiding the custom controls again.
  **Remove image / Hide this text / Hide these cards** intentionally hides
  optional content; **Show & edit** brings the controls back.
- The **Site-wide defaults** tab contains **Edit site-wide defaults**. These
  values apply to every theme without its own change to that item.
  Here, **Use original content** restores the application's original value.
- Image selections show a local preview before saving. Color samples and image
  previews are not published until saved. **Save & preview** saves the current
  area and opens the actual theme in your administrator-only preview in a new tab.
  The editor stays open; validation errors remain there without discarding entries.
  **Exit preview** ends the session preview, then closes that tab. If the browser
  prevents closing a manually opened tab, it returns to Themes & appearance instead.
- The save bar shows unsaved changes; leaving the editor prompts before
  discarding them. Server validation failures keep entries and selected files.
  Custom controls follow the dropdown even without JavaScript. JavaScript
  enhances the controls with visual editors and image previews.
- Content cards show their titles in a compact list. Open a card for its text,
  and expand **Add an image or link** for optional artwork and link settings.
  Cards support a small label/number, title, rich text, image description,
  optional link/button, move-up/down controls, and removal on save (uncheck to undo).
  A region can contain up to 30 boxes. Theme-specific box lists replace that
  region's inherited list; they do not modify the shared list.
- Existing sidebar modules remain managed in **Sidebar modules**. Theme settings
  can override their title/body or hide them without creating duplicate modules.
- Guide, rules, world, adoption, privacy, and contact bodies remain in their
  existing editors, linked from **Site-wide defaults**. Theme settings control their
  presentation headings and supplementary boxes, not independent article copies.

Saving the active theme or shared content changes the live site immediately.
There is no draft/version-history system. Prepare an inactive theme first when
you need to review a change before visitors see it.

## Artwork and links

Use JPG, PNG, GIF, or WebP files up to 5 MB and 6000 x 6000 pixels. SVG and
executable files are not accepted. Wide, reasonably sized artwork is recommended
for the site header. Supply descriptive alternative text for meaningful artwork;
an empty alternative text identifies decorative artwork.

The header supports contain/cover and left/center/right alignment. Hiding the
text title still retains the linked, accessible site identity; without header
artwork the title remains visible.

Content-box artwork uploads require JavaScript. Uploaded artwork is not attached
to a region until that region is saved. Previously uploaded files are retained
when an image, box, or theme is removed because another theme or article may
still reference them. Automatic orphan-media pruning is not included. A new
image uploaded as part of a failed presentation save is cleaned up.

Custom links accept application paths such as `/guide` or HTTP(S) URLs.
Application action links such as registration, character creation, and purchasing
retain their existing destinations and permissions. Rich text is stored as
Markdown and rendered with raw HTML stripped and unsafe link schemes disabled.

### Interactive map

The map-page editing area allows replacement of the interactive map artwork.
It must be **781 x 781 pixels**, and the administrator must confirm that
territory positions match the existing artwork. Equal dimensions do not prove
that geographic positions match. Test the clickable areas in preview before
activation. This feature does not move or edit map hotspots.

Hide removes map artwork but leaves the territory-directory links available.
**Use site default** restores shared artwork or the original map.

## Preview and activation

**Preview** and **Preview saved theme** open the saved theme in a new tab,
only in the current administrator session. Preview state is shared between
tabs in that session; it is not an isolated per-tab theme.
A visible banner provides **Exit preview**. Preview does not change visitor
themes, the selected base theme, schedules, or authorization.

**Use this theme** on a card, or the **Active theme** dropdown and **Apply** in
either layout, selects the manually chosen site-wide theme and exits preview.
Enabled schedules still take precedence. **On your site now** shows the actual
visitor theme even while an administrator previews another theme.

A selected theme, built-in preset, or theme referenced by an enabled schedule
cannot be deleted. Select a different base and disable its schedules first.
Deleting an eligible custom theme also removes its disabled schedules, while
retaining artwork.

## Schedules

Open **Automatic switching** to add or edit event dates or pause switching.
Schedules use `config('app.timezone')`, displayed alongside the event forms.
The current application configuration is UTC. Changing the configured timezone
changes when calendar-day boundaries occur; do not assume browser-local time.

- **Just once**: an inclusive start/end date, from 1900 through 9998.
- **Every year**: choose the first and last month/day, for example December 20
  to January 5. Without JavaScript, enter `MM-DD` in the labeled fields.
  Annual ranges can cross New Year.
- Activation begins at local midnight on the first day and lasts through the
  final day. At midnight after the end date, the selected base returns.
- February 29 endpoints become February 28 in non-leap years.
- Enabled schedules cannot overlap, even if they use the same theme. Sharing an
  endpoint day is an overlap. Annual/one-off conflicts are checked against future
  occurrences as well as annual/annual and one-off/one-off conflicts.
- Disabled schedules are retained without activating or reserving dates.
- **Pause automatic switching** immediately uses the selected base, without deleting
  schedules. **Turn automatic switching on** uses the event matching today's date, if any.
- Date resolution is evaluated on page requests, so a cached previous theme
  cannot freeze a transition. An already open page updates on its next request
  or reload; there is no live-push refresh.

## Protected information and recovery

Theme editing cannot replace forum/thread identity, character/member names,
listing data, clan navigation records, balances, live statistics, forms, or
permissions. Profile/detail pages expose decorative artwork and supplementary
regions while retaining their authoritative identity. The dashboard greeting's
member name is dynamic and links to that member's public profile.

Theme selections, edits, duplication/deletion, shared-presentation edits, and
schedule changes are recorded in the existing audit log. If a stored selected
theme is missing, Fall is used and administrators see a warning to select a
valid theme. Upload and validation failures display an error rather than a
success message.

No arbitrary HTML/CSS editor, personal member skins, map-hotspot editor, or
general-purpose page builder is included.
