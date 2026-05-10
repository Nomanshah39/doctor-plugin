# Doctor Directory Tabs

Doctor Directory Tabs is a production-ready WordPress plugin for managing and displaying a responsive doctor directory with specialty tabs, group headings, AJAX filtering, CSV import, and cache invalidation.

## Installation

1. Place this plugin folder in `wp-content/plugins/doctor-directory-tabs/`.
2. In WordPress admin, go to **Plugins**.
3. Activate **Doctor Directory Tabs**.
4. Go to **Doctors** to add or import doctors.

## Display the directory

Add this shortcode in Gutenberg, Elementor, the Classic Editor, widgets, or any normal WordPress page content:

```text
[doctor-list-a-to-z]
```

The shortcode returns its output, displays all doctors by default, and sorts doctors by display order first, then last name A-Z, then first name A-Z.

### Optional shortcode attributes

```text
[doctor-list-a-to-z group="cardiology"]
[doctor-list-a-to-z specialty="heart-specialist"]
[doctor-list-a-to-z show_all="true"]
[doctor-list-a-to-z columns="3"]
[doctor-list-a-to-z image_size="medium"]
```

- `group`: group taxonomy slug to limit the directory.
- `specialty`: specialty taxonomy slug for the initial view.
- `show_all`: defaults to `true` so the All Doctors tab appears.
- `columns`: accepts `1`, `2`, `3`, or `4`.
- `image_size`: accepts registered WordPress image sizes; invalid values fall back to `medium`.

## CSV import

Go to **Doctors → CSV Import** and upload a `.csv` file.

Required headers:

```csv
First Name,Last Name,Speciality,Group Name,Profile Link
```

`Speciality` and `Specialty` are both accepted and map to the Specialty taxonomy.

Optional headers:

```csv
Image URL,Designation,Clinic,Department,Display Order
```

Importer behavior:

- Creates or updates doctors by matching First Name + Last Name.
- Updates the first match and logs a warning if duplicate names already exist.
- Creates missing Specialty and Group terms.
- Assigns taxonomy terms to the doctor.
- Validates profile links as URLs.
- Sideloads Image URL values into the Media Library and avoids duplicate downloads for the same source URL.
- Ignores empty rows and handles UTF-8 BOM headers.
- Shows a summary for Created, Updated, Skipped, Warnings, and Errors.
- Provides a downloadable CSV import log.

## Cache clearing

The plugin uses versioned transient/object-cache keys for specialty lists and doctor results. Cache is automatically invalidated when:

- A doctor is created, updated, deleted, trashed, or restored.
- Specialty or Group terms are created, edited, deleted, or assigned.
- A CSV import completes.

## Image handling

Doctor images are selected from the WordPress Media Library and stored as attachment IDs. CSV Image URLs are sideloaded into the Media Library, tagged with their source URL, and reused if the same URL is imported again. Frontend cards use thumbnail/medium-style image sizes with lazy loading and never intentionally load full-size originals.

## No-JavaScript fallback

Specialty tabs are real links using the `ddt_specialty` query string, so the directory still works when JavaScript is disabled. With JavaScript enabled, filtering happens through secure AJAX without a full page reload.
