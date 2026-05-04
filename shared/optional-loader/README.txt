Optional shared loader package for the group.

Nothing in this folder affects the project unless someone includes the files manually.

How to use:
1. Link `shared/optional-loader/loader.css` in the page layout head.
2. Include the markup from `shared/optional-loader/loader-snippet.php` near the end of the body.
3. Load `shared/optional-loader/loader.js` before the closing body tag.

Result:
- A lightweight loading overlay appears on form submissions and page navigation.
- It stays visually neutral enough to work with the existing GoService template.
