# Super-Swank Featured Image

A WordPress plugin that sets a default featured image for posts, pages, and custom post types when no featured image is set.

## Description

Super-Swank Featured Image allows you to set a default featured image that will be used when no featured image is manually set for a post, page, or custom post type. This ensures your content always has a consistent look, even when authors forget to set a featured image.

### Features

- Set a default featured image for all content types
- Support for both classic and block editor
- Image cropping functionality with customizable aspect ratio
- Responsive admin interface
- Translation-ready

## Requirements

- WordPress 5.9 or higher
- PHP 8.2 or higher

## Installation

1. Download the plugin zip file
2. Go to WordPress admin panel > Plugins > Add New
3. Click "Upload Plugin"
4. Upload the zip file
5. Activate the plugin

## Configuration

1. Go to Settings > Default Featured Image
2. Click "Select Image" to choose your default featured image
3. Optionally crop the image to your desired dimensions
4. Save changes

## Usage

Once configured, the plugin will automatically use your selected default image as the featured image for any post, page, or custom post type that doesn't have a featured image set.

### Filters

The plugin provides several filters to customize its behavior:

```php
// Customize the crop aspect ratio (default 16:9)
add_filter('ssfi_crop_aspect_ratio', function() {
    return 4/3; // Change to desired aspect ratio
});

// Customize minimum crop dimensions
add_filter('ssfi_crop_min_width', function() {
    return 300; // Change minimum width in pixels
});

add_filter('ssfi_crop_min_height', function() {
    return 300; // Change minimum height in pixels
});
```

## Development

### Prerequisites

- PHP 8.2 or higher
- WordPress 5.9 or higher
- Composer (for development)

### Setup for Development

1. Clone this repository
2. Run `composer install` to install development dependencies

### Building Assets

The plugin uses vanilla JavaScript and CSS, so no build process is required.

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the GPL v3 License - see the [LICENSE](LICENSE) file for details.

## Author

eD! Thomas - [edequalsaweso.me](https://edequalsaweso.me) 