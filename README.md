# KupieTools Editor File Manager

A powerful enhancement for WordPress's built-in theme and plugin editors that adds essential file management capabilities directly within the editor interface.

## Features

- Adds file operation buttons to both plugin and theme editor screens
- Create new files in any directory with a simple dialog
- Rename existing files easily without FTP or server access
- Download files directly from the editor interface
- Clean, unobtrusive UI that integrates with WordPress admin
- Security measures including nonce verification and proper file permission checks
- Support for appropriate file extensions based on context (plugin vs theme)
- Fully interactive file tree that updates dynamically

## How It Works

The plugin enhances WordPress's built-in code editors by:

1. Adding action buttons that appear when hovering over files in the editor sidebar
2. Providing simple dialogs for file operations like renaming and creating files
3. Handling file operations securely with proper permission checks
4. Maintaining the state of the editor after operations
5. Supporting direct file downloads with proper headers

## Developer Benefits

- Streamline your workflow by eliminating the need to switch between WordPress and FTP
- Create new theme/plugin files on the fly as you work
- Organize your code better with easy file renaming
- Download files for backup or offline editing
- Work more efficiently with a complete file management solution

## Security Features

- WordPress nonce verification for all operations
- Validation of file extensions to prevent security issues
- File permission checks before operations
- Proper sanitization of inputs
- Admin-only access controls

## Installation

1. Upload the plugin files to the `/wp-content/plugins/ktwp-editor-filemgr` directory
2. Activate the plugin through the WordPress admin interface
3. Navigate to Appearance > Theme Editor or Plugins > Plugin Editor to see the new file management options

## Usage

- Hover over any file in the editor sidebar to reveal file operation buttons
- Click "New File" to create a file in the current directory
- Click "Rename" to change a file's name
- Click "Download" to download the file to your computer
- Complete the operation in the dialog that appears

## Configuration

You can modify the plugin's behavior by editing the constant at the top of the plugin file:
- Set `FILEOPS_LOAD_NEW_FILE` to `true` to automatically load a file after creating or renaming it
- Set it to `false` to stay on the current file

## License

This project is licensed under the GNU General Public License v3.0 - see the [LICENSE](LICENSE) file for details.
