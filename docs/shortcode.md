# Shortcode documentation

Use `[doctor-list-a-to-z]` to display the directory. It works in Gutenberg, Elementor, Classic Editor, and standard WordPress content because it is a normal WordPress shortcode.

Examples:

```text
[doctor-list-a-to-z]
[doctor-list-a-to-z group="cardiology"]
[doctor-list-a-to-z specialty="heart-specialist"]
[doctor-list-a-to-z show_all="true" columns="3" image_size="medium"]
```

Doctors are sorted by Display Order ASC when present, then Last Name A-Z, then First Name A-Z.
